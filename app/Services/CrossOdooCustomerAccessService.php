<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;

class CrossOdooCustomerAccessService
{
    public function __construct(private OdooXmlRpcService $odoo)
    {
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    public function fetchCustomersAndProducts(?User $user): array
    {
        $assignedCustomer = null;
        $assignedOdooId = null;

        if ($user?->customer_id !== null) {
            $assignedCustomer = Customer::query()->find($user->customer_id);
            $assignedOdooId = $assignedCustomer
                ? filter_var(trim((string) $assignedCustomer->customers_id_odoo), FILTER_VALIDATE_INT)
                : false;

            abort_unless(
                $assignedCustomer !== null && $assignedCustomer->is_active && is_int($assignedOdooId) && $assignedOdooId > 0,
                403,
            );
        }

        $templates = $this->odoo->searchRead(
            'product.template',
            ['id', 'name', 'default_code', 'x_studio_customer'],
            null,
            [['x_studio_customer', '!=', false]],
            ['lang' => 'en_US'],
        );

        $customers = [];
        $products = [];

        foreach ($templates as $template) {
            $customer = $template['x_studio_customer'] ?? null;
            $customerId = is_array($customer) && isset($customer[0])
                ? filter_var($customer[0], FILTER_VALIDATE_INT)
                : false;

            if (! is_int($customerId) || $customerId <= 0) {
                continue;
            }

            if ($assignedOdooId !== null && $customerId !== $assignedOdooId) {
                continue;
            }

            $customerName = $assignedCustomer?->name ?? (string) ($customer[1] ?? '');
            $customers[$customerId] = [
                'customer_id' => $customerId,
                'customer_name' => $customerName,
            ];

            $products[] = [
                'product_id' => (int) $template['id'],
                'default_code' => ($template['default_code'] ?? false) !== false ? (string) $template['default_code'] : null,
                'product_name' => (string) $template['name'],
                'customer_id' => $customerId,
                'customer_name' => $customerName,
            ];
        }

        if ($assignedCustomer !== null && $assignedOdooId !== null) {
            $customers[$assignedOdooId] = [
                'customer_id' => $assignedOdooId,
                'customer_name' => $assignedCustomer->name,
            ];
        }

        $customers = array_values($customers);
        usort($customers, fn ($a, $b) => strcmp($a['customer_name'], $b['customer_name']));
        usort($products, fn ($a, $b) => strcmp($a['customer_name'].'|'.$a['product_name'], $b['customer_name'].'|'.$b['product_name']));

        return [$customers, $products];
    }

    /**
     * @param  array<int, array<string, mixed>>  $customers
     */
    public static function resolveCustomerId(mixed $requestedId, array $customers): ?int
    {
        if ($requestedId === null || $requestedId === '') {
            return isset($customers[0]['customer_id']) ? (int) $customers[0]['customer_id'] : null;
        }

        $customerId = filter_var($requestedId, FILTER_VALIDATE_INT);
        $allowedIds = array_map('intval', array_column($customers, 'customer_id'));

        abort_unless(is_int($customerId) && in_array($customerId, $allowedIds, true), 403);

        return $customerId;
    }
}