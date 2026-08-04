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
        ?::date AS date_from,
        ?::date AS date_to,
        ?::INTEGER AS owner_id,
        ?::INTEGER AS product_id,
        ?::INTEGER AS warehouse_id

),

locations AS (

    SELECT
        id,
        usage,
        warehouse_id
    FROM stock_location

),

opening_balance AS (

    SELECT

        sml.owner_id,
        sml.product_id,

        SUM(
            CASE
                WHEN dst.usage = 'internal' THEN sml.quantity
                WHEN src.usage = 'internal' THEN -sml.quantity
                ELSE 0
            END
        ) AS opening_qty

    FROM stock_move_line sml

    JOIN locations src
        ON src.id = sml.location_id

    JOIN locations dst
        ON dst.id = sml.location_dest_id

    LEFT JOIN stock_picking sp
        ON sp.id = sml.picking_id

    LEFT JOIN approval_request ar
        ON ar.id = sp.x_studio_approval_id

    LEFT JOIN stock_picking_type spt
        ON spt.id = sp.picking_type_id

    CROSS JOIN params p

    WHERE sml.state = 'done'

      AND sml.date < p.date_from

      AND (
            p.owner_id IS NULL
            OR sml.owner_id = p.owner_id
      )

      AND (
            p.product_id IS NULL
            OR sml.product_id = p.product_id
      )

      AND (
            p.warehouse_id IS NULL
            OR src.warehouse_id = p.warehouse_id
            OR dst.warehouse_id = p.warehouse_id
      )

      AND (
            (
                spt.code = 'incoming'
                AND spt.name->>'en_US' IN
                (
                    'Receipts',
                    'Purchase Order',
                    'Credit Note/Return'
                )
            )
            OR
            (
                spt.code = 'outgoing'
                AND spt.name->>'en_US' IN
                (
                    'Delivery Orders',
                    'Return Receipts',
                    'Retur Purchase Order'
                )
            )
      )

    GROUP BY
        sml.owner_id,
        sml.product_id

),

trx AS (

SELECT

    sml.id,

    sml.owner_id,

    sml.product_id,

    rp.ref                                        AS kd_customer,
    rp.name                                       AS nm_customer,

    pp.default_code                               AS kd_barang,

    pt.name->>'en_US'                             AS nm_barang,

    sml.date::date                                AS tgl_tran,

    wh.code                                       AS kd_gudang,

    ar.x_studio_vehicle_plate_number              AS no_mobil,

    sp.name                                       AS no_reference_1,

    sp.origin                                     AS no_reference_2,

    COALESCE(sm.origin, sp.origin)                AS no_po_so,

    am.name                                       AS no_invoice,

    CONCAT_WS(' / ',
        sm.name,
        sm.description_picking,
        sp.note
    )                                             AS keterangan,

    CASE
        WHEN spt.code='incoming'
        THEN sml.quantity
        ELSE 0
    END AS qty_in,

    CASE
        WHEN spt.code='outgoing'
        THEN sml.quantity
        ELSE 0
    END AS qty_out

FROM stock_move_line sml

JOIN stock_move sm
    ON sm.id = sml.move_id

LEFT JOIN stock_picking sp
    ON sp.id = sml.picking_id

LEFT JOIN approval_request ar
    ON ar.id = sp.x_studio_approval_id

LEFT JOIN stock_picking_type spt
    ON spt.id = sp.picking_type_id

LEFT JOIN account_move am
    ON am.stock_move_id = sm.id

LEFT JOIN product_product pp
    ON pp.id = sml.product_id

LEFT JOIN product_template pt
    ON pt.id = pp.product_tmpl_id

LEFT JOIN res_partner rp
    ON rp.id = COALESCE(sml.owner_id, sp.partner_id)

JOIN locations src
    ON src.id = sml.location_id

JOIN locations dst
    ON dst.id = sml.location_dest_id

LEFT JOIN stock_warehouse wh
    ON wh.id = COALESCE(dst.warehouse_id, src.warehouse_id)


CROSS JOIN params p

WHERE sml.state='done'

AND sml.date BETWEEN p.date_from AND p.date_to

AND (
        p.owner_id IS NULL
        OR sml.owner_id = p.owner_id
)

AND (
        p.product_id IS NULL
        OR sml.product_id = p.product_id
)

AND (
        p.warehouse_id IS NULL
        OR src.warehouse_id = p.warehouse_id
        OR dst.warehouse_id = p.warehouse_id
)

AND (
        (
            spt.code='incoming'
            AND spt.name->>'en_US' IN
            (
                'Receipts',
                'Purchase Order',
                'Credit Note/Return'
            )
        )
        OR
        (
            spt.code='outgoing'
            AND spt.name->>'en_US' IN
            (
                'Delivery Orders',
                'Return Receipts',
                'Retur Purchase Order'
            )
        )
)

),

