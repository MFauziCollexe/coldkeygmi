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
$http = $response->toResponse($request);
$content = $http->getContent();
$found = strpos($content, 'department_attendance_by_department');
echo 'found=' . ($found !== false ? 'yes' : 'no') . "\n";
if ($found !== false) {
    $start = strpos($content, 'department_attendance_by_department');
    echo substr($content, max(0, $start-200), 600);
}
