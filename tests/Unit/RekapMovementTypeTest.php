<?php

namespace Tests\Unit;

use App\Http\Controllers\CrossOdoo\RekapInboundController;
use App\Http\Controllers\CrossOdoo\RekapOutboundController;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class RekapMovementTypeTest extends TestCase
{
    #[DataProvider('regularMovementTypes')]
    public function test_recap_keeps_only_regular_inbound_and_outbound(string $controllerClass, string $label, string $expectedDirection): void
    {
        $controller = new $controllerClass;
        $method = new ReflectionMethod($controller, 'movementDirection');
        $method->setAccessible(true);

        $this->assertSame(
            $expectedDirection,
            $method->invoke($controller, ['picking_type_id' => [1, $label]])
        );
    }

    public static function regularMovementTypes(): array
    {
        return [
            'inbound receipts' => [RekapInboundController::class, 'Receipts', 'in'],
            'prefixed inbound receipts' => [RekapInboundController::class, 'WH: Receipts', 'in'],
            'outbound delivery orders' => [RekapOutboundController::class, 'Delivery Orders', 'out'],
            'prefixed outbound delivery orders' => [RekapOutboundController::class, 'WH: Delivery Orders', 'out'],
        ];
    }

    #[DataProvider('excludedMovementTypes')]
    public function test_recap_excludes_returns_and_other_movement_types(string $controllerClass, string $label): void
    {
        $controller = new $controllerClass;
        $method = new ReflectionMethod($controller, 'movementDirection');
        $method->setAccessible(true);

        $this->assertNull(
            $method->invoke($controller, ['picking_type_id' => [1, $label]])
        );
    }

    public static function excludedMovementTypes(): array
    {
        return [
            'inbound return' => [RekapInboundController::class, 'Return Receipts'],
            'outbound return' => [RekapOutboundController::class, 'Return Receipts'],
            'repack inbound' => [RekapInboundController::class, 'Repack Inbound'],
            'repack outbound' => [RekapOutboundController::class, 'Repack Outbound'],
            'adjustment inbound' => [RekapInboundController::class, 'Adjustment Inbound'],
            'adjustment outbound' => [RekapOutboundController::class, 'Adjustment Outbound'],
            'credit note' => [RekapInboundController::class, 'Credit Note'],
            'other type containing receipts' => [RekapInboundController::class, 'Custom Receipts Adjustment'],
            'other type with inbound locations' => [RekapInboundController::class, 'Internal Transfer'],
        ];
    }

    public function test_recap_uses_actual_line_quantity_before_global_move_fallback(): void
    {
        $controller = new RekapInboundController;
        $method = new ReflectionMethod($controller, 'resolveActualQuantity');
        $method->setAccessible(true);

        $this->assertSame(
            ['quantity' => 3.0, 'source' => 'Aktual mutasi: stock.move.line.quantity', 'available' => true, 'fallback_allocated' => false],
            $method->invoke($controller, ['quantity' => 3, 'move_id' => [5, 'MOVE/5']], [5 => 8.0], [5 => 3.0], false)
        );
        $this->assertSame(
            ['quantity' => 5.0, 'source' => 'Fallback global: sisa stock.move.quantity setelah aktual per baris dikurangkan; dialokasikan satu kali per mutasi', 'available' => true, 'fallback_allocated' => true],
            $method->invoke($controller, ['move_id' => [5, 'MOVE/5']], [5 => 8.0], [5 => 3.0], false)
        );
        $this->assertSame(
            ['quantity' => 0.0, 'source' => 'Fallback global: sisa stock.move.quantity setelah aktual per baris dikurangkan; dialokasikan satu kali per mutasi', 'available' => true, 'fallback_allocated' => false],
            $method->invoke($controller, ['move_id' => [5, 'MOVE/5']], [5 => 8.0], [5 => 3.0], true)
        );
        $this->assertSame(
            ['quantity' => null, 'source' => 'Kuantitas aktual dan fallback global tidak tersedia; kuantitas demand tidak digunakan', 'available' => false, 'fallback_allocated' => false],
            $method->invoke($controller, ['product_uom_qty' => 10], [], [], false)
        );
    }
}
