<?php

namespace App\Http\Controllers;

use App\Models\AttendanceHoliday;
use App\Models\AttendanceLockArea;
use App\Models\AttendancePresence;
use App\Models\Employee;
use App\Models\RosterEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AbsensiController extends Controller
{
    private const FACE_MATCH_MAX_DISTANCE = 0.5;

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user()->loadMissing([
            'employee.department',
            'employee.position',
            'department',
            'position',
        ]);

        $today = now('Asia/Jakarta')->startOfDay();
        $employee = $user->employee;
        $shift = $this->resolveShift($user, $employee, $today);

        $attendance = AttendancePresence::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $today->toDateString())
            ->first();

        $areas = AttendanceLockArea::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'latitude', 'longitude', 'radius_meters']);

        return Inertia::render('GMIHR/Absensi/Index', [
            'greetingName' => $this->resolveGreetingName($user),
            'todayLabel' => $today->locale('id')->translatedFormat('l, j F Y'),
            'attendance' => $attendance ? $this->formatAttendance($attendance) : null,
            'shift' => $shift,
            'areas' => $areas,
            'canSubmitAttendance' => true,
            'hasFaceReference' => !empty($employee?->face_reference_descriptor) && !empty($employee?->face_reference_photo_path),
            'faceReferenceDescriptor' => !empty($employee?->face_reference_descriptor)
                ? json_decode((string) $employee->face_reference_descriptor, true)
                : null,
            'faceMatchMaxDistance' => self::FACE_MATCH_MAX_DISTANCE,
        ]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'check_out_reason' => ['nullable', 'string', 'max:1000'],
            'photo_data' => ['required', 'string'],
            'face_descriptor' => ['required', 'array', 'size:128'],
            'face_descriptor.*' => ['numeric'],
        ]);

        /** @var User $user */
        $user = $request->user()->loadMissing(['employee.department', 'department']);
        $today = now('Asia/Jakarta')->startOfDay();
        $employee = $user->employee;
        $shift = $this->resolveShift($user, $employee, $today);

        $faceMatch = $this->evaluateEmployeeFace($employee, $validated['face_descriptor']);
        if (!$faceMatch['matched']) {
            return back()->with('message', [
                'type' => 'error',
                'text' => 'Wajah tidak cocok dengan foto referensi employee. Skor kecocokan ' . $faceMatch['score'] . '%.',
            ]);
        }

        $area = $this->resolveMatchingArea(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
        );
        if (!$area) {
            return back()->with('message', [
                'type' => 'error',
                'text' => 'Anda berada di luar area kantor yang diizinkan untuk absensi.',
            ]);
        }

        $attendance = AttendancePresence::query()->firstOrNew([
            'user_id' => $user->id,
            'attendance_date' => $today->toDateString(),
        ]);

        if (!$attendance->exists) {
            $attendance->employee_id = $employee?->id;
            $attendance->attendance_lock_area_id = $area->id;
            $attendance->roster_entry_id = $shift['roster_entry_id'];
            $attendance->shift_source = $shift['source_key'];
            $attendance->shift_name = $shift['name'];
            $attendance->shift_start_time = $shift['start_time'] ?: null;
            $attendance->shift_end_time = $shift['end_time'] ?: null;
            $attendance->is_off = (bool) $shift['is_off'];
            $attendance->holiday_name = $shift['holiday_name'] ?: null;
        }

        $now = now('Asia/Jakarta');
        $accuracy = $validated['accuracy'] ?? null;

        if (!$attendance->check_in_at) {
            $attendance->attendance_lock_area_id = $area->id;
            $attendance->check_in_at = $now;
            $attendance->check_in_latitude = $validated['latitude'];
            $attendance->check_in_longitude = $validated['longitude'];
            $attendance->check_in_accuracy = $accuracy;
            $attendance->check_in_area_name = $area->name;
            $attendance->check_in_photo_path = $this->storeAttendancePhoto(
                $validated['photo_data'],
                $user->id,
                $today,
                'check-in',
                $attendance->check_in_photo_path,
            );
            $attendance->save();

            return back()->with('message', [
                'type' => 'success',
                'text' => 'Absen masuk berhasil dicatat.',
            ]);
        }

        if (!$attendance->check_out_at) {
            $checkOutReason = trim((string) ($validated['check_out_reason'] ?? ''));

            if ($this->requiresEarlyCheckoutReason($attendance, $shift, $now) && $checkOutReason === '') {
                return back()->with('message', [
                    'type' => 'error',
                    'text' => 'Absen pulang sebelum jam pulang wajib mengisi alasan.',
                ]);
            }

            $attendance->attendance_lock_area_id = $area->id;
            $attendance->check_out_at = $now;
            $attendance->check_out_latitude = $validated['latitude'];
            $attendance->check_out_longitude = $validated['longitude'];
            $attendance->check_out_accuracy = $accuracy;
            $attendance->check_out_area_name = $area->name;
            $attendance->check_out_reason = $checkOutReason !== '' ? $checkOutReason : null;
            $attendance->check_out_photo_path = $this->storeAttendancePhoto(
                $validated['photo_data'],
                $user->id,
                $today,
                'check-out',
                $attendance->check_out_photo_path,
            );
            $attendance->save();

            return back()->with('message', [
                'type' => 'success',
                'text' => 'Absen pulang berhasil dicatat.',
            ]);
        }

        return back()->with('message', [
            'type' => 'info',
            'text' => 'Absensi hari ini sudah lengkap.',
        ]);
    }

    private function formatAttendance(AttendancePresence $attendance): array
    {
        return [
            'id' => $attendance->id,
            'check_in_at' => optional($attendance->check_in_at)->format('H:i'),
            'check_in_photo_url' => $attendance->check_in_photo_path ? Storage::disk('public')->url($attendance->check_in_photo_path) : null,
            'check_out_at' => optional($attendance->check_out_at)->format('H:i'),
            'check_out_reason' => $attendance->check_out_reason,
            'check_out_photo_url' => $attendance->check_out_photo_path ? Storage::disk('public')->url($attendance->check_out_photo_path) : null,
            'status' => $this->resolveAttendanceStatus($attendance),
        ];
    }

    private function resolveAttendanceStatus(AttendancePresence $attendance): string
    {
        if ($attendance->check_in_at && $attendance->check_out_at) {
            return 'Sudah Absen';
        }

        if ($attendance->check_in_at) {
            return 'Sudah Absen Masuk';
        }

        return 'Belum Absen';
    }

    private function resolveGreetingName(User $user): string
    {
        $name = trim((string) ($user->employee?->name ?: $user->name ?: 'User'));
        $first = preg_split('/\s+/', $name)[0] ?? 'User';
        return mb_convert_case(mb_strtolower($first), MB_CASE_TITLE, 'UTF-8');
    }

    private function resolveShift(User $user, ?Employee $employee, Carbon $date): array
    {
        $holiday = $this->resolveHoliday($employee, $date);
        if ($holiday) {
            return [
                'source_key' => 'holiday',
                'source' => 'Libur',
                'name' => 'Libur',
                'label' => "Libur ({$holiday})",
                'start_time' => null,
                'end_time' => null,
                'is_off' => false,
                'is_holiday' => true,
                'holiday_name' => $holiday,
                'roster_entry_id' => null,
            ];
        }

        $roster = $this->findRosterEntry($user, $employee, $date);
        if ($roster) {
            $start = $this->formatTime($roster->start_time);
            $end = $this->formatTime($roster->end_time);
            $code = trim((string) ($roster->shift_code ?: 'Roster'));
            $label = $roster->is_off
                ? 'OFF'
                : trim($code . (($start || $end) ? " ({$start} - {$end})" : ''));

            return [
                'source_key' => 'roster',
                'source' => 'Roster',
                'name' => $code,
                'label' => $label,
                'start_time' => $start,
                'end_time' => $end,
                'is_off' => (bool) $roster->is_off,
                'is_holiday' => false,
                'holiday_name' => null,
                'roster_entry_id' => $roster->id,
            ];
        }

        if ($date->isSunday()) {
            return [
                'source_key' => 'non_roster',
                'source' => 'Non Roster',
                'name' => 'OFF',
                'label' => 'OFF / Minggu',
                'start_time' => null,
                'end_time' => null,
                'is_off' => true,
                'is_holiday' => false,
                'holiday_name' => null,
                'roster_entry_id' => null,
            ];
        }

        $start = '08:00';
        $end = $date->isSaturday() ? '13:00' : '16:00';

        return [
            'source_key' => 'non_roster',
            'source' => 'Non Roster',
            'name' => 'Non Roster',
            'label' => "Non Roster ({$start} - {$end})",
            'start_time' => $start,
            'end_time' => $end,
            'is_off' => false,
            'is_holiday' => false,
            'holiday_name' => null,
            'roster_entry_id' => null,
        ];
    }

    private function findRosterEntry(User $user, ?Employee $employee, Carbon $date): ?RosterEntry
    {
        $candidates = collect([
            $employee?->nik,
            $user->account,
            $employee?->name,
            $user->name,
        ])
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->flatMap(function ($value) {
                $normalized = ltrim($value, '0');
                return array_values(array_unique(array_filter([$value, $normalized])));
            })
            ->values()
            ->all();

        if (empty($candidates)) {
            return null;
        }

        return RosterEntry::query()
            ->whereDate('roster_date', $date->toDateString())
            ->where(function ($query) use ($candidates, $employee, $user) {
                $query->whereIn('employee_nrp', $candidates)
                    ->orWhereIn('employee_key', $candidates);

                $nameCandidates = array_values(array_unique(array_filter([
                    $employee?->name,
                    $user->name,
                ])));

                if (!empty($nameCandidates)) {
                    $query->orWhereIn('employee_name', $nameCandidates);
                }
            })
            ->latest('id')
            ->first();
    }

    private function resolveHoliday(?Employee $employee, Carbon $date): ?string
    {
        $workGroup = strtolower(trim((string) ($employee?->work_group ?: '')));
        $holiday = AttendanceHoliday::query()
            ->whereDate('holiday_date', $date->toDateString())
            ->where(function ($query) use ($workGroup) {
                $query->where('scope_type', 'all');

                if ($workGroup !== '') {
                    $query->orWhere('scope_type', $workGroup);
                }
            })
            ->orderByRaw("CASE WHEN scope_type = 'all' THEN 0 ELSE 1 END")
            ->first();

        return $holiday?->name;
    }

    private function resolveMatchingArea(float $latitude, float $longitude, ?float $accuracy = null): ?AttendanceLockArea
    {
        $areas = AttendanceLockArea::query()
            ->where('is_active', true)
            ->get();

        return $areas->first(function (AttendanceLockArea $area) use ($latitude, $longitude, $accuracy) {
            $distance = $this->distanceInMeters(
                $latitude,
                $longitude,
                (float) $area->latitude,
                (float) $area->longitude,
            );

            $accuracyGrace = $this->resolveAccuracyGraceInMeters($accuracy, (float) $area->radius_meters);

            return $distance <= ((float) $area->radius_meters + $accuracyGrace);
        });
    }

    private function requiresEarlyCheckoutReason(AttendancePresence $attendance, array $shift, Carbon $checkOutAt): bool
    {
        if ($attendance->check_out_at) {
            return false;
        }

        if (!empty($shift['is_holiday']) || !empty($shift['is_off'])) {
            return true;
        }

        if (empty($shift['end_time'])) {
            return false;
        }

        $scheduledEnd = Carbon::parse($attendance->attendance_date->toDateString() . ' ' . $shift['end_time'], 'Asia/Jakarta');

        return $checkOutAt->lt($scheduledEnd);
    }

    private function evaluateEmployeeFace(?Employee $employee, array $descriptor): array
    {
        if (!$employee || empty($employee->face_reference_descriptor)) {
            return [
                'matched' => false,
                'distance' => null,
                'score' => 0,
            ];
        }

        $reference = json_decode((string) $employee->face_reference_descriptor, true);
        if (!is_array($reference) || count($reference) !== 128 || count($descriptor) !== 128) {
            return [
                'matched' => false,
                'distance' => null,
                'score' => 0,
            ];
        }

        $sum = 0.0;
        foreach ($reference as $index => $value) {
            $delta = (float) $value - (float) ($descriptor[$index] ?? 0);
            $sum += $delta * $delta;
        }

        $distance = sqrt($sum);
        $score = max(0, (int) round((1 - ($distance / self::FACE_MATCH_MAX_DISTANCE)) * 100));

        return [
            'matched' => $distance <= self::FACE_MATCH_MAX_DISTANCE,
            'distance' => $distance,
            'score' => $score,
        ];
    }

    private function storeAttendancePhoto(string $photoData, int $userId, Carbon $attendanceDate, string $type, ?string $existingPath = null): string
    {
        if (!preg_match('/^data:image\/(?P<extension>jpeg|jpg|png);base64,(?P<data>.+)$/i', $photoData, $matches)) {
            abort(422, 'Format foto absensi tidak valid.');
        }

        $binary = base64_decode($matches['data'], true);
        if ($binary === false) {
            abort(422, 'Foto absensi tidak dapat diproses.');
        }

        $extension = strtolower($matches['extension']) === 'png' ? 'png' : 'jpg';
        $directory = 'attendance-selfies/' . $attendanceDate->format('Y/m/d') . '/' . $userId;
        $filename = $type . '-' . now('Asia/Jakarta')->format('His') . '-' . Str::random(8) . '.' . $extension;
        $path = $directory . '/' . $filename;

        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function resolveAccuracyGraceInMeters(?float $accuracy, float $radiusMeters): float
    {
        if ($accuracy === null || $accuracy <= 0) {
            return 0;
        }

        return min($accuracy, max($radiusMeters, 10));
    }

    private function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function formatTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return substr((string) $value, 0, 5);
    }
}
