<?php

namespace Tests\Unit;

use App\Http\Controllers\ProcurementMasterItemController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class ProcurementMasterItemControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('procurement_master_items');
        Schema::dropIfExists('stock_card_item_types');

        Schema::create('stock_card_item_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
        });

        Schema::create('procurement_master_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('procurement_master_items');
        Schema::dropIfExists('stock_card_item_types');

        parent::tearDown();
    }

    public function test_item_code_generation_uses_highest_existing_suffix_instead_of_latest_record_id(): void
    {
        DB::table('stock_card_item_types')->insert([
            'name' => 'Non-CSM',
            'code' => 'Non-CSM',
        ]);
        DB::table('procurement_master_items')->insert([
            ['item_code' => 'Non-CSM-00001'],
            ['item_code' => 'Non-CSM-00007'],
            ['item_code' => 'Non-CSM-legacy'],
        ]);

        $controller = new ProcurementMasterItemController();
        $method = new ReflectionMethod($controller, 'generateItemCodeForType');
        $method->setAccessible(true);

        $this->assertSame('Non-CSM-00008', $method->invoke($controller, 'Non-CSM'));
    }

    public function test_item_code_generation_skips_an_existing_next_code(): void
    {
        DB::table('stock_card_item_types')->insert([
            'name' => 'Non-CSM',
            'code' => 'Non-CSM',
        ]);
        DB::table('procurement_master_items')->insert([
            ['item_code' => 'Non-CSM-00001'],
            ['item_code' => 'Non-CSM-00002'],
        ]);

        $controller = new ProcurementMasterItemController();
        $method = new ReflectionMethod($controller, 'generateItemCodeForType');
        $method->setAccessible(true);

        $this->assertSame('Non-CSM-00003', $method->invoke($controller, 'Non-CSM'));
    }
}
