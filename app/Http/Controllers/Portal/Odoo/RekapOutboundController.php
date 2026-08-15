<?php

namespace App\Http\Controllers\Portal\Odoo;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\TProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RekapOutboundController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $userOwnerId = $user && !empty($user->from_owner_id) ? (string) $user->from_owner_id : null;

        $owners = Customer::query()
            ->whereNotNull('customers_id_odoo')
            ->where('customers_id_odoo', '!=', '')
            ->orderBy('name')
            ->get(['customers_id_odoo', 'name'])
            ->map(fn ($customer) => [
                'owner_id' => (string) $customer->customers_id_odoo,
                'owner_name' => $customer->name,
            ])
            ->values()
            ->all();

        $products = TProduct::query()
            ->whereNotNull('internal_reference')
            ->where('internal_reference', '!=', '')
            ->orderBy('internal_reference')
            ->get(['internal_reference', 'name', 'customer_id'])
            ->map(fn ($product) => [
                'owner_id' => $product->customer_id !== null && $product->customer_id !== '' ? (string) $product->customer_id : null,
                'product_id' => $product->internal_reference,
                'default_code' => $product->internal_reference,
                'product_name' => $product->name,
            ])
            ->values()
            ->all();

        $selectedOwnerId = $request->input('owner_id');
        if ($userOwnerId !== null) {
            $selectedOwnerId = $userOwnerId;
        }

        $targetProductId = $request->input('product_id');
        if ($targetProductId !== null && $targetProductId !== '') {
            $targetProductId = (string) $targetProductId;
        } else {
            $targetProductId = null;
        }

        $hasFilters = $request->query('start_date') !== null || $request->query('end_date') !== null;

        $defaultStartDate = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        $defaultEndDate = \Carbon\Carbon::now()->startOfMonth()->addMonth()->format('Y-m-d');

        $startDate = $defaultStartDate;
        $endDate = $defaultEndDate;
        $rows = [];
        $totalRows = 0;

        if ($hasFilters) {
            $startDate = $request->input('start_date', '');
            $endDate = $request->input('end_date', '');

            try {
                $start = new \DateTime($startDate);
                $end = new \DateTime($endDate);
            } catch (\Exception $exception) {
                $start = new \DateTime($defaultStartDate);
                $end = new \DateTime($defaultEndDate);
            }

            if ($end < $start) {
                $end = clone $start;
            }

            $startDate = $start->format('Y-m-d');
            $endDate = $end->format('Y-m-d');

            $customer = (string) ($selectedOwnerId ?? '');
            $product = (string) ($targetProductId ?? '');

            $rowsQuery = <<<'SQL'
SELECT
    DATE(t.`date`) AS tanggal,
    t.from_location AS kd_gudang,
    MAX(t.product_customer_reference) AS kd_customer,
    MAX(t.from_owner) AS nm_customer,
    GROUP_CONCAT(DISTINCT t.reference) AS no_delivery,
    GROUP_CONCAT(DISTINCT t.transfer_plate_number) AS no_mobil,
    GROUP_CONCAT(DISTINCT t.source_documents) AS source_documents,
    t.product_internal_reference AS kd_barang,
    MAX(t.product_name) AS nm_barang,
    SUM(t.quantity) AS qty,
    t.lot_serial_number AS lot,
    MAX(t.expiration_date) AS expired_date,
    MAX(t.unit_of_measure) AS uom
FROM t_move_history t
WHERE t.operation_type IN (
    'PT Golden Multi Indotama: Delivery Orders',
    'PT Golden Multi Indotama: Repack Outbound',
    'PT Golden Multi Indotama: Return Receipts'
)
  AND DATE(t.`date`) BETWEEN ? AND ?
  AND (? = '' OR t.from_owner_id = ?)
  AND (? = '' OR t.product_internal_reference = ?)
GROUP BY
    DATE(t.`date`),
    t.from_location,
    t.from_owner,
    t.product_internal_reference,
    t.lot_serial_number,
    t.unit_of_measure
ORDER BY
    tanggal,
    kd_gudang,
    kd_barang,
    lot;
SQL;

            $rows = DB::select($rowsQuery, [
                $startDate,
                $endDate,
                $customer,
                $customer,
                $product,
                $product,
            ]);
            $rows = array_map(fn ($row) => (array) $row, $rows);
            $totalRows = count($rows);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $page = min($page, max(1, (int) ceil($totalRows / $perPage)));

        return Inertia::render('Portal/Odoo/RekapOutbound/Index', [
            'rows' => $rows,
            'owners' => $owners,
            'products' => $products,
            'selectedOwnerId' => $selectedOwnerId,
            'targetProductId' => $targetProductId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'applied' => $hasFilters,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
        ]);
    }
}
