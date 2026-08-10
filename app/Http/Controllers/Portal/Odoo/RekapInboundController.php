<?php

namespace App\Http\Controllers\Portal\Odoo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RekapInboundController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $userOwnerId = $user && !empty($user->from_owner_id) ? (string) $user->from_owner_id : null;

        $baseQuery = function () use ($userOwnerId) {
            $query = DB::table('t_move_history');

            if ($userOwnerId !== null) {
                $query->where('from_owner_id', $userOwnerId);
            }

            return $query;
        };

        $owners = $baseQuery()
            ->select('from_owner_id')
            ->selectRaw('MAX(from_owner) AS from_owner')
            ->whereNotNull('from_owner_id')
            ->where('from_owner_id', '!=', '')
            ->groupBy('from_owner_id')
            ->orderBy('from_owner_id')
            ->get()
            ->map(fn ($owner) => [
                'owner_id' => $owner->from_owner_id,
                'owner_name' => $owner->from_owner ?: $owner->from_owner_id,
            ])
            ->values()
            ->all();

        $products = $baseQuery()
            ->select('product_internal_reference', 'product_name')
            ->whereNotNull('product_internal_reference')
            ->whereNotNull('product_name')
            ->distinct()
            ->orderBy('product_internal_reference')
            ->get()
            ->map(fn ($product) => [
                'product_id' => $product->product_internal_reference,
                'default_code' => $product->product_internal_reference,
                'product_name' => $product->product_name,
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
    t.to_location AS kd_gudang,
    t.from_owner AS kd_customer,
    MAX(t.display_name) AS nm_customer,
    GROUP_CONCAT(DISTINCT t.reference) AS no_receipt,
    GROUP_CONCAT(DISTINCT t.so_contract) AS no_po,
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
    'PT Golden Multi Indotama: Receipts',
    'PT Golden Multi Indotama: Repack Inbound',
    'PT Golden Multi Indotama: Adjustment Inbound',
    'PT Golden Multi Indotama: Credit Note/Return'
)
  AND DATE(t.`date`) BETWEEN ? AND ?
  AND (? = '' OR t.from_owner_id = ?)
  AND (? = '' OR t.product_internal_reference = ?)
GROUP BY
    DATE(t.`date`),
    t.to_location,
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

        return Inertia::render('Portal/Odoo/RekapInbound/Index', [
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
