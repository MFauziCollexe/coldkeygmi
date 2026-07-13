<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Collection;
use App\Http\Controllers\AttendanceLogController;
$controller = new AttendanceLogController();
$rows = new Collection([
    ['department_name' => 'HSE', 'log_date' => '2026-07-01', 'expected' => 'On Time'],
    ['department_name' => 'HSE', 'log_date' => '2026-07-02', 'expected' => 'On Time'],
]);
$method = new ReflectionMethod($controller, 'buildDepartmentAttendanceSummaries');
$method->setAccessible(true);
$result = $method->invoke($controller, $rows, ['HSE' => 1], '2026-07-01', '2026-07-31');
print_r($result);
