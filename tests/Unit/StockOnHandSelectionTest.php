<?php

namespace Tests\Unit;

use App\Http\Controllers\CrossOdoo\StockOnHandController;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class StockOnHandSelectionTest extends TestCase
{
    public function test_all_item_selection_includes_every_product_for_the_selected_customer(): void
    {
        $controller = new StockOnHandController;
        $method = new ReflectionMethod($controller, 'resolveSelection');
        $method->setAccessible(true);

        $selection = $method->invoke(
            $controller,
            Request::create('/', 'GET', ['customer_id' => 10, 'product_id' => [0]]),
            [['customer_id' => 10, 'customer_name' => 'Customer A']],
            [
                ['product_id' => 101, 'customer_id' => 10, 'product_name' => 'Product A'],
                ['product_id' => 102, 'customer_id' => 10, 'product_name' => 'Product B'],
                ['product_id' => 201, 'customer_id' => 20, 'product_name' => 'Other customer product'],
            ],
        );

        $this->assertSame([], $selection['selectedProductIds']);
        $this->assertNull($selection['selectedProductId']);
        $this->assertSame('All Item', $selection['productName']);
    }

    public function test_single_product_selection_remains_scoped_to_the_customer(): void
    {
        $controller = new StockOnHandController;
        $method = new ReflectionMethod($controller, 'resolveSelection');
        $method->setAccessible(true);

        $selection = $method->invoke(
            $controller,
            Request::create('/', 'GET', ['customer_id' => 10, 'product_id' => [102]]),
            [['customer_id' => 10, 'customer_name' => 'Customer A']],
            [
                ['product_id' => 101, 'customer_id' => 10, 'product_name' => 'Product A'],
                ['product_id' => 102, 'customer_id' => 10, 'product_name' => 'Product B'],
                ['product_id' => 201, 'customer_id' => 20, 'product_name' => 'Other customer product'],
            ],
        );

        $this->assertSame([102], $selection['selectedProductIds']);
        $this->assertSame(102, $selection['selectedProductId']);
        $this->assertSame('Product B', $selection['productName']);
    }
}
