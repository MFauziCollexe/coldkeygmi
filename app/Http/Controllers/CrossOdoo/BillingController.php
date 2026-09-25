<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Services\OdooXmlRpcService;
use App\Services\CrossOdoo\BillingLedgerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Carbon;

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
}