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

    private function makeScans(array $scanDates): Collection
    {
        return collect($scanDates)->map(function (string $scanDate) {
            $scan = new stdClass();
            $scan->scan_date = $scanDate;

            return $scan;
        });
    }
}
