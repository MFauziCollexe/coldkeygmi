<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Http\Request;
use App\Http\Controllers\AttendanceLogController;
$request = Request::create('/attendance-log', 'GET', ['date_from' => '2026-07-01', 'date_to' => '2026-07-10', 'per_page' => 2000]);
$controller = new AttendanceLogController();
$response = $controller->index($request);
if (method_exists($response, 'getData')) {
    $data = $response->getData();
} else {
    $data = $response->toArray();
}
$summary = $data['props']['summary'] ?? $data['summary'] ?? null;
if ($summary === null) {
    echo "SUMMARY MISSING\n";
    var_export($data);
    exit(1);
}
print_r($summary['department_attendance_by_department'] ?? 'missing department_attendance_by_department');
print_r($summary['expected_counts'] ?? 'missing expected_counts');
