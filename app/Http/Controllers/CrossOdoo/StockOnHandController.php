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

class StockOnHandController extends Controller
{
    private const HISTORY_START_DATE = '2024-01-01';

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
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        $rows = [];
        $totalRows = 0;
        $totalSoh = 0.0;
        $totalQtySoh = 0.0;
        $totalReserve = 0.0;
        $totalQtyAvailable = 0.0;

        if ($selectedProductId !== null) {
            $odoo = new OdooXmlRpcService;

            $grouped = $this->computeGroupedRows($odoo, (int) $selectedProductId, (int) $selectedCustomerId, $endDate, $customerName);

            $totalRows = count($grouped);
            foreach ($grouped as $groupedRow) {
                $totalSoh += (float) $groupedRow['SOH Available'];
                $totalQtySoh += (float) $groupedRow['Qty SOH'];
                $totalReserve += (float) $groupedRow['Qty Reserve'];
                $totalQtyAvailable += (float) $groupedRow['On Hand Available'];
            }
            $page = min($page, max(1, (int) ceil($totalRows / $perPage)));
            $offset = ($page - 1) * $perPage;
            $rows = array_slice($grouped, $offset, $perPage);
        }

        return Inertia::render('GMISL/CrossOdoo/SOH/Index', [
            'rows' => $rows,
            'customers' => $customers,
            'products' => $products,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => $selectedProductId,
            'customerName' => $customerName,
            'productName' => $productName,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'totalSoh' => $totalSoh,
            'totalQtySoh' => $totalQtySoh,
            'totalReserve' => $totalReserve,
            'totalOnHandAvailable' => $totalQtyAvailable,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$customers, $products] = $this->fetchCustomersAndProducts();

        $selection = $this->resolveSelection($request, $customers, $products);
        $selectedCustomerId = $selection['selectedCustomerId'];
        $selectedProductId = $selection['selectedProductId'];
        $customerName = $selection['customerName'];

        $endDate = $request->input('end_date') ?: now()->toDateString();

        $headers = [
            'Owner',
            'Location',
            'Destination package',
            'Kode barang',
            'Nama barang',
            'Preference',
            'Source Document',
            'Expired',
            'Available',
            'Qty SOH (KG)',
            'Qty Reserve',
            'On Hand Available',
            'UOM',
            'Lot',
            'Nopol',
        ];
        $data = [];

        if ($selectedProductId !== null) {
            $odoo = new OdooXmlRpcService;

            $grouped = $this->computeGroupedRows($odoo, (int) $selectedProductId, (int) $selectedCustomerId, $endDate, $customerName);

            foreach ($grouped as $row) {
                $data[] = [
                    $row['Owner'],
                    $row['Location'],
                    $row['Destination package'],
                    $row['Kode barang'],
                    $row['Nama barang'],
                    $row['Preference'],
                    $row['Source Document'],
                    $row['Expired'],
                    (float) $row['SOH Available'],
                    (float) $row['Qty SOH'],
                    (float) $row['Qty Reserve'],
                    (float) $row['On Hand Available'],
                    $row['UOM'],
                    $row['Lot'],
                    $row['Nopol'],
                ];
            }
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($data, null, 'A2');

        $safePart = $selection['productName'] ?? 'product';
        $safePart = preg_replace('/[^A-Za-z0-9\-_]+/', '_', (string) $safePart);
        $filename = 'soh_'.($safePart !== '' ? $safePart : 'product').'_'.now()->format('Ymd_His').'.xlsx';

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
     * @return array<int, array<string, mixed>>
     */
    private function computeGroupedRows(OdooXmlRpcService $odoo, int $selectedProductId, int $selectedCustomerId, string $endDate, ?string $customerName): array
    {
        $variantIds = $this->productVariantIds($odoo, $selectedProductId);

        if ($variantIds === []) {
            return [];
        }

        $template = $this->fetchTemplateDetails($odoo, $selectedProductId);
        $quants = $this->fetchStockQuants($odoo, $variantIds, $selectedCustomerId);

        if ($quants === []) {
            return [];
        }

        $cutoff = $this->fetchPostCutoffQuantities($odoo, $variantIds, $endDate);

        $locationIds = [];
        $lotIds = [];
        $packageIds = [];
        foreach ($quants as $quant) {
            $locationRef = $quant['location_id'] ?? false;
            if (is_array($locationRef) && isset($locationRef[0])) {
                $locationIds[(int) $locationRef[0]] = true;
            }

            $lotRef = $quant['lot_id'] ?? false;
            if (is_array($lotRef) && isset($lotRef[0])) {
                $lotIds[(int) $lotRef[0]] = true;
            }

            $packageRef = $quant['package_id'] ?? false;
            if (is_array($packageRef) && isset($packageRef[0])) {
                $packageIds[(int) $packageRef[0]] = true;
            }
        }

        $locationInfos = $this->fetchLocationInfos($odoo, array_keys($locationIds));
        $lotInfos = $this->fetchLotInfos($odoo, array_keys($lotIds));
        $packageInfos = $this->fetchPackageInfos($odoo, array_keys($packageIds));
        $history = $this->fetchMovementHistory($odoo, $variantIds);

        return $this->aggregateRows(
            $quants,
            $template,
            $cutoff,
            $locationInfos,
            $lotInfos,
            $packageInfos,
            $history,
            $customerName,
        );
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
     * @return array<string, mixed>
     */
    private function fetchTemplateDetails(OdooXmlRpcService $odoo, int $templateId): array
    {
        $templates = $odoo->searchRead(
            'product.template',
            ['id', 'default_code', 'name', 'weight', 'uom_id'],
            null,
            [['id', '=', $templateId]],
            ['lang' => 'en_US'],
        );

        if ($templates === []) {
            return ['default_code' => null, 'name' => null, 'weight' => 1.0, 'uom' => null];
        }

        $template = $templates[0];

        $uomId = is_array($template['uom_id'] ?? false) ? (int) $template['uom_id'][0] : null;
        $uomName = null;
        if ($uomId !== null) {
            $uoms = $odoo->searchRead(
                'uom.uom',
                ['id', 'name'],
                null,
                [['id', '=', $uomId]],
                ['lang' => 'en_US'],
            );
            $uomName = isset($uoms[0]['name']) ? (string) $uoms[0]['name'] : null;
        }

        return [
            'default_code' => ($template['default_code'] ?? false) !== false ? (string) $template['default_code'] : null,
            'name' => (string) $template['name'],
            'weight' => (float) ($template['weight'] ?? 1) ?: 1.0,
            'uom' => $uomName,
        ];
    }

    /**
     * Ambil kuantitas stock dari lokasi internal dan transit (usage internal/transit).
     *
     * @param  array<int, int>  $variantIds
     * @return array<int, array<string, mixed>>
     */
    private function fetchStockQuants(OdooXmlRpcService $odoo, array $variantIds, int $customerId): array
    {
        return $odoo->searchRead(
            'stock.quant',
            ['id', 'product_id', 'lot_id', 'location_id', 'package_id', 'quantity', 'reserved_quantity', 'ns_weight', 'owner_id', 'ns_plate_number'],
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
    }

    /**
     * Hitung ulang pergerakan stock setelah tanggal cutoff.
     * Mengembalikan map key "packageId|productId|lotId" => delta kuantitas.
     *
     * @param  array<int, int>  $variantIds
     * @return array<string, float>
     */
    private function fetchPostCutoffQuantities(OdooXmlRpcService $odoo, array $variantIds, string $endDate): array
    {
        $nextDay = Carbon::parse($endDate)->addDay()->toDateString();

        $lines = $odoo->searchRead(
            'stock.move.line',
            ['date', 'quantity', 'product_id', 'lot_id', 'package_id', 'result_package_id', 'location_id', 'location_dest_id', 'picking_type_id'],
            null,
            [
                ['state', '=', 'done'],
                ['product_id', 'in', $variantIds],
                ['date', '>=', $nextDay.' 00:00:00'],
            ],
        );

        if ($lines === []) {
            return [];
        }

        $locationUsages = $this->fetchLocationUsages($odoo, $lines);

        $cutoff = [];
        foreach ($lines as $line) {
            $direction = $this->movementDirection($line, $locationUsages);

            if ($direction !== 'in' && $direction !== 'out') {
                continue;
            }

            $packageRef = $line['package_id'] ?? false;
            $resultRef = $line['result_package_id'] ?? false;
            $packageId = is_array($packageRef) && isset($packageRef[0])
                ? (int) $packageRef[0]
                : (is_array($resultRef) && isset($resultRef[0]) ? (int) $resultRef[0] : null);

            if ($packageId === null) {
                continue;
            }

            $productId = (int) $line['product_id'][0];
            $lotRef = $line['lot_id'] ?? false;
            $lotId = is_array($lotRef) ? (int) $lotRef[0] : null;
            $key = $packageId.'|'.$productId.'|'.($lotId ?? '');

            $delta = $direction === 'in' ? (float) $line['quantity'] : -((float) $line['quantity']);
            $cutoff[$key] = ($cutoff[$key] ?? 0.0) + $delta;
        }

        return $cutoff;
    }

    /**
     * Ambil reference inbound pertama, origin picking inbound terakhir, dan plat
     * nomor kendaraan untuk setiap kombinasi (product, lot).
     *
     * @param  array<int, int>  $variantIds
     * @return array<string, array<string, array<string, string>>>
     */
    private function fetchMovementHistory(OdooXmlRpcService $odoo, array $variantIds): array
    {
        $lines = $odoo->searchRead(
            'stock.move.line',
            ['id', 'product_id', 'lot_id', 'reference', 'picking_id', 'location_id', 'location_dest_id', 'picking_type_id'],
            null,
            [
                ['state', '=', 'done'],
                ['product_id', 'in', $variantIds],
                ['date', '>=', self::HISTORY_START_DATE.' 00:00:00'],
            ],
        );

        if ($lines === []) {
            return ['preferences' => [], 'sourceDocs' => [], 'nopols' => []];
        }

        $locationUsages = $this->fetchLocationUsages($odoo, $lines);

        $pickingIds = [];
        foreach ($lines as $line) {
            $picking = $line['picking_id'] ?? false;
            if (is_array($picking) && isset($picking[0])) {
                $pickingIds[(int) $picking[0]] = true;
            }
        }

        $pickingMap = $this->fetchPickingMap($odoo, array_keys($pickingIds));

        $preferences = [];
        $sourceDocs = [];
        $nopols = [];

        foreach ($lines as $line) {
            $productId = (int) $line['product_id'][0];
            $lotRef = $line['lot_id'] ?? false;
            $lotId = is_array($lotRef) ? (int) $lotRef[0] : null;
            $key = $productId.'|'.($lotId ?? '');
            $id = (int) $line['id'];

            $direction = $this->movementDirection($line, $locationUsages);

            if ($direction === 'in' && ($line['reference'] ?? false) !== false) {
                $reference = (string) $line['reference'];
                if ($reference !== '' && (! isset($preferences[$key]) || $id < $preferences[$key]['id'])) {
                    $preferences[$key] = ['id' => $id, 'value' => $reference];
                }
            }

            $picking = $line['picking_id'] ?? false;
            $pickingId = is_array($picking) ? (int) $picking[0] : null;
            $origin = $pickingId !== null ? ($pickingMap[$pickingId]['origin'] ?? null) : null;
            $plate = $pickingId !== null ? ($pickingMap[$pickingId]['plate'] ?? null) : null;

            if ($direction === 'in' && $origin !== null && $origin !== '' && (! isset($sourceDocs[$key]) || $id > $sourceDocs[$key]['id'])) {
                $sourceDocs[$key] = ['id' => $id, 'value' => $origin];
            }

            if ($plate !== null && $plate !== '' && ! isset($nopols[$key])) {
                $nopols[$key] = $plate;
            }
        }

        return [
            'preferences' => array_map(fn ($item) => $item['value'], $preferences),
            'sourceDocs' => array_map(fn ($item) => $item['value'], $sourceDocs),
            'nopols' => $nopols,
        ];
    }

    /**
     * @param  array<int, int>  $pickingIds
     * @return array<int, array{origin: string|null, plate: string|null}>
     */
    private function fetchPickingMap(OdooXmlRpcService $odoo, array $pickingIds): array
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
                'origin' => ($origin !== false && $origin !== null && $origin !== '') ? (string) $origin : null,
                'plate' => ($plate !== false && $plate !== null && $plate !== '') ? (string) $plate : null,
            ];
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $locationIds
     * @return array<int, string>
     */
    private function fetchLocationInfos(OdooXmlRpcService $odoo, array $locationIds): array
    {
        if ($locationIds === []) {
            return [];
        }

        $locations = $odoo->searchRead(
            'stock.location',
            ['id', 'complete_name'],
            null,
            [['id', 'in', $locationIds]],
        );

        $map = [];
        foreach ($locations as $location) {
            $map[(int) $location['id']] = (string) ($location['complete_name'] ?? '');
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $lotIds
     * @return array<int, array{expiration: string|null}>
     */
    private function fetchLotInfos(OdooXmlRpcService $odoo, array $lotIds): array
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

            $map[(int) $lot['id']] = [
                'expiration' => ($expiration !== false && $expiration !== null && $expiration !== '')
                    ? (string) $expiration
                    : null,
            ];
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $packageIds
     * @return array<int, array{name: string, plate: string|null}>
     */
    private function fetchPackageInfos(OdooXmlRpcService $odoo, array $packageIds): array
    {
        if ($packageIds === []) {
            return [];
        }

        $packages = $odoo->searchRead(
            'stock.quant.package',
            ['id', 'name', 'ns_plate_number'],
            null,
            [['id', 'in', $packageIds]],
        );

        $map = [];
        foreach ($packages as $package) {
            $plate = $package['ns_plate_number'] ?? false;

            $map[(int) $package['id']] = [
                'name' => (string) ($package['name'] ?? ''),
                'plate' => ($plate !== false && $plate !== null && $plate !== '') ? (string) $plate : null,
            ];
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

    /**
     * @param  array<int, array<string, mixed>>  $quants
     * @param  array<string, mixed>  $template
     * @param  array<string, float>  $cutoff
     * @param  array<int, string>  $locations
     * @param  array<int, array{expiration: string|null}>  $lots
     * @param  array<int, array{name: string, plate: string|null}>  $packages
     * @param  array<string, array<string, array<string, string>>>  $history
     * @return array<int, array<string, mixed>>
     */
    private function aggregateRows(
        array $quants,
        array $template,
        array $cutoff,
        array $locations,
        array $lots,
        array $packages,
        array $history,
        ?string $customerName,
    ): array {
        $groups = [];

        foreach ($quants as $quant) {
            $productRef = $quant['product_id'] ?? false;
            if (! is_array($productRef) || ! isset($productRef[0])) {
                continue;
            }
            $productId = (int) $productRef[0];

            $locationRef = $quant['location_id'] ?? false;
            if (! is_array($locationRef) || ! isset($locationRef[0])) {
                continue;
            }
            $locationId = (int) $locationRef[0];
            $locationName = $locations[$locationId] ?? (string) $locationRef[1];

            $lotRef = $quant['lot_id'] ?? false;
            $lotId = is_array($lotRef) ? (int) $lotRef[0] : null;
            $lotName = is_array($lotRef) ? (string) $lotRef[1] : null;

            $packageRef = $quant['package_id'] ?? false;
            $packageId = is_array($packageRef) ? (int) $packageRef[0] : null;
            $packageName = is_array($packageRef)
                ? (string) $packageRef[1]
                : ($packageId !== null ? ($packages[$packageId]['name'] ?? null) : null);

            $ownerRef = $quant['owner_id'] ?? false;
            $ownerName = is_array($ownerRef) ? (string) $ownerRef[1] : $customerName;

            $lotKey = $productId.'|'.($lotId ?? '');
            $cutoffKey = ($packageId ?? '').'|'.$productId.'|'.($lotId ?? '');
            $adjusted = (float) $quant['quantity'] - (float) ($cutoff[$cutoffKey] ?? 0.0);

            $expiredRaw = $lotId !== null ? ($lots[$lotId]['expiration'] ?? null) : null;
            $expired = $expiredRaw !== null ? substr((string) $expiredRaw, 0, 10) : null;

            $quantPlate = ($quant['ns_plate_number'] ?? false) !== false && $quant['ns_plate_number'] !== '' && $quant['ns_plate_number'] !== null
                ? (string) $quant['ns_plate_number']
                : null;
            $packagePlate = $packageId !== null ? ($packages[$packageId]['plate'] ?? null) : null;
            $historyPlate = $history['nopols'][$lotKey] ?? null;
            $nopol = $quantPlate ?? $packagePlate ?? $historyPlate;

            $groupKey = ($ownerName ?? '').'|'.($locationName ?? '').'|'.($packageName ?? '').'|'
                .$productId.'|'.($lotId ?? '').'|'.($expired ?? '').'|'.($nopol ?? '');

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'Owner' => $ownerName ?? $customerName ?? '-',
                    'Location' => $locationName,
                    'Destination package' => $packageName,
                    'Kode barang' => $template['default_code'],
                    'Nama barang' => $template['name'],
                    'Preference' => $history['preferences'][$lotKey] ?? null,
                    'Source Document' => $history['sourceDocs'][$lotKey] ?? null,
                    'Expired' => $expired,
                    'SOH Available' => 0.0,
                    'Qty SOH' => 0.0,
                    'Qty Reserve' => 0.0,
                    'UOM' => $template['uom'],
                    'Lot' => $lotName,
                    'Nopol' => $nopol,
                ];
            }

            $groups[$groupKey]['SOH Available'] += $adjusted;
            $groups[$groupKey]['Qty SOH'] += (float) ($quant['ns_weight'] ?? 0.0);
            $groups[$groupKey]['Qty Reserve'] += (float) ($quant['reserved_quantity'] ?? 0.0);
        }

        $groups = array_filter($groups, fn ($group) => (float) $group['SOH Available'] > 0);

        foreach ($groups as &$group) {
            $group['On Hand Available'] = (float) $group['SOH Available'] - (float) $group['Qty Reserve'];
        }

        unset($group);

        uasort($groups, function ($a, $b) {
            $aname = (string) ($a['Destination package'] ?? '');
            $bname = (string) ($b['Destination package'] ?? '');

            if ($aname === $bname) {
                if ($a['Destination package'] === $b['Destination package']) {
                    return strcmp((string) $a['Owner'], (string) $b['Owner']);
                }

                return $a['Destination package'] === null ? -1 : 1;
            }

            return strcmp($aname, $bname);
        });

        return array_values($groups);
    }
}
