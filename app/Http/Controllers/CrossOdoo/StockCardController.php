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

    private const EXCLUDE_PATTERNS = ['INTERNAL TRANSFER', 'PICKING', 'PUTAWAY'];

    public function index(Request $request): Response
    {
        [$customers, $products] = $this->fetchCustomersAndProducts();

        $selection = $this->resolveSelection($request, $customers, $products);
        $selectedCustomerId = $selection['selectedCustomerId'];
        $selectedProductId = $selection['selectedProductId'];
        $customerName = $selection['customerName'];
        $productName = $selection['productName'];

        $period = $request->input('period');
        if (! is_string($period) || ! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = now()->format('Y-m');
        }
        $startDate = $period.'-01';
        $endDate = Carbon::parse($period.'-01')->endOfMonth()->toDateString();
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
        $openingBalanceKg = 0.0;
        $totalInKg = 0.0;
        $totalOutKg = 0.0;
        $finalSaldoKg = 0.0;
        $allItems = $selectedProductId === null;

        if ($selectedCustomerId !== null) {
            $odoo = new OdooXmlRpcService;

            $result = $this->buildStockRows($odoo, $selectedProductId, (int) $selectedCustomerId, $products, $openingStartDate, $startDate, $endDate);

            $allRows = $result['rows'];
            $openingBalance = $result['openingBalance'];
            $openingBalanceKg = $result['openingBalanceKg'];
            $totalIn = $result['totalIn'];
            $totalOut = $result['totalOut'];
            $finalSaldo = $result['finalSaldo'];
            $totalInKg = $result['totalInKg'];
            $totalOutKg = $result['totalOutKg'];
            $finalSaldoKg = $result['finalSaldoKg'];
            $totalRows = count($allRows);

            if ($allItems) {
                $pageRows = $allRows;
            } else {
                $offset = ($page - 1) * $perPage;
                $pageRows = array_slice($allRows, $offset, $perPage);
            }

            $formattedRows = array_map(fn ($row) => [
                'transaction_date' => $row['date'] ?? null,
                'operation_type' => $row['operation_type'] ?? null,
                'trans' => $row['trans'] ?? null,
                'source_document' => $row['source_document'] ?? null,
                'nopol' => $row['nopol'] ?? null,
                'kd_barang' => $row['kd_barang'] ?? null,
                'nm_barang' => $row['nm_barang'] ?? null,
                'qty_in' => array_key_exists('qty_in', $row) && $row['qty_in'] !== null ? (float) $row['qty_in'] : null,
                'qty_out' => array_key_exists('qty_out', $row) && $row['qty_out'] !== null ? (float) $row['qty_out'] : null,
                'saldo' => (float) ($row['saldo'] ?? 0),
                'qty_in_kg' => array_key_exists('qty_in_kg', $row) && $row['qty_in_kg'] !== null ? (float) $row['qty_in_kg'] : null,
                'qty_out_kg' => array_key_exists('qty_out_kg', $row) && $row['qty_out_kg'] !== null ? (float) $row['qty_out_kg'] : null,
                'saldo_kg' => (float) ($row['saldo_kg'] ?? 0),
                'item_start' => ! empty($row['item_start']),
                'item_subtotal' => ! empty($row['item_subtotal']),
            ], $pageRows);
        }

        return Inertia::render('GMISL/CrossOdoo/StockCard/Index', [
            'rows' => $formattedRows,
            'customers' => $customers,
            'products' => $products,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => $selectedProductId,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'allItems' => $allItems,
            'customerName' => $customerName,
            'productName' => $productName,
            'openingBalance' => $openingBalance,
            'openingBalanceKg' => $openingBalanceKg,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'finalSaldo' => $finalSaldo,
            'totalInKg' => $totalInKg,
            'totalOutKg' => $totalOutKg,
            'finalSaldoKg' => $finalSaldoKg,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$customers, $products] = $this->fetchCustomersAndProducts();

        $selection = $this->resolveSelection($request, $customers, $products);
        $selectedProductId = $selection['selectedProductId'];
        $selectedCustomerId = $selection['selectedCustomerId'];

        $period = $request->input('period');
        if (! is_string($period) || ! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = now()->format('Y-m');
        }
        $startDate = $period.'-01';
        $endDate = Carbon::parse($period.'-01')->endOfMonth()->toDateString();
        $openingStartDate = $startDate > self::OPENING_BALANCE_START_DATE
            ? self::OPENING_BALANCE_START_DATE
            : null;

        $headers = ['TANGGAL', 'OPERATION TYPE', 'TRANSAKSI', 'SOURCE DOCUMENTS', 'NOPOL', 'KD BARANG', 'NM BARANG', 'QTY IN', 'QTY OUT', 'SALDO', 'QTY IN KG', 'QTY OUT KG', 'SALDO KG'];
        $data = [];

        if ($selectedCustomerId !== null) {
            $odoo = new OdooXmlRpcService;

            $result = $this->buildStockRows($odoo, $selectedProductId, (int) $selectedCustomerId, $products, $openingStartDate, $startDate, $endDate);

            if ($selectedProductId !== null) {
                $data[] = [$startDate, '-', 'Saldo Awal', '-', '-', '-', '-', '', '', (float) $result['openingBalance'], '', '', (float) $result['openingBalanceKg']];

                foreach ($result['rows'] as $row) {
                    $data[] = [
                        $row['date'],
                        $row['operation_type'],
                        $row['trans'],
                        $row['source_document'],
                        $row['nopol'],
                        $row['kd_barang'],
                        $row['nm_barang'],
                        $row['qty_in'] !== null ? (float) $row['qty_in'] : '',
                        $row['qty_out'] !== null ? (float) $row['qty_out'] : '',
                        (float) $row['saldo'],
                        $row['qty_in_kg'] !== null ? (float) $row['qty_in_kg'] : '',
                        $row['qty_out_kg'] !== null ? (float) $row['qty_out_kg'] : '',
                        (float) $row['saldo_kg'],
                    ];
                }
            } else {
                foreach ($result['rows'] as $row) {
                    $data[] = [
                        $row['date'] ?? '-',
                        $row['operation_type'] ?? '-',
                        $row['trans'] ?? '-',
                        $row['source_document'] ?? '-',
                        $row['nopol'] ?? '-',
                        $row['kd_barang'] ?? '-',
                        $row['nm_barang'] ?? '-',
                        array_key_exists('qty_in', $row) && $row['qty_in'] !== null ? (float) $row['qty_in'] : '',
                        array_key_exists('qty_out', $row) && $row['qty_out'] !== null ? (float) $row['qty_out'] : '',
                        (float) ($row['saldo'] ?? 0),
                        array_key_exists('qty_in_kg', $row) && $row['qty_in_kg'] !== null ? (float) $row['qty_in_kg'] : '',
                        array_key_exists('qty_out_kg', $row) && $row['qty_out_kg'] !== null ? (float) $row['qty_out_kg'] : '',
                        (float) ($row['saldo_kg'] ?? 0),
                    ];
                }
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
        $selectedProduct = null;
        if ($requestedProductId !== null && $requestedProductId !== '') {
            foreach ($selectedCustomerProducts as $product) {
                if ((int) $product['product_id'] === (int) $requestedProductId) {
                    $selectedProduct = $product;
                    break;
                }
            }
        }

        $selectedProductId = $selectedProduct['product_id'] ?? null;
        $productName = $selectedProductId !== null
            ? ($selectedProduct['product_name'] ?? null)
            : 'All Item';

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
    * @param  array<int, int>  $variantIds
    * @return array{allRows: array<int, array<string, mixed>>, openingBalance: float, openingBalanceKg: float, totalIn: float, totalOut: float, finalSaldo: float, totalInKg: float, totalOutKg: float, finalSaldoKg: float}
     */
    private function computeAllRows(OdooXmlRpcService $odoo, array $variantIds, int $customerId, ?string $openingStartDate, string $startDate, string $endDate): array
    {
        $openingBalance = 0.0;
        $totalIn = 0.0;
        $totalOut = 0.0;
        $finalSaldo = 0.0;
        $openingBalanceKg = 0.0;
        $totalInKg = 0.0;
        $totalOutKg = 0.0;
        $finalSaldoKg = 0.0;
        $allRows = [];

        if ($variantIds !== []) {
            $opening = $this->fetchOpeningBalance($odoo, $variantIds, $openingStartDate, $startDate);
            $openingBalance = $opening['quantity'];

            $groups = $this->fetchTransactionGroups($odoo, $variantIds, $startDate, $endDate);
            if ($startDate < self::OPENING_BALANCE_START_DATE) {
                $neurusoftGroup = $this->fetchNeurusoftOpeningGroup($odoo, $variantIds, $endDate);
                if ($neurusoftGroup !== null) {
                    $openingBalance += $neurusoftGroup['balance_delta'];
                }
            }
            $groups = $this->sortGroups($groups);

            foreach ($groups as $group) {
                $totalIn += (float) ($group['qty_in'] ?? 0);
                $totalOut += (float) ($group['qty_out'] ?? 0);
                $totalInKg += (float) ($group['qty_in_kg'] ?? 0);
                $totalOutKg += (float) ($group['qty_out_kg'] ?? 0);
            }

            $sohWeightKg = $this->fetchSohWeightKg($odoo, $variantIds, $customerId);
            $openingBalanceKg = $sohWeightKg - ($totalInKg - $totalOutKg);

            $running = $openingBalance;
            $runningKg = $openingBalanceKg;
            foreach ($groups as $group) {
                $running += $group['balance_delta'] ?? ($group['qty_in'] - $group['qty_out']);
                $runningKg += $group['balance_delta_kg'] ?? ($group['qty_in_kg'] - $group['qty_out_kg']);
                $group['saldo'] = $running;
                $group['saldo_kg'] = $runningKg;
                $allRows[] = $group;
            }

            $finalSaldo = $running;
            $finalSaldoKg = $runningKg;
        }

        return [
            'allRows' => $allRows,
            'openingBalance' => $openingBalance,
            'openingBalanceKg' => $openingBalanceKg,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'finalSaldo' => $finalSaldo,
            'totalInKg' => $totalInKg,
            'totalOutKg' => $totalOutKg,
            'finalSaldoKg' => $finalSaldoKg,
        ];
    }

    /**
     * Susun baris tampilan stock card.
     *
     * - Product tunggal: baris transaksi berisi info kd/nm barang product tsb.
     * - All Item (null): setiap item tampil berurutan dengan baris Saldo Awal item,
     *   baris-baris transaksinya, lalu baris Subtotal (QTY IN/OUT, saldo akhir item).
     *
     * @param  array<int, array<string, mixed>>  $products
     * @return array{rows: array<int, array<string, mixed>>, openingBalance: float, openingBalanceKg: float, totalIn: float, totalOut: float, finalSaldo: float, totalInKg: float, totalOutKg: float, finalSaldoKg: float}
     */
    private function buildStockRows(
        OdooXmlRpcService $odoo,
        ?int $selectedProductId,
        int $selectedCustomerId,
        array $products,
        ?string $openingStartDate,
        string $startDate,
        string $endDate,
    ): array {
        $totals = [
            'rows' => [],
            'openingBalance' => 0.0,
            'openingBalanceKg' => 0.0,
            'totalIn' => 0.0,
            'totalOut' => 0.0,
            'finalSaldo' => 0.0,
            'totalInKg' => 0.0,
            'totalOutKg' => 0.0,
            'finalSaldoKg' => 0.0,
        ];

        if ($selectedProductId !== null) {
            $product = null;
            foreach ($products as $entry) {
                if ((int) $entry['product_id'] === $selectedProductId) {
                    $product = $entry;
                    break;
                }
            }

            $variantIds = $this->productVariantIds($odoo, $selectedProductId);
            $computed = $this->computeAllRows($odoo, $variantIds, $selectedCustomerId, $openingStartDate, $startDate, $endDate);

            $kd = $product['default_code'] ?? null;
            $nm = $product['product_name'] ?? null;

            foreach ($computed['allRows'] as $row) {
                $row['kd_barang'] = $kd;
                $row['nm_barang'] = $nm;
                $row['item_start'] = false;
                $row['item_subtotal'] = false;
                $totals['rows'][] = $row;
            }

            $totals['openingBalance'] = $computed['openingBalance'];
            $totals['openingBalanceKg'] = $computed['openingBalanceKg'];
            $totals['totalIn'] = $computed['totalIn'];
            $totals['totalOut'] = $computed['totalOut'];
            $totals['finalSaldo'] = $computed['finalSaldo'];
            $totals['totalInKg'] = $computed['totalInKg'];
            $totals['totalOutKg'] = $computed['totalOutKg'];
            $totals['finalSaldoKg'] = $computed['finalSaldoKg'];

            return $totals;
        }

        foreach ($products as $product) {
            if ((int) $product['customer_id'] !== $selectedCustomerId) {
                continue;
            }

            $variantIds = $this->productVariantIds($odoo, (int) $product['product_id']);
            if ($variantIds === []) {
                continue;
            }

            $computed = $this->computeAllRows($odoo, $variantIds, $selectedCustomerId, $openingStartDate, $startDate, $endDate);

            if ($computed['allRows'] === [] && (float) $computed['openingBalance'] == 0 && (float) $computed['openingBalanceKg'] == 0) {
                continue;
            }

            $kd = $product['default_code'] ?? null;
            $nm = $product['product_name'] ?? null;

            $totals['rows'][] = [
                'date' => $startDate,
                'operation_type' => 'Saldo Awal',
                'trans' => '-',
                'source_document' => '-',
                'nopol' => '-',
                'kd_barang' => $kd,
                'nm_barang' => $nm,
                'qty_in' => null,
                'qty_out' => null,
                'saldo' => $computed['openingBalance'],
                'qty_in_kg' => null,
                'qty_out_kg' => null,
                'saldo_kg' => $computed['openingBalanceKg'],
                'item_start' => true,
                'item_subtotal' => false,
            ];

            foreach ($computed['allRows'] as $row) {
                $row['kd_barang'] = $kd;
                $row['nm_barang'] = $nm;
                $row['item_start'] = false;
                $row['item_subtotal'] = false;
                $totals['rows'][] = $row;
            }

            $totals['rows'][] = [
                'date' => null,
                'operation_type' => null,
                'trans' => 'Subtotal',
                'source_document' => '-',
                'nopol' => '-',
                'kd_barang' => $kd,
                'nm_barang' => $nm,
                'qty_in' => $computed['totalIn'],
                'qty_out' => $computed['totalOut'],
                'saldo' => $computed['finalSaldo'],
                'qty_in_kg' => $computed['totalInKg'],
                'qty_out_kg' => $computed['totalOutKg'],
                'saldo_kg' => $computed['finalSaldoKg'],
                'item_start' => false,
                'item_subtotal' => true,
            ];

            $totals['openingBalance'] += $computed['openingBalance'];
            $totals['openingBalanceKg'] += $computed['openingBalanceKg'];
            $totals['totalIn'] += $computed['totalIn'];
            $totals['totalOut'] += $computed['totalOut'];
            $totals['finalSaldo'] += $computed['finalSaldo'];
            $totals['totalInKg'] += $computed['totalInKg'];
            $totals['totalOutKg'] += $computed['totalOutKg'];
            $totals['finalSaldoKg'] += $computed['finalSaldoKg'];
        }

        return $totals;
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
    ): array {
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
            ['quantity', 'product_id', 'ns_actual_weight', 'reference', 'picking_type_id', 'location_id', 'location_dest_id'],
            null,
            $domain,
        );

        $locationUsages = $this->fetchLocationUsages($odoo, $lines);
        $productWeights = $this->fetchProductWeights($odoo, $variantIds);

        $balance = 0.0;
        $weightBalance = 0.0;

        foreach ($lines as $line) {
            $reference = ($line['reference'] ?? false) !== false
                ? strtoupper((string) $line['reference'])
                : '';

            if ($this->isNeurusoftResetMovement($reference)) {
                continue;
            }

            $direction = $this->movementDirection($line, $locationUsages);

            if ($direction === 'in') {
                $balance += (float) $line['quantity'];
                $weightBalance += $this->lineWeightKg($line, $productWeights);
            } elseif ($direction === 'out') {
                $balance -= (float) $line['quantity'];
                $weightBalance -= $this->lineWeightKg($line, $productWeights);
            } elseif ($this->isNeurusoftOpeningMovement($reference)) {
                $balance += (float) $line['quantity'];
                $weightBalance += $this->lineWeightKg($line, $productWeights);
            }
        }

        return ['quantity' => $balance, 'weight' => $weightBalance];
    }

    /**
     * @param  array<int, int>  $variantIds
     * @return array<int, float>
     */
    private function fetchProductWeights(OdooXmlRpcService $odoo, array $variantIds): array
    {
        $products = $odoo->searchRead(
            'product.product',
            ['id', 'weight'],
            null,
            [['id', 'in', $variantIds]],
        );

        $weights = [];
        foreach ($products as $product) {
            $weights[(int) $product['id']] = (float) ($product['weight'] ?? 0);
        }

        return $weights;
    }

    /**
     * Bobot on-hand (KG) dari stock.quant (field ns_weight), logika sama
     * dengan kolom QTY SOH (KG) pada modul SOH.
     *
     * @param  array<int, int>  $variantIds
     */
    private function fetchSohWeightKg(OdooXmlRpcService $odoo, array $variantIds, int $customerId): float
    {
        $quants = $odoo->searchRead(
            'stock.quant',
            ['ns_weight'],
            null,
            [
                ['location_id.usage', 'in', ['internal', 'transit']],
                ['quantity', '>', 0],
                ['product_id', 'in', $variantIds],
                '|',
                ['owner_id', '=', false],
                ['owner_id', '=', $customerId],
            ],
        );

        $total = 0.0;
        foreach ($quants as $quant) {
            $total += (float) ($quant['ns_weight'] ?? 0.0);
        }

        return $total;
    }

    /**
     * @param  array<string, mixed>  $line
     * @param  array<int, float>  $productWeights
     */
    private function lineWeightKg(array $line, array $productWeights): float
    {
        $product = $line['product_id'] ?? false;
        $productId = is_array($product) ? (int) ($product[0] ?? 0) : 0;
        $productWeight = $productWeights[$productId] ?? 0.0;

        if ($productWeight > 0) {
            return abs((float) ($line['quantity'] ?? 0)) * $productWeight;
        }

        return abs((float) ($line['ns_actual_weight'] ?? 0));
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
        $balanceDeltaKg = 0.0;
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
            $weight = abs((float) ($line['ns_actual_weight'] ?? 0));

            if ($direction === 'in') {
                $balanceDelta += $quantity;
                $balanceDeltaKg += $weight;
            } elseif ($direction === 'out') {
                $balanceDelta -= $quantity;
                $balanceDeltaKg -= $weight;
            }
        }

        if (! $found) {
            return null;
        }

        return [
            'date' => self::OPENING_BALANCE_START_DATE,
            'trans' => 'SALDO AWAL NEUROSOFT',
            'source_document' => null,
            'expired' => null,
            'qty_in' => null,
            'qty_out' => null,
            'balance_delta' => $balanceDelta,
            'balance_delta_kg' => $balanceDeltaKg,
        ];
    }

    private function isNeurusoftOpeningMovement(string $reference): bool
    {
        return str_contains($reference, 'PRODUCT QUANTITY UPDATED')
            || str_contains($reference, 'UPDATE QTY KILOGRAM')
            || str_contains($reference, 'UPDATE KILOGRAM STOK AWAL')
            || str_contains($reference, 'SALDO AWAL');
    }

    private function isNeurusoftResetMovement(string $reference): bool
    {
        return str_contains($reference, 'SALDO AWAL');
    }

    private function isWeightAdjustmentMovement(string $reference): bool
    {
        return str_contains($reference, 'UPDATE KILOGRAM NON STANDARD')
            || str_contains($reference, 'ADJUST WEIGHT KILOGRAM STANDARD');
    }

    /**
     * @param  array<int, int>  $variantIds
     * @return array<int, array<string, mixed>>
     */
    private function fetchTransactionGroups(OdooXmlRpcService $odoo, array $variantIds, string $startDate, string $endDate): array
    {
        $lines = $odoo->searchRead(
            'stock.move.line',
            ['date', 'quantity', 'ns_actual_weight', 'lot_id', 'reference', 'picking_type_id', 'picking_id', 'location_id', 'location_dest_id'],
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
        $pickingIds = [];
        foreach ($lines as $line) {
            if (is_array($line['lot_id'] ?? null)) {
                $lotIds[(int) $line['lot_id'][0]] = true;
            }

            $picking = $line['picking_id'] ?? false;
            if (is_array($picking) && isset($picking[0])) {
                $pickingIds[(int) $picking[0]] = true;
            }
        }

        $expirations = $this->fetchLotExpirations($odoo, array_keys($lotIds));
        $pickingInfos = $this->fetchPickingInfos($odoo, array_keys($pickingIds));

        $groups = [];
        foreach ($lines as $line) {
            $date = (string) ($line['date'] ?? '');
            $label = $this->pickingTypeLabel($line['picking_type_id'] ?? false);
            $reference = ($line['reference'] ?? false) !== false
                ? strtoupper((string) $line['reference'])
                : '';

            if (str_contains($reference, 'PRODUCT QUANTITY CONFIRMED')) {
                continue;
            }

            if ($this->isWeightAdjustmentMovement($reference)) {
                continue;
            }

            $direction = $this->movementDirection($line, $locationUsages);
            $inbound = $direction === 'in';
            $outbound = $direction === 'out';

            if ($this->isNeurusoftOpeningMovement($reference)) {
                continue;
            }

            if (! $inbound && ! $outbound) {
                continue;
            }

            $lotRef = $line['lot_id'] ?? false;
            $lotId = is_array($lotRef) ? (int) $lotRef[0] : null;
            $trans = ($line['reference'] ?? false) !== false ? (string) $line['reference'] : null;
            $expiredRaw = $lotId !== null ? ($expirations[$lotId] ?? null) : null;
            $expired = $expiredRaw !== null ? substr($expiredRaw, 0, 10) : null;

            $picking = $line['picking_id'] ?? false;
            $pickingId = is_array($picking) ? (int) $picking[0] : null;
            $pickingInfo = $pickingId !== null ? ($pickingInfos[$pickingId] ?? null) : null;
            $source = $pickingInfo !== null ? $pickingInfo['origin'] : null;
            $plate = $pickingInfo !== null ? $pickingInfo['plate'] : null;

            $key = $date.'|'.($trans ?? '').'|'.($expiredRaw ?? '').'|'.($source ?? '');

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'date' => $date,
                    'operation_type' => $label,
                    'trans' => $trans,
                    'expired' => $expired,
                    'expired_raw' => $expiredRaw,
                    'source_document' => $source,
                    'nopol' => null,
                    'qty_in' => 0.0,
                    'qty_out' => 0.0,
                    'qty_in_kg' => 0.0,
                    'qty_out_kg' => 0.0,
                ];
            }

            if ($groups[$key]['nopol'] === null && $plate !== null && $plate !== '') {
                $groups[$key]['nopol'] = $plate;
            }

            $actualWeight = abs((float) ($line['ns_actual_weight'] ?? 0));
            if ($inbound) {
                $groups[$key]['qty_in'] += (float) $line['quantity'];
                $groups[$key]['qty_in_kg'] += $actualWeight;
            }

            if ($outbound) {
                $groups[$key]['qty_out'] += (float) $line['quantity'];
                $groups[$key]['qty_out_kg'] += $actualWeight;
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
            ['active_test' => false],
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
        $label = $this->pickingTypeLabel($line['picking_type_id'] ?? false);

        if ($label !== null && $this->isExcludedLabel($label)) {
            return null;
        }

        if ($label !== null && $this->isInboundLabel($label)) {
            return 'in';
        }

        if ($label !== null && $this->isOutboundLabel($label)) {
            return 'out';
        }

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
     * @param  array<int, int>  $pickingIds
     * @return array<int, array{origin: string|null, plate: string|null}>
     */
    private function fetchPickingInfos(OdooXmlRpcService $odoo, array $pickingIds): array
    {
        if ($pickingIds === []) {
            return [];
        }

        $pickings = $odoo->searchRead(
            'stock.picking',
            ['id', 'origin', 'x_studio_no_kendaraan'],
            null,
            [['id', 'in', $pickingIds]],
        );

        $map = [];

        foreach ($pickings as $picking) {
            $origin = $picking['origin'] ?? false;
            $plate = $picking['x_studio_no_kendaraan'] ?? false;

            $map[(int) $picking['id']] = [
                'origin' => ($origin !== false && $origin !== null && $origin !== '')
                    ? (string) $origin
                    : null,
                'plate' => ($plate !== false && $plate !== null && $plate !== '')
                    ? (string) $plate
                    : null,
            ];
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

        $name = trim((string) $pickingType[1]);
        $colon = strrpos($name, ':');

        if ($colon !== false) {
            $name = trim(substr($name, $colon + 1));
        }

        return strtoupper($name);
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

    private function isExcludedLabel(string $label): bool
    {
        foreach (self::EXCLUDE_PATTERNS as $pattern) {
            if (str_contains($label, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
