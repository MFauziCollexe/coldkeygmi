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
        $controller = new RosterController();

        $method = new ReflectionMethod($controller, 'resolveDefaultWorkHours');
        $method->setAccessible(true);

        $hours = $method->invoke($controller, Carbon::parse('2026-05-16'), null);

        $this->assertSame(5, $hours);
    }

    public function test_numeric_saturday_shift_ends_after_five_hours(): void
    {
        $controller = new RosterController();

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
}
