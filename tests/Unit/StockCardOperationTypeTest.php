<?php

namespace Tests\Unit;

use App\Http\Controllers\CrossOdoo\StockCardController;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class StockCardOperationTypeTest extends TestCase
{
    #[DataProvider('operationTypesWithoutDisplayValue')]
    public function test_empty_operation_types_are_not_displayable(?string $operationType): void
    {
        $controller = new StockCardController;
        $method = new ReflectionMethod($controller, 'hasDisplayableOperationType');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, $operationType));
    }

    public static function operationTypesWithoutDisplayValue(): array
    {
        return [
            'null' => [null],
            'blank' => [''],
            'whitespace' => ['   '],
            'dash' => ['-'],
            'padded dash' => [' - '],
        ];
    }

    public function test_named_operation_type_remains_displayable(): void
    {
        $controller = new StockCardController;
        $method = new ReflectionMethod($controller, 'hasDisplayableOperationType');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, 'RECEIPTS'));
    }

    public function test_stock_card_displays_only_regular_inbound_and_outbound_types(): void
    {
        $controller = new StockCardController;
        $method = new ReflectionMethod($controller, 'isRegularInOutLabel');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, 'RECEIPTS'));
        $this->assertTrue($method->invoke($controller, 'DELIVERY ORDERS'));
        $this->assertFalse($method->invoke($controller, 'RETURN RECEIPTS'));
        $this->assertFalse($method->invoke($controller, 'REPACK INBOUND'));
        $this->assertFalse($method->invoke($controller, 'ADJUSTMENT OUTBOUND'));
        $this->assertFalse($method->invoke($controller, 'CREDIT NOTE'));
    }

    public function test_stock_card_direction_uses_location_usages_for_return_movements(): void
    {
        $controller = new StockCardController;
        $method = new ReflectionMethod($controller, 'movementDirection');
        $method->setAccessible(true);

        $this->assertSame('in', $method->invoke($controller, [
            'picking_type_id' => [1, 'Return Receipts'],
            'location_id' => [10, 'Customers'],
            'location_dest_id' => [20, 'GMI/Output'],
        ], [10 => 'customer', 20 => 'internal']));
        $this->assertSame('out', $method->invoke($controller, [
            'picking_type_id' => [2, 'Delivery Orders'],
            'location_id' => [20, 'GMI/Output'],
            'location_dest_id' => [10, 'Customers'],
        ], [10 => 'customer', 20 => 'internal']));
    }

    public function test_stock_card_accumulates_transaction_details_by_source_location(): void
    {
        $controller = new StockCardController;
        $method = new ReflectionMethod($controller, 'addLocationDetail');
        $method->setAccessible(true);
        $group = ['location_details' => []];

        $args = [&$group, ['location_id' => [10, 'GMI/WH/Stock']], false, 8.0, 80.5, 'Units', null];
        $method->invokeArgs($controller, $args);
        $args = [&$group, ['location_id' => [10, 'GMI/WH/Stock']], false, 10.0, 111.02, 'Units', null];
        $method->invokeArgs($controller, $args);
        $args = [&$group, ['location_id' => [11, 'GMI/WH/Cold Storage']], false, 5.0, 50.0, 'Units', null];
        $method->invokeArgs($controller, $args);

        $this->assertCount(2, $group['location_details']);
        $this->assertSame('GMI/WH/Stock', $group['location_details'][10]['location']);
        $this->assertSame(18.0, $group['location_details'][10]['qty_out']);
        $this->assertEqualsWithDelta(191.52, $group['location_details'][10]['qty_out_kg'], 0.000001);
        $this->assertSame('GMI/WH/Cold Storage', $group['location_details'][11]['location']);
        $this->assertSame(5.0, $group['location_details'][11]['qty_out']);
        $this->assertSame(50.0, $group['location_details'][11]['qty_out_kg']);
    }

    public function test_stock_card_uses_latest_prior_cold_storage_move_for_same_lot(): void
    {
        $controller = new StockCardController;
        $method = new ReflectionMethod($controller, 'previousColdStorageSources');
        $method->setAccessible(true);

        $sources = $method->invoke($controller, [
            'id' => 500,
            'date' => '2026-11-07 10:00:00',
            'product_id' => [12, 'CC012'],
            'lot_id' => [34, 'LOT-34'],
            'location_id' => [20, 'GMI/Output'],
        ], [
            'moves' => [
                '12|34|20' => [
                    ['id' => 200, 'location_id' => 30, 'location' => 'GMI/Pick', 'destination_id' => 20, 'destination' => 'GMI/Output', 'destination_location' => 'GMI/Output', 'source_package' => null, 'destination_package' => 'PK-OUT', 'lot_serial_number' => 'LOT-34', 'uom' => 'Units', 'expiration_date' => '2027-05-01', 'quantity' => 18.0, 'weight' => 191.52, 'reference' => 'PICK-200', 'date' => '2026-11-07 09:00:00'],
                    ['id' => 600, 'location_id' => 3, 'location' => 'CS/CS 3/C01', 'destination_id' => 20, 'destination' => 'GMI/Output', 'destination_location' => 'GMI/Output', 'source_package' => null, 'destination_package' => null, 'lot_serial_number' => 'LOT-34', 'uom' => 'Units', 'expiration_date' => '2027-05-01', 'quantity' => 2.0, 'weight' => 21.2, 'reference' => 'PICK-600', 'date' => '2026-11-07 11:00:00'],
                ],
                '12|34|30' => [
                    ['id' => 150, 'location_id' => 2, 'location' => 'CS 02/B01', 'destination_id' => 30, 'destination' => 'GMI/Pick', 'destination_location' => 'GMI/Pick', 'source_package' => 'PK-CS', 'destination_package' => 'PK-PICK', 'lot_serial_number' => 'LOT-34', 'uom' => 'Units', 'expiration_date' => '2027-05-01', 'quantity' => 18.0, 'weight' => 191.52, 'reference' => 'PICK-150', 'date' => '2026-11-07 08:00:00'],
                    ['id' => 100, 'location_id' => 1, 'location' => 'CS/CS 1/A01', 'destination_id' => 30, 'destination' => 'GMI/Pick', 'destination_location' => 'GMI/Pick', 'source_package' => null, 'destination_package' => null, 'lot_serial_number' => 'LOT-34', 'uom' => 'Units', 'expiration_date' => '2027-05-01', 'quantity' => 20.0, 'weight' => 212.8, 'reference' => 'PICK-100', 'date' => '2026-11-06 09:00:00'],
                ],
            ],
            'locations' => [
                2 => ['name' => 'CS 02/B01', 'usage' => 'internal'],
                30 => ['name' => 'GMI/Pick', 'usage' => 'internal'],
            ],
        ]);

        $this->assertSame([
            [
                'id' => 150,
                'location_id' => 2,
                'location' => 'CS 02/B01',
                'date' => '2026-11-07 08:00:00',
                'route' => 'CS 02/B01 → GMI/Pick → GMI/Output',
                'destination' => 'GMI/Pick',
                'source_package' => 'PK-CS',
                'destination_package' => 'PK-PICK',
                'source_package_id' => null,
                'destination_package_id' => null,
                'lot_serial_number' => 'LOT-34',
                'uom' => 'Units',
                'expiration_date' => '2027-05-01',
                'quantity' => 18.0,
                'weight' => 191.52,
                'reference' => 'PICK-150',
            ],
        ], $sources);
    }

    public function test_stock_card_matches_each_delivery_line_to_its_package_and_quantity(): void
    {
        $controller = new StockCardController;
        $method = new ReflectionMethod($controller, 'previousColdStorageSources');
        $method->setAccessible(true);
        $previousMoves = [
            'moves' => [
                '12|34|20' => [
                    [
                        'id' => 501, 'location_id' => 101, 'location' => 'CS 05/E1010102',
                        'destination_id' => 20, 'destination' => 'GMI/Output',
                        'source_package' => 'MF004 - 04', 'destination_package' => 'MF004 - 04',
                        'source_package_id' => 1004, 'destination_package_id' => 1004,
                        'lot_serial_number' => 'SZLU-9758967', 'uom' => 'Carton',
                        'expiration_date' => '2028-08-04', 'quantity' => 5.0, 'weight' => 53.2,
                        'reference' => 'PICK02652', 'date' => '2026-11-12 14:46:11',
                    ],
                    [
                        'id' => 502, 'location_id' => 102, 'location' => 'CS 05/E1110604',
                        'destination_id' => 20, 'destination' => 'GMI/Output',
                        'source_package' => 'MF004 - 02', 'destination_package' => 'MF004 - 02',
                        'source_package_id' => 1002, 'destination_package_id' => 1002,
                        'lot_serial_number' => 'SZLU-9758967', 'uom' => 'Carton',
                        'expiration_date' => '2028-08-04', 'quantity' => 10.0, 'weight' => 106.4,
                        'reference' => 'PICK02652', 'date' => '2026-11-12 14:46:11',
                    ],
                ],
            ],
            'locations' => [],
            'lot_expirations' => [],
        ];

        $fiveCartons = $method->invoke($controller, [
            'id' => 601, 'date' => '2026-11-13 01:59:15',
            'product_id' => [12, 'CC005'], 'lot_id' => [34, 'SZLU-9758967'],
            'location_id' => [20, 'GMI/Output'], 'package_id' => [1004, 'MF004 - 04'],
            'quantity' => 5,
        ], $previousMoves);
        $tenCartons = $method->invoke($controller, [
            'id' => 602, 'date' => '2026-11-13 01:59:15',
            'product_id' => [12, 'CC005'], 'lot_id' => [34, 'SZLU-9758967'],
            'location_id' => [20, 'GMI/Output'], 'package_id' => [1002, 'MF004 - 02'],
            'quantity' => 10,
        ], $previousMoves);

        $this->assertSame('CS 05/E1010102', $fiveCartons[0]['location']);
        $this->assertSame(5.0, $fiveCartons[0]['quantity']);
        $this->assertSame('MF004 - 04', $fiveCartons[0]['destination_package']);
        $this->assertSame('CS 05/E1110604', $tenCartons[0]['location']);
        $this->assertSame(10.0, $tenCartons[0]['quantity']);
        $this->assertSame('MF004 - 02', $tenCartons[0]['destination_package']);
        $this->assertSame(15.0, $fiveCartons[0]['quantity'] + $tenCartons[0]['quantity']);
    }

}
