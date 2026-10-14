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
}
