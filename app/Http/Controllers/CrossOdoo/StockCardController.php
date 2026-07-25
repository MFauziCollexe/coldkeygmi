<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockCardController extends Controller
{
    public function index(Request $request): Response
    {
        $ownerQuery = <<<'SQL'
SELECT DISTINCT
    rp.id AS owner_id,
    rp.name AS owner_name
FROM stock_quant sq
JOIN res_partner rp
    ON rp.id = sq.owner_id
WHERE sq.owner_id IS NOT NULL
ORDER BY rp.name;
SQL;

        $owners = DB::connection('pgsql')->select($ownerQuery);
        $owners = array_map(fn ($owner) => (array) $owner, $owners);

        $selectedOwnerId = (int) $request->input('owner_id', $owners[0]['owner_id'] ?? 29);
        $targetProductId = $request->input('product_id');
        $startDate = $request->input('start_date', '2026-01-01');
        $endDate = $request->input('end_date', '2026-12-31');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        if ($targetProductId !== null && $targetProductId !== '') {
            $targetProductId = (int) $targetProductId;
        } else {
            $targetProductId = null;
        }

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
        } catch (\Exception $exception) {
            $start = new \DateTime('2026-01-01');
            $end = new \DateTime('2026-01-31');
        }

        if ($end < $start) {
            $end = clone $start;
        }

        $maxEnd = (clone $start)->modify('+1 month');
        if ($end > $maxEnd) {
            $end = $maxEnd;
        }

        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');

        $offset = ($page - 1) * $perPage;

        $query = <<<'SQL'
WITH params AS (

    SELECT

        DATE '2026-01-01' AS date_from,
        DATE '2026-01-31' AS date_to,

        NULL::INTEGER AS owner_id,
        NULL::INTEGER AS product_id,
        NULL::INTEGER AS warehouse_id

),

locations AS (

    SELECT
        sl.id,
        sl.usage,
        sl.warehouse_id
    FROM stock_location sl

),

opening_balance AS (

    SELECT

        sml.owner_id,
        sml.product_id,

        SUM(

            CASE
                WHEN dst.usage='internal' THEN sml.quantity
                WHEN src.usage='internal' THEN -sml.quantity
                ELSE 0
            END

        ) opening_qty

    FROM stock_move_line sml

    JOIN locations src
        ON src.id=sml.location_id

    JOIN locations dst
        ON dst.id=sml.location_dest_id

    CROSS JOIN params p

    WHERE sml.state='done'

      AND sml.date < p.date_from

      AND (p.owner_id IS NULL OR sml.owner_id=p.owner_id)
      AND (p.product_id IS NULL OR sml.product_id=p.product_id)

      AND (
            p.warehouse_id IS NULL
            OR src.warehouse_id=p.warehouse_id
            OR dst.warehouse_id=p.warehouse_id
      )

    GROUP BY

        sml.owner_id,
        sml.product_id
),

trx AS (

SELECT

    sml.id,

    sml.owner_id,

    rp.ref                                        AS kd_customer,
    rp.name                                       AS nm_customer,

    sml.product_id,

    pp.default_code                               AS kd_barang,

    pt.name->>'en_US'                             AS nm_barang,

    sml.date::date                                AS tgl_tran,

    wh.code                                       AS kd_gudang,
    wh.name                                       AS nm_gudang,

    sp.x_studio_no_kendaraan                      AS no_mobil,

    sp.name                                       AS no_reference_1,

    sp.origin                                     AS no_reference_2,

    COALESCE(sm.origin,sp.origin)                 AS no_po_so,

    am.name                                       AS no_invoice,

    CONCAT_WS(' / ',
        sm.name,
        sm.description_picking,
        sp.note
    )                                             AS keterangan,

    CASE
        WHEN dst.usage='internal' THEN sml.quantity
        ELSE 0
    END qty_in,

    CASE
        WHEN src.usage='internal' THEN sml.quantity
        ELSE 0
    END qty_out

FROM stock_move_line sml

JOIN stock_move sm
ON sm.id=sml.move_id

LEFT JOIN stock_picking sp
ON sp.id=sml.picking_id

LEFT JOIN account_move am
ON am.stock_move_id=sm.id

LEFT JOIN product_product pp
ON pp.id=sml.product_id

LEFT JOIN product_template pt
ON pt.id=pp.product_tmpl_id

LEFT JOIN res_partner rp
ON rp.id=COALESCE(sml.owner_id,sp.partner_id)

JOIN locations src
ON src.id=sml.location_id

JOIN locations dst
ON dst.id=sml.location_dest_id

LEFT JOIN stock_warehouse wh
ON wh.id=COALESCE(dst.warehouse_id,src.warehouse_id)

CROSS JOIN params p

WHERE sml.state='done'

AND sml.date BETWEEN p.date_from
                 AND p.date_to

AND (p.owner_id IS NULL OR sml.owner_id=p.owner_id)

AND (p.product_id IS NULL OR sml.product_id=p.product_id)

AND (
        p.warehouse_id IS NULL
        OR src.warehouse_id=p.warehouse_id
        OR dst.warehouse_id=p.warehouse_id
)

),

running_balance AS (

SELECT

    t.*,

    COALESCE(ob.opening_qty,0) sd_aw,

    SUM(
        qty_in-qty_out
    ) OVER(
        PARTITION BY owner_id, product_id
        ORDER BY tgl_tran, id
    ) + COALESCE(ob.opening_qty,0) AS saldo_akhir

FROM trx t

LEFT JOIN opening_balance ob
ON ob.owner_id IS NOT DISTINCT FROM t.owner_id
AND ob.product_id=t.product_id

)

SELECT
    kd_gudang,
    kd_customer,
    nm_customer,
    kd_barang,
    nm_barang,
    tgl_tran,
    no_mobil,
    no_reference_1,
    no_reference_2,
    no_po_so,
    no_invoice,
    keterangan,
    sd_aw,
    qty_in,
    qty_out,
    saldo_akhir AS saldo_akhir_qty,
    NULL::numeric AS sd_aw_kg,
    NULL::numeric AS mutasi_in_kg,
    NULL::numeric AS mutasi_out_kg,
    NULL::numeric AS saldo_akhir_kg
FROM running_balance

ORDER BY
    nm_customer,
    nm_barang,
    tgl_tran,
    id
SQL;

        $countQuery = "SELECT COUNT(*) AS total_count FROM ({$query}) AS total_count_wrapper";
        $rowsQuery = "{$query} LIMIT ? OFFSET ?";

        $countResult = DB::connection('pgsql')->selectOne($countQuery);
        $totalRows = $countResult->total_count ?? 0;

        $rows = DB::connection('pgsql')->select($rowsQuery, [$perPage, $offset]);

        $formattedRows = array_map(function ($row) {
            return [
                'transaction_date' => $row->tgl_tran,
                'warehouse_code' => $row->kd_gudang,
                'customer_code' => $row->kd_customer,
                'customer_name' => $row->nm_customer,
                'product_code' => $row->kd_barang,
                'product_name' => $row->nm_barang,
                'mobile_no' => $row->no_mobil,
                'reference_1' => $row->no_reference_1,
                'reference_2' => $row->no_reference_2,
                'po_so' => $row->no_po_so,
                'invoice_no' => $row->no_invoice,
                'description' => $row->keterangan,
                'opening_qty' => (float) ($row->sd_aw ?? 0),
                'qty_in' => (float) ($row->qty_in ?? 0),
                'qty_out' => (float) ($row->qty_out ?? 0),
                'balance_qty' => (float) ($row->saldo_akhir_qty ?? $row->saldo_akhir ?? 0),
                'sd_aw_kg' => (float) ($row->sd_aw_kg ?? 0),
                'mutasi_in_kg' => (float) ($row->mutasi_in_kg ?? 0),
                'mutasi_out_kg' => (float) ($row->mutasi_out_kg ?? 0),
                'saldo_akhir_kg' => (float) ($row->saldo_akhir_kg ?? 0),
            ];
        }, $rows);

        $ownerName = $formattedRows[0]['customer_name'] ?? ($owners[0]['owner_name'] ?? null);
        $productName = $formattedRows[0]['product_name'] ?? null;

        return Inertia::render('GMISL/CrossOdoo/StockCard/Index', [
            'rows' => $formattedRows,
            'owners' => $owners,
            'selectedOwnerId' => $selectedOwnerId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'targetProductId' => $targetProductId,
            'customerName' => $ownerName,
            'productName' => $productName,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
        ]);
    }
}
