<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Services\OdooXmlRpcService;
use App\Services\CrossOdoo\BillingLedgerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillingController
{
    public function __construct(private BillingLedgerService $ledgerService)
    {
    }

    public function index(Request $request): Response
    {
        $odoo = new OdooXmlRpcService;
        $catalog = $this->ledgerService->catalog($odoo);
        $customers = $catalog['customers'];
        $products = $catalog['products'];

        $selection = $this->ledgerService->resolveSelection(
            $request->integer('customer_id') ?: null,
            $request->integer('product_id') ?: null,
            $customers,
            $products,
        );
        $selectedCustomerId = $selection['selectedCustomerId'];
        $selectedProductId = $selection['selectedProductId'];
        $customerName = $selection['customerName'];
        $productName = $selection['productName'];
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

        if ($selectedProductId !== null && $selectedCustomerId !== null) {
            $ledger = $this->ledgerService->ledger(
                $odoo,
                (int) $selectedProductId,
                (int) $selectedCustomerId,
                $startDate,
                $endDate,
            );
            $totalRows = $ledger['totalRows'];
            $rows = $ledger['rows'];
            $dailySummaries = $ledger['dailySummaries'];
        }

        return Inertia::render('GMISL/CrossOdoo/Billing/Index', [
            'rows' => $rows,
            'customers' => $customers,
            'products' => $products,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => $selectedProductId,
            'customerName' => $customerName,
            'productName' => $productName,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalRows' => $totalRows,
            'dailySummaries' => $dailySummaries,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $odoo = new OdooXmlRpcService;
        $catalog = $this->ledgerService->catalog($odoo);
        $selection = $this->ledgerService->resolveSelection(
            $request->integer('customer_id') ?: null,
            $request->integer('product_id') ?: null,
            $catalog['customers'],
            $catalog['products'],
        );
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

        if ($selection['selectedProductId'] !== null && $selection['selectedCustomerId'] !== null) {
            $ledger = $this->ledgerService->ledger(
                $odoo,
                (int) $selection['selectedProductId'],
                (int) $selection['selectedCustomerId'],
                $startDate,
                $endDate,
            );
            $palletValue = static function (float $quantity): int|string {
                $pallets = (int) round($quantity / 40);

                return $pallets > 1 ? $pallets : '-';
            };
            $palletCount = static fn (float $quantity): int => $quantity > 0
                ? (int) round($quantity / 40)
                : 0;
            $date = Carbon::createFromFormat('!Y-m-d', $startDate);
            $lastDate = Carbon::createFromFormat('!Y-m-d', $endDate);
            while ($date->lte($lastDate)) {
                $dateKey = $date->toDateString();
                $summary = $ledger['dailySummaries'][$dateKey] ?? [];
                $opening = (float) ($summary['opening'] ?? 0);
                $inbound = (float) ($summary['in'] ?? 0);
                $outbound = (float) ($summary['out'] ?? 0);
                $closing = (float) ($summary['closing'] ?? ($opening + $inbound - $outbound));
                $startPallet = $palletCount($opening);
                $inboundPallet = $palletCount($inbound);
                $outboundPallet = $palletCount($outbound);
                $adjustPallet = 0;
                $endPallet = $startPallet + $inboundPallet - $outboundPallet + $adjustPallet;
                $storage = $startPallet + $inboundPallet;
                $headerData[] = [
                    $dateKey,
                    $opening,
                    $palletValue($opening),
                    $inbound,
                    $palletValue($inbound),
                    $outbound,
                    $palletValue($outbound),
                    0,
                    '-',
                    $closing,
                    $endPallet > 1 ? $endPallet : '-',
                    $storage > 1 ? $storage : '-',
                ];
                $date->addDay();
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

        $safePart = preg_replace('/[^A-Za-z0-9\-_]+/', '_', (string) ($selection['productName'] ?? 'product'));
        $filename = 'billing_'.($safePart !== '' ? $safePart : 'product').'_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
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