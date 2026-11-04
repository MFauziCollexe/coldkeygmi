<?php

namespace Tests\Unit;

use App\Http\Controllers\RosterController;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class RosterControllerTest extends TestCase
{
    public function test_resolve_default_work_hours_uses_five_hours_on_saturday(): void
    {
        $controller = new RosterController;

        $method = new ReflectionMethod($controller, 'resolveDefaultWorkHours');
        $method->setAccessible(true);

        $hours = $method->invoke($controller, Carbon::parse('2026-05-16'), null);

        $this->assertSame(5, $hours);
    }

    public function test_numeric_saturday_shift_ends_after_five_hours(): void
    {
        $controller = new RosterController;

        $method = new ReflectionMethod($controller, 'resolveRosterShiftTiming');
        $method->setAccessible(true);

        $schedule = $method->invoke(
            $controller,
            '8',
            Carbon::parse('2026-05-16'),
            null,
            '',
            '',
            ''
        );

        $this->assertSame('08:00:00', $schedule['start_time']);
        $this->assertSame('13:00:00', $schedule['end_time']);
        $this->assertSame(5, $schedule['work_hours']);
    }

    public function test_roster_list_prioritizes_new_pending_current_approved_old_pending_then_replaced_approved(): void
    {
        $controller = new RosterController;

        $method = new ReflectionMethod($controller, 'rosterListPriorityOrderSql');
        $method->setAccessible(true);

        $orderSql = $method->invoke($controller, [12, 14], [10, 11]);

        $priorities = [
            "status = 'pending' AND id IN (12,14)",
            "status = 'approved' AND id IN (10,11)",
            "WHEN status = 'pending' THEN 3",
            "WHEN status = 'approved' THEN 4",
        ];
        $previousPosition = -1;

        foreach ($priorities as $priority) {
            $position = strpos($orderSql, $priority);
            $this->assertNotFalse($position);
            $this->assertGreaterThan($previousPosition, $position);
            $previousPosition = $position;
        }

        $emptyOrderSql = $method->invoke($controller, [], []);
        $this->assertStringContainsString('id IN (0)', $emptyOrderSql);
        $this->assertStringNotContainsString('id IN ()', $emptyOrderSql);
    }
}
