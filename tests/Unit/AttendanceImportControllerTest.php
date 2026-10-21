<?php

namespace Tests\Unit;

use App\Http\Controllers\AttendanceImportController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function test_attendance_export_streams_an_xlsx_file(): void
    {
        $controller = new AttendanceImportController();
        $method = new ReflectionMethod($controller, 'exportAttendanceExcel');
        $method->setAccessible(true);
        $groups = collect([
            [
                'pin' => '000123',
                'name' => 'Test Employee',
                'department_name' => 'Operations',
                'supervisor_name' => 'Supervisor',
                'total_records' => 1,
                'rows' => collect([
                    [
                        'attendance_date' => '2026-10-01',
                        'day_name' => 'Selasa',
                        'shift_code' => 'A',
                        'check_in' => '08:00',
                        'check_out' => '17:00',
                        'status' => 'On Time',
                    ],
                ]),
            ],
        ]);

        $response = $method->invoke($controller, $groups, '2026-10-01', '2026-10-02');
        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringStartsWith('PK', $output);

        $sheet = $this->loadWorkbookFromOutput($output)->getActiveSheet();
        $this->assertSame('No', $sheet->getCell('A1')->getValue());
        $this->assertSame('Nama', $sheet->getCell('B1')->getValue());
        $this->assertEquals(1, $sheet->getCell('C1')->getValue());
        $this->assertSame('Kam', $sheet->getCell('C2')->getValue());
        $this->assertEquals(2, $sheet->getCell('D1')->getValue());
        $this->assertSame('Jum', $sheet->getCell('D2')->getValue());
        $this->assertSame('000123', $sheet->getCell('A3')->getValue());
        $this->assertSame('Test Employee', $sheet->getCell('B3')->getValue());
        $this->assertSame("08:00\n17:00\nOn Time", $sheet->getCell('C3')->getValue());
    }

    private function loadWorkbookFromOutput(string $output)
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'attendance-xlsx-');
        $workbookPath = $temporaryPath . '.xlsx';
        rename($temporaryPath, $workbookPath);

        try {
            file_put_contents($workbookPath, $output);
            return IOFactory::load($workbookPath);
        } finally {
            @unlink($workbookPath);
        }
    }
}
