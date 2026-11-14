<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CrossOdooCustomerAccessService;
use App\Services\OdooXmlRpcService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapInboundController extends Controller
{
    private const IN_PATTERNS = ['RECEIPTS'];

    private const OUT_PATTERNS = ['DELIVERY ORDERS'];

    public function index(Request $request): Response
    {
        $filtersApplied = $request->boolean('filters_applied');
        [$customers, $products] = $this->fetchCustomersAndProducts($request->user());

        $selection = $this->resolveSelection($request, $customers, $products);
        $selectedCustomerId = $selection['selectedCustomerId'];
        $selectedProductIds = $selection['selectedProductIds'];
        $customerName = $selection['customerName'];
        $productName = $selection['productName'];

        $period = $request->input('period');
        if (! is_string($period) || ! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = now()->format('Y-m');
        }
        $startDate = $period.'-01';
        $endDate = Carbon::parse($period.'-01')->endOfMonth()->toDateString();

        $rows = [];
        $totalRows = 0;
        $totalQty = 0.0;
        $totalQtyKg = 0.0;
        $totalQtyUnavailable = false;
        $allRows = [];
        $allItems = count($selectedProductIds) !== 1;

        if ($filtersApplied && $selectedCustomerId !== null) {
            $odoo = app(OdooXmlRpcService::class);

            $allRows = $this->buildAllRows($odoo, $selectedProductIds, (int) $selectedCustomerId, $products, $startDate, $endDate);

            foreach ($allRows as $allRow) {
                if (! empty($allRow['is_subtotal'])) {
                    continue;
                }
                if ($allRow['qty'] === null) {
                    $totalQtyUnavailable = true;
                } else {
                    $totalQty += (float) $allRow['qty'];
                }
                $totalQtyKg += (float) ($allRow['qty_kg'] ?? 0);
            }
            if ($totalQtyUnavailable) {
                $totalQty = null;
            }

            $totalRows = count(array_filter($allRows, fn ($row) => empty($row['is_subtotal'])));
            $rows = $allRows;
        }

        return Inertia::render('GMISL/CrossOdoo/RekapInbound/Index', [
            'rows' => $rows,
            'customers' => $customers,
            'products' => $products,
            'filtersApplied' => $filtersApplied,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => count($selectedProductIds) === 1 ? $selectedProductIds[0] : null,
            'selectedProductIds' => $selectedProductIds,
            'customerName' => $customerName,
            'productName' => $productName,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'allItems' => $allItems,
            'totalRows' => $totalRows,
            'totalQty' => $totalQty,
            'totalQtyKg' => $totalQtyKg,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filtersApplied = $request->boolean('filters_applied');
        [$customers, $products] = $this->fetchCustomersAndProducts($request->user());

        $selection = $this->resolveSelection($request, $customers, $products);
        $selectedCustomerId = $selection['selectedCustomerId'];
        $selectedProductIds = $selection['selectedProductIds'];

        $period = $request->input('period');
        if (! is_string($period) || ! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = now()->format('Y-m');
        }
        $startDate = $period.'-01';
        $endDate = Carbon::parse($period.'-01')->endOfMonth()->toDateString();

        $headers = ['NO', 'TANGGAL', 'KD CUSTOMER', 'NM CUSTOMER', 'NO DELIVERY', 'SOURCE DOCUMENTS', 'NO MOBIL', 'KD BARANG', 'NM BARANG', 'QTY', 'QTY KG', 'UOM', 'KETERANGAN ToS'];
        $data = [];

        if ($filtersApplied && $selectedCustomerId !== null) {
            $odoo = app(OdooXmlRpcService::class);

            $no = 0;
            foreach ($this->buildAllRows($odoo, $selectedProductIds, (int) $selectedCustomerId, $products, $startDate, $endDate) as $row) {
                if (! empty($row['is_subtotal'])) {
                    continue;
                }

                $no++;
                $data[] = [
                    $no,
                    $row['tanggal'],
                    $row['kd_customer'],
                    $row['nm_customer'],
                    $row['no_delivery'],
                    $row['source_documents'],
                    $row['no_mobil'],
                    $row['kd_barang'],
                    $row['nm_barang'],
                    $row['qty'] === null ? null : (float) $row['qty'],
                    (float) $row['qty_kg'],
                    $row['uom'],
                    $row['qty_source'],
                ];
            }
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($data, null, 'A2');

        $safePart = $selection['productName'] ?? 'product';
        $safePart = preg_replace('/[^A-Za-z0-9\-_]+/', '_', (string) $safePart);
        $filename = 'rekap_inbound_'.($safePart !== '' ? $safePart : 'product').'_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $customers
     * @param  array<int, array<string, mixed>>  $products
     * @return array{selectedCustomerId: int|null, selectedProductId: int|null, selectedProductIds: array<int, int>, customerName: string|null, productName: string|null}
     */
    private function resolveSelection(Request $request, array $customers, array $products): array
    {
        $selectedCustomerId = CrossOdooCustomerAccessService::resolveCustomerId($request->input('customer_id'), $customers);

        $selectedCustomerProducts = array_values(array_filter(
            $products,
            fn ($product) => (int) $product['customer_id'] === (int) $selectedCustomerId
        ));

        $requestedProductIds = $request->input('product_id', []);
        $requestedProductIds = is_array($requestedProductIds) ? $requestedProductIds : [$requestedProductIds];
        $requestedProductIds = array_values(array_unique(array_filter(
            array_map('intval', $requestedProductIds),
            fn ($id) => $id > 0
        )));
        $selectedProducts = array_values(array_filter(
            $selectedCustomerProducts,
            fn ($product) => in_array((int) $product['product_id'], $requestedProductIds, true)
        ));
        $selectedProductIds = array_map(fn ($product) => (int) $product['product_id'], $selectedProducts);
        $selectedProductId = count($selectedProductIds) === 1 ? $selectedProductIds[0] : null;
        $productName = $selectedProducts
            ? implode(', ', array_map(fn ($product) => $product['product_name'], $selectedProducts))
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
            'selectedProductIds' => $selectedProductIds,
            'customerName' => $customerName,
            'productName' => $productName,
        ];
    }

    /**
     * Ambil daftar customer dan product langsung dari Odoo (via XML-RPC).
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function fetchCustomersAndProducts(?User $user): array
    {
        return app(CrossOdooCustomerAccessService::class)->fetchCustomersAndProducts($user);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function computeRows(
        OdooXmlRpcService $odoo,
        int $templateId,
        ?int $customerId,
        string $startDate,
        string $endDate,
    ): array {
        $variants = $odoo->searchRead(
            'product.product',
            ['id'],
            null,
            [['product_tmpl_id', '=', $templateId]],
        );

        if ($variants === []) {
            return [];
        }

        $variantIds = array_map(fn ($variant) => (int) $variant['id'], $variants);

        $domain = [
            ['state', '=', 'done'],
            ['product_id', 'in', $variantIds],
            ['date', '>=', $startDate.' 00:00:00'],
            ['date', '<=', $endDate.' 23:59:59'],
        ];

        $lines = $odoo->searchRead(
            'stock.move.line',
            ['id', 'date', 'quantity', 'ns_actual_weight', 'product_id', 'lot_id', 'owner_id', 'picking_id', 'move_id', 'product_uom_id', 'reference', 'picking_type_id', 'location_id', 'location_dest_id'],
            null,
            $domain,
        );

        if ($lines === []) {
            return [];
        }

        $pickingIds = [];
        $moveIds = [];
        $productProductIds = [];
        $uomIds = [];
        foreach ($lines as $line) {
            $picking = $line['picking_id'] ?? false;
            if (is_array($picking) && isset($picking[0])) {
                $pickingIds[(int) $picking[0]] = true;
            }

            $move = $line['move_id'] ?? false;
            if (is_array($move) && isset($move[0]) && (! isset($line['quantity']) || ! is_numeric($line['quantity']))) {
                $moveIds[(int) $move[0]] = true;
            }

            $product = $line['product_id'] ?? false;
            if (is_array($product) && isset($product[0])) {
                $productProductIds[(int) $product[0]] = true;
            }

            $uom = $line['product_uom_id'] ?? false;
            if (is_array($uom) && isset($uom[0])) {
                $uomIds[(int) $uom[0]] = true;
            }
        }

        $pickings = $this->fetchPickings($odoo, array_keys($pickingIds));
        $moveQuantities = $this->fetchMoveQuantities($odoo, array_keys($moveIds));
        $knownMoveQuantities = [];
        foreach ($lines as $line) {
            $move = $line['move_id'] ?? false;
            if (is_array($move) && isset($move[0]) && isset($line['quantity']) && is_numeric($line['quantity'])) {
                $moveId = (int) $move[0];
                $knownMoveQuantities[$moveId] = ($knownMoveQuantities[$moveId] ?? 0.0) + (float) $line['quantity'];
            }
        }
        $fallbackAllocatedMoves = [];
        $productDetails = $this->fetchProductDetails($odoo, array_keys($productProductIds));
        $uoms = $this->fetchUoms($odoo, array_keys($uomIds));

        $rows = [];

        foreach ($lines as $line) {
            $reference = ($line['reference'] ?? false) !== false
                ? strtoupper((string) $line['reference'])
                : '';

            if (str_contains($reference, 'PRODUCT QUANTITY CONFIRMED')) {
                continue;
            }

            if ($this->isWeightAdjustmentMovement($reference)) {
                continue;
            }

            $direction = $this->movementDirection($line);

            if ($this->isNeurusoftOpeningMovement($reference)) {
                continue;
            }

            if ($direction !== 'in') {
                continue;
            }

            $move = $line['move_id'] ?? false;
            $moveId = is_array($move) && isset($move[0]) ? (int) $move[0] : null;
            $actualQuantity = $this->resolveActualQuantity(
                $line,
                $moveQuantities,
                $knownMoveQuantities,
                $moveId !== null && ! empty($fallbackAllocatedMoves[$moveId]),
            );
            if ($actualQuantity['fallback_allocated'] && $moveId !== null) {
                $fallbackAllocatedMoves[$moveId] = true;
            }
            $picking = $line['picking_id'] ?? false;
            $pickingId = is_array($picking) ? (int) $picking[0] : null;
            $pickingInfo = $pickingId !== null ? ($pickings[$pickingId] ?? null) : null;

            $owner = $line['owner_id'] ?? false;
            $ownerId = is_array($owner) ? (int) $owner[0] : null;
            $partnerId = $ownerId ?? ($pickingInfo['partner_id'] ?? null);
            $partner = $partnerId !== null ? ($pickings['_partners'][$partnerId] ?? null) : null;

            $product = $line['product_id'] ?? false;
            $productId = is_array($product) ? (int) $product[0] : null;
            $productInfo = $productId !== null ? ($productDetails[$productId] ?? null) : null;

            $uom = $line['product_uom_id'] ?? false;
            $uomId = is_array($uom) ? (int) $uom[0] : null;

            $rows[] = [
                'tanggal' => substr((string) ($line['date'] ?? ''), 0, 10),
                'kd_customer' => $partner['ref'] ?? null,
                'nm_customer' => $partner['name'] ?? null,
                'transaksi' => ($line['reference'] ?? false) !== false ? (string) $line['reference'] : null,
                'no_delivery' => $pickingInfo['name'] ?? null,
                'source_documents' => $pickingInfo['origin'] ?? null,
                'no_mobil' => $pickingInfo['plate'] ?? null,
                'kd_barang' => $productInfo['default_code'] ?? null,
                'nm_barang' => $productInfo['name'] ?? null,
                'qty' => $actualQuantity['quantity'],
                'qty_source' => $actualQuantity['source'],
                'qty_available' => $actualQuantity['available'],
                'qty_kg' => abs((float) ($line['ns_actual_weight'] ?? 0)),
                'uom' => $uomId !== null ? ($uoms[$uomId] ?? null) : null,
            ];
        }

        $groupedRows = [];
        foreach ($rows as $row) {
            $groupKey = json_encode([
                $row['tanggal'],
                $row['transaksi'],
                $row['kd_customer'],
                $row['nm_customer'],
                $row['no_delivery'],
                $row['source_documents'],
                $row['no_mobil'],
                $row['kd_barang'],
                $row['nm_barang'],
            ]);

            if (! isset($groupedRows[$groupKey])) {
                $groupedRows[$groupKey] = $row;
                continue;
            }

            $groupedRows[$groupKey]['qty'] += $row['qty'] ?? 0;
            $groupedRows[$groupKey]['qty_available'] = $groupedRows[$groupKey]['qty_available'] && $row['qty_available'];
            $groupedRows[$groupKey]['qty_source'] = $this->combineQuantitySources($groupedRows[$groupKey]['qty_source'], $row['qty_source']);
            $groupedRows[$groupKey]['qty_kg'] += $row['qty_kg'];
        }

        $rows = array_values($groupedRows);
        foreach ($rows as &$row) {
            if (! $row['qty_available']) {
                $row['qty'] = null;
            }
            unset($row['qty_available']);
        }
        unset($row);

        usort($rows, function ($a, $b) {
            return strcmp((string) $a['tanggal'], (string) $b['tanggal'])
                ?: strcmp((string) $a['no_delivery'], (string) $b['no_delivery'])
                ?: strcmp((string) $a['kd_barang'], (string) $b['kd_barang']);
        });

        return $rows;
    }

    /**
     * Ambil ID template seluruh product milik customer tertentu.
     *
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, int>
     */
    private function productTemplateIds(int $selectedCustomerId, array $products, array $selectedProductIds = []): array
    {
        $templateIds = [];
        foreach ($products as $product) {
            if ((int) $product['customer_id'] === $selectedCustomerId) {
                if ($selectedProductIds !== [] && ! in_array((int) $product['product_id'], $selectedProductIds, true)) {
                    continue;
                }
                $templateIds[] = (int) $product['product_id'];
            }
        }

        return $templateIds;
    }

    /**
     * Hitung baris untuk satu product terpilih.
     * Jika null (All Item), kelompokkan seluruh product customer per item
     * (urut per item, lalu diakhiri baris subtotal QTY & QTY KG per item).
     *
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function buildAllRows(
        OdooXmlRpcService $odoo,
        array $selectedProductIds,
        int $selectedCustomerId,
        array $products,
        string $startDate,
        string $endDate,
    ): array {
        if (count($selectedProductIds) === 1) {
            return $this->groupRowsByTransaction(
                $this->computeRows($odoo, $selectedProductIds[0], $selectedCustomerId, $startDate, $endDate)
            );
        }

        $rows = [];
        foreach ($this->productTemplateIds($selectedCustomerId, $products, $selectedProductIds) as $templateId) {
            array_push($rows, ...$this->computeRows($odoo, $templateId, $selectedCustomerId, $startDate, $endDate));
        }

        return $this->groupRowsByTransaction($rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function groupRowsByTransaction(array $rows): array
    {
        $transactions = [];
        foreach ($rows as $row) {
            $key = $row['transaksi'] !== null && $row['transaksi'] !== ''
                ? json_encode(['transaksi', $row['transaksi']])
                : json_encode(['fallback', $row['tanggal'], $row['no_delivery'], $row['kd_customer']]);
            $transactions[$key][] = $row;
        }

        uasort($transactions, function ($a, $b) {
            $firstA = $a[0];
            $firstB = $b[0];

            return strcmp((string) $firstA['tanggal'], (string) $firstB['tanggal'])
                ?: strcmp((string) $firstA['transaksi'], (string) $firstB['transaksi'])
                ?: strcmp((string) $firstA['no_delivery'], (string) $firstB['no_delivery'])
                ?: strcmp((string) $firstA['source_documents'], (string) $firstB['source_documents']);
        });

        $groupedRows = [];
        foreach ($transactions as $items) {
            usort($items, fn ($a, $b) => strcmp((string) $a['kd_barang'], (string) $b['kd_barang']));
            $qty = 0.0;
            $qtyKg = 0.0;
            $qtyAvailable = true;
            $qtySource = null;
            foreach ($items as $item) {
                if ($item['qty'] === null) {
                    $qtyAvailable = false;
                } else {
                    $qty += (float) $item['qty'];
                }
                $qtySource = $this->combineQuantitySources($qtySource, $item['qty_source']);
                $qtyKg += (float) $item['qty_kg'];
                $groupedRows[] = $item;
            }

            $first = $items[0];
            $groupedRows[] = [
                'tanggal' => null,
                'transaksi' => $first['transaksi'],
                'kd_customer' => null,
                'nm_customer' => null,
                'no_delivery' => $first['no_delivery'],
                'source_documents' => $first['source_documents'],
                'no_mobil' => null,
                'kd_barang' => null,
                'nm_barang' => null,
                'qty' => $qtyAvailable ? $qty : null,
                'qty_source' => $qtySource,
                'qty_kg' => $qtyKg,
                'uom' => null,
                'is_subtotal' => true,
            ];
        }

        return $groupedRows;
    }

    /**
     * @param  array<int, int>  $pickingIds
     * @return array<int, array<string, mixed>>
     */
    private function fetchPickings(OdooXmlRpcService $odoo, array $pickingIds): array
    {
        if ($pickingIds === []) {
            return [];
        }

        $pickings = $odoo->searchRead(
            'stock.picking',
            ['id', 'name', 'origin', 'partner_id', 'x_studio_no_kendaraan'],
            null,
            [['id', 'in', $pickingIds]],
        );

        $map = [];
        $partnerIds = [];

        foreach ($pickings as $picking) {
            $id = (int) $picking['id'];
            $partner = $picking['partner_id'] ?? false;
            $partnerId = is_array($partner) && isset($partner[0]) ? (int) $partner[0] : null;
            if ($partnerId !== null) {
                $partnerIds[$partnerId] = true;
            }

            $origin = $picking['origin'] ?? false;
            $plate = $picking['x_studio_no_kendaraan'] ?? false;

            $map[$id] = [
                'id' => $id,
                'name' => (string) ($picking['name'] ?? ''),
                'origin' => ($origin !== false && $origin !== null && $origin !== '') ? (string) $origin : null,
                'plate' => ($plate !== false && $plate !== null && $plate !== '') ? (string) $plate : null,
                'partner_id' => $partnerId,
            ];
        }

        if ($partnerIds !== []) {
            $partners = $odoo->searchRead(
                'res.partner',
                ['id', 'ref', 'name'],
                null,
                [['id', 'in', array_keys($partnerIds)]],
            );

            foreach ($partners as $partner) {
                $ref = $partner['ref'] ?? false;
                $map['_partners'][(int) $partner['id']] = [
                    'ref' => ($ref !== false && $ref !== null && $ref !== '') ? (string) $ref : null,
                    'name' => (string) ($partner['name'] ?? ''),
                ];
            }
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $moveIds
     * @return array<int, float>
     */
    private function fetchMoveQuantities(OdooXmlRpcService $odoo, array $moveIds): array
    {
        if ($moveIds === []) {
            return [];
        }

        $moves = $odoo->searchRead(
            'stock.move',
            ['id', 'quantity'],
            null,
            [['id', 'in', $moveIds]],
        );

        $quantities = [];
        foreach ($moves as $move) {
            if (isset($move['quantity']) && is_numeric($move['quantity'])) {
                $quantities[(int) $move['id']] = (float) $move['quantity'];
            }
        }

        return $quantities;
    }

    /**
     * @param  array<string, mixed>  $line
     * @param  array<int, float>  $moveQuantities
     * @param  array<int, float>  $knownMoveQuantities
     * @return array{quantity: float|null, source: string, available: bool, fallback_allocated: bool}
     */
    private function resolveActualQuantity(array $line, array $moveQuantities, array $knownMoveQuantities, bool $fallbackAlreadyAllocated): array
    {
        if (isset($line['quantity']) && is_numeric($line['quantity'])) {
            return [
                'quantity' => (float) $line['quantity'],
                'source' => 'Aktual mutasi: stock.move.line.quantity',
                'available' => true,
                'fallback_allocated' => false,
            ];
        }

        $move = $line['move_id'] ?? false;
        $moveId = is_array($move) && isset($move[0]) ? (int) $move[0] : null;
        if ($moveId !== null && array_key_exists($moveId, $moveQuantities)) {
            $quantity = 0.0;
            $fallbackAllocated = ! $fallbackAlreadyAllocated;
            if ($fallbackAllocated) {
                $quantity = max(0.0, $moveQuantities[$moveId] - ($knownMoveQuantities[$moveId] ?? 0.0));
            }

            return [
                'quantity' => $quantity,
                'source' => 'Fallback global: sisa stock.move.quantity setelah aktual per baris dikurangkan; dialokasikan satu kali per mutasi',
                'available' => true,
                'fallback_allocated' => $fallbackAllocated,
            ];
        }

        return [
            'quantity' => null,
            'source' => 'Kuantitas aktual dan fallback global tidak tersedia; kuantitas demand tidak digunakan',
            'available' => false,
            'fallback_allocated' => false,
        ];
    }

    private function combineQuantitySources(?string $current, string $next): string
    {
        if ($current === null || $current === '') {
            return $next;
        }

        if (str_contains($current, $next)) {
            return $current;
        }

        return $current.'; '.$next;
    }

    /**
     * @param  array<int, int>  $productProductIds
     * @return array<int, array{default_code: string|null, name: string|null}>
     */
    private function fetchProductDetails(OdooXmlRpcService $odoo, array $productProductIds): array
    {
        if ($productProductIds === []) {
            return [];
        }

        $products = $odoo->searchRead(
            'product.product',
            ['id', 'product_tmpl_id'],
            null,
            [['id', 'in', $productProductIds]],
        );

        $templateIds = [];
        foreach ($products as $product) {
            $template = $product['product_tmpl_id'] ?? false;
            if (is_array($template) && isset($template[0])) {
                $templateIds[(int) $template[0]] = true;
            }
        }

        $templates = $odoo->searchRead(
            'product.template',
            ['id', 'default_code', 'name'],
            null,
            [['id', 'in', array_keys($templateIds)]],
            ['lang' => 'en_US'],
        );

        $templateMap = [];
        foreach ($templates as $template) {
            $defaultCode = $template['default_code'] ?? false;
            $templateMap[(int) $template['id']] = [
                'default_code' => ($defaultCode !== false && $defaultCode !== null && $defaultCode !== '')
                    ? (string) $defaultCode
                    : null,
                'name' => (string) ($template['name'] ?? ''),
            ];
        }

        $map = [];
        foreach ($products as $product) {
            $template = $product['product_tmpl_id'] ?? false;
            $templateId = is_array($template) ? (int) $template[0] : null;
            $info = $templateId !== null ? ($templateMap[$templateId] ?? null) : null;

            $map[(int) $product['id']] = [
                'default_code' => $info['default_code'] ?? null,
                'name' => $info['name'] ?? null,
            ];
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $uomIds
     * @return array<int, string|null>
     */
    private function fetchUoms(OdooXmlRpcService $odoo, array $uomIds): array
    {
        $map = [];

        foreach (array_chunk($uomIds, 50) as $chunk) {
            $uoms = $odoo->searchRead(
                'uom.uom',
                ['id', 'name'],
                null,
                [['id', 'in', $chunk]],
            );

            foreach ($uoms as $uom) {
                $map[(int) $uom['id']] = (string) ($uom['name'] ?? '');
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function movementDirection(array $line): ?string
    {
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
            if ($label === $pattern) {
                return true;
            }
        }

        return false;
    }

    private function isOutboundLabel(string $label): bool
    {
        foreach (self::OUT_PATTERNS as $pattern) {
            if ($label === $pattern) {
                return true;
            }
        }

        return false;
    }

    private function isNeurusoftOpeningMovement(string $reference): bool
    {
        return str_contains($reference, 'PRODUCT QUANTITY UPDATED')
            || str_contains($reference, 'UPDATE QTY KILOGRAM')
            || str_contains($reference, 'UPDATE KILOGRAM STOK AWAL')
            || str_contains($reference, 'SALDO AWAL');
    }

    private function isWeightAdjustmentMovement(string $reference): bool
    {
        return str_contains($reference, 'UPDATE KILOGRAM NON STANDARD')
            || str_contains($reference, 'ADJUST WEIGHT KILOGRAM STANDARD');
    }
}
