<?php

namespace App\Http\Controllers;

use App\Models\ElectricityHvLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ElectricityHvMeterController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'meter_id' => trim((string) $request->input('meter_id', '')),
            'start_date' => trim((string) $request->input('start_date', '')),
            'end_date' => trim((string) $request->input('end_date', '')),
        ];

        $query = ElectricityHvLog::query()->with('user:id,name');
        $this->applyFilters($query, $filters);

        $logs = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('jam')
            ->paginate(50)
            ->withQueryString();

        $logs->setCollection($this->appendUsage($logs->getCollection()));

        $meterOptions = ElectricityHvLog::query()
            ->select('meter_id')
            ->distinct()
            ->orderBy('meter_id')
            ->pluck('meter_id')
            ->map(fn($meterId) => ['value' => $meterId, 'label' => $meterId])
            ->values()
            ->all();

        return Inertia::render('GMIUM/ResourceMonitoring/Electricity/HvMeter/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'meterOptions' => $meterOptions,
            'trendData' => $this->buildDailyTrend($filters),
            'canEditList' => $this->canEditList($request),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'lbp' => $this->normalizeNumericInput($request->input('lbp')),
            'wbp' => $this->normalizeNumericInput($request->input('wbp')),
            'kvarh' => $this->normalizeNumericInput($request->input('kvarh')),
        ]);

        $validated = $request->validate([
            'meter_id' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'lbp' => ['required', 'numeric', 'min:0'],
            'wbp' => ['required', 'numeric', 'min:0'],
            'kvarh' => ['nullable', 'numeric', 'min:0'],
        ]);

        $total = (float) $validated['lbp'] + (float) $validated['wbp'];

        ElectricityHvLog::create([
            'meter_id' => $validated['meter_id'],
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'] . ':00',
            'lbp' => $validated['lbp'],
            'wbp' => $validated['wbp'],
            'total' => $total,
            'kvarh' => $validated['kvarh'] ?? 0,
            'user_id' => optional($request->user())->id,
        ]);

        return redirect()->back()->with('success', 'Log HV Meter berhasil disimpan.');
    }

    public function update(Request $request, ElectricityHvLog $log)
    {
        if (!$this->canEditList($request)) {
            abort(403, 'Hanya user IT yang bisa edit data list.');
        }

        $request->merge([
            'lbp' => $this->normalizeNumericInput($request->input('lbp')),
            'wbp' => $this->normalizeNumericInput($request->input('wbp')),
            'kvarh' => $this->normalizeNumericInput($request->input('kvarh')),
        ]);

        $validated = $request->validate([
            'meter_id' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'lbp' => ['required', 'numeric', 'min:0'],
            'wbp' => ['required', 'numeric', 'min:0'],
            'kvarh' => ['nullable', 'numeric', 'min:0'],
        ]);

        $log->update([
            'meter_id' => $validated['meter_id'],
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'] . ':00',
            'lbp' => $validated['lbp'],
            'wbp' => $validated['wbp'],
            'total' => (float) $validated['lbp'] + (float) $validated['wbp'],
            'kvarh' => $validated['kvarh'] ?? 0,
            'user_id' => optional($request->user())->id ?: $log->user_id,
        ]);

        return redirect()->back()->with('success', 'Data list HV berhasil diupdate.');
    }

    public function export(Request $request)
    {
        $filters = [
            'meter_id' => trim((string) $request->input('meter_id', '')),
            'start_date' => trim((string) $request->input('start_date', '')),
            'end_date' => trim((string) $request->input('end_date', '')),
        ];

        $query = ElectricityHvLog::query()->with('user:id,name');
        $this->applyFilters($query, $filters);

        $rows = $query
            ->orderBy('meter_id')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get();

        $rows = $this->appendUsage($rows);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Electricity HV');

        $headers = ['Meter ID', 'Tanggal', 'Jam', 'LBP', 'WBP', 'Total', 'kVARh', 'Pemakaian', 'Warning', 'User'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '1', $header);
        }

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNumber}", (string) $row->meter_id);
            $sheet->setCellValue("B{$rowNumber}", (string) optional($row->tanggal)->format('Y-m-d'));
            $sheet->setCellValue("C{$rowNumber}", substr((string) $row->jam, 0, 5));
            $sheet->setCellValue("D{$rowNumber}", (float) $row->lbp);
            $sheet->setCellValue("E{$rowNumber}", (float) $row->wbp);
            $sheet->setCellValue("F{$rowNumber}", (float) $row->total);
            $sheet->setCellValue("G{$rowNumber}", (float) $row->kvarh);
            $sheet->setCellValue("H{$rowNumber}", $row->usage !== null ? (float) $row->usage : '');
            $sheet->setCellValue("I{$rowNumber}", $row->is_warning ? 'Ya' : '');
            $sheet->setCellValue("J{$rowNumber}", (string) optional($row->user)->name);
            $rowNumber++;
        }

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'electricity_hv_logs_' . now()->format('Ymd_His') . '.xlsx';
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
        return $collection->map(function (ElectricityHvLog $row) {
            $previousDayLast = ElectricityHvLog::query()
                ->where('meter_id', $row->meter_id)
                ->whereDate('tanggal', '<', $row->tanggal)
                ->orderByDesc('tanggal')
                ->orderByDesc('jam')
                ->first();

            $previousTotal = $previousDayLast ? (float) $previousDayLast->total : null;
            $currentTotal = (float) $row->total;
            $usage = $previousTotal !== null ? $currentTotal - $previousTotal : null;

            $row->usage = $usage;
            $row->is_warning = $previousTotal !== null && $usage < 0;

            return $row;
        });
    }

    private function buildDailyTrend(array $filters): array
    {
        $query = ElectricityHvLog::query();
        $this->applyFilters($query, $filters);

        $rows = $query
            ->orderBy('meter_id')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get(['meter_id', 'tanggal', 'jam', 'total']);

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
            // last total value per day
            $lastByDate = [];
            foreach ($meterRows as $row) {
                $dateKey = optional($row->tanggal)->format('Y-m-d');
                if (!$dateKey) {
                    continue;
                }
                $lastByDate[$dateKey] = (float) $row->total;
            }

            ksort($lastByDate);
            $points = [];
            $prevTotal = null;
            foreach ($lastByDate as $date => $total) {
                $usage = $prevTotal !== null ? max(0, $total - $prevTotal) : 0;
                $points[] = ['date' => $date, 'usage' => $usage];
                $prevTotal = $total;
            }

            $result[] = [
                'meter_id' => $meter,
                'points' => $points,
            ];
        }

        return $result;
    }

    private function canEditList(Request $request): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        $user->loadMissing('department:id,code');
        return strtoupper((string) optional($user->department)->code) === 'IT';
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
