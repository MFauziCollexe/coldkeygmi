<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$total = \App\Models\WaterLog::query()
    ->where('meter_id', 'OFFICE-01')
    ->whereBetween('tanggal', ['2026-02-01', '2026-02-29'])
    ->count();

$am = \App\Models\WaterLog::query()
    ->where('meter_id', 'OFFICE-01')
    ->whereBetween('tanggal', ['2026-02-01', '2026-02-29'])
    ->where('jam', '06:00:00')
    ->count();

$pm = \App\Models\WaterLog::query()
    ->where('meter_id', 'OFFICE-01')
    ->whereBetween('tanggal', ['2026-02-01', '2026-02-29'])
    ->where('jam', '17:00:00')
    ->count();

echo "total={$total};06={$am};17={$pm}";
