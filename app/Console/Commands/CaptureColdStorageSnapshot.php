<?php

namespace App\Console\Commands;

use App\Models\ColdStorageSnapshotBatch;
use App\Services\CrossOdoo\ColdStorageDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class CaptureColdStorageSnapshot extends Command
{
    protected $signature = 'cold-storage:snapshot {--date= : Snapshot date in YYYY-MM-DD format}';

    protected $description = 'Capture and store the daily Cold Storage state from Odoo';

    public function handle(ColdStorageDataService $coldStorageData): int
    {
        $dateOption = $this->option('date');
        $snapshotDate = $dateOption
            ? Validator::make(['date' => $dateOption], ['date' => 'required|date_format:Y-m-d'])->validate()['date']
            : now('Asia/Jakarta')->toDateString();
        $data = $coldStorageData->fetch();

        if (! $data['rootFound']) {
            throw new RuntimeException('Cold Storage root location "CS" was not found in Odoo; no snapshot was saved.');
        }

        DB::transaction(function () use ($snapshotDate, $data): void {
            $batch = ColdStorageSnapshotBatch::query()
                ->whereDate('snapshot_date', $snapshotDate)
                ->first();

            if ($batch === null) {
                $batch = new ColdStorageSnapshotBatch;
                $batch->snapshot_date = $snapshotDate;
            }

            $batch->captured_at = now('Asia/Jakarta');
            $batch->save();
            $batch->storages()->delete();

            foreach ($data['storages'] as $storageData) {
                $storage = $batch->storages()->create([
                    'odoo_location_id' => $storageData['id'],
                    'name' => $storageData['name'],
                    'complete_name' => $storageData['completeName'],
                    'capacity' => $storageData['capacity'],
                    'used' => $storageData['used'],
                    'available' => $storageData['available'],
                    'occupancy' => $storageData['occupancy'],
                    'status' => $storageData['status'],
                ]);

                foreach ($storageData['customerOccupancy'] as $customer) {
                    $storage->customers()->create([
                        'odoo_owner_id' => $customer['id'],
                        'name' => $customer['customer'],
                        'occupied_slots' => $customer['occupiedSlots'],
                        'percentage' => $customer['percentage'],
                    ]);
                }
            }
        });

        $this->info(sprintf(
            'Cold Storage snapshot for %s saved (%d storage units).',
            $snapshotDate,
            count($data['storages'])
        ));

        return self::SUCCESS;
    }
}
