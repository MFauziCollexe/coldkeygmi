<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Http\Controllers\Controller;
use App\Models\ColdStorageSnapshotBatch;
use App\Services\CrossOdoo\ColdStorageDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ColdStorageController extends Controller
{
    public function index(Request $request, ColdStorageDataService $coldStorageData): Response
    {
        $data = $coldStorageData->fetch();
        $dateTo = now('Asia/Jakarta')->toDateString();
        $dateFrom = now('Asia/Jakarta')->subDays(364)->toDateString();
        $batches = ColdStorageSnapshotBatch::query()
            ->with('storages')
            ->whereBetween('snapshot_date', [$dateFrom, $dateTo])
            ->orderBy('snapshot_date')
            ->get();

        $historySnapshots = $batches->map(function (ColdStorageSnapshotBatch $batch): array {
            $capacity = (int) $batch->storages->sum('capacity');
            $used = (int) $batch->storages->sum('used');

            return [
                'date' => $batch->snapshot_date->toDateString(),
                'capturedAt' => $batch->captured_at?->format('Y-m-d H:i'),
                'capacity' => $capacity,
                'used' => $used,
                'available' => max(0, $capacity - $used),
                'occupancy' => $capacity > 0 ? round($used / $capacity * 100, 1) : 0,
            ];
        })->values();

        return Inertia::render('GMISL/CrossOdoo/ColdStorage/Index', [
            'storages' => $data['storages'],
            'customerOccupancy' => $data['customerOccupancy'],
            'updatedAt' => $data['updatedAt'],
            'history' => [
                'startDate' => $dateFrom,
                'endDate' => $dateTo,
                'snapshots' => $historySnapshots,
            ],
        ]);
    }
}
