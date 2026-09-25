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
        $headers = [
            'Date', 'Owner', 'Transaksi', 'Destination package', 'Kode barang',
            'Nama barang', 'Source Document', 'Expired', 'Location', 'To',
            'Saldo Awal', 'IN', 'OUT', 'Saldo Akhir',
        ];
        $data = [];

        if ($selection['selectedProductId'] !== null && $selection['selectedCustomerId'] !== null) {
            $ledger = $this->ledgerService->ledger(
                $odoo,
                (int) $selection['selectedProductId'],
                (int) $selection['selectedCustomerId'],
                $startDate,
                $endDate,
            );
            foreach ($ledger['rows'] as $row) {
                $data[] = [
                    implode("\n", $this->formatExportTimes($row)),
                    $row['Owner'] ?? null,
                    $row['Transaksi'] ?? null,
                    $row['Destination package'] ?? null,
                    $row['Kode barang'] ?? null,
                    $row['Nama barang'] ?? null,
                    $row['Source Document'] ?? null,
                    $row['Expired'] ?? null,
                    $row['Location'] ?? null,
                    $row['To'] ?? null,
                    (float) ($row['Saldo Awal'] ?? 0),
                    (float) ($row['In'] ?? 0),
                    (float) ($row['Out'] ?? 0),
                    (float) ($row['Saldo Akhir'] ?? 0),
                ];
            }
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($data, null, 'A2');
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

    /** @return array<int, string> */
    private function formatExportTimes(array $row): array
    {
        $times = is_array($row['Times'] ?? null) ? $row['Times'] : [];
        return $times !== [] ? $times : [(string) ($row['Date'] ?? '')];
    }
}