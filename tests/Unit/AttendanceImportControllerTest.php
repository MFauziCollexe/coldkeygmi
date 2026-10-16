<?php

namespace Tests\Unit;

use App\Http\Controllers\AttendanceImportController;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class AttendanceImportControllerTest extends TestCase
{
    public function test_only_it_department_can_view_attendance_import_history(): void
    {
        $controller = new AttendanceImportController();
        $method = new ReflectionMethod($controller, 'isItDepartment');
        $method->setAccessible(true);

        foreach ([
            ['IT', true],
            ['it', true],
            ['HRD', false],
            [null, false],
        ] as [$departmentCode, $expected]) {
            $request = Request::create('/attendance');
            $request->setUserResolver(fn () => (object) [
                'department' => $departmentCode === null ? null : (object) ['code' => $departmentCode],
            ]);

            $this->assertSame($expected, $method->invoke($controller, $request));
        }
    }

    public function test_flat_export_date_parser_keeps_july_days_in_one_month(): void
    {
        $controller = new AttendanceImportController();
        $detectDateOrder = new ReflectionMethod($controller, 'detectFlatDateOrder');
        $detectDateOrder->setAccessible(true);
        $parseDate = new ReflectionMethod($controller, 'parseFlatDateCell');
        $parseDate->setAccessible(true);

        foreach ([
            ['mdy', fn (int $day) => sprintf('7/%d/2025', $day)],
            ['dmy', fn (int $day) => sprintf('%d/07/2025', $day)],
        ] as [$expectedOrder, $makeDate]) {
            $rows = array_map(fn (int $day) => [$makeDate($day)], range(1, 31));
            $dateOrder = $detectDateOrder->invoke($controller, $rows, 0, 0);
            $this->assertSame($expectedOrder, $dateOrder);

            $dates = array_map(
                fn (array $row) => $parseDate->invoke($controller, $row[0], $dateOrder),
                $rows,
            );

            $this->assertSame('2025-07-01', $dates[0]);
            $this->assertSame('2025-07-31', $dates[30]);
            $this->assertCount(1, array_unique(array_map(fn (string $date) => substr($date, 0, 7), $dates)));
        }
    }
}
