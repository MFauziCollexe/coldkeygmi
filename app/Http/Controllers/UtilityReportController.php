<?php

namespace App\Http\Controllers;

use App\Models\ElectricityHvLog;
use App\Models\ElectricityStandardLog;
use App\Models\WaterLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UtilityReportController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $selectedMonth = (int) $request->input('month', $today->month);
        $selectedYear = (int) $request->input('year', $today->year);

        if ($selectedMonth < 1 || $selectedMonth > 12) {
            $selectedMonth = $today->month;
        }
        if ($selectedYear < 2000 || $selectedYear > 2100) {
            $selectedYear = $today->year;
        }

        $periodDate = Carbon::create($selectedYear, $selectedMonth, 1);
        $monthStart = $periodDate->copy()->startOfMonth();
        $monthEnd = $periodDate->copy()->endOfMonth();

        $electricityRows = $this->buildElectricityUsageRows();
        $waterRows = $this->buildWaterUsageRows();

        $electricityToday = $this->sumUsageByDate($electricityRows, $today->toDateString());
        $waterToday = $this->sumUsageByDate($waterRows, $today->toDateString());
        $electricityMonth = $this->sumUsageBetweenDates($electricityRows, $monthStart->toDateString(), $monthEnd->toDateString());
        $waterMonth = $this->sumUsageBetweenDates($waterRows, $monthStart->toDateString(), $monthEnd->toDateString());

        $dashboardTrend = $this->buildDashboardTrend($electricityRows, $waterRows, $monthStart, $monthEnd);
        $electricityByMeter = $this->groupUsageByKey($electricityRows, 'meter_id');
        $electricityByLocation = $this->groupElectricityByLocation($electricityRows);
        $electricityByMonth = $this->groupUsageByMonth($electricityRows);
        $waterByMeter = $this->groupUsageByKey($waterRows, 'meter_id');
        $waterByMonth = $this->groupUsageByMonth($waterRows);

        $electricityByLocationMonth = $this->groupElectricityByLocation($electricityRows, $monthStart->toDateString(), $monthEnd->toDateString());
        $comparison = [
            'pln_vs_office' => [
                'pln' => (float) ($electricityByLocationMonth['PLN'] ?? 0),
                'office' => (float) ($electricityByLocationMonth['Office'] ?? 0),
            ],
            'electricity_vs_water' => [
                'electricity' => (float) $electricityMonth,
                'water' => (float) $waterMonth,
            ],
        ];

        return Inertia::render('GMIUM/UtilityReport/Index', [
            'summary' => [
                'today' => [
                    'electricity' => round($electricityToday, 2),
                    'water' => round($waterToday, 2),
                ],
                'month' => [
                    'electricity' => round($electricityMonth, 2),
                    'water' => round($waterMonth, 2),
                    'total' => round($electricityMonth + $waterMonth, 2),
                ],
                'period' => [
                    'start' => $monthStart->toDateString(),
                    'end' => $monthEnd->toDateString(),
                ],
            ],
            'filters' => [
                'month' => $selectedMonth,
                'year' => $selectedYear,
            ],
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->yearOptions(),
            'dashboardTrend' => $dashboardTrend,
            'electricityRecap' => [
                'by_meter' => $this->normalizeGroupResult($electricityByMeter),
                'by_location' => $this->normalizeGroupResult($electricityByLocation),
                'by_month' => $this->normalizeGroupResult($electricityByMonth),
            ],
            'waterRecap' => [
                'by_meter' => $this->normalizeGroupResult($waterByMeter),
                'by_month' => $this->normalizeGroupResult($waterByMonth),
            ],
            'comparison' => $comparison,
        ]);
    }

    private function buildElectricityUsageRows(): array
    {
        $rows = [];

        $stdRows = ElectricityStandardLog::query()
            ->orderBy('meter_id')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get(['meter_id', 'tanggal', 'jam', 'kwh']);

        $prevStd = [];
        foreach ($stdRows as $row) {
            $meter = (string) $row->meter_id;
            $current = (float) $row->kwh;
            $prev = $prevStd[$meter] ?? null;
            $usage = $prev !== null ? max(0, $current - $prev) : 0;
            $prevStd[$meter] = $current;

            $rows[] = [
                'meter_id' => $meter,
                'date' => optional($row->tanggal)->format('Y-m-d'),
                'usage' => $usage,
                'location' => $this->resolveLocation($meter),
            ];
        }

        $hvRows = ElectricityHvLog::query()
            ->orderBy('meter_id')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get(['meter_id', 'tanggal', 'jam', 'total']);

        $dailyLast = [];
        foreach ($hvRows as $row) {
            $meter = (string) $row->meter_id;
            $date = optional($row->tanggal)->format('Y-m-d');
            if (!$date) {
                continue;
            }
            $dailyLast[$meter][$date] = (float) $row->total;
        }

        foreach ($dailyLast as $meter => $dateTotals) {
            ksort($dateTotals);
            $prev = null;
            foreach ($dateTotals as $date => $total) {
                $usage = $prev !== null ? max(0, $total - $prev) : 0;
                $prev = $total;

                $rows[] = [
                    'meter_id' => $meter,
                    'date' => $date,
                    'usage' => $usage,
                    'location' => $this->resolveLocation($meter),
                ];
            }
        }

        return array_values(array_filter($rows, fn($row) => !empty($row['date'])));
    }

    private function buildWaterUsageRows(): array
    {
        $rows = WaterLog::query()
            ->orderBy('meter_id')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get(['meter_id', 'tanggal', 'jam', 'meter_value']);

        $result = [];
        $prevByMeter = [];
        foreach ($rows as $row) {
            $meter = (string) $row->meter_id;
            $current = (float) $row->meter_value;
            $prev = $prevByMeter[$meter] ?? null;
            $usage = $prev !== null ? max(0, $current - $prev) : 0;
            $prevByMeter[$meter] = $current;

            $date = optional($row->tanggal)->format('Y-m-d');
            if (!$date) {
                continue;
            }

            $result[] = [
                'meter_id' => $meter,
                'date' => $date,
                'usage' => $usage,
            ];
        }

        return $result;
    }

    private function resolveLocation(string $meterId): string
    {
        $upper = strtoupper($meterId);
        if (str_contains($upper, 'OFFICE') || str_contains($upper, 'CRMI')) {
            return 'Office';
        }
        if (str_contains($upper, 'GARDU') || str_contains($upper, 'PLN')) {
            return 'PLN';
        }
        return 'Lainnya';
    }

    private function sumUsageByDate(array $rows, string $date): float
    {
        $sum = 0;
        foreach ($rows as $row) {
            if (($row['date'] ?? null) === $date) {
                $sum += (float) ($row['usage'] ?? 0);
            }
        }
        return $sum;
    }

    private function sumUsageBetweenDates(array $rows, string $startDate, string $endDate): float
    {
        $sum = 0;
        foreach ($rows as $row) {
            $date = (string) ($row['date'] ?? '');
            if ($date >= $startDate && $date <= $endDate) {
                $sum += (float) ($row['usage'] ?? 0);
            }
        }
        return $sum;
    }

    private function groupUsageByKey(array $rows, string $key, ?string $startDate = null, ?string $endDate = null): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $date = (string) ($row['date'] ?? '');
            if ($startDate && $date < $startDate) {
                continue;
            }
            if ($endDate && $date > $endDate) {
                continue;
            }

            $groupKey = (string) ($row[$key] ?? '-');
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = 0;
            }
            $grouped[$groupKey] += (float) ($row['usage'] ?? 0);
        }

        arsort($grouped);
        return $grouped;
    }

    private function groupElectricityByLocation(array $rows, ?string $startDate = null, ?string $endDate = null): array
    {
        $grouped = [
            'PLN' => 0,
            'Office' => 0,
            'Lainnya' => 0,
        ];

        foreach ($rows as $row) {
            $date = (string) ($row['date'] ?? '');
            if ($startDate && $date < $startDate) {
                continue;
            }
            if ($endDate && $date > $endDate) {
                continue;
            }

            $location = (string) ($row['location'] ?? 'Lainnya');
            if (!isset($grouped[$location])) {
                $grouped[$location] = 0;
            }
            $grouped[$location] += (float) ($row['usage'] ?? 0);
        }

        arsort($grouped);
        return $grouped;
    }

    private function groupUsageByMonth(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $date = (string) ($row['date'] ?? '');
            if ($date === '') {
                continue;
            }
            $month = substr($date, 0, 7);
            if (!isset($grouped[$month])) {
                $grouped[$month] = 0;
            }
            $grouped[$month] += (float) ($row['usage'] ?? 0);
        }

        ksort($grouped);
        return $grouped;
    }

    private function normalizeGroupResult(array $grouped): array
    {
        $result = [];
        foreach ($grouped as $label => $value) {
            $result[] = [
                'label' => $label,
                'value' => round((float) $value, 2),
            ];
        }
        return $result;
    }

    private function buildDashboardTrend(array $electricityRows, array $waterRows, Carbon $monthStart, Carbon $monthEnd): array
    {
        $byDateElectric = [];
        foreach ($electricityRows as $row) {
            $date = (string) ($row['date'] ?? '');
            if ($date < $monthStart->toDateString() || $date > $monthEnd->toDateString()) {
                continue;
            }
            if (!isset($byDateElectric[$date])) {
                $byDateElectric[$date] = 0;
            }
            $byDateElectric[$date] += (float) ($row['usage'] ?? 0);
        }

        $byDateWater = [];
        foreach ($waterRows as $row) {
            $date = (string) ($row['date'] ?? '');
            if ($date < $monthStart->toDateString() || $date > $monthEnd->toDateString()) {
                continue;
            }
            if (!isset($byDateWater[$date])) {
                $byDateWater[$date] = 0;
            }
            $byDateWater[$date] += (float) ($row['usage'] ?? 0);
        }

        $labels = [];
        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $labels[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'electricity' => array_map(fn($date) => round((float) ($byDateElectric[$date] ?? 0), 2), $labels),
            'water' => array_map(fn($date) => round((float) ($byDateWater[$date] ?? 0), 2), $labels),
        ];
    }

    private function monthOptions(): array
    {
        return [
            ['value' => 1, 'label' => 'Januari'],
            ['value' => 2, 'label' => 'Februari'],
            ['value' => 3, 'label' => 'Maret'],
            ['value' => 4, 'label' => 'April'],
            ['value' => 5, 'label' => 'Mei'],
            ['value' => 6, 'label' => 'Juni'],
            ['value' => 7, 'label' => 'Juli'],
            ['value' => 8, 'label' => 'Agustus'],
            ['value' => 9, 'label' => 'September'],
            ['value' => 10, 'label' => 'Oktober'],
            ['value' => 11, 'label' => 'November'],
            ['value' => 12, 'label' => 'Desember'],
        ];
    }

    private function yearOptions(): array
    {
        $current = (int) now()->year;
        $years = [];
        for ($year = $current - 3; $year <= $current + 3; $year++) {
            $years[] = ['value' => $year, 'label' => (string) $year];
        }
        return $years;
    }
}
