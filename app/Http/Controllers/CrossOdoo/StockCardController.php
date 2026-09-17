<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Http\Controllers\Controller;
use App\Services\OdooXmlRpcService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockCardController extends Controller
{
    private const OPENING_BALANCE_START_DATE = '2026-08-17';

    private const IN_PATTERNS = ['RECEIPTS', 'REPACK INBOUND', 'ADJUSTMENT INBOUND', 'CREDIT NOTE'];

    private const OUT_PATTERNS = ['DELIVERY ORDERS', 'RETURN RECEIPTS', 'REPACK OUTBOUND', 'ADJUSTMENT OUTBOUND'];

    public function index(Request $request): Response
    {
        [$customers, $products] = $this->fetchCustomersAndProducts();

        $selection = $this->resolveSelection($request, $customers, $products);
        $selectedCustomerId = $selection['selectedCustomerId'];
        $selectedProductId = $selection['selectedProductId'];
        $customerName = $selection['customerName'];
        $productName = $selection['productName'];

        $endDate = $request->input('end_date') ?: now()->toDateString();
        $startDate = $request->input('start_date') ?: Carbon::parse($endDate)->subMonth()->toDateString();
        $openingStartDate = $startDate > self::OPENING_BALANCE_START_DATE
            ? self::OPENING_BALANCE_START_DATE
            : null;
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        $openingBalance = 0.0;
        $formattedRows = [];
        $totalRows = 0;
        $totalIn = 0.0;
        $totalOut = 0.0;
        $finalSaldo = 0.0;

        if ($selectedProductId !== null) {
            $odoo = new OdooXmlRpcService;

            $computed = $this->computeAllRows($odoo, (int) $selectedProductId, $openingStartDate, $startDate, $endDate);

            $allRows = $computed['allRows'];
            $openingBalance = $computed['openingBalance'];
            $totalIn = $computed['totalIn'];
            $totalOut = $computed['totalOut'];
            $finalSaldo = $computed['finalSaldo'];
            $totalRows = count($allRows);

            $offset = ($page - 1) * $perPage;
            $pageRows = array_slice($allRows, $offset, $perPage);

            $formattedRows = array_map(fn ($row) => [
                'transaction_date' => $row['date'],
                'lot' => $row['lot'],
                'trans' => $row['trans'],
                'expired' => $row['expired'],
                'qty_in' => $row['qty_in'] !== null ? (float) $row['qty_in'] : null,
                'qty_out' => $row['qty_out'] !== null ? (float) $row['qty_out'] : null,
                'saldo' => (float) $row['saldo'],
            ], $pageRows);
        }

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
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'finalSaldo' => $finalSaldo,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$customers, $products] = $this->fetchCustomersAndProducts();

        $selection = $this->resolveSelection($request, $customers, $products);
        $selectedProductId = $selection['selectedProductId'];

        $endDate = $request->input('end_date') ?: now()->toDateString();
        $startDate = $request->input('start_date') ?: Carbon::parse($endDate)->subMonth()->toDateString();
        $openingStartDate = $startDate > self::OPENING_BALANCE_START_DATE
            ? self::OPENING_BALANCE_START_DATE
            : null;

        $headers = ['TANGGAL', 'LOT', 'TRANSAKSI', 'EXPIRED', 'QTY IN', 'QTY OUT', 'SALDO'];
        $data = [];

        if ($selectedProductId !== null) {
            $odoo = new OdooXmlRpcService;

            $computed = $this->computeAllRows($odoo, (int) $selectedProductId, $openingStartDate, $startDate, $endDate);

            $data[] = [$startDate, '-', 'Saldo Awal', '-', '', '', (float) $computed['openingBalance']];

            foreach ($computed['allRows'] as $row) {
                $data[] = [
                    $row['date'],
                    $row['lot'],
                    $row['trans'],
                    $row['expired'],
                    $row['qty_in'] !== null ? (float) $row['qty_in'] : '',
                    $row['qty_out'] !== null ? (float) $row['qty_out'] : '',
                    (float) $row['saldo'],
                ];
            }
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($data, null, 'A2');

        $safePart = $selection['productName'] ?? 'product';
        $safePart = preg_replace('/[^A-Za-z0-9\-_]+/', '_', (string) $safePart);
        $filename = 'stock_card_'.($safePart !== '' ? $safePart : 'product').'_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $customers
     * @param  array<int, array<string, mixed>>  $products
     * @return array{selectedCustomerId: int|null, selectedProductId: int|null, customerName: string|null, productName: string|null}
     */
    private function resolveSelection(Request $request, array $customers, array $products): array
    {
        $selectedCustomerId = $request->input('customer_id');
        if ($selectedCustomerId !== null && $selectedCustomerId !== '') {
            $selectedCustomerId = (int) $selectedCustomerId;
        } else {
            $selectedCustomerId = $customers[0]['customer_id'] ?? null;
        }

        $selectedCustomerProducts = array_values(array_filter(
            $products,
            fn ($product) => (int) $product['customer_id'] === (int) $selectedCustomerId
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

        $selectedCustomerName = null;
        foreach ($customers as $customer) {
            if ((int) $customer['customer_id'] === (int) $selectedCustomerId) {
                $selectedCustomerName = $customer['customer_name'];
                break;
            }
        }
        $customerName = $selectedCustomerName ?? ($customers[0]['customer_name'] ?? null);

        return [
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => $selectedProductId,
            'customerName' => $customerName,
            'productName' => $productName,
        ];
    }

    /**
     * @return array{allRows: array<int, array<string, mixed>>, openingBalance: float, totalIn: float, totalOut: float, finalSaldo: float}
     */
    private function computeAllRows(OdooXmlRpcService $odoo, int $selectedProductId, ?string $openingStartDate, string $startDate, string $endDate): array
    {
        $openingBalance = 0.0;
        $totalIn = 0.0;
        $totalOut = 0.0;
        $finalSaldo = 0.0;
        $allRows = [];

        $variantIds = $this->productVariantIds($odoo, $selectedProductId);

        if ($variantIds !== []) {
            $openingBalance = $this->fetchOpeningBalance($odoo, $variantIds, $openingStartDate, $startDate);

            $groups = $this->fetchTransactionGroups($odoo, $variantIds, $startDate, $endDate);
            if ($startDate < self::OPENING_BALANCE_START_DATE) {
                $neurusoftGroup = $this->fetchNeurusoftOpeningGroup($odoo, $variantIds, $endDate);
                if ($neurusoftGroup !== null) {
                    $openingBalance += $neurusoftGroup['balance_delta'];
                }
            }
            $groups = $this->sortGroups($groups);

            $running = $openingBalance;
            foreach ($groups as $group) {
                $running += $group['balance_delta'] ?? ($group['qty_in'] - $group['qty_out']);
                $group['saldo'] = $running;
                $allRows[] = $group;
            }

            foreach ($allRows as $allRow) {
                $totalIn += (float) ($allRow['qty_in'] ?? 0);
                $totalOut += (float) ($allRow['qty_out'] ?? 0);
            }
            $finalSaldo = $running;
        }

        return [
            'allRows' => $allRows,
            'openingBalance' => $openingBalance,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'finalSaldo' => $finalSaldo,
        ];
    }

    /**
     * Ambil daftar customer dan product langsung dari Odoo (via XML-RPC).
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function fetchCustomersAndProducts(): array
    {
        $odoo = new OdooXmlRpcService;

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

            if (! is_array($customer) || count($customer) < 2) {
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
                $a['customer_name'].'|'.$a['product_name'],
                $b['customer_name'].'|'.$b['product_name'],
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
    ): float {
        $domain = [
            ['state', '=', 'done'],
            ['product_id', 'in', $variantIds],
            ['date', '<', $startDate.' 00:00:00'],
        ];

        if ($openingStartDate !== null) {
            $domain[] = ['date', '>=', $openingStartDate.' 00:00:00'];
        }

        $lines = $odoo->searchRead(
            'stock.move.line',
            ['quantity', 'picking_type_id', 'location_id', 'location_dest_id'],
            null,
            $domain,
        );

        $locationUsages = $this->fetchLocationUsages($odoo, $lines);

        $balance = 0.0;

        foreach ($lines as $line) {
            $direction = $this->movementDirection($line, $locationUsages);

            if ($direction === 'in') {
                $balance += (float) $line['quantity'];
            } elseif ($direction === 'out') {
                $balance -= (float) $line['quantity'];
            }
        }

        return $balance;
    }

    /**
     * @param  array<int, int>  $variantIds
     * @return array<string, mixed>|null
     */
    private function fetchNeurusoftOpeningGroup(OdooXmlRpcService $odoo, array $variantIds, string $endDate): ?array
    {
        $lines = $odoo->searchRead(
            'stock.move.line',
            ['date', 'quantity', 'reference', 'picking_type_id', 'location_id', 'location_dest_id'],
            null,
            [
                ['state', '=', 'done'],
                ['product_id', 'in', $variantIds],
                ['date', '>=', self::OPENING_BALANCE_START_DATE.' 00:00:00'],
                ['date', '<=', $endDate.' 23:59:59'],
            ],
        );

        $locationUsages = $this->fetchLocationUsages($odoo, $lines);
        $balanceDelta = 0.0;
        $found = false;

        foreach ($lines as $line) {
            $reference = ($line['reference'] ?? false) !== false
                ? strtoupper((string) $line['reference'])
                : '';

            if (! $this->isNeurusoftOpeningMovement($reference)) {
                continue;
            }

            $found = true;
            $direction = $this->movementDirection($line, $locationUsages);
            $quantity = (float) $line['quantity'];

            if ($direction === 'in') {
                $balanceDelta += $quantity;
            } elseif ($direction === 'out') {
                $balanceDelta -= $quantity;
            }
        }

        if (! $found) {
            return null;
        }

        return [
            'date' => self::OPENING_BALANCE_START_DATE,
            'lot' => null,
            'trans' => 'SALDO AWAL NEUROSOFT',
            'expired' => null,
            'qty_in' => null,
            'qty_out' => null,
            'balance_delta' => $balanceDelta,
        ];
    }

    private function isNeurusoftOpeningMovement(string $reference): bool
    {
        return str_contains($reference, 'PRODUCT QUANTITY UPDATED')
            || str_contains($reference, 'UPDATE QTY KILOGRAM')
            || str_contains($reference, 'UPDATE KILOGRAM STOK AWAL');
    }

    /**
     * @param  array<int, int>  $variantIds
     * @return array<int, array<string, mixed>>
     */
    private function fetchTransactionGroups(OdooXmlRpcService $odoo, array $variantIds, string $startDate, string $endDate): array
    {
        $lines = $odoo->searchRead(
            'stock.move.line',
            ['date', 'quantity', 'lot_id', 'reference', 'picking_type_id', 'location_id', 'location_dest_id'],
            null,
            [
                ['state', '=', 'done'],
                ['product_id', 'in', $variantIds],
                ['date', '>=', $startDate.' 00:00:00'],
                ['date', '<=', $endDate.' 23:59:59'],
            ],
        );

        if ($lines === []) {
            return [];
        }

        $locationUsages = $this->fetchLocationUsages($odoo, $lines);

        $lotIds = [];
        foreach ($lines as $line) {
            if (is_array($line['lot_id'] ?? null)) {
                $lotIds[(int) $line['lot_id'][0]] = true;
            }
        }

        $expirations = $this->fetchLotExpirations($odoo, array_keys($lotIds));

        $groups = [];
        foreach ($lines as $line) {
            $date = (string) ($line['date'] ?? '');
            $label = $this->pickingTypeLabel($line['picking_type_id'] ?? false);
            $reference = ($line['reference'] ?? false) !== false
                ? strtoupper((string) $line['reference'])
                : '';
            $isAdjustment = ($label !== null && str_contains($label, 'ADJUSTMENT'))
                || str_contains($reference, 'ADJS')
                || str_contains($reference, 'ADJUSTMENT');

            if (str_contains($reference, 'PRODUCT QUANTITY CONFIRMED')) {
                continue;
            }

            $direction = $this->movementDirection($line, $locationUsages);
            $inbound = $direction === 'in';
            $outbound = $direction === 'out';

            if ($isAdjustment || $this->isNeurusoftOpeningMovement($reference)) {
                continue;
            }

            if (! $inbound && ! $outbound) {
                continue;
            }

            $lotRef = $line['lot_id'] ?? false;
            $lot = is_array($lotRef) ? (string) $lotRef[1] : null;
            $lotId = is_array($lotRef) ? (int) $lotRef[0] : null;
            $trans = ($line['reference'] ?? false) !== false ? (string) $line['reference'] : null;
            $expiredRaw = $lotId !== null ? ($expirations[$lotId] ?? null) : null;
            $expired = $expiredRaw !== null ? substr($expiredRaw, 0, 10) : null;

            $key = $date.'|'.($lot ?? '').'|'.($trans ?? '').'|'.($expiredRaw ?? '');

            if (! isset($groups[$key])) {
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
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, string>
     */
    private function fetchLocationUsages(OdooXmlRpcService $odoo, array $lines): array
    {
        $locationIds = [];

        foreach ($lines as $line) {
            foreach (['location_id', 'location_dest_id'] as $field) {
                $location = $line[$field] ?? false;

                if (is_array($location) && isset($location[0])) {
                    $locationIds[(int) $location[0]] = true;
                }
            }
        }

        if ($locationIds === []) {
            return [];
        }

        $locations = $odoo->searchRead(
            'stock.location',
            ['id', 'usage'],
            null,
            [['id', 'in', array_keys($locationIds)]],
        );

        $usages = [];
        foreach ($locations as $location) {
            $usages[(int) $location['id']] = (string) ($location['usage'] ?? '');
        }

        return $usages;
    }

    /**
     * @param  array<string, mixed>  $line
     * @param  array<int, string>  $locationUsages
     */
    private function movementDirection(array $line, array $locationUsages): ?string
    {
        $source = $line['location_id'] ?? false;
        $destination = $line['location_dest_id'] ?? false;
        $sourceUsage = is_array($source) ? ($locationUsages[(int) ($source[0] ?? 0)] ?? null) : null;
        $destinationUsage = is_array($destination) ? ($locationUsages[(int) ($destination[0] ?? 0)] ?? null) : null;

        if ($sourceUsage !== null && $destinationUsage !== null) {
            if ($sourceUsage !== 'internal' && $destinationUsage === 'internal') {
                return 'in';
            }

            if ($sourceUsage === 'internal' && $destinationUsage !== 'internal') {
                return 'out';
            }

            return null;
        }

        $label = $this->pickingTypeLabel($line['picking_type_id'] ?? false);

        if ($label !== null && $this->isInboundLabel($label)) {
            return 'in';
        }

        if ($label !== null && $this->isOutboundLabel($label)) {
            return 'out';
        }

        return null;
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
        if (! is_array($pickingType) || ! isset($pickingType[1])) {
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