running_balance AS (

SELECT

    t.*,

    COALESCE(ob.opening_qty,0) AS sd_aw,

    COALESCE(ob.opening_qty,0)
    +
    SUM(
        t.qty_in - t.qty_out
    ) OVER(
        PARTITION BY
            t.owner_id,
            t.product_id
        ORDER BY
            t.tgl_tran,
            t.id
    ) AS saldo_akhir

FROM trx t

LEFT JOIN opening_balance ob

ON ob.owner_id IS NOT DISTINCT FROM t.owner_id
AND ob.product_id = t.product_id

)

SELECT

    kd_gudang                              AS "KD_GUDANG",

    kd_customer                            AS "KD_CUST",

    nm_customer                            AS "NM_CUST",

    kd_barang                              AS "KD_BRG",

    nm_barang                              AS "NM_BRG",

    tgl_tran                               AS "TGL_TRAN",

    no_mobil                               AS "NO_MOBIL",

    no_reference_1                         AS "NO_REFERENCE_1",

    no_reference_2                         AS "NO_REFERENCE_2",

    no_po_so                               AS "NO_PO/SO",

    no_invoice                             AS "NO_INVOICE",

    keterangan                             AS "KETERANGAN",

    sd_aw                                  AS "SD_AW",

    qty_in                                 AS "MUTASI_IN",

    qty_out                                AS "MUTASI_OUT",

    saldo_akhir                            AS "SALDO_AKHIR_QTY",

    NULL::numeric                          AS "SD_AW_KG",

    NULL::numeric                          AS "MUTASI_IN_KG",

    NULL::numeric                          AS "MUTASI_OUT_KG",

    NULL::numeric                          AS "SALDO_AKHIR_KG"

FROM running_balance

ORDER BY

    kd_customer,
    kd_barang,
    tgl_tran,
    id
SQL;

        $countQuery = <<<'SQL'
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
        id,
        usage,
        warehouse_id
    FROM stock_location

),

opening_balance AS (

    SELECT

        sml.owner_id,
        sml.product_id,

        SUM(
            CASE
                WHEN dst.usage = 'internal' THEN sml.quantity
                WHEN src.usage = 'internal' THEN -sml.quantity
                ELSE 0
            END
        ) AS opening_qty

    FROM stock_move_line sml

    JOIN locations src
        ON src.id = sml.location_id

    JOIN locations dst
        ON dst.id = sml.location_dest_id

    LEFT JOIN stock_picking sp
        ON sp.id = sml.picking_id

    LEFT JOIN approval_request ar
        ON ar.id = sp.x_studio_approval_id

    LEFT JOIN stock_picking_type spt
        ON spt.id = sp.picking_type_id

    CROSS JOIN params p

    WHERE sml.state = 'done'

      AND sml.date < p.date_from

      AND (
            p.owner_id IS NULL
            OR sml.owner_id = p.owner_id
      )

      AND (
            p.product_id IS NULL
            OR sml.product_id = p.product_id
      )

      AND (
            p.warehouse_id IS NULL
            OR src.warehouse_id = p.warehouse_id
            OR dst.warehouse_id = p.warehouse_id
      )

      AND (
            (
                spt.code = 'incoming'
                AND spt.name->>'en_US' IN
                (
                    'Receipts',
                    'Purchase Order',
                    'Credit Note/Return'
                )
            )
            OR
            (
                spt.code = 'outgoing'
                AND spt.name->>'en_US' IN
                (
                    'Delivery Orders',
                    'Return Receipts',
                    'Retur Purchase Order'
                )
            )
      )

    GROUP BY
        sml.owner_id,
        sml.product_id

),

