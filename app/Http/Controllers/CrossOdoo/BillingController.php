<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Services\CrossOdoo\BillingLedgerService;
use App\Services\OdooXmlRpcService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillingController
{
    public function __construct(private BillingLedgerService $ledgerService) {}

    public function index(Request $request): Response
    {
        $odoo = new OdooXmlRpcService;
        $catalog = $this->ledgerService->catalog($odoo);
        $customers = $catalog['customers'];
        $products = $catalog['products'];

        $selection = $this->ledgerService->resolveSelection(
            $request->integer('customer_id') ?: null,
            null,
            $customers,
            $products,
        );
        $selectedCustomerId = $selection['selectedCustomerId'];
        $productSelection = $this->resolveProductSelection($request, (int) $selectedCustomerId, $products);
        $selectedProductIds = $productSelection['selectedProductIds'];
        $selectedProductId = $productSelection['selectedProductId'];
        $customerName = $selection['customerName'];
        $productName = $productSelection['productName'];
        $today = now();
        $startDate = $today->copy()->startOfMonth()->toDateString();
        $endDate = $today->copy()->endOfMonth()->toDateString();
        $requestedStartDate = (string) $request->input('start_date', '');
        $requestedEndDate = (string) $request->input('end_date', '');
        if ($requestedStartDate !== '' && $requestedEndDate !== '') {
            try {
                $rangeStart = Carbon::createFromFormat('!Y-m-d', $requestedStartDate);
                $rangeEnd = Carbon::createFromFormat('!Y-m-d', $requestedEndDate);
                $maximumEndDate = $rangeStart->copy()->addDays(30);
                if ($rangeStart->lte($rangeEnd) && $rangeEnd->lte($maximumEndDate)) {
                    $startDate = $rangeStart->toDateString();
                    $endDate = $rangeEnd->toDateString();
                }
            } catch (\Throwable) {
                // Fall back to the selected month when the range is invalid.
            }
        }
        $rows = [];
        $totalRows = 0;
        $dailySummaries = [];
        $dailyTransactionPallets = [];

        if ($selectedProductIds !== [] && $selectedCustomerId !== null) {
            $ledger = $this->ledgerService->ledgers(
                $odoo,
                $selectedProductIds,
                (int) $selectedCustomerId,
                $startDate,
                $endDate,
            );
            $totalRows = $ledger['totalRows'];
            $rows = $ledger['rows'];
            $dailySummaries = $ledger['dailySummaries'];
            $dailyTransactionPallets = $this->calculateDailyTransactionPallets(
                $ledger['rows'],
                $productSelection['selectedProductIds'],
                $products,
                $ledger['productDailySummaries'],
            );
        }

        return Inertia::render('GMISL/CrossOdoo/Billing/Index', [
            'rows' => $rows,
            'customers' => $customers,
            'products' => $products,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => $selectedProductId,
            'selectedProductIds' => $productSelection['selectedProductIdsForUi'],
            'allProducts' => $productSelection['allProducts'],
            'customerName' => $customerName,
            'productName' => $productName,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalRows' => $totalRows,
            'dailySummaries' => $dailySummaries,
            'dailyTransactionPallets' => $dailyTransactionPallets,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $odoo = new OdooXmlRpcService;
        $catalog = $this->ledgerService->catalog($odoo);
        $selection = $this->ledgerService->resolveSelection(
            $request->integer('customer_id') ?: null,
            null,
            $catalog['customers'],
            $catalog['products'],
        );
        $productSelection = $this->resolveProductSelection(
            $request,
            (int) $selection['selectedCustomerId'],
            $catalog['products'],
        );
        $selectedProductIds = $productSelection['selectedProductIds'];
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $headerColumns = [
            'Date', 'Start Qty', 'Start Pallet', 'Inbound Qty', 'Inbound_Pallet',
            'Outbound_Qty', 'Outbound_Pallet', 'Adjust_Qty', 'Adjust_Pallet',
            'End Qty', 'End Pallet', 'Storage',
        ];
        $detailColumns = [
            'Tanggal', 'Jam', 'Owner', 'Transaksi', 'Destination', 'Nama barang',
            'Doc', 'Expired', 'Location', 'To', 'Saldo', 'IN', 'OUT',
        ];
        $headerData = [];
        $detailData = [];
        $productStandards = [];
        foreach ($catalog['products'] as $product) {
            $productId = (int) $product['product_id'];
            if (in_array($productId, $selectedProductIds, true)) {
                $productStandards[$productId] = $product['pallet_qty'];
            }
        }

        if ($selectedProductIds !== [] && $selection['selectedCustomerId'] !== null) {
            $ledger = $this->ledgerService->ledgers(
                $odoo,
                $selectedProductIds,
                (int) $selection['selectedCustomerId'],
                $startDate,
                $endDate,
            );
            $dailyTransactionPallets = $this->calculateDailyTransactionPallets(
                $ledger['rows'],
                $selectedProductIds,
                $catalog['products'],
                $ledger['productDailySummaries'],
            );
            $palletValue = static fn (?int $count): int|string => $count !== null && $count > 0
                ? $count
                : '-';
            $date = Carbon::createFromFormat('!Y-m-d', $startDate);
            $lastDate = Carbon::createFromFormat('!Y-m-d', $endDate);
            while ($date->lte($lastDate)) {
                $dateKey = $date->toDateString();
                $summary = $ledger['dailySummaries'][$dateKey] ?? [];
                $transactionPallets = $dailyTransactionPallets[$dateKey] ?? [];
                $startPallet = $transactionPallets['start'] ?? null;
                $inboundPallet = $transactionPallets['in'] ?? null;
                $outboundPallet = $transactionPallets['out'] ?? null;
                $adjustPallet = $transactionPallets['adjust'] ?? null;
                $endPallet = $transactionPallets['end'] ?? null;
                $storage = $transactionPallets['storage'] ?? null;
                $opening = (float) ($summary['opening'] ?? 0);
                $inbound = (float) ($summary['in'] ?? 0);
                $outbound = (float) ($summary['out'] ?? 0);
                $adjust = (float) ($summary['adjust'] ?? 0);
                $closing = (float) ($summary['closing'] ?? ($opening + $inbound - $outbound + $adjust));
                $headerData[] = [
                    $dateKey,
                    $opening,
                    $palletValue($startPallet),
                    $inbound,
                    $palletValue($transactionPallets['in'] ?? null),
                    $outbound,
                    $palletValue($transactionPallets['out'] ?? null),
                    $adjust !== 0.0 ? $adjust : '-',
                    $palletValue($adjustPallet),
                    $closing,
                    $palletValue($endPallet),
                    $palletValue($storage),
                ];
                $date->addDay();
            }

            if ($headerData !== []) {
                $sumHeaderColumn = static fn (int $column): float => array_sum(array_map(
                    static fn (array $row): float => is_numeric($row[$column] ?? null) ? (float) $row[$column] : 0.0,
                    $headerData,
                ));
                $subtotalStartPallet = $sumHeaderColumn(2);
                $subtotalInboundPallet = $sumHeaderColumn(4);
                $subtotalOutboundPallet = $sumHeaderColumn(6);
                $subtotalAdjustPallet = $sumHeaderColumn(8);
                $subtotalEndPallet = $sumHeaderColumn(10);
                $subtotalStorage = $sumHeaderColumn(11);
                $headerData[] = [
                    'SUB TOTAL',
                    $sumHeaderColumn(1),
                    $subtotalStartPallet > 0 ? $subtotalStartPallet : '-',
                    $sumHeaderColumn(3),
                    $subtotalInboundPallet > 0 ? $subtotalInboundPallet : '-',
                    $sumHeaderColumn(5),
                    $subtotalOutboundPallet > 0 ? $subtotalOutboundPallet : '-',
                    $sumHeaderColumn(7) !== 0.0 ? $sumHeaderColumn(7) : '-',
                    $subtotalAdjustPallet !== 0.0 ? $subtotalAdjustPallet : '-',
                    $sumHeaderColumn(9),
                    $subtotalEndPallet > 0 ? $subtotalEndPallet : '-',
                    $subtotalStorage > 0 ? $subtotalStorage : '-',
                ];
            }

            foreach ($ledger['rows'] as $row) {
                $dateTimes = $this->formatExportDateTimes($row);
                $detailData[] = [
                    implode("\n", array_column($dateTimes, 'date')),
                    implode("\n", array_column($dateTimes, 'time')),
                    $row['Owner'] ?? null,
                    $row['Transaksi'] ?? null,
                    $row['Destination package'] ?? null,
                    $row['Nama barang'] ?? null,
                    $row['Source Document'] ?? null,
                    $this->formatExpiredDate($row['Expired'] ?? null),
                    $row['Location'] ?? null,
                    $row['To'] ?? null,
                    (float) ($row['Saldo Awal'] ?? 0),
                    (float) ($row['In'] ?? 0),
                    (float) ($row['Out'] ?? 0),
                ];
            }
        }

        $spreadsheet = new Spreadsheet;
        $headerSheet = $spreadsheet->getActiveSheet();
        $headerSheet->setTitle('Header');
        $headerSheet->fromArray($headerColumns, null, 'A1');
        if ($headerData !== []) {
            $headerSheet->fromArray($headerData, null, 'A2');
        }

        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle('Detail');
        $detailSheet->fromArray($detailColumns, null, 'A1');
        if ($detailData !== []) {
            $detailSheet->fromArray($detailData, null, 'A2');
        }

        $safePart = preg_replace('/[^A-Za-z0-9\-_]+/', '_', (string) $productSelection['productName']);
        $filename = 'billing_'.($safePart !== '' ? $safePart : 'product').'_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array{selectedProductIds: array<int, int>, selectedProductIdsForUi: array<int, int>, selectedProductId: int|null, productName: string, allProducts: bool}
     */
    private function resolveProductSelection(Request $request, int $customerId, array $products): array
    {
        $customerProducts = array_values(array_filter(
            $products,
            fn (array $product): bool => (int) $product['customer_id'] === $customerId,
        ));
        $rawProductIds = $request->input('product_id');
        $rawProductIds = $rawProductIds === null
            ? []
            : (is_array($rawProductIds) ? $rawProductIds : [$rawProductIds]);
        $selectionProvided = $rawProductIds !== [];
        $requestedProductIds = [];
        foreach ($rawProductIds as $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $productId = filter_var($value, FILTER_VALIDATE_INT);
            if ($productId !== false && $productId >= 0) {
                $requestedProductIds[] = $productId;
            }
        }
        $requestedProductIds = array_values(array_unique($requestedProductIds));
        $allProducts = ! $selectionProvided || in_array(0, $requestedProductIds, true);

        $selectedProducts = $allProducts
            ? $customerProducts
            : array_values(array_filter(
                $customerProducts,
                fn (array $product): bool => in_array((int) $product['product_id'], $requestedProductIds, true),
            ));
        $selectedProductIds = array_map(
            fn (array $product): int => (int) $product['product_id'],
            $selectedProducts,
        );
        $productName = $allProducts
            ? 'All Item'
            : implode(', ', array_map(
                fn (array $product): string => (string) $product['product_name'],
                $selectedProducts,
            ));

        return [
            'selectedProductIds' => $selectedProductIds,
            'selectedProductIdsForUi' => $allProducts ? [] : $selectedProductIds,
            'selectedProductId' => ! $allProducts && count($selectedProductIds) === 1
                ? $selectedProductIds[0]
                : null,
            'productName' => $productName,
            'allProducts' => $allProducts,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $selectedProductIds
     * @param  array<int, array<string, mixed>>  $products
     * @param  array<string, array<int, array<string, float>>>  $productDailySummaries
     * @return array<string, array{start: int|null, in: int|null, out: int|null, adjust: int|null, end: int|null, storage: int|null}>
     */
    private function calculateDailyTransactionPallets(
        array $rows,
        array $selectedProductIds,
        array $products,
        array $productDailySummaries,
    ): array {
        $productStandards = [];
        foreach ($products as $product) {
            $productId = (int) $product['product_id'];
            if (in_array($productId, $selectedProductIds, true)) {
                $productStandards[$productId] = $product['pallet_qty'] ?? null;
            }
        }

        $inboundPallets = [];
        foreach ($rows as $row) {
            $date = (string) ($row['Date'] ?? '');
            $productId = (int) ($row['product_id'] ?? 0);
            $standard = $productStandards[$productId] ?? null;
            if ($date === '' || ! is_numeric($standard) || (float) $standard <= 0) {
                continue;
            }

            $quantity = (float) ($row['In'] ?? 0);
            if ($quantity > 0) {
                $inboundPallets[$date] = ($inboundPallets[$date] ?? 0)
                    + (int) ceil($quantity / (float) $standard);
            }
        }

        ksort($productDailySummaries);
        $dailyPallets = [];
        $previousEndPallet = null;
        foreach ($productDailySummaries as $date => $productSummaries) {
            $hasStandard = false;
            $openingPallet = 0;
            $outboundPallet = 0;
            $adjustPallet = 0;

            foreach ($productSummaries as $productId => $summary) {
                $standard = $productStandards[(int) $productId] ?? null;
                if (! is_numeric($standard) || (float) $standard <= 0) {
                    continue;
                }

                $hasStandard = true;
                $openingPallet += max(
                    0,
                    (int) floor((float) ($summary['opening'] ?? 0) / (float) $standard),
                );
                $outbound = (float) ($summary['out'] ?? 0);
                if ($outbound > 0) {
                    $outboundPallet += (int) floor($outbound / (float) $standard);
                }

                $adjust = (float) ($summary['adjust'] ?? 0);
                if ($adjust !== 0.0) {
                    $adjustPallet += (int) (
                        ceil(abs($adjust) / (float) $standard)
                        * ($adjust < 0 ? -1 : 1)
                    );
                }
            }

            if (! $hasStandard) {
                $dailyPallets[$date] = [
                    'start' => null,
                    'in' => null,
                    'out' => null,
                    'adjust' => null,
                    'end' => null,
                    'storage' => null,
                ];

                continue;
            }

            $startPallet = $previousEndPallet ?? $openingPallet;
            $inboundPallet = (int) ($inboundPallets[$date] ?? 0);
            $storage = $startPallet + $inboundPallet;
            $endPallet = $storage - $outboundPallet + $adjustPallet;
            $dailyPallets[$date] = [
                'start' => $startPallet,
                'in' => $inboundPallet,
                'out' => $outboundPallet,
                'adjust' => $adjustPallet,
                'end' => $endPallet,
                'storage' => $storage,
            ];
            $previousEndPallet = $endPallet;
        }

        return $dailyPallets;
    }

    /** @return array{0: string, 1: string} */
    private function resolveDateRange(Request $request): array
    {
        $today = now();
        $startDate = $today->copy()->startOfMonth()->toDateString();
        $endDate = $today->copy()->endOfMonth()->toDateString();
        try {
            $rangeStart = Carbon::createFromFormat('!Y-m-d', (string) $request->input('start_date'));
            $rangeEnd = Carbon::createFromFormat('!Y-m-d', (string) $request->input('end_date'));
            if ($rangeStart->lte($rangeEnd) && $rangeEnd->lte($rangeStart->copy()->addDays(30))) {
                $startDate = $rangeStart->toDateString();
                $endDate = $rangeEnd->toDateString();
            }
        } catch (\Throwable) {
            // Keep the current-month fallback.
        }

        return [$startDate, $endDate];
    }

    /** @return array<int, array{date: string, time: string}> */
    private function formatExportDateTimes(array $row): array
    {
        $times = is_array($row['Times'] ?? null) ? $row['Times'] : [];
        if ($times === []) {
            $times = [(string) ($row['Date'] ?? '')];
        }

        return array_map(function (string $value): array {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/', $value, $matches) === 1) {
                return [
                    'date' => $matches[3].'/'.$matches[2].'/'.substr($matches[1], -2),
                    'time' => isset($matches[4], $matches[5]) ? $matches[4].':'.$matches[5] : '-',
                ];
            }

            return ['date' => $value, 'time' => '-'];
        }, $times);
    }

    private function formatExpiredDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $matches) === 1
            ? $matches[0]
            : $value;
    }
}
