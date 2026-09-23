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

class RekapInboundController extends Controller
{
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

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        $rows = [];
        $totalRows = 0;
        $totalQty = 0.0;
        $totalQtyKg = 0.0;
        $allRows = [];
        $allItems = $selectedProductId === null;

        if ($selectedCustomerId !== null) {
            $odoo = new OdooXmlRpcService;

            $allRows = $this->buildAllRows($odoo, $selectedProductId, (int) $selectedCustomerId, $products, $startDate, $endDate);

            foreach ($allRows as $allRow) {
                if (! empty($allRow['is_subtotal'])) {
                    continue;
                }
                $totalQty += (float) ($allRow['qty'] ?? 0);
                $totalQtyKg += (float) ($allRow['qty_kg'] ?? 0);
            }

            $totalRows = count($allRows);

            if ($allItems) {
                $rows = $allRows;
            } else {
                $page = min($page, max(1, (int) ceil($totalRows / $perPage)));
                $offset = ($page - 1) * $perPage;
                $rows = array_slice($allRows, $offset, $perPage);
            }
        }

        return Inertia::render('GMISL/CrossOdoo/RekapInbound/Index', [
            'rows' => $rows,
            'customers' => $customers,
            'products' => $products,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => $selectedProductId,
            'customerName' => $customerName,
            'productName' => $productName,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'allItems' => $allItems,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'totalQty' => $totalQty,
            'totalQtyKg' => $totalQtyKg,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$customers, $products] = $this->fetchCustomersAndProducts();

        $selection = $this->resolveSelection($request, $customers, $products);
        $selectedCustomerId = $selection['selectedCustomerId'];
        $selectedProductId = $selection['selectedProductId'];

        $period = $request->input('period');
        if (! is_string($period) || ! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = now()->format('Y-m');
        }
        $startDate = $period.'-01';
        $endDate = Carbon::parse($period.'-01')->endOfMonth()->toDateString();

        $headers = ['NO', 'TANGGAL', 'KD CUSTOMER', 'NM CUSTOMER', 'NO DELIVERY', 'SOURCE DOCUMENTS', 'NO MOBIL', 'KD BARANG', 'NM BARANG', 'QTY', 'QTY KG', 'UOM', 'EXPIRED DATE', 'LOT'];
        $data = [];

        if ($selectedCustomerId !== null) {
            $odoo = new OdooXmlRpcService;

            $no = 0;
            foreach ($this->buildAllRows($odoo, $selectedProductId, (int) $selectedCustomerId, $products, $startDate, $endDate) as $row) {
                if (! empty($row['is_subtotal'])) {
                    $data[] = [
                        '',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        $row['kd_barang'],
                        $row['nm_barang'],
                        (float) $row['qty'],
                        (float) $row['qty_kg'],
                        '-',
                        '-',
                        '-',
                    ];
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
                    (float) $row['qty'],
                    (float) $row['qty_kg'],
                    $row['uom'],
                    $row['expired_date'],
                    $row['lot'],
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
            ['id', 'date', 'quantity', 'ns_actual_weight', 'product_id', 'lot_id', 'owner_id', 'picking_id', 'product_uom_id', 'reference', 'picking_type_id', 'location_id', 'location_dest_id'],
            null,
            $domain,
        );

        if ($lines === []) {
            return [];
        }

        $locationUsages = $this->fetchLocationUsages($odoo, $lines);

        $pickingIds = [];
        $lotIds = [];
        $productProductIds = [];
        $uomIds = [];
        foreach ($lines as $line) {
            $picking = $line['picking_id'] ?? false;
            if (is_array($picking) && isset($picking[0])) {
                $pickingIds[(int) $picking[0]] = true;
            }

            $lot = $line['lot_id'] ?? false;
            if (is_array($lot) && isset($lot[0])) {
                $lotIds[(int) $lot[0]] = true;
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
        $lots = $this->fetchLots($odoo, array_keys($lotIds));
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

            $direction = $this->movementDirection($line, $locationUsages);

            if ($this->isNeurusoftOpeningMovement($reference)) {
                continue;
            }

            if ($direction !== 'in') {
                continue;
            }

            $picking = $line['picking_id'] ?? false;
            $pickingId = is_array($picking) ? (int) $picking[0] : null;
            $pickingInfo = $pickingId !== null ? ($pickings[$pickingId] ?? null) : null;

            $owner = $line['owner_id'] ?? false;
            $ownerId = is_array($owner) ? (int) $owner[0] : null;
            $partnerId = $ownerId ?? ($pickingInfo['partner_id'] ?? null);
            $partner = $partnerId !== null ? ($pickings['_partners'][$partnerId] ?? null) : null;

            $lot = $line['lot_id'] ?? false;
            $lotId = is_array($lot) ? (int) $lot[0] : null;
            $lotInfo = $lotId !== null ? ($lots[$lotId] ?? null) : null;

            $product = $line['product_id'] ?? false;
            $productId = is_array($product) ? (int) $product[0] : null;
            $productInfo = $productId !== null ? ($productDetails[$productId] ?? null) : null;

            $expiredRaw = $lotInfo['expiration_date'] ?? null;
            $expired = $expiredRaw !== null && $expiredRaw !== ''
                ? substr((string) $expiredRaw, 0, 10)
                : null;

            $uom = $line['product_uom_id'] ?? false;
            $uomId = is_array($uom) ? (int) $uom[0] : null;

            $rows[] = [
                'tanggal' => substr((string) ($line['date'] ?? ''), 0, 10),
                'kd_customer' => $partner['ref'] ?? null,
                'nm_customer' => $partner['name'] ?? null,
                'no_delivery' => $pickingInfo['name'] ?? null,
                'source_documents' => $pickingInfo['origin'] ?? null,
                'no_mobil' => $pickingInfo['plate'] ?? null,
                'kd_barang' => $productInfo['default_code'] ?? null,
                'nm_barang' => $productInfo['name'] ?? null,
                'qty' => (float) ($line['quantity'] ?? 0),
                'qty_kg' => abs((float) ($line['ns_actual_weight'] ?? 0)),
                'uom' => $uomId !== null ? ($uoms[$uomId] ?? null) : null,
                'expired_date' => $expired,
                'lot' => $lotInfo['name'] ?? null,
            ];
        }

        $groupedRows = [];
        foreach ($rows as $row) {
            $groupKey = json_encode([
                $row['tanggal'],
                $row['kd_customer'],
                $row['nm_customer'],
                $row['no_delivery'],
                $row['source_documents'],
                $row['no_mobil'],
                $row['kd_barang'],
                $row['nm_barang'],
                $row['expired_date'],
                $row['lot'],
            ]);

            if (! isset($groupedRows[$groupKey])) {
                $groupedRows[$groupKey] = $row;
                continue;
            }

            $groupedRows[$groupKey]['qty'] += $row['qty'];
            $groupedRows[$groupKey]['qty_kg'] += $row['qty_kg'];
        }

        $rows = array_values($groupedRows);

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
    private function productTemplateIds(int $selectedCustomerId, array $products): array
    {
        $templateIds = [];
        foreach ($products as $product) {
            if ((int) $product['customer_id'] === $selectedCustomerId) {
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
        ?int $selectedProductId,
        int $selectedCustomerId,
        array $products,
        string $startDate,
        string $endDate,
    ): array {
        if ($selectedProductId !== null) {
            return $this->computeRows($odoo, $selectedProductId, $selectedCustomerId, $startDate, $endDate);
        }

        $byItem = [];
        foreach ($this->productTemplateIds($selectedCustomerId, $products) as $templateId) {
            foreach ($this->computeRows($odoo, $templateId, $selectedCustomerId, $startDate, $endDate) as $row) {
                $key = (string) ($row['kd_barang'] ?? '').'|'.(string) ($row['nm_barang'] ?? '');
                $byItem[$key][] = $row;
            }
        }

        $keys = array_keys($byItem);
        usort($keys, fn ($a, $b) => strcmp($a, $b));

        $rows = [];
        foreach ($keys as $key) {
            $items = $byItem[$key];
            usort($items, function ($a, $b) {
                return strcmp((string) $a['tanggal'], (string) $b['tanggal'])
                    ?: strcmp((string) $a['no_delivery'], (string) $b['no_delivery']);
            });

            $qty = 0.0;
            $qtyKg = 0.0;
            foreach ($items as $item) {
                $qty += (float) $item['qty'];
                $qtyKg += (float) $item['qty_kg'];
                $rows[] = $item;
            }

            $first = $items[0];
            $rows[] = [
                'tanggal' => null,
                'kd_customer' => null,
                'nm_customer' => null,
                'no_delivery' => null,
                'source_documents' => null,
                'no_mobil' => null,
                'kd_barang' => $first['kd_barang'],
                'nm_barang' => $first['nm_barang'],
                'qty' => $qty,
                'qty_kg' => $qtyKg,
                'uom' => null,
                'expired_date' => null,
                'lot' => null,
                'is_subtotal' => true,
            ];
        }

        return $rows;
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
     * @param  array<int, int>  $lotIds
     * @return array<int, array{name: string, expiration_date: string|null}>
     */
    private function fetchLots(OdooXmlRpcService $odoo, array $lotIds): array
    {
        if ($lotIds === []) {
            return [];
        }

        $lots = $odoo->searchRead(
            'stock.lot',
            ['id', 'name', 'expiration_date'],
            null,
            [['id', 'in', $lotIds]],
        );

        $map = [];
        foreach ($lots as $lot) {
            $expiration = $lot['expiration_date'] ?? false;
            $map[(int) $lot['id']] = [
                'name' => (string) ($lot['name'] ?? ''),
                'expiration_date' => ($expiration !== false && $expiration !== null && $expiration !== '')
                    ? (string) $expiration
                    : null,
            ];
        }

        return $map;
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
