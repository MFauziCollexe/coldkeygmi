<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = app(App\Support\AccessRuleService::class);
$userId = 11;
$deptId = 19;
echo 'isAdmin=' . ($service->isAdmin($userId) ? '1' : '0') . PHP_EOL;
echo 'isManager=' . ($service->isManager($userId) ? '1' : '0') . PHP_EOL;
echo 'visibleApprove=' . json_encode($service->visibleDepartmentIds($userId, 'overtime', 'approve')) . PHP_EOL;
echo 'canApproveDept=' . ($service->canAccessDepartment($userId, 'overtime', 'approve', $deptId) ? '1' : '0') . PHP_EOL;
