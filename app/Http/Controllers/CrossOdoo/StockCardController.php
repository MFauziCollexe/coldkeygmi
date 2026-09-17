<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Http\Controllers\Controller;
use App\Services\OdooXmlRpcService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StockCardController extends Controller
{
    private const IN_PATTERNS = ['RECEIPTS', 'REPACK INBOUND', 'ADJUSTMENT INBOUND', 'CREDIT NOTE'];

    private const OUT_PATTERNS = ['DELIVERY ORDERS', 'RETURN RECEIPTS', 'REPACK OUTBOUND', 'ADJUSTMENT OUTBOUND'];

    public function index(Request $request): Response
    {
        [$customers, $products] = $this->fetchCustomersAndProducts();

        $selectedCustomerId = $request->input('customer_id');
        if ($selectedCustomerId !== null && $selectedCustomerId !== '') {
            $selectedCustomerId = (int) $selectedCustomerId;
        } else {
            $selectedCustomerId = $customers[0]['customer_id'] ?? null;
        }

        $selectedCustomerProducts = array_values(array_filter(
            $products,
            fn ($product) => (int) $product['customer_id'] === $selectedCustomerId
        ));

        $requestedProductId = $request->input('product_id');
        $requestedProduct = null;
        foreach ($selectedCustomerProducts as $product) {
            if ((int) $product['product_id'] === (int) $requestedProductId) {
                $requestedProduct = $product;
                break;
            }
        }

        $selectedProduct = $requestedProduct ?? ($selectedCustomerProducts[0] ?? null);
        $selectedProductId = $selectedProduct['product_id'] ?? null;
        $productName = $selectedProduct['product_name'] ?? null;

        $endDate = $request->input('end_date') ?: now()->toDateString();
        $startDate = $request->input('start_date') ?: Carbon::parse($endDate)->subMonth()->toDateString();
        $openingStartDate = '2026-08-17';
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        $openingBalance = 0.0;
        $formattedRows = [];
        $totalRows = 0;

        if ($selectedProductId !== null) {
            $odoo = new OdooXmlRpcService();

            $variantIds = $this->productVariantIds($odoo, (int) $selectedProductId);

            if ($variantIds !== []) {
                $openingBalance = $this->fetchOpeningBalance($odoo, $variantIds, $openingStartDate, $startDate);

                $groups = $this->fetchTransactionGroups($odoo, $variantIds, $startDate, $endDate);
                $groups = $this->sortGroups($groups);

                $running = $openingBalance;
                $allRows = [];
                foreach ($groups as $group) {
                    $running += $group['qty_in'] - $group['qty_out'];
                    $group['saldo'] = $running;
                    $allRows[] = $group;
                }

                $totalRows = count($allRows);

                $offset = ($page - 1) * $perPage;
                $pageRows = array_slice($allRows, $offset, $perPage);

                $formattedRows = array_map(fn ($row) => [
                    'transaction_date' => $row['date'],
                    'lot' => $row['lot'],
                    'trans' => $row['trans'],
                    'expired' => $row['expired'],
                    'qty_in' => (float) $row['qty_in'],
                    'qty_out' => (float) $row['qty_out'],
                    'saldo' => (float) $row['saldo'],
                ], $pageRows);
            }
        }

        $selectedCustomerName = null;
        foreach ($customers as $customer) {
            if ((int) $customer['customer_id'] === $selectedCustomerId) {
                $selectedCustomerName = $customer['customer_name'];
                break;
            }
        }

        $customerName = $selectedCustomerName ?? ($customers[0]['customer_name'] ?? null);

        return Inertia::render('GMISL/CrossOdoo/StockCard/Index', [
            'rows' => $formattedRows,
            'customers' => $customers,
            'products' => $products,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => $selectedProductId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'customerName' => $customerName,
            'productName' => $productName,
            'openingBalance' => $openingBalance,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
        ]);
    }

    /**
     * Ambil daftar customer dan product langsung dari Odoo (via XML-RPC).
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function fetchCustomersAndProducts(): array
    {
        $odoo = new OdooXmlRpcService();

        $templates = $odoo->searchRead(
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

            if (!is_array($customer) || count($customer) < 2) {
                continue;
            }

            $customerId = (int) $customer[0];
            $customerName = (string) $customer[1];

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

        $customers = array_values($customers);
        usort($customers, fn ($a, $b) => strcmp($a['customer_name'], $b['customer_name']));

        usort($products, function ($a, $b) {
            return strcmp(
                $a['customer_name'] . '|' . $a['product_name'],
                $b['customer_name'] . '|' . $b['product_name'],
            );
        });

        return [$customers, $products];
    }

    /**
     * @return array<int, int>
     */
    private function productVariantIds(OdooXmlRpcService $odoo, int $templateId): array
    {
        $variants = $odoo->searchRead(
            'product.product',
            ['id'],
            null,
            [['product_tmpl_id', '=', $templateId]],
        );

        return array_map(fn ($variant) => (int) $variant['id'], $variants);
    }

    /**
     * @param  array<int, int>  $variantIds
     */
    private function fetchOpeningBalance(
        OdooXmlRpcService $odoo,
        array $variantIds,
        ?string $openingStartDate,
        string $startDate,
    ): float
    {
        $domain = [
            ['state', '=', 'done'],
            ['product_id', 'in', $variantIds],
            ['date', '<', $startDate . ' 00:00:00'],
        ];

        if ($openingStartDate !== null) {
            $domain[] = ['date', '>=', $openingStartDate . ' 00:00:00'];
        }

        $lines = $odoo->searchRead(
            'stock.move.line',
            ['quantity', 'picking_type_id'],
            null,
            $domain,
        );

        $balance = 0.0;

        foreach ($lines as $line) {
            $label = $this->pickingTypeLabel($line['picking_type_id'] ?? false);

            if ($label === null) {
                continue;
            }

            if ($this->isInboundLabel($label)) {
                $balance += (float) $line['quantity'];
            } elseif ($this->isOutboundLabel($label)) {
                $balance -= (float) $line['quantity'];
            }
        }

        return $balance;
    }

    /**
     * @param  array<int, int>  $variantIds
     * @return array<int, array<string, mixed>>
     */
    private function fetchTransactionGroups(OdooXmlRpcService $odoo, array $variantIds, string $startDate, string $endDate): array
    {
        $lines = $odoo->searchRead(
            'stock.move.line',
            ['date', 'quantity', 'lot_id', 'reference', 'picking_type_id'],
            null,
            [
                ['state', '=', 'done'],
                ['product_id', 'in', $variantIds],
                ['date', '>=', $startDate . ' 00:00:00'],
                ['date', '<=', $endDate . ' 23:59:59'],
            ],
        );

        if ($lines === []) {
            return [];
        }

        $lotIds = [];
        foreach ($lines as $line) {
            if (is_array($line['lot_id'] ?? null)) {
                $lotIds[(int) $line['lot_id'][0]] = true;
            }
        }

        $expirations = $this->fetchLotExpirations($odoo, array_keys($lotIds));

        $groups = [];

        foreach ($lines as $line) {
            $label = $this->pickingTypeLabel($line['picking_type_id'] ?? false);

            if ($label === null) {
                continue;
            }

            $inbound = $this->isInboundLabel($label);
            $outbound = $this->isOutboundLabel($label);

            if (!$inbound && !$outbound) {
                continue;
            }

            $date = (string) ($line['date'] ?? '');
            $lotRef = $line['lot_id'] ?? false;
            $lot = is_array($lotRef) ? (string) $lotRef[1] : null;
            $lotId = is_array($lotRef) ? (int) $lotRef[0] : null;
            $trans = ($line['reference'] ?? false) !== false ? (string) $line['reference'] : null;
            $expiredRaw = $lotId !== null ? ($expirations[$lotId] ?? null) : null;
            $expired = $expiredRaw !== null ? substr($expiredRaw, 0, 10) : null;

            $key = $date . '|' . ($lot ?? '') . '|' . ($trans ?? '') . '|' . ($expiredRaw ?? '');

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'date' => $date,
                    'lot' => $lot,
                    'trans' => $trans,
                    'expired' => $expired,
                    'expired_raw' => $expiredRaw,
                    'qty_in' => 0.0,
                    'qty_out' => 0.0,
                ];
            }

            if ($inbound) {
                $groups[$key]['qty_in'] += (float) $line['quantity'];
            }

            if ($outbound) {
                $groups[$key]['qty_out'] += (float) $line['quantity'];
            }
        }

        return array_values($groups);
    }

    /**
     * @param  array<int, int>  $lotIds
     * @return array<int, string|null>
     */
    private function fetchLotExpirations(OdooXmlRpcService $odoo, array $lotIds): array
    {
        if ($lotIds === []) {
            return [];
        }

        $lots = $odoo->searchRead(
            'stock.lot',
            ['id', 'expiration_date'],
            null,
            [['id', 'in', $lotIds]],
        );

        $map = [];

        foreach ($lots as $lot) {
            $expiration = $lot['expiration_date'] ?? false;

            $map[(int) $lot['id']] = ($expiration !== false && $expiration !== null && $expiration !== '')
                ? (string) $expiration
                : null;
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $groups
     * @return array<int, array<string, mixed>>
     */
    private function sortGroups(array $groups): array
    {
        usort($groups, function ($a, $b) {
            return $this->compareNullable((string) $a['date'], (string) $b['date'])
                ?: $this->compareNullable($a['trans'], $b['trans'])
                ?: $this->compareNullable($a['expired'], $b['expired']);
        });

        return $groups;
    }

    private function compareNullable(?string $a, ?string $b): int
    {
        if ($a === $b) {
            return 0;
        }

        if ($a === null) {
            return 1;
        }

        if ($b === null) {
            return -1;
        }

        return strcmp($a, $b);
    }

    /**
     * @param  mixed  $pickingType  Hasil many2one Odoo: [id, name] atau false.
     */
    private function pickingTypeLabel($pickingType): ?string
    {
        if (!is_array($pickingType) || !isset($pickingType[1])) {
            return null;
        }

        return strtoupper((string) $pickingType[1]);
    }

    private function isInboundLabel(string $label): bool
    {
        foreach (self::IN_PATTERNS as $pattern) {
            if (str_contains($label, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function isOutboundLabel(string $label): bool
    {
        foreach (self::OUT_PATTERNS as $pattern) {
            if (str_contains($label, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
