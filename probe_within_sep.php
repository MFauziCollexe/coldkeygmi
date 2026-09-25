<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ChecklistEntryController;

$c = new ChecklistEntryController();
$rm = new ReflectionMethod($c, 'withinDateRange');
$rm->setAccessible(true);

$entry = [
    'form' => [
        'template'             => 'apar_smoke_detector_fire_alarm',
        'submitted_months'     => ['sep'],
        'approved_months'      => [],
        'monthly_check_dates'  => ['sep' => '24 September 2026'],
    ],
];

$cases = [
    'bln-sep-penuh' => ['start' => '2026-09-01', 'end' => '2026-09-30'],
    'bln-sep-hari24' => ['start' => '2026-09-24', 'end' => '2026-09-30'],
    'bln-sep-awal'  => ['start' => '2026-09-01', 'end' => '2026-09-01'],
];

foreach ($cases as $label => $r) {
    $res = $rm->invoke($c, $entry, $r['start'], $r['end']);
    echo str_pad($label, 16) . ' => ' . var_export($res, true) . PHP_EOL;
}
