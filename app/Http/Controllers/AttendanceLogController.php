<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLogCorrection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceLogController extends Controller
{
    // Easy rollback switch for scan pairing logic.
    private const USE_SCHEDULE_WINDOWS = true;

    public function index(Request $request)
    {
        $canManageCorrections = $this->canManageCorrections($request->user());

        $monthInput = $request->input('month');
        $yearInput = $request->input('year');
        $month = ($monthInput === null || $monthInput === '' || $monthInput === 'all') ? null : (int) $monthInput;
        $year = ($yearInput === null || $yearInput === '' || $yearInput === 'all') ? null : (int) $yearInput;
        $status = strtolower(trim((string) $request->input('status', 'all')));
        $q = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 2000);

        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }
        if ($year !== null && ($year < 2000 || $year > 2100)) {
            $year = null;
        }
        if ($perPage < 10 || $perPage > 5000) {
            $perPage = 2000;
        }

        $corrections = AttendanceLogCorrection::query()
            ->when($year !== null, fn($query) => $query->whereYear('log_date', $year))
            ->when($month !== null, fn($query) => $query->whereMonth('log_date', $month))
            ->get();

        $correctionMap = $corrections->keyBy(function (AttendanceLogCorrection $correction) {
            return $correction->log_date->format('Y-m-d') . '|' . $this->normalizePin((string) $correction->pin);
        });

        $scanRows = DB::table('fingerprints')
            ->when($year !== null, fn($query) => $query->whereYear('scan_date_only', $year))
            ->when($month !== null, fn($query) => $query->whereMonth('scan_date_only', $month))
            ->whereNotNull('pin')
            ->where('pin', '<>', '')
            ->get([
                'scan_date_only as log_date',
                'pin',
                'name',
                'scan_date',
            ]);

        $employeeNameMap = DB::table('employees')
            ->whereNotNull('nik')
            ->where('nik', '<>', '')
            ->pluck('name', 'nik')
            ->mapWithKeys(function ($name, $nik) {
                $pin = $this->normalizePin((string) $nik);
                if ($pin === '') {
                    return [];
                }
                return [$pin => trim((string) $name)];
            });

        $scanGroups = $scanRows
            ->groupBy(fn($row) => $this->toDateString($row->log_date) . '|' . $this->normalizePin((string) $row->pin))
            ->map(fn(Collection $group) => $group->sortBy('scan_date')->values());

        $rosterRows = DB::table('roster_entries as re')
            ->join('roster_upload_batches as rub', 'rub.id', '=', 're.batch_id')
            ->when($year !== null, fn($query) => $query->whereYear('re.roster_date', $year))
            ->when($month !== null, fn($query) => $query->whereMonth('re.roster_date', $month))
            ->where('rub.status', 'approved')
            ->where('rub.is_current', true)
            ->whereNotNull('re.employee_nrp')
            ->where('re.employee_nrp', '<>', '')
            ->get([
                're.roster_date as log_date',
                're.employee_nrp as pin',
                're.employee_name as roster_name',
                're.shift_code',
                're.is_off',
                're.start_time',
                're.end_time',
            ]);

        $holidaysByDate = DB::table('attendance_holidays')
            ->when($year !== null, fn($query) => $query->whereYear('holiday_date', $year))
            ->when($month !== null, fn($query) => $query->whereMonth('holiday_date', $month))
            ->get(['holiday_date', 'name', 'scope_type'])
            ->groupBy(function ($row) {
                $date = $this->toDateString($row->holiday_date);
                return $date ?? '__invalid__';
            });

        if ($holidaysByDate->has('__invalid__')) {
            $holidaysByDate->forget('__invalid__');
        }

        $pinWorkGroupMap = DB::table('employees')
            ->whereNotNull('nik')
            ->where('nik', '<>', '')
            ->whereNotNull('work_group')
            ->where('work_group', '<>', '')
            ->pluck('work_group', 'nik')
            ->mapWithKeys(function ($group, $nik) {
                $pin = $this->normalizePin((string) $nik);
                if ($pin === '') {
                    return [];
                }
                $normalized = strtolower(trim((string) $group));
                if (!in_array($normalized, ['office', 'operational'], true)) {
                    return [];
                }
                return [$pin => $normalized];
            })
            ->all();

        $fingerprintWorkGroupFallback = DB::table('fingerprints')
            ->whereNotNull('pin')
            ->where('pin', '<>', '')
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->orderBy('scan_date', 'desc')
            ->get(['pin', 'department'])
            ->reduce(function ($carry, $row) {
                $pin = $this->normalizePin((string) $row->pin);
                if ($pin === '' || isset($carry[$pin])) {
                    return $carry;
                }
                $carry[$pin] = $this->resolveWorkGroup((string) $row->department);
                return $carry;
            }, []);

        foreach ($fingerprintWorkGroupFallback as $pin => $group) {
            if (!isset($pinWorkGroupMap[$pin]) && in_array($group, ['office', 'operational'], true)) {
                $pinWorkGroupMap[$pin] = $group;
            }
        }

        $rosterKeys = [];
        $rosterPinIndex = [];
        $rows = collect();

        foreach ($rosterRows as $row) {
            $logDate = $this->toDateString($row->log_date);
            $pin = $this->normalizePin((string) $row->pin);
            $scanLookupKeys = $this->pinCandidatesForMatch($pin);
            foreach ($scanLookupKeys as $rosterPinCandidate) {
                $rosterPinIndex[$rosterPinCandidate] = true;
            }
            $scans = collect();
            $startTime = $this->normalizeTime($row->start_time);
            $endTime = $this->normalizeTime($row->end_time);
            $lookupDates = $this->scanLookupDatesForSchedule($logDate, $startTime, $endTime);

            foreach ($scanLookupKeys as $lookupPin) {
                foreach ($lookupDates as $lookupDate) {
                    $extraKey = $lookupDate . '|' . $lookupPin;
                    if ($lookupDate === $logDate) {
                        $rosterKeys[$extraKey] = true;
                    }
                    $scans = $scans->merge($scanGroups->get($extraKey, collect()));
                }
            }
            $scans = $scans->sortBy('scan_date')->values();
            $isOff = (bool) ($row->is_off ?? false);
            if ($isOff) {
                $scans = $this->filterOffdayBoundaryScans($scans);
            }
            $scanCount = $scans->count();
            [$firstScan, $lastScan] = $this->resolveScanWindow($scans, $startTime, $endTime, $logDate);
            $correction = $correctionMap->get($logDate . '|' . $pin);
            if ($correction && $correction->status === 'approved') {
                if ($correction->corrected_first_scan !== null) {
                    $firstScan = $correction->corrected_first_scan->format('Y-m-d H:i:s');
                }
                if ($correction->corrected_last_scan !== null) {
                    $lastScan = $correction->corrected_last_scan->format('Y-m-d H:i:s');
                }
            }
            $firstScanTime = $this->normalizeTime($firstScan);
            $lastScanTime = $this->normalizeTime($lastScan);
            $timeMatched = $startTime !== null && $firstScanTime !== null && substr($startTime, 0, 5) === substr($firstScanTime, 0, 5);
            $hasFirstScan = $firstScan !== null;
            $hasLastScan = $lastScan !== null;
            $hasScheduleTime = $startTime !== null && $endTime !== null;
            $expected = null;
            $reason = null;
            [$isNationalHoliday, $holidayName] = $this->resolveHolidayForPin(
                $holidaysByDate,
                $logDate,
                $pin,
                $pinWorkGroupMap
            );

            $rowStatus = 'no_roster';
            if ($isNationalHoliday) {
                $rowStatus = 'holiday_national';
                $expected = 'Libur Nasional';
                $reason = $holidayName !== '' ? 'Libur Nasional: ' . $holidayName : 'Libur Nasional.';
            } elseif ($isOff && $scanCount > 0) {
                $rowStatus = 'check_again';
                $expected = 'Cek Lagi';
                $reason = 'Jadwal OFF namun ada scan.';
            } elseif ($isOff) {
                $rowStatus = 'offday';
                $expected = 'OFF';
            } elseif (!$hasScheduleTime && ($hasFirstScan || $hasLastScan)) {
                $rowStatus = 'check_again';
                $expected = 'Cek Lagi';
                $reason = 'Jadwal tidak memiliki jam tetapi ada scan.';
            } elseif (!$hasFirstScan && !$hasLastScan) {
                $rowStatus = 'missing_scan';
                $expected = 'Tidak Masuk';
                $reason = 'Tidak ada scan masuk pada tanggal jadwal.';
            } elseif ($hasFirstScan && !$hasLastScan) {
                [$evaluationExpected, $evaluationReason] = $this->evaluateCheckIn($startTime, $firstScanTime, $firstScan, $logDate);
                if ($evaluationExpected === 'Terlambat') {
                    $rowStatus = 'time_mismatch';
                    $expected = 'Terlambat';
                    $reason = $evaluationReason;
                } else {
                    $rowStatus = 'missing_checkout';
                    $expected = 'Tidak Scan pulang';
                    $reason = 'Scan pulang tidak ditemukan.';
                }
            } elseif (!$hasFirstScan && $hasLastScan) {
                $rowStatus = 'missing_checkin';
                $expected = 'Tidak Scan masuk';
                $reason = 'Scan masuk tidak ditemukan.';
            } elseif ($timeMatched) {
                $rowStatus = 'matched';
                $expected = 'On Time';
                $reason = 'Jam masuk sesuai jadwal.';
            } else {
                $rowStatus = 'time_mismatch';
                [$expected, $reason] = $this->evaluateCheckIn($startTime, $firstScanTime, $firstScan, $logDate);
            }

            $rows->push([
                'log_date' => $logDate,
                'pin' => $pin,
                'name' => (string) ($row->roster_name ?: ($scans->last()->name ?? '-')),
                'roster_name' => $row->roster_name,
                'fingerprint_name' => $scans->last()->name ?? null,
                'shift_code' => $row->shift_code,
                'is_off' => $isOff,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'first_scan' => $firstScan,
                'last_scan' => $lastScan,
                'scan_time' => $firstScanTime,
                'scan_count' => $scanCount,
                'status' => $rowStatus,
                'expected' => $expected,
                'reason' => $reason,
                'has_overtime' => $this->hasOvertime($logDate, $startTime, $endTime, $lastScan, $isOff || $isNationalHoliday),
                'overtime_minutes' => $this->overtimeMinutes($logDate, $startTime, $endTime, $lastScan, $isOff || $isNationalHoliday),
                'overtime_label' => $this->overtimeLabel($logDate, $startTime, $endTime, $lastScan, $isOff || $isNationalHoliday),
                'correction' => $this->formatCorrection($correction),
            ]);
        }

        $unmatchedByPinDate = [];
        foreach ($scanGroups as $key => $scans) {
            if (isset($rosterKeys[$key])) {
                continue;
            }

            [$logDate, $pin] = explode('|', $key, 2);
            $normalizedPin = $this->normalizePin($pin);
            if (!isset($unmatchedByPinDate[$normalizedPin])) {
                $unmatchedByPinDate[$normalizedPin] = [];
            }
            $unmatchedByPinDate[$normalizedPin][$logDate] = $scans->sortBy('scan_date')->values();
        }

        // Also include historical fingerprint PINs even when selected month has no scans,
        // so "Tanpa Roster" can still be shown for that period.
        $historicalPins = DB::table('fingerprints')
            ->whereNotNull('pin')
            ->where('pin', '<>', '')
            ->distinct()
            ->pluck('pin')
            ->map(fn($pin) => $this->normalizePin((string) $pin))
            ->filter()
            ->unique()
            ->values();

        foreach ($historicalPins as $historicalPin) {
            $pin = (string) $historicalPin;
            if ($pin === '') {
                continue;
            }

            if ($this->hasRosterPinMatch($pin, $rosterPinIndex)) {
                continue;
            }

            if (!isset($unmatchedByPinDate[$pin])) {
                $unmatchedByPinDate[$pin] = [];
            }
        }

        foreach ($unmatchedByPinDate as $pin => $dateMap) {
            // If this PIN can still be matched with roster using normalized/legacy variants,
            // do not generate fallback no-roster rows.
            if ($this->hasRosterPinMatch($pin, $rosterPinIndex)) {
                continue;
            }

            $monthKeys = collect(array_keys($dateMap))
                ->map(fn($date) => Carbon::parse($date)->format('Y-m'))
                ->filter(function ($ym) use ($year, $month) {
                    [$y, $m] = array_map('intval', explode('-', $ym));
                    if ($year !== null && $y !== (int) $year) return false;
                    if ($month !== null && $m !== (int) $month) return false;
                    return true;
                })
                ->unique()
                ->values()
                ->all();

            if (empty($monthKeys) && $year !== null && $month !== null) {
                $monthKeys = [sprintf('%04d-%02d', $year, $month)];
            }

            foreach ($monthKeys as $monthKey) {
                $monthStart = Carbon::createFromFormat('Y-m-d', $monthKey . '-01');
                $daysInMonth = $monthStart->daysInMonth;
                $displayName = collect($dateMap)
                    ->flatten(1)
                    ->sortByDesc('scan_date')
                    ->map(fn($scan) => trim((string) ($scan->name ?? '')))
                    ->first(fn($name) => $name !== '');

                if ($displayName === null || $displayName === '') {
                    $displayName = trim((string) ($employeeNameMap->get($pin) ?? ''));
                }

                if ($displayName === '') {
                    $displayName = '-';
                }

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $logDate = $monthStart->copy()->day($day)->format('Y-m-d');
                    $scans = collect($dateMap[$logDate] ?? []);

                    $scanCount = $scans->count();
                    [$firstScan, $lastScan] = $this->resolveScanWindow($scans, '08:00:00', '16:00:00', $logDate);
                    $correction = $correctionMap->get($logDate . '|' . $pin);
                    if ($correction && $correction->status === 'approved') {
                        if ($correction->corrected_first_scan !== null) {
                            $firstScan = $correction->corrected_first_scan->format('Y-m-d H:i:s');
                        }
                        if ($correction->corrected_last_scan !== null) {
                            $lastScan = $correction->corrected_last_scan->format('Y-m-d H:i:s');
                        }
                    }
                    $firstScanTime = $this->normalizeTime($firstScan);
                    $lastScanTime = $this->normalizeTime($lastScan);
                    $hasFirstScan = $firstScan !== null;
                    $hasLastScan = $lastScan !== null;
                    $isSunday = Carbon::parse($logDate)->isSunday();
                    [$isNationalHoliday, $holidayName] = $this->resolveHolidayForPin(
                        $holidaysByDate,
                        $logDate,
                        $pin,
                        $pinWorkGroupMap
                    );

                    $rowStatus = 'no_roster';
                    $expected = 'Cek Lagi';
                    $reason = 'Tidak ada roster, jadwal default 08:00 - 16:00.';

                    if ($isNationalHoliday) {
                        $rowStatus = 'holiday_national';
                        $expected = 'Libur Nasional';
                        $reason = $holidayName !== '' ? 'Libur Nasional: ' . $holidayName : 'Libur Nasional.';
                    } elseif ($isSunday) {
                        $rowStatus = 'offday';
                        $expected = 'OFF';
                        $reason = 'Hari Minggu tanpa roster dianggap OFF.';
                    } elseif (!$hasFirstScan && !$hasLastScan) {
                        $rowStatus = 'missing_scan';
                        $expected = 'Tidak Masuk';
                        $reason = 'Tidak ada scan masuk pada tanggal jadwal.';
                    } elseif ($hasFirstScan && !$hasLastScan) {
                        [$evaluationExpected, $evaluationReason] = $this->evaluateCheckIn('08:00:00', $firstScanTime, $firstScan, $logDate);
                        if ($evaluationExpected === 'Terlambat') {
                            $rowStatus = 'time_mismatch';
                            $expected = 'Terlambat';
                            $reason = $evaluationReason;
                        } else {
                            $rowStatus = 'missing_checkout';
                            $expected = 'Tidak Scan pulang';
                            $reason = 'Scan pulang tidak ditemukan.';
                        }
                    } elseif (!$hasFirstScan && $hasLastScan) {
                        $rowStatus = 'missing_checkin';
                        $expected = 'Tidak Scan masuk';
                        $reason = 'Scan masuk tidak ditemukan.';
                    } else {
                        [$evaluationExpected, $evaluationReason] = $this->evaluateCheckIn('08:00:00', $firstScanTime, $firstScan, $logDate);
                        if ($evaluationExpected === 'Terlambat') {
                            $rowStatus = 'time_mismatch';
                            $expected = 'Terlambat';
                            $reason = $evaluationReason;
                        } elseif ($evaluationExpected === 'On Time') {
                            $rowStatus = 'matched';
                            $expected = 'On Time';
                            $reason = $evaluationReason;
                        }
                    }

                    $rows->push([
                        'log_date' => $logDate,
                        'pin' => $pin,
                        'name' => $displayName,
                        'roster_name' => null,
                        'fingerprint_name' => $displayName !== '-' ? $displayName : null,
                        'shift_code' => null,
                        'is_off' => false,
                        'start_time' => '08:00:00',
                        'end_time' => '16:00:00',
                        'first_scan' => $firstScan,
                        'last_scan' => $lastScan,
                        'scan_time' => $firstScanTime,
                        'scan_count' => $scanCount,
                        'status' => $rowStatus,
                        'expected' => $expected,
                        'reason' => $reason,
                        'has_overtime' => $this->hasOvertime($logDate, '08:00:00', '16:00:00', $lastScan, $isSunday || $isNationalHoliday),
                        'overtime_minutes' => $this->overtimeMinutes($logDate, '08:00:00', '16:00:00', $lastScan, $isSunday || $isNationalHoliday),
                        'overtime_label' => $this->overtimeLabel($logDate, '08:00:00', '16:00:00', $lastScan, $isSunday || $isNationalHoliday),
                        'correction' => $this->formatCorrection($correction),
                    ]);
                }
            }
        }

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = $rows->filter(function (array $row) use ($needle) {
                return str_contains(mb_strtolower((string) $row['pin']), $needle)
                    || str_contains(mb_strtolower((string) $row['name']), $needle);
            })->values();
        }

        $summaryRows = $rows;

        if ($status !== 'all') {
            $rows = $rows->filter(function (array $row) use ($status) {
                $expected = mb_strtolower(trim((string) ($row['expected'] ?? '')));
                $hasNoRosterSource = trim((string) ($row['roster_name'] ?? '')) === '';

                return match ($status) {
                    'on_time' => $expected === 'on time',
                    'terlambat' => $expected === 'terlambat',
                    'tidak_masuk' => $expected === 'tidak masuk',
                    'tidak_scan_masuk' => $expected === 'tidak scan masuk',
                    'tidak_scan_pulang' => $expected === 'tidak scan pulang',
                    'off' => $expected === 'off',
                    'libur_nasional' => $expected === 'libur nasional',
                    'cek_lagi' => $expected === 'cek lagi',
                    'no_roster' => $row['status'] === 'no_roster' || $hasNoRosterSource,
                    default => $row['status'] === $status,
                };
            })->values();
        }

        $rows = $rows
            ->sortBy([
                ['name', 'asc'],
                ['log_date', 'asc'],
                ['pin', 'asc'],
            ])
            ->values();

        if ($request->boolean('export')) {
            return $this->exportRowsToExcel($rows);
        }

        $expectedCounts = $summaryRows
            ->groupBy(function (array $row) {
                $label = trim((string) ($row['expected'] ?? ''));
                return $label !== '' ? $label : '-';
            })
            ->map(fn(Collection $group) => $group->count())
            ->all();

        $summary = [
            'total' => $summaryRows->count(),
            'on_time' => (int) ($expectedCounts['On Time'] ?? 0),
            'terlambat' => (int) ($expectedCounts['Terlambat'] ?? 0),
            'tidak_masuk' => (int) ($expectedCounts['Tidak Masuk'] ?? 0),
            'tidak_scan_pulang' => (int) ($expectedCounts['Tidak Scan pulang'] ?? 0),
            'tidak_scan_masuk' => (int) ($expectedCounts['Tidak Scan masuk'] ?? 0),
            'off' => (int) ($expectedCounts['OFF'] ?? 0),
            'cek_lagi' => (int) ($expectedCounts['Cek Lagi'] ?? 0),
            'expected_counts' => $expectedCounts,
        ];

        $attendanceLogs = $this->paginateCollection($rows, $perPage, $request);

        return Inertia::render('GMIHR/AttendanceLog/Index', [
            'attendanceLogs' => $attendanceLogs,
            'summary' => $summary,
            'canManageCorrections' => $canManageCorrections,
            'filters' => [
                'month' => $month ?? 'all',
                'year' => $year ?? 'all',
                'status' => $status,
                'q' => $q,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function storeCorrection(Request $request)
    {
        if (!$this->canManageCorrections($request->user())) {
            abort(403, 'Hanya IT/HRD yang dapat melakukan koreksi attendance.');
        }

        $validated = $request->validate([
            'log_date' => ['required', 'date'],
            'pin' => ['required', 'string', 'max:32'],
            'start_time' => ['nullable', 'date_format:H:i:s'],
            'end_time' => ['nullable', 'date_format:H:i:s'],
            'corrected_first_time' => ['nullable', 'date_format:H:i'],
            'corrected_last_time' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($validated['corrected_first_time']) && empty($validated['corrected_last_time'])) {
            return redirect()->back()->withErrors([
                'corrected_time' => 'Isi minimal salah satu jam koreksi: masuk atau pulang.',
            ]);
        }

        $pin = $this->normalizePin((string) $validated['pin']);
        $logDate = Carbon::parse($validated['log_date'])->format('Y-m-d');
        $startTime = $validated['start_time'] ?? null;
        $endTime = $validated['end_time'] ?? null;

        $firstScan = $this->buildCorrectionDateTime($logDate, $validated['corrected_first_time'] ?? null, $startTime, $endTime, false);
        $lastScan = $this->buildCorrectionDateTime($logDate, $validated['corrected_last_time'] ?? null, $startTime, $endTime, true);

        AttendanceLogCorrection::updateOrCreate(
            [
                'log_date' => $logDate,
                'pin' => $pin,
            ],
            [
                'corrected_first_scan' => $firstScan,
                'corrected_last_scan' => $lastScan,
                'note' => $validated['note'] ?? null,
                'status' => 'pending',
                'requested_by' => optional($request->user())->id,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]
        );

        return redirect()->back()->with('success', 'Koreksi attendance berhasil diajukan. Menunggu approval IT/HRD.');
    }

    public function approveCorrection(Request $request, AttendanceLogCorrection $correction)
    {
        if (!$this->canManageCorrections($request->user())) {
            abort(403, 'Hanya IT/HRD yang dapat menyetujui koreksi attendance.');
        }

        $correction->update([
            'status' => 'approved',
            'approved_by' => optional($request->user())->id,
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        return redirect()->back()->with('success', 'Koreksi attendance disetujui.');
    }

    public function rejectCorrection(Request $request, AttendanceLogCorrection $correction)
    {
        if (!$this->canManageCorrections($request->user())) {
            abort(403, 'Hanya IT/HRD yang dapat menolak koreksi attendance.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $correction->update([
            'status' => 'rejected',
            'approved_by' => optional($request->user())->id,
            'approved_at' => null,
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Koreksi attendance ditolak.');
    }

    private function resolveScanWindow(Collection $scans, ?string $startTime, ?string $endTime, ?string $logDate = null): array
    {
        if (self::USE_SCHEDULE_WINDOWS && $logDate !== null && $startTime !== null && $endTime !== null) {
            return $this->resolveByScheduleWindows($scans, $startTime, $endTime, $logDate);
        }

        return $this->resolveScanWindowLegacy($scans, $startTime, $endTime, $logDate);
    }

    private function resolveByScheduleWindows(Collection $scans, string $startTime, string $endTime, string $logDate): array
    {
        if ($scans->isEmpty()) {
            return [null, null];
        }

        try {
            $sorted = $scans->sortBy('scan_date')->values();
            $anchorStart = Carbon::parse($logDate . ' ' . $startTime);
            $anchorEnd = Carbon::parse($logDate . ' ' . $endTime);
            if ($this->isOvernightShift($startTime, $endTime)) {
                $anchorEnd->addDay();
            }

            $inBeforeHours = substr($startTime, 0, 8) === '00:00:00' ? 6 : 4;
            $inFrom = $anchorStart->copy()->subHours($inBeforeHours);
            $inTo = $anchorStart->copy()->addHours(3);
            $outFrom = $anchorEnd->copy()->subHours(3);
            $outTo = $anchorEnd->copy()->addHours(8);

            $checkinCandidates = $sorted->filter(function ($scan) use ($inFrom, $inTo) {
                try {
                    $scanAt = Carbon::parse($scan->scan_date);
                    return $scanAt->betweenIncluded($inFrom, $inTo);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            $checkoutCandidates = $sorted->filter(function ($scan) use ($outFrom, $outTo) {
                try {
                    $scanAt = Carbon::parse($scan->scan_date);
                    return $scanAt->betweenIncluded($outFrom, $outTo);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            $checkinScan = $this->pickClosestScan($checkinCandidates, $anchorStart, 'before');
            $checkoutScan = $this->pickClosestScan($checkoutCandidates, $anchorEnd, 'after');

            if ($checkinScan && $checkoutScan) {
                $inTs = $this->toDateTimeString($checkinScan->scan_date ?? null);
                $outTs = $this->toDateTimeString($checkoutScan->scan_date ?? null);
                if ($inTs !== null && $inTs === $outTs) {
                    $inDiff = abs(Carbon::parse($inTs)->diffInMinutes($anchorStart, false));
                    $outDiff = abs(Carbon::parse($outTs)->diffInMinutes($anchorEnd, false));
                    if ($inDiff <= $outDiff) {
                        $checkoutScan = null;
                    } else {
                        $checkinScan = null;
                    }
                }
            }

            return [
                $this->toDateTimeString(optional($checkinScan)->scan_date ?? null),
                $this->toDateTimeString(optional($checkoutScan)->scan_date ?? null),
            ];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    private function resolveScanWindowLegacy(Collection $scans, ?string $startTime, ?string $endTime, ?string $logDate = null): array
    {
        if ($scans->isEmpty()) {
            return [null, null];
        }

        $sorted = $scans->sortBy('scan_date')->values();
        if ($this->requiresPreviousDayWindow($startTime, $endTime) && $logDate !== null) {
            return $this->resolveEarlyStartWindow($sorted, $startTime, $endTime, $logDate);
        }

        if ($sorted->count() === 1) {
            $only = $sorted->first();
            $onlyTime = $this->normalizeTime($only->scan_date);
            if ($this->isEarlyMorningTime($onlyTime) || $this->isLikelyCheckoutTime($onlyTime, $startTime, $endTime)) {
                return [null, $this->toDateTimeString($only->scan_date ?? null)];
            }

            return [$this->toDateTimeString($only->scan_date ?? null), null];
        }

        $earlyMorningScans = $sorted->filter(function ($scan) {
            $time = $this->normalizeTime($scan->scan_date);
            return $this->isEarlyMorningTime($time);
        })->values();
        $regularScans = $sorted->reject(function ($scan) {
            $time = $this->normalizeTime($scan->scan_date);
            return $this->isEarlyMorningTime($time);
        })->values();

        if ($earlyMorningScans->isNotEmpty() && $regularScans->isNotEmpty()) {
            $first = $regularScans->sortBy('scan_date')->first();
            $last = $earlyMorningScans->sortByDesc('scan_date')->first();
            return [
                $this->toDateTimeString($first->scan_date ?? null),
                $this->toDateTimeString($last->scan_date ?? null),
            ];
        }

        $isOvernightShift = $this->isOvernightShift($startTime, $endTime);
        $candidate = $isOvernightShift
            ? $sorted->first()
            : $sorted->last();

        $first = $isOvernightShift ? $sorted->last() : $sorted->first();
        return [
            $this->toDateTimeString($first->scan_date ?? null),
            $this->toDateTimeString($candidate->scan_date ?? null),
        ];
    }

    private function resolveEarlyStartWindow(Collection $sortedScans, ?string $startTime, ?string $endTime, string $logDate): array
    {
        if ($startTime === null || $endTime === null) {
            return [null, null];
        }

        try {
            $startAt = Carbon::parse($logDate . ' ' . $startTime);
            $endAt = Carbon::parse($logDate . ' ' . $endTime);

            $checkinCandidates = $sortedScans->filter(function ($scan) use ($startAt) {
                try {
                    $scanAt = Carbon::parse($scan->scan_date);
                } catch (\Throwable $e) {
                    return false;
                }
                return $scanAt->betweenIncluded($startAt->copy()->subHours(6), $startAt->copy()->addHours(4));
            })->values();

            $checkoutCandidates = $sortedScans->filter(function ($scan) use ($endAt) {
                try {
                    $scanAt = Carbon::parse($scan->scan_date);
                } catch (\Throwable $e) {
                    return false;
                }
                return $scanAt->betweenIncluded($endAt->copy()->subHours(4), $endAt->copy()->addHours(8));
            })->values();

            $checkinScan = $this->pickClosestScan($checkinCandidates, $startAt, 'before');
            $checkoutScan = $this->pickClosestScan($checkoutCandidates, $endAt, 'after');

            return [
                $this->toDateTimeString(optional($checkinScan)->scan_date ?? null),
                $this->toDateTimeString(optional($checkoutScan)->scan_date ?? null),
            ];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    private function pickClosestScan(Collection $candidates, Carbon $anchor, string $prefer = 'before')
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        $primary = $candidates->filter(function ($scan) use ($anchor, $prefer) {
            try {
                $scanAt = Carbon::parse($scan->scan_date);
            } catch (\Throwable $e) {
                return false;
            }
            return $prefer === 'after'
                ? $scanAt->greaterThanOrEqualTo($anchor)
                : $scanAt->lessThanOrEqualTo($anchor);
        });

        $pool = $primary->isNotEmpty() ? $primary : $candidates;
        return $pool->sortBy(function ($scan) use ($anchor) {
            try {
                return abs(Carbon::parse($scan->scan_date)->diffInMinutes($anchor, false));
            } catch (\Throwable $e) {
                return PHP_INT_MAX;
            }
        })->first();
    }

    private function isOvernightShift(?string $startTime, ?string $endTime): bool
    {
        if ($startTime === null || $endTime === null) {
            return false;
        }

        return $endTime <= $startTime;
    }

    private function scanLookupDatesForSchedule(?string $logDate, ?string $startTime, ?string $endTime): array
    {
        if ($logDate === null) {
            return [];
        }

        $dates = [$logDate];
        try {
            if ($startTime !== null && substr($startTime, 0, 8) === '00:00:00') {
                $dates[] = Carbon::parse($logDate)->subDay()->format('Y-m-d');
            }
            if ($this->isOvernightShift($startTime, $endTime)) {
                $dates[] = Carbon::parse($logDate)->addDay()->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            // keep base log date only
        }

        return array_values(array_unique($dates));
    }

    private function requiresPreviousDayWindow(?string $startTime, ?string $endTime): bool
    {
        if ($startTime === null || $endTime === null) {
            return false;
        }

        if ($this->isOvernightShift($startTime, $endTime)) {
            return false;
        }

        try {
            $startHour = (int) substr($startTime, 0, 2);
            $endHour = (int) substr($endTime, 0, 2);
            return $startHour <= 2 && $endHour <= 12;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function filterOffdayBoundaryScans(Collection $scans): Collection
    {
        return $scans->filter(function ($scan) {
            $time = $this->normalizeTime($scan->scan_date);
            if ($time === null) {
                return false;
            }

            // For OFF rows, ignore midnight boundary scans that usually belong
            // to adjacent scheduled shifts (previous/next day).
            return $time >= '06:00:00' && $time < '21:00:00';
        })->values();
    }

    private function paginateCollection(Collection $rows, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));
        $total = $rows->count();
        $items = $rows->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function toDateString($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function toDateTimeString($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizePin(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
    }

    private function pinCandidatesForMatch(string $pin): array
    {
        $pin = $this->normalizePin($pin);
        if ($pin === '') {
            return [];
        }

        $candidates = [$pin];

        // Support legacy pattern where fingerprint stores extra 0 after "25":
        // 2581507 <-> 25081507
        if (strlen($pin) === 7 && str_starts_with($pin, '25')) {
            $candidates[] = substr($pin, 0, 2) . '0' . substr($pin, 2);
        }
        if (strlen($pin) === 8 && str_starts_with($pin, '250')) {
            $candidates[] = substr($pin, 0, 2) . substr($pin, 3);
        }

        // Support PIN with leading zero in one source and without leading zero in another:
        // 080414383 <-> 80414383
        if (str_starts_with($pin, '0')) {
            $trimmedLeadingZero = ltrim($pin, '0');
            if ($trimmedLeadingZero !== '') {
                $candidates[] = $trimmedLeadingZero;
            }
        }

        // Support decimal artifact from import (e.g. 25120126.0 -> 251201260 after normalization):
        // try removing one trailing zero variant.
        if (strlen($pin) >= 8 && str_ends_with($pin, '0')) {
            $candidates[] = substr($pin, 0, -1);
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function hasRosterPinMatch(string $pin, array $rosterPinIndex): bool
    {
        foreach ($this->pinCandidatesForMatch($pin) as $candidate) {
            if (isset($rosterPinIndex[$candidate])) {
                return true;
            }
        }

        return false;
    }

    private function resolveWorkGroup(string $department): ?string
    {
        $value = mb_strtolower(trim($department));
        if ($value === '') {
            return null;
        }

        if (str_contains($value, 'office') || str_contains($value, 'kantor')) {
            return 'office';
        }

        if (
            str_contains($value, 'operational')
            || str_contains($value, 'opperational')
            || str_contains($value, 'operation')
            || str_contains($value, 'ops')
        ) {
            return 'operational';
        }

        return null;
    }

    private function resolveHolidayForPin(Collection $holidaysByDate, string $logDate, string $pin, array $pinWorkGroupMap): array
    {
        $holidayRows = $holidaysByDate->get($logDate, collect());
        if ($holidayRows->isEmpty()) {
            return [false, ''];
        }

        $workGroup = $pinWorkGroupMap[$pin] ?? null;
        $scopes = $holidayRows
            ->map(fn($row) => strtolower((string) ($row->scope_type ?? 'all')))
            ->filter()
            ->unique()
            ->values();

        // If both scoped holidays exist on same date (office + operational),
        // treat the day as holiday for everyone.
        if ($scopes->contains('office') && $scopes->contains('operational')) {
            $first = $holidayRows->first();
            return [true, trim((string) ($first->name ?? ''))];
        }

        $matched = $holidayRows->first(function ($row) use ($workGroup) {
            $scope = strtolower((string) ($row->scope_type ?? 'all'));
            if ($scope === 'all') {
                return true;
            }
            if ($scope === 'office' && $workGroup === 'office') {
                return true;
            }
            if ($scope === 'operational' && $workGroup === 'operational') {
                return true;
            }
            return false;
        });

        if (!$matched) {
            return [false, ''];
        }

        return [true, trim((string) ($matched->name ?? ''))];
    }

    private function isEarlyMorningTime(?string $time): bool
    {
        if ($time === null) {
            return false;
        }

        return strcmp(substr($time, 0, 8), '05:00:00') <= 0;
    }

    private function isLikelyCheckoutTime(?string $time, ?string $startTime, ?string $endTime): bool
    {
        if ($time === null || $startTime === null || $endTime === null) {
            return false;
        }

        try {
            $scan = Carbon::parse('2000-01-01 ' . $time);
            $start = Carbon::parse('2000-01-01 ' . $startTime);
            $end = Carbon::parse('2000-01-01 ' . $endTime);
            $isOvernight = $end->lessThanOrEqualTo($start);
            if ($isOvernight) {
                $end->addDay();
            }

            $scanAlt = Carbon::parse('2000-01-01 ' . $time);
            // Only shift scan to next day for overnight schedule.
            if ($isOvernight && $scanAlt->lessThan($start)) {
                $scanAlt->addDay();
            }

            $toStart = abs($scanAlt->diffInMinutes($start, false));
            $toEnd = abs($scanAlt->diffInMinutes($end, false));
            return $toEnd < $toStart;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function buildCorrectionDateTime(?string $logDate, ?string $timeHm, ?string $startTime, ?string $endTime, bool $isLastScan): ?string
    {
        if ($logDate === null || $timeHm === null || $timeHm === '') {
            return null;
        }

        try {
            $scan = Carbon::parse($logDate . ' ' . $timeHm . ':00');

            // For correction checkout, treat times that are earlier than shift start
            // as next-day checkout (crossing midnight).
            if ($isLastScan && $startTime !== null) {
                $start = Carbon::parse($logDate . ' ' . $startTime);
                if ($scan->lessThan($start)) {
                    $scan->addDay();
                }
            }

            return $scan->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatCorrection(?AttendanceLogCorrection $correction): ?array
    {
        if (!$correction) {
            return null;
        }

        return [
            'id' => $correction->id,
            'status' => $correction->status,
            'first_scan' => optional($correction->corrected_first_scan)->format('Y-m-d H:i:s'),
            'last_scan' => optional($correction->corrected_last_scan)->format('Y-m-d H:i:s'),
            'note' => $correction->note,
            'rejection_reason' => $correction->rejection_reason,
        ];
    }

    private function exportRowsToExcel(Collection $rows)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendance Logs');

        $headers = [
            'Tanggal',
            'PIN',
            'Nama',
            'Shift',
            'Hari',
            'Jadwal',
            'Scan Pertama',
            'Scan Terakhir',
            'Overtime',
            'Status',
        ];

        foreach ($headers as $index => $header) {
            $cell = Coordinate::stringFromColumnIndex($index + 1) . '1';
            $sheet->setCellValue($cell, $header);
        }

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $rowIndex, (string) ($row['log_date'] ?? '-'));
            $sheet->setCellValue('B' . $rowIndex, (string) ($row['pin'] ?? '-'));
            $sheet->setCellValue('C' . $rowIndex, (string) ($row['name'] ?? '-'));
            $sheet->setCellValue('D' . $rowIndex, (string) ($row['shift_code'] ?? (($row['is_off'] ?? false) ? 'OFF' : '-')));
            $sheet->setCellValue('E' . $rowIndex, $this->exportDayName((string) ($row['log_date'] ?? '')));
            $sheet->setCellValue('F' . $rowIndex, $this->exportSchedule($row['start_time'] ?? null, $row['end_time'] ?? null));
            $sheet->setCellValue('G' . $rowIndex, $this->exportTimeOnly($row['first_scan'] ?? null));
            $sheet->setCellValue('H' . $rowIndex, $this->exportTimeOnly($row['last_scan'] ?? null));
            $sheet->setCellValue('I' . $rowIndex, (string) ($row['overtime_label'] ?? '-'));
            $sheet->setCellValue('J' . $rowIndex, (string) ($row['expected'] ?? '-'));
            $rowIndex++;
        }

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'attendance_logs_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function exportTimeOnly($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return '-';
        }
    }

    private function exportSchedule($start, $end): string
    {
        $startTime = $this->normalizeTime($start);
        $endTime = $this->normalizeTime($end);

        if ($startTime === null || $endTime === null) {
            return '-';
        }

        return substr($startTime, 0, 5) . ' - ' . substr($endTime, 0, 5);
    }

    private function exportDayName(string $logDate): string
    {
        if ($logDate === '') {
            return '-';
        }

        try {
            return Carbon::parse($logDate)->locale('id')->translatedFormat('l');
        } catch (\Throwable $e) {
            return '-';
        }
    }

    private function hasOvertime(?string $logDate, ?string $startTime, ?string $endTime, ?string $lastScan, bool $isOff = false): bool
    {
        return $this->overtimeMinutes($logDate, $startTime, $endTime, $lastScan, $isOff) > 0;
    }

    private function overtimeLabel(?string $logDate, ?string $startTime, ?string $endTime, ?string $lastScan, bool $isOff = false): string
    {
        $minutes = $this->overtimeMinutes($logDate, $startTime, $endTime, $lastScan, $isOff);
        if ($minutes <= 0) {
            return '-';
        }

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function overtimeMinutes(?string $logDate, ?string $startTime, ?string $endTime, ?string $lastScan, bool $isOff = false): int
    {
        if ($isOff || $logDate === null || $endTime === null || $lastScan === null) {
            return 0;
        }

        try {
            $scheduledEnd = Carbon::parse($logDate . ' ' . $endTime);
            if ($this->isOvernightShift($startTime, $endTime)) {
                $scheduledEnd->addDay();
            }

            $lastScanAt = Carbon::parse($lastScan);
            if ($startTime !== null) {
                $startAt = Carbon::parse($logDate . ' ' . $startTime);
                if ($lastScanAt->lessThan($startAt)) {
                    $lastScanAt->addDay();
                }
            }
            // Overtime starts after 1 hour grace from scheduled end time.
            $minutes = (int) $scheduledEnd
                ->copy()
                ->addHour()
                ->diffInMinutes($lastScanAt, false);

            return max(0, $minutes);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function canManageCorrections($user): bool
    {
        if (!$user) {
            return false;
        }

        $department = DB::table('departments')
            ->where('id', $user->department_id)
            ->first(['code', 'name']);

        if (!$department) {
            return false;
        }

        $code = strtoupper(trim((string) ($department->code ?? '')));
        $name = strtoupper(trim((string) ($department->name ?? '')));

        if ($code === 'IT') {
            return true;
        }

        if (str_contains($name, 'IT')) {
            return true;
        }

        return false;
    }

    private function evaluateCheckIn(?string $startTime, ?string $firstScanTime, ?string $firstScanDateTime = null, ?string $logDate = null): array
    {
        if ($startTime === null || $firstScanTime === null) {
            return [null, null];
        }

        try {
            if ($firstScanDateTime !== null && $logDate !== null) {
                $start = Carbon::parse($logDate . ' ' . $startTime);
                $scan = Carbon::parse($firstScanDateTime);
            } else {
                $start = Carbon::parse('2000-01-01 ' . $startTime);
                $scan = Carbon::parse('2000-01-01 ' . $firstScanTime);
            }
            $diffMinutes = (int) floor(($scan->getTimestamp() - $start->getTimestamp()) / 60);
            $scanHm = substr($firstScanTime, 0, 5);
            $startHm = substr($startTime, 0, 5);

            if ($diffMinutes > 10) {
                return [
                    'Terlambat',
                    "{$scanHm} masuk telat. Telat kalau lebih dari 10 menit dari jadwal {$startHm}.",
                ];
            }

            if ($diffMinutes > 0) {
                return [
                    'On Time',
                    "{$scanHm} masuk telat tapi masih dalam toleransi 10 menit dari jadwal {$startHm}.",
                ];
            }

            if ($diffMinutes < 0) {
                return [
                    'On Time',
                    "{$scanHm} masuk lebih cepat dari jadwal {$startHm}.",
                ];
            }

            return ['On Time', "Jam masuk sesuai jadwal {$startHm}."];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }
}
