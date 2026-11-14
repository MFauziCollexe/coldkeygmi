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
            $method->invoke($controller, ['picking_type_id' => [1, $label]], [])
        );
    }

    public static function regularMovementTypes(): array
    {
        return [
            'inbound receipts' => [RekapInboundController::class, 'Receipts', 'in'],
            'outbound delivery orders' => [RekapOutboundController::class, 'Delivery Orders', 'out'],
        ];
    }

    #[DataProvider('excludedMovementTypes')]
    public function test_recap_excludes_returns_and_other_movement_types(string $controllerClass, string $label): void
    {
        $controller = new $controllerClass;
        $method = new ReflectionMethod($controller, 'movementDirection');
        $method->setAccessible(true);

        $this->assertNull(
            $method->invoke($controller, ['picking_type_id' => [1, $label]], [])
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
        ];
    }
}
