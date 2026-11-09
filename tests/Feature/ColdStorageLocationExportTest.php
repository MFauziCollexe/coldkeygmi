<?php

namespace Tests\Feature;

use App\Services\CrossOdoo\ColdStorageLocationExportService;
use App\Services\OdooXmlRpcService;
use Illuminate\Support\Facades\File;
use Mockery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ColdStorageLocationExportTest extends TestCase
{
    public function test_export_service_fetches_only_requested_storage_categories_and_positive_quants(): void
    {
        $categoryNames = [
            'Damage',
            'Dummy',
            'In-Transit',
            'Pickslot',
            'Quarantine',
            'Receipt',
            'Reserve',
            'Return',
            'Spill',
            'Storage',
        ];
        $categories = array_map(
            fn (string $name, int $index): array => ['id' => $index + 1, 'name' => $name],
            $categoryNames,
            array_keys($categoryNames),
        );
        $odoo = Mockery::mock(OdooXmlRpcService::class);
        $odoo->shouldReceive('searchRead')
            ->once()
            ->with('stock.storage.category', ['id', 'name'], null, [['name', 'in', $categoryNames]], ['lang' => 'en_US'])
            ->andReturn($categories);
        $odoo->shouldReceive('searchRead')
            ->once()
            ->with(
                'stock.location',
                ['id', 'name', 'complete_name', 'storage_category_id'],
                null,
                [['storage_category_id', 'in', range(1, 10)]],
                ['lang' => 'en_US'],
            )
            ->andReturn([
                [
                    'id' => 101,
                    'name' => 'CS 1/A01',
                    'complete_name' => 'CS/CS 1/A01',
                    'storage_category_id' => [10, 'Storage'],
                ],
            ]);
        $odoo->shouldReceive('searchRead')
            ->once()
            ->with(
                'stock.quant',
                [
                    'location_id',
                    'product_id',
                    'package_id',
                    'lot_id',
                    'quantity',
                    'reserved_quantity',
                    'product_uom_id',
                    'owner_id',
                    'ns_plate_number',
                    'ns_weight',
                    'reference',
                    'source_document',
                ],
                null,
                [
                    ['location_id', 'in', [101]],
                    ['quantity', '>', 0],
                ],
                ['lang' => 'en_US'],
            )
            ->andReturn([
                [
                    'location_id' => [101, 'CS/CS 1/A01'],
                    'product_id' => [201, '[ITEM-01] Example Item'],
                    'package_id' => [301, 'PACK-01'],
                    'lot_id' => [401, 'LOT-01'],
                    'quantity' => 12.5,
                    'reserved_quantity' => 2,
                    'product_uom_id' => [501, 'Units'],
                    'owner_id' => [701, 'Example Owner'],
                    'ns_plate_number' => 'B 1234 XYZ',
                    'ns_weight' => 125,
                    'reference' => 'Internal Transfer',
                    'source_document' => 'WH/INT/00001',
                ],
            ]);
        $odoo->shouldReceive('executeKw')
            ->once()
            ->with(
                'stock.quant',
                'fields_get',
                [],
                ['attributes' => ['string', 'type']],
            )
            ->andReturn([
                'reference' => ['string' => 'Reference', 'type' => 'char'],
                'source_document' => ['string' => 'Source Document', 'type' => 'char'],
            ]);

        $rows = (new ColdStorageLocationExportService($odoo))->fetchRows();

        $this->assertSame([[
            'Location' => 'CS/CS 1/A01',
            'Product' => '[ITEM-01] Example Item',
            'Package' => 'PACK-01',
            'Lot/Serial Number' => 'LOT-01',
            'Owner' => 'Example Owner',
            'Plate Number' => 'B 1234 XYZ',
            'Reference' => 'Internal Transfer',
            'Source Document' => 'WH/INT/00001',
            'On Hand Quantity' => 12.5,
            'Reserved Quantity' => 2.0,
            'Available Quantity' => 10.5,
            'Qty Kilogram' => 125.0,
            'Unit' => 'Units',
        ]], $rows);
    }

    public function test_export_command_writes_a_dated_excel_file(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cold-storage-export-'.uniqid('', true);
        $exportService = Mockery::mock(ColdStorageLocationExportService::class);
        $exportService->shouldReceive('fetchRows')
            ->once()
            ->andReturn([[
                'Location' => 'CS/CS 1/A01',
                'Product' => '[ITEM-01] Example Item',
                'Package' => '',
                'Lot/Serial Number' => 'LOT-01',
                'Owner' => 'Example Owner',
                'Plate Number' => 'B 1234 XYZ',
                'Reference' => 'Internal Transfer',
                'Source Document' => 'WH/INT/00001',
                'On Hand Quantity' => 12.5,
                'Reserved Quantity' => 2,
                'Available Quantity' => 10.5,
                'Qty Kilogram' => 125,
                'Unit' => 'Units',
            ]]);
        $this->app->instance(ColdStorageLocationExportService::class, $exportService);

        try {
            $this->artisan('cold-storage:export-locations', [
                '--date' => '2026-11-09',
                '--directory' => $directory,
            ])->assertExitCode(0);

            $path = $directory.DIRECTORY_SEPARATOR.'Odoo_Locations_2026-11-09.xlsx';
            $this->assertFileExists($path);
            $sheet = IOFactory::load($path)->getActiveSheet();
            $this->assertSame('Location', $sheet->getCell('A1')->getValue());
            $this->assertSame('Unit', $sheet->getCell('M1')->getValue());
            $this->assertSame('CS/CS 1/A01', $sheet->getCell('A2')->getValue());
            $this->assertSame(12.5, $sheet->getCell('I2')->getValue());
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
