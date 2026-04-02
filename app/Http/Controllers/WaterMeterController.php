<?php

namespace App\Http\Controllers;

use App\Models\WaterLog;
use App\Support\AccessRuleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WaterMeterController extends Controller
{
    private const ACCESS_MODULE = 'water_meter';

    protected function accessRules(): AccessRuleService
    {
        return app(AccessRuleService::class);
    }

    public function index(Request $request)
    {
        $filters = [
            'meter_id' => trim((string) $request->input('meter_id', '')),
            'start_date' => trim((string) $request->input('start_date', '')),
            'end_date' => trim((string) $request->input('end_date', '')),
        ];

        $query = WaterLog::query()->with('user:id,name');
        $this->applyFilters($query, $filters);

        $logs = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('jam')
            ->paginate(50)
            ->withQueryString();

        $logs->setCollection($this->appendUsage($logs->getCollection()));

        $trendData = $this->buildDailyTrend($filters);
        $avgUsage = $this->buildAverageUsage($trendData);

        $meterOptions = WaterLog::query()
            ->select('meter_id')
            ->distinct()
            ->orderBy('meter_id')
            ->pluck('meter_id')
            ->map(fn($meterId) => ['value' => $meterId, 'label' => $meterId])
            ->values()
            ->all();

        return Inertia::render('GMIUM/ResourceMonitoring/WaterMeter/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'meterOptions' => $meterOptions,
            'trendData' => $trendData,
            'averageUsage' => $avgUsage,
            'canEditList' => $this->canEditList($request),
            'canExportLogs' => $this->canExportLogs($request),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'meter_value' => $this->normalizeNumericInput($request->input('meter_value')),
        ]);

        $validated = $request->validate([
            'meter_id' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'meter_value' => ['required', 'numeric', 'min:0'],
        ]);

        WaterLog::create([
            'meter_id' => $validated['meter_id'],
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'] . ':00',
            'meter_value' => $validated['meter_value'],
            'user_id' => optional($request->user())->id,
        ]);

        return redirect()->back()->with('success', 'Log water meter berhasil disimpan.');
    }

    public function update(Request $request, WaterLog $log)
    {
        if (!$this->canEditList($request)) {
            abort(403, 'Hanya user IT yang bisa edit data list.');
        }

        $request->merge([
            'meter_value' => $this->normalizeNumericInput($request->input('meter_value')),
        ]);

        $validated = $request->validate([
            'meter_id' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'meter_value' => ['required', 'numeric', 'min:0'],
        ]);

        $newJam = $validated['jam'] . ':00';

        $log->update([
            'meter_id' => $validated['meter_id'],
            'tanggal' => $validated['tanggal'],
            'jam' => $newJam,
            'meter_value' => $validated['meter_value'],
            'user_id' => optional($request->user())->id ?: $log->user_id,
        ]);

        return redirect()->back()->with('success', 'Data list water meter berhasil diupdate.');
    }

    public function export(Request $request)
    {
        if (!$this->canExportLogs($request)) {
            abort(403, 'Anda tidak memiliki akses export data water meter.');
        }

        $filters = [
            'meter_id' => trim((string) $request->input('meter_id', '')),
            'start_date' => trim((string) $request->input('start_date', '')),
            'end_date' => trim((string) $request->input('end_date', '')),
        ];

        $query = WaterLog::query()->with('user:id,name');
        $this->applyFilters($query, $filters);

        $rows = $query
            ->orderBy('meter_id')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get();

        $rows = $this->appendUsage($rows);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Water Meter');

        $headers = ['Meter ID', 'Tanggal', 'Jam', 'Meter Value', 'Selisih', 'Warning', 'User'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '1', $header);
        }

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNumber}", (string) $row->meter_id);
            $sheet->setCellValue("B{$rowNumber}", (string) optional($row->tanggal)->format('Y-m-d'));
            $sheet->setCellValue("C{$rowNumber}", substr((string) $row->jam, 0, 5));
            $sheet->setCellValue("D{$rowNumber}", (float) $row->meter_value);
            $sheet->setCellValue("E{$rowNumber}", $row->usage !== null ? (float) $row->usage : '');
            $sheet->setCellValue("F{$rowNumber}", $row->is_warning ? 'Ya' : '');
            $sheet->setCellValue("G{$rowNumber}", (string) optional($row->user)->name);
            $rowNumber++;
        }

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'water_meter_logs_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['meter_id'] !== '') {
            $query->where('meter_id', $filters['meter_id']);
        }
        if ($filters['start_date'] !== '') {
            $query->whereDate('tanggal', '>=', $filters['start_date']);
        }
        if ($filters['end_date'] !== '') {
            $query->whereDate('tanggal', '<=', $filters['end_date']);
        }
    }

    private function appendUsage($collection)
    {
        return $collection->map(function (WaterLog $row) {
            $previous = WaterLog::query()
                ->where('meter_id', $row->meter_id)
                ->where(function ($query) use ($row) {
                    $query->whereDate('tanggal', '<', $row->tanggal)
                        ->orWhere(function ($inner) use ($row) {
                            $inner->whereDate('tanggal', '=', $row->tanggal)
                                ->whereTime('jam', '<', $row->jam);
                        });
                })
                ->orderByDesc('tanggal')
                ->orderByDesc('jam')
                ->first();

            $previousValue = $previous ? (float) $previous->meter_value : null;
            $currentValue = (float) $row->meter_value;
            $usage = $previousValue !== null ? $currentValue - $previousValue : null;

            $row->previous_value = $previousValue;
            $row->usage = $usage;
            $row->is_warning = $previousValue !== null && $usage < 0;

            return $row;
        });
    }

    private function buildDailyTrend(array $filters): array
    {
        $query = WaterLog::query();
        $this->applyFilters($query, $filters);

        $rows = $query
            ->orderBy('meter_id')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get(['meter_id', 'tanggal', 'jam', 'meter_value']);

        $byMeter = [];
        foreach ($rows as $row) {
            $meter = (string) $row->meter_id;
            if (!isset($byMeter[$meter])) {
                $byMeter[$meter] = [];
            }
            $byMeter[$meter][] = $row;
        }

        $result = [];
        foreach ($byMeter as $meter => $meterRows) {
            $prevValue = null;
            $dailyUsage = [];

            foreach ($meterRows as $row) {
                $current = (float) $row->meter_value;
                $usage = $prevValue !== null ? max(0, $current - $prevValue) : 0;
                $dateKey = optional($row->tanggal)->format('Y-m-d');
                if (!$dateKey) {
                    $prevValue = $current;
                    continue;
                }

                if (!isset($dailyUsage[$dateKey])) {
                    $dailyUsage[$dateKey] = 0;
                }
                $dailyUsage[$dateKey] += $usage;
                $prevValue = $current;
            }

            ksort($dailyUsage);
            $points = [];
            foreach ($dailyUsage as $date => $usage) {
                $points[] = ['date' => $date, 'usage' => $usage];
            }

            $result[] = [
                'meter_id' => $meter,
                'points' => $points,
            ];
        }

        return $result;
    }

    private function buildAverageUsage(array $trendData): float
    {
        $sum = 0.0;
        $count = 0;
        foreach ($trendData as $series) {
            foreach (($series['points'] ?? []) as $point) {
                $sum += (float) ($point['usage'] ?? 0);
                $count++;
            }
        }

        return $count > 0 ? round($sum / $count, 2) : 0.0;
    }

    private function canEditList(Request $request): bool
    {
        return $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'edit_list');
    }

    private function canExportLogs(Request $request): bool
    {
        return $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'export_logs');
    }

    private function normalizeNumericInput($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_numeric($value)) {
            return $value;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return $raw;
        }

        $raw = str_replace(' ', '', $raw);
        $hasComma = str_contains($raw, ',');
        $hasDot = str_contains($raw, '.');

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($raw, ',');
            $lastDot = strrpos($raw, '.');
            if ($lastComma > $lastDot) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($hasComma) {
            $parts = explode(',', $raw);
            if (count($parts) > 1 && strlen((string) end($parts)) === 3) {
                $raw = str_replace(',', '', $raw);
            } else {
                $raw = str_replace(',', '.', $raw);
            }
        }

        return $raw;
    }

}
