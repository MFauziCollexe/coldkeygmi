<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\CrossOdooCustomerAccessService;
use App\Services\OdooXmlRpcService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CrossOdooCustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_assignment_scopes_odoo_options_while_unassigned_users_keep_all_customers(): void
    {
        $assignedCustomer = Customer::create([
            'name' => 'CITRA RASA MAKMUR INVESTINDO, PT',
            'customers_id_odoo' => '501',
            'is_active' => true,
        ]);
        $otherCustomer = Customer::create([
            'name' => 'Other Customer',
            'customers_id_odoo' => '502',
            'is_active' => true,
        ]);
        $inactiveCustomer = Customer::create([
            'name' => 'Inactive Customer',
            'customers_id_odoo' => '503',
            'is_active' => false,
        ]);

        $templates = [
            ['id' => 11, 'name' => 'Product A', 'default_code' => 'A', 'x_studio_customer' => [501, 'Odoo Name A']],
            ['id' => 12, 'name' => 'Product B', 'default_code' => 'B', 'x_studio_customer' => [502, 'Odoo Name B']],
            ['id' => 13, 'name' => 'Product C', 'default_code' => 'C', 'x_studio_customer' => [503, 'Odoo Name C']],
            ['id' => 14, 'name' => 'Unmapped Product', 'default_code' => 'D', 'x_studio_customer' => [504, 'Unmapped']],
            ['id' => 15, 'name' => 'Malformed Product', 'default_code' => 'E', 'x_studio_customer' => false],
        ];

        $odoo = Mockery::mock(OdooXmlRpcService::class);
        $odoo->shouldReceive('searchRead')->once()->andReturn($templates);
        $assignedOptions = (new CrossOdooCustomerAccessService($odoo))->fetchCustomersAndProducts(
            $this->createUser(['customer_id' => $assignedCustomer->id]),
        );

        $this->assertSame([501], array_column($assignedOptions[0], 'customer_id'));
        $this->assertSame('CITRA RASA MAKMUR INVESTINDO, PT', $assignedOptions[0][0]['customer_name']);
        $this->assertSame([11], array_column($assignedOptions[1], 'product_id'));

        $odoo = Mockery::mock(OdooXmlRpcService::class);
        $odoo->shouldReceive('searchRead')->once()->andReturn($templates);
        $unassignedOptions = (new CrossOdooCustomerAccessService($odoo))->fetchCustomersAndProducts(
            $this->createUser(),
        );

        $this->assertSame([501, 502, 503, 504], array_column($unassignedOptions[0], 'customer_id'));
        $this->assertSame([11, 12, 13, 14], array_column($unassignedOptions[1], 'product_id'));
        $this->assertNotContains((int) $otherCustomer->customers_id_odoo, array_column($assignedOptions[0], 'customer_id'));
        $this->assertNotContains((int) $inactiveCustomer->customers_id_odoo, array_column($assignedOptions[0], 'customer_id'));
    }

    public function test_admin_can_assign_customer_to_user_and_invalid_customer_ids_are_rejected(): void
    {
        $customer = Customer::create([
            'name' => 'CITRA RASA MAKMUR INVESTINDO, PT',
            'customers_id_odoo' => '501',
            'is_active' => true,
        ]);
        $adminUser = $this->createUser([], ['control.users']);
        $this->actingAs($adminUser);

        $response = $this->post(route('control.users.store'), [
            'name' => 'Customer Login',
            'account' => 'customer-login',
            'email' => 'customer-login@example.test',
            'password' => 'password',
            'status' => 'active',
            'customer_id' => (string) $customer->id,
            'is_admin' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'account' => 'customer-login',
            'customer_id' => $customer->id,
        ]);

        $this->post(route('control.users.store'), [
            'name' => 'Invalid Customer Login',
            'account' => 'invalid-customer-login',
            'email' => 'invalid-customer-login@example.test',
            'password' => 'password',
            'status' => 'active',
            'customer_id' => 999999,
            'is_admin' => false,
        ])->assertSessionHasErrors('customer_id');
    }

    public function test_assigned_user_cannot_request_another_customer_on_reports_or_exports(): void
    {
        $customer = Customer::create([
            'name' => 'Assigned Customer',
            'customers_id_odoo' => '501',
            'is_active' => true,
        ]);
        $user = $this->createUser(['customer_id' => $customer->id], [
            'gmisl.cross_odoo.stock_card',
            'gmisl.cross_odoo.rekap_inbound',
            'gmisl.cross_odoo.rekap_outbound',
        ]);

        $odoo = Mockery::mock(OdooXmlRpcService::class);
        $odoo->shouldReceive('searchRead')
            ->times(8)
            ->with('product.template', ['id', 'name', 'default_code', 'x_studio_customer'], null, [['x_studio_customer', '!=', false]], ['lang' => 'en_US'])
            ->andReturn([
                ['id' => 11, 'name' => 'Product A', 'default_code' => 'A', 'x_studio_customer' => [501, 'Assigned']],
                ['id' => 12, 'name' => 'Product B', 'default_code' => 'B', 'x_studio_customer' => [502, 'Other']],
            ]);
        $this->app->instance(OdooXmlRpcService::class, $odoo);
        $this->actingAs($user);

        $routes = [
            ['stock-card.cross-odoo.index', 'stock-card.cross-odoo.export'],
            ['cross-odoo.soh.index', 'cross-odoo.soh.export'],
            ['cross-odoo.rekap-inbound.index', 'cross-odoo.rekap-inbound.export'],
            ['cross-odoo.rekap-outbound.index', 'cross-odoo.rekap-outbound.export'],
        ];

        foreach ($routes as [$indexRoute, $exportRoute]) {
            $this->get(route($indexRoute, ['customer_id' => 502]))->assertForbidden();
            $this->get(route($exportRoute, ['customer_id' => 502]))->assertForbidden();
        }
    }
}