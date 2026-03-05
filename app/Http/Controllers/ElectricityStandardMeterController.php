<?php

namespace App\Http\Controllers;

use App\Models\ElectricityStandardLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ElectricityStandardMeterController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'meter_id' => trim((string) $request->input('meter_id', '')),
            'start_date' => trim((string) $request->input('start_date', '')),
            'end_date' => trim((string) $request->input('end_date', '')),
        ];

        $query = ElectricityStandardLog::query()->with('user:id,name');
        $this->applyFilters($query, $filters);

        $logs = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('jam')
            ->paginate(50)
            ->withQueryString();

        $logs->setCollection($this->appendUsage($logs->getCollection()));

        $meterOptions = ElectricityStandardLog::query()
            ->select('meter_id')
            ->distinct()
            ->orderBy('meter_id')
            ->pluck('meter_id')
            ->map(fn($meterId) => ['value' => $meterId, 'label' => $meterId])
            ->values()
            ->all();

        return Inertia::render('GMIUM/ResourceMonitoring/Electricity/StandardMeter/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'meterOptions' => $meterOptions,
            'trendData' => $this->buildUsageTrend($filters),
            'monthlyRecap' => $this->buildMonthlyRecap($filters),
            'canEditList' => $this->canEditList($request),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'kwh' => $this->normalizeNumericInput($request->input('kwh')),
        ]);

        $validated = $request->validate([
            'meter_id' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'kwh' => ['required', 'numeric', 'min:0'],
        ]);

        ElectricityStandardLog::create([
            'meter_id' => $validated['meter_id'],
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'] . ':00',
            'kwh' => $validated['kwh'],
            'user_id' => optional($request->user())->id,
        ]);

        return redirect()->back()->with('success', 'Log Standard Meter berhasil disimpan.');
    }

    public function update(Request $request, ElectricityStandardLog $log)
    {
        if (!$this->canEditList($request)) {
            abort(403, 'Hanya user IT yang bisa edit data list.');
        }

        $request->merge([
            'kwh' => $this->normalizeNumericInput($request->input('kwh')),
        ]);

        $validated = $request->validate([
            'meter_id' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'kwh' => ['required', 'numeric', 'min:0'],
        ]);

        $newJam = $validated['jam'] . ':00';

        $log->update([
            'meter_id' => $validated['meter_id'],
            'tanggal' => $validated['tanggal'],
            'jam' => $newJam,
            'kwh' => $validated['kwh'],
            'user_id' => optional($request->user())->id ?: $log->user_id,
        ]);

        return redirect()->back()->with('success', 'Data list berhasil diupdate.');
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
        return $collection->map(function (ElectricityStandardLog $row) {
            $previous = ElectricityStandardLog::query()
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

            $previousKwh = $previous ? (float) $previous->kwh : null;
            $currentKwh = (float) $row->kwh;
            $usage = $previousKwh !== null ? $currentKwh - $previousKwh : null;

            $row->previous_kwh = $previousKwh;
            $row->usage = $usage;
            $row->is_warning = $previousKwh !== null && $usage < 0;

            return $row;
        });
    }

    private function buildUsageTrend(array $filters): array
    {
        $query = ElectricityStandardLog::query();
        $this->applyFilters($query, $filters);

        $rows = $query
            ->orderBy('meter_id')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get(['meter_id', 'tanggal', 'jam', 'kwh']);

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
            $points = [];
            $previousKwh = null;
            foreach ($meterRows as $row) {
                $currentKwh = (float) $row->kwh;
                $usage = $previousKwh !== null ? max(0, $currentKwh - $previousKwh) : 0;
                $points[] = [
                    'date' => optional($row->tanggal)->format('Y-m-d'),
                    'usage' => $usage,
                ];
                $previousKwh = $currentKwh;
            }

            $result[] = [
                'meter_id' => $meter,
                'points' => $points,
            ];
        }

        return $result;
    }

    private function buildMonthlyRecap(array $filters): array
    {
        $query = ElectricityStandardLog::query();
        $this->applyFilters($query, $filters);

        $rows = $query
            ->orderBy('meter_id')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get(['meter_id', 'tanggal', 'jam', 'kwh']);

        $meterMonthUsage = [];
        $prevByMeter = [];

        foreach ($rows as $row) {
            $meter = (string) $row->meter_id;
            $monthKey = optional($row->tanggal)->format('Y-m');
            if (!$monthKey) {
                continue;
            }

            $current = (float) $row->kwh;
            $usage = 0;
            if (array_key_exists($meter, $prevByMeter)) {
                $usage = max(0, $current - $prevByMeter[$meter]);
            }
            $prevByMeter[$meter] = $current;

            $compound = $meter . '|' . $monthKey;
            if (!isset($meterMonthUsage[$compound])) {
                $meterMonthUsage[$compound] = [
                    'meter_id' => $meter,
                    'month' => $monthKey,
                    'total_usage' => 0,
                ];
            }
            $meterMonthUsage[$compound]['total_usage'] += $usage;
        }

        return array_values($meterMonthUsage);
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
                // 1.234,56 -> 1234.56
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                // 1,234.56 -> 1234.56
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($hasComma) {
            // 123,45 -> 123.45  OR 1,234 -> 1234
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
