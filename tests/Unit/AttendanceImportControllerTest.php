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
                        'attendance_date_label' => '20 Okt 2026',
                        'day_name' => 'Selasa',
                        'shift_code' => 'A',
                        'check_in' => '08:00',
                        'check_out' => '17:00',
                        'status' => 'On Time',
                    ],
                ]),
            ],
        ]);

        $response = $method->invoke($controller, $groups, '2026-10-01', '2026-10-31');
        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringStartsWith('PK', $output);
        $this->assertDummyWorkbookHeaders($output);
    }

    public function test_attendance_template_download_uses_flat_dummy_workbook(): void
    {
        $controller = new AttendanceImportController();
        $request = Request::create('/attendance/import/template', 'GET', [
            'month' => 7,
            'year' => 2026,
        ]);
        $request->setUserResolver(fn () => (object) [
            'department' => (object) ['code' => 'IT'],
        ]);

        $response = $controller->template($request);
        $disposition = $response->headers->get('Content-Disposition');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertStringContainsString('template_attendance_dummy_2026_07.xlsx', $disposition);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringStartsWith('PK', $output);
        $this->assertDummyWorkbookHeaders($output);
    }

    private function assertDummyWorkbookHeaders(string $output): void
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'attendance-xlsx-');
        $workbookPath = $temporaryPath . '.xlsx';
        rename($temporaryPath, $workbookPath);

        try {
            file_put_contents($workbookPath, $output);
            $workbook = IOFactory::load($workbookPath);
            $sheet = $workbook->getActiveSheet();

            $this->assertSame([
                'Tanggal', 'PIN', 'Nama', 'Shift', 'Hari', 'Masuk', 'Pulang',
            ], $sheet->rangeToArray('A1:G1', null, true, false)[0]);
            $this->assertNull($sheet->getCell('H1')->getValue());
        } finally {
            @unlink($workbookPath);
        }
    }
}
