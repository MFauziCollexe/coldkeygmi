<?php

namespace Tests\Unit;

use App\Http\Controllers\AttendanceLogController;
use Illuminate\Support\Collection;
use ReflectionMethod;
use stdClass;
use Tests\TestCase;

class AttendanceLogControllerTest extends TestCase
{
    public function test_resolve_by_schedule_windows_ignores_same_day_early_morning_carryover_for_non_overnight_shift(): void
    {
        $controller = new AttendanceLogController();

        [$firstScan, $lastScan] = $this->invokeResolveByScheduleWindows(
            $controller,
            $this->makeScans([
                '2026-04-17 01:39:00',
                '2026-04-17 14:03:10',
                '2026-04-18 00:38:59',
            ]),
            '13:00:00',
            '21:00:00',
            '2026-04-17',
            '13:00:00'
        );

        $this->assertSame('2026-04-17 14:03:10', $firstScan);
        $this->assertSame('2026-04-18 00:38:59', $lastScan);
    }

    public function test_resolve_by_schedule_windows_keeps_current_shift_scans_for_next_day_row(): void
    {
        $controller = new AttendanceLogController();

        [$firstScan, $lastScan] = $this->invokeResolveByScheduleWindows(
            $controller,
            $this->makeScans([
                '2026-04-18 00:38:59',
                '2026-04-18 13:06:27',
                '2026-04-18 18:01:11',
            ]),
            '13:00:00',
            '17:00:00',
            '2026-04-18',
            '13:00:00'
        );

        $this->assertSame('2026-04-18 13:06:27', $firstScan);
        $this->assertSame('2026-04-18 18:01:11', $lastScan);
    }

    public function test_resolve_by_schedule_windows_marks_single_late_scan_as_checkout_only(): void
    {
        $controller = new AttendanceLogController();

        [$firstScan, $lastScan] = $this->invokeResolveByScheduleWindows(
            $controller,
            $this->makeScans([
                '2026-04-04 14:00:00',
            ]),
            '08:00:00',
            '13:00:00',
            '2026-04-04',
            '08:00:00'
        );

        $this->assertNull($firstScan);
        $this->assertSame('2026-04-04 14:00:00', $lastScan);
    }

    public function test_resolve_by_schedule_windows_uses_previous_day_checkin_for_midnight_shift(): void
    {
        $controller = new AttendanceLogController();

        [$firstScan, $lastScan] = $this->invokeResolveByScheduleWindows(
            $controller,
            $this->makeScans([
                '2026-04-10 23:55:29',
                '2026-04-11 08:12:58',
                '2026-04-11 23:53:42',
            ]),
            '00:00:00',
            '08:00:00',
            '2026-04-11',
            '00:00:00'
        );

        $this->assertSame('2026-04-10 23:55:29', $firstScan);
        $this->assertSame('2026-04-11 08:12:58', $lastScan);
    }

    public function test_resolve_roster_schedule_for_attendance_recalculates_numeric_saturday_shift_from_shift_code(): void
    {
        $controller = new AttendanceLogController();

        $schedule = $this->invokeResolveRosterScheduleForAttendance(
            $controller,
            '2026-05-16',
            '8',
            false,
            'Inventory',
            '25111732',
            'Memet Wibowo',
            '08:00:00',
            '12:00:00'
        );

        $this->assertSame('08:00:00', $schedule['start_time']);
        $this->assertSame('13:00:00', $schedule['end_time']);
    }

    public function test_resolve_roster_schedule_for_attendance_keeps_stored_schedule_for_unknown_shift_code(): void
    {
        $controller = new AttendanceLogController();

        $schedule = $this->invokeResolveRosterScheduleForAttendance(
            $controller,
            '2026-05-16',
            'CUSTOM',
            false,
            'Inventory',
            '25111732',
            'Memet Wibowo',
            '09:00:00',
            '15:00:00'
        );

        $this->assertSame('09:00:00', $schedule['start_time']);
        $this->assertSame('15:00:00', $schedule['end_time']);
    }

    public function test_normalize_pin_preserves_letter_prefixes_to_avoid_employee_collision(): void
    {
        $controller = new AttendanceLogController();

        $this->assertSame('AKU26002', $this->invokeNormalizePin($controller, 'AKU26002'));
        $this->assertSame('KBM26002', $this->invokeNormalizePin($controller, 'KBM26002'));
        $this->assertNotSame(
            $this->invokeNormalizePin($controller, 'AKU26002'),
            $this->invokeNormalizePin($controller, 'KBM26002')
        );
    }

    public function test_pin_candidates_keep_numeric_legacy_variants_only_for_numeric_pins(): void
    {
        $controller = new AttendanceLogController();

        $this->assertSame(['2581507', '25081507'], $this->invokePinCandidatesForMatch($controller, '2581507'));
        $this->assertSame(['25081507', '2581507'], $this->invokePinCandidatesForMatch($controller, '25081507'));
        $this->assertSame(['080414383', '80414383'], $this->invokePinCandidatesForMatch($controller, '080414383'));
        $this->assertSame(['KBM26002'], $this->invokePinCandidatesForMatch($controller, 'KBM26002'));
    }

    private function invokeResolveByScheduleWindows(
        AttendanceLogController $controller,
        Collection $scans,
        string $startTime,
        string $endTime,
        string $logDate,
        ?string $nextDayStartTime = null
    ): array {
        $method = new ReflectionMethod($controller, 'resolveByScheduleWindows');
        $method->setAccessible(true);

        return $method->invoke($controller, $scans, $startTime, $endTime, $logDate, $nextDayStartTime);
    }

    private function invokeResolveRosterScheduleForAttendance(
        AttendanceLogController $controller,
        string $logDate,
        string $shiftCode,
        bool $isOff,
        string $departmentName,
        string $pin,
        string $rosterName,
        ?string $storedStartTime,
        ?string $storedEndTime
    ): array {
        $method = new ReflectionMethod($controller, 'resolveRosterScheduleForAttendance');
        $method->setAccessible(true);

        return $method->invoke(
            $controller,
            $logDate,
            $shiftCode,
            $isOff,
            $departmentName,
            $pin,
            $rosterName,
            $storedStartTime,
            $storedEndTime
        );
    }

    private function invokeNormalizePin(AttendanceLogController $controller, string $pin): string
    {
        $method = new ReflectionMethod($controller, 'normalizePin');
        $method->setAccessible(true);

        return $method->invoke($controller, $pin);
    }

    private function invokePinCandidatesForMatch(AttendanceLogController $controller, string $pin): array
    {
        $method = new ReflectionMethod($controller, 'pinCandidatesForMatch');
        $method->setAccessible(true);

        return $method->invoke($controller, $pin);
    }

    private function makeScans(array $scanDates): Collection
    {
        return collect($scanDates)->map(function (string $scanDate) {
            $scan = new stdClass();
            $scan->scan_date = $scanDate;

            return $scan;
        });
    }
}
