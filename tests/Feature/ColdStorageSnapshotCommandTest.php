<?php

namespace Tests\Feature;

use App\Services\CrossOdoo\ColdStorageDataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ColdStorageSnapshotCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (require base_path('database/migrations/2026_10_29_000001_create_cold_storage_snapshot_tables.php'))->up();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('cold_storage_snapshot_customers');
        Schema::dropIfExists('cold_storage_snapshot_storages');
        Schema::dropIfExists('cold_storage_snapshot_batches');

        parent::tearDown();
    }

    public function test_snapshot_command_replaces_same_day_snapshot_without_duplicates(): void
    {
        $service = Mockery::mock(ColdStorageDataService::class);
        $service->shouldReceive('fetch')
            ->twice()
            ->andReturn(
                $this->snapshotData(4, 2, 1),
                $this->snapshotData(5, 3, 2),
            );
        $this->instance(ColdStorageDataService::class, $service);

        $this->artisan('cold-storage:snapshot', ['--date' => '2026-10-29'])
            ->assertExitCode(0);
        $this->artisan('cold-storage:snapshot', ['--date' => '2026-10-29'])
            ->assertExitCode(0);

        $this->assertSame(1, DB::table('cold_storage_snapshot_batches')->count());
        $this->assertSame(1, DB::table('cold_storage_snapshot_storages')->count());
        $this->assertSame(1, DB::table('cold_storage_snapshot_customers')->count());
        $this->assertSame(5, (int) DB::table('cold_storage_snapshot_storages')->value('capacity'));
        $this->assertSame(2, (int) DB::table('cold_storage_snapshot_customers')->value('occupied_slots'));
    }

    public function test_snapshot_command_does_not_save_when_odoo_cold_storage_root_is_missing(): void
    {
        $service = Mockery::mock(ColdStorageDataService::class);
        $service->shouldReceive('fetch')->once()->andReturn([
            'rootFound' => false,
            'storages' => [],
            'customerOccupancy' => [],
            'updatedAt' => '29/10/2026 00:00',
        ]);
        $this->instance(ColdStorageDataService::class, $service);

        try {
            $this->artisan('cold-storage:snapshot', ['--date' => '2026-10-29']);
            $this->fail('The snapshot command should fail when the Odoo root is missing.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('root location "CS" was not found', $exception->getMessage());
        }

        $this->assertSame(0, DB::table('cold_storage_snapshot_batches')->count());
    }

    private function snapshotData(int $capacity, int $used, int $occupiedByCustomer): array
    {
        return [
            'rootFound' => true,
            'storages' => [[
                'id' => 100,
                'name' => 'CS 1',
                'completeName' => 'CS/CS 1',
                'capacity' => $capacity,
                'used' => $used,
                'available' => $capacity - $used,
                'occupancy' => round($used / $capacity * 100, 1),
                'status' => 'low',
                'customerOccupancy' => [[
                    'id' => 200,
                    'customer' => 'Example Customer',
                    'occupiedSlots' => $occupiedByCustomer,
                    'percentage' => round($occupiedByCustomer / $capacity * 100, 1),
                ]],
            ]],
            'customerOccupancy' => [],
            'updatedAt' => '29/10/2026 00:00',
        ];
    }
}