trx AS (

SELECT

    sml.id,
    sml.owner_id,
    sml.product_id,
    rp.ref                                        AS kd_customer,
    rp.name                                       AS nm_customer,
    pp.default_code                               AS kd_barang,
    pt.name->>'en_US'                             AS nm_barang,
    sml.date::date                                AS tgl_tran,
    wh.code                                       AS kd_gudang,
    ar.x_studio_vehicle_plate_number                      AS no_mobil,
    sp.name                                       AS no_reference_1,
    sp.origin                                     AS no_reference_2,
    COALESCE(sm.origin, sp.origin)                AS no_po_so,
    am.name                                       AS no_invoice,
    CONCAT_WS(' / ',
        sm.name,
        sm.description_picking,
        sp.note
    )                                             AS keterangan,
    CASE
        WHEN spt.code='incoming'
        THEN sml.quantity
        ELSE 0
    END AS qty_in,
    CASE
        WHEN spt.code='outgoing'
        THEN sml.quantity
        ELSE 0
    END AS qty_out
FROM stock_move_line sml

JOIN stock_move sm
    ON sm.id = sml.move_id

LEFT JOIN stock_picking sp
    ON sp.id = sml.picking_id

LEFT JOIN approval_request ar
    ON ar.id = sp.x_studio_approval_id

LEFT JOIN stock_picking_type spt
    ON spt.id = sp.picking_type_id

LEFT JOIN account_move am
    ON am.stock_move_id = sm.id

LEFT JOIN product_product pp
    ON pp.id = sml.product_id

LEFT JOIN product_template pt
    ON pt.id = pp.product_tmpl_id

LEFT JOIN res_partner rp
    ON rp.id = COALESCE(sml.owner_id, sp.partner_id)

JOIN locations src
    ON src.id = sml.location_id

JOIN locations dst
    ON dst.id = sml.location_dest_id

LEFT JOIN stock_warehouse wh
    ON wh.id = COALESCE(dst.warehouse_id, src.warehouse_id)

CROSS JOIN params p

WHERE sml.state='done'

AND sml.date BETWEEN p.date_from AND p.date_to

AND (
        p.owner_id IS NULL
        OR sml.owner_id = p.owner_id
)

AND (
        p.product_id IS NULL
        OR sml.product_id = p.product_id
)

AND (
        p.warehouse_id IS NULL
        OR src.warehouse_id = p.warehouse_id
        OR dst.warehouse_id = p.warehouse_id
)

AND (
        (
            spt.code='incoming'
            AND spt.name->>'en_US' IN
            (
                'Receipts',
                'Purchase Order',
                'Credit Note/Return'
            )
        )
        OR
        (
            spt.code='outgoing'
            AND spt.name->>'en_US' IN
            (
                'Delivery Orders',
                'Return Receipts',
                'Retur Purchase Order'
            )
        )
)
)

SELECT COUNT(*) AS total_count
FROM trx;
SQL;

        $rowsQuery = "{$query} LIMIT ? OFFSET ?";

        $countResult = DB::connection('pgsql')->selectOne($countQuery, $bindings);
        $totalRows = $countResult->total_count ?? 0;

        $rows = DB::connection('pgsql')->select($rowsQuery, array_merge($bindings, [$perPage, $offset]));

        $formattedRows = array_map(function ($row) {
            return [
                'transaction_date' => $row->TGL_TRAN,
                'warehouse_code' => $row->KD_GUDANG,
                'customer_code' => $row->KD_CUST,
                'customer_name' => $row->NM_CUST,
                'product_code' => $row->KD_BRG,
                'product_name' => $row->NM_BRG,
                'mobile_no' => $row->NO_MOBIL,
                'reference_1' => $row->NO_REFERENCE_1,
                'reference_2' => $row->NO_REFERENCE_2,
                'po_so' => $row->{'NO_PO/SO'},
                'invoice_no' => $row->NO_INVOICE,
                'description' => $row->KETERANGAN,
                'opening_qty' => (float) ($row->SD_AW ?? 0),
                'qty_in' => (float) ($row->MUTASI_IN ?? 0),
                'qty_out' => (float) ($row->MUTASI_OUT ?? 0),
                'balance_qty' => (float) ($row->SALDO_AKHIR_QTY ?? $row->SALDO_AKHIR ?? 0),
                'sd_aw_kg' => (float) ($row->SD_AW_KG ?? 0),
                'mutasi_in_kg' => (float) ($row->MUTASI_IN_KG ?? 0),
                'mutasi_out_kg' => (float) ($row->MUTASI_OUT_KG ?? 0),
                'saldo_akhir_kg' => (float) ($row->SALDO_AKHIR_KG ?? 0),
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
