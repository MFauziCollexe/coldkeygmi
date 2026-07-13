<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$employees = DB::table('employees as e')
    ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
    ->whereNotNull('e.nik')
    ->where('e.nik', '<>', '')
    ->select('e.nik', 'd.name as department_name')
    ->get();
$counts = [];
foreach ($employees as $row) {
    $name = trim((string) $row->department_name);
    if ($name === '') {
        $name = '(blank)';
    }
    if (!isset($counts[$name])) {
        $counts[$name] = 0;
    }
    $counts[$name]++;
}
print_r($counts);
