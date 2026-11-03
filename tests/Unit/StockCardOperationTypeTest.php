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
}
