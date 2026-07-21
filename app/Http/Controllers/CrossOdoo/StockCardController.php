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

        if ($targetProductId !== null && $targetProductId !== '') {
            $targetProductId = (int) $targetProductId;
        } else {
            $targetProductId = null;
        }

        $query = <<<'SQL'
WITH params AS (

    SELECT

        /*=========================================================
          FILTER
        =========================================================*/

        ?::INTEGER      AS p_owner_id,

        ?::INTEGER      AS p_product_id,

        ?::date         AS p_date_from,

        ?::date         AS p_date_to

),

/*=============================================================
OPENING BALANCE
=============================================================*/

opening_balance AS (

SELECT

    sml.owner_id,

    rp.name AS owner_name,

    sml.product_id,

    pp.default_code,

    pt.name->>'en_US' AS product_name,

    SUM(

        CASE

            /* Barang Masuk Gudang */

            WHEN src.usage <> 'internal'
             AND dst.usage = 'internal'

            THEN sml.quantity

            /* Barang Keluar Gudang */

            WHEN src.usage = 'internal'
             AND dst.usage <> 'internal'

            THEN -sml.quantity

            /* Transfer Internal */

            ELSE 0

        END

    ) AS opening_balance

FROM stock_move_line sml

JOIN product_product pp
ON pp.id=sml.product_id

JOIN product_template pt
ON pt.id=pp.product_tmpl_id

LEFT JOIN res_partner rp
ON rp.id=sml.owner_id

JOIN stock_location src
ON src.id=sml.location_id

JOIN stock_location dst
ON dst.id=sml.location_dest_id

CROSS JOIN params p

WHERE sml.state='done'

AND sml.owner_id=p.p_owner_id

AND (
        p.p_product_id IS NULL
        OR sml.product_id=p.p_product_id
)

AND DATE(sml.date) < p.p_date_from

GROUP BY

    sml.owner_id,

    rp.name,

    sml.product_id,

    pp.default_code,

    pt.name

),

/*=============================================================
TRANSACTION DETAIL
PART 2 START HERE
=============================================================*/

transaction_detail AS (

SELECT

    sml.id AS move_line_id,

    sml.date,

    sml.owner_id,

    rp.name AS owner_name,

    sml.product_id,

    pp.default_code,

    pt.name->>'en_US' AS product_name,

    sml.quantity,

    sml.location_id,

    sml.location_dest_id,

    src.complete_name AS source_location,

    dst.complete_name AS destination_location,

    src.usage AS source_usage,

    dst.usage AS destination_usage,

    sml.move_id,

    sml.picking_id,

    sml.reference,

    sml.lot_id,

    lot.name AS lot_number,

    sml.expiration_date,

    sml.package_id,

    sml.result_package_id,

    pkg.name AS package_name,

    sml.x_studio_total_in_sack,

    sml.gmi_pallet_assigned,

    sp.name AS picking_number,

    sm.origin,

    sm.reference AS move_reference,

    spt.code,

    spt.name->>'en_US' AS operation_type,

    sp.x_studio_no_kendaraan

FROM stock_move_line sml

JOIN product_product pp
ON pp.id=sml.product_id

JOIN product_template pt
ON pt.id=pp.product_tmpl_id

LEFT JOIN res_partner rp
ON rp.id=sml.owner_id

LEFT JOIN stock_lot lot
ON lot.id=sml.lot_id

LEFT JOIN stock_quant_package pkg
ON pkg.id=sml.package_id

JOIN stock_location src
ON src.id=sml.location_id

JOIN stock_location dst
ON dst.id=sml.location_dest_id

LEFT JOIN stock_move sm
ON sm.id=sml.move_id

LEFT JOIN stock_picking sp
ON sp.id=sml.picking_id

LEFT JOIN stock_picking_type spt
ON spt.id=sp.picking_type_id

CROSS JOIN params p

WHERE sml.state='done'

AND sml.owner_id=p.p_owner_id

AND (
        p.p_product_id IS NULL
        OR sml.product_id=p.p_product_id
)

AND DATE(sml.date)
BETWEEN p.p_date_from
AND p.p_date_to

),

/*=============================================================
MOVEMENT CLASSIFICATION
=============================================================*/

movement AS (

SELECT

    td.*,

    CASE

        WHEN td.code='incoming'
        THEN 'RECEIPT'

        WHEN td.code='outgoing'
        THEN 'DELIVERY'

        WHEN td.code='internal'
             AND td.source_usage='internal'
             AND td.destination_usage='internal'
        THEN 'TRANSFER'

        WHEN td.code='incoming'
             AND td.source_usage='customer'
        THEN 'CUSTOMER RETURN'

        WHEN td.code='outgoing'
             AND td.destination_usage='supplier'
        THEN 'VENDOR RETURN'

        WHEN td.source_usage='inventory'
          OR td.destination_usage='inventory'
        THEN 'ADJUSTMENT'

        ELSE td.operation_type

    END AS movement_type,

    CASE

        WHEN td.source_usage='supplier'
         AND td.destination_usage='internal'
        THEN td.quantity

        WHEN td.source_usage='customer'
         AND td.destination_usage='internal'
        THEN td.quantity

        WHEN td.source_usage='inventory'
         AND td.destination_usage='internal'
        THEN td.quantity

        ELSE 0

    END AS qty_in,

    CASE

        WHEN td.source_usage='internal'
         AND td.destination_usage='customer'
        THEN td.quantity

        WHEN td.source_usage='internal'
         AND td.destination_usage='supplier'
        THEN td.quantity

        WHEN td.source_usage='internal'
         AND td.destination_usage='inventory'
        THEN td.quantity

        ELSE 0

    END AS qty_out,

    CASE

        WHEN td.source_usage='supplier'
         AND td.destination_usage='internal'
        THEN td.quantity

        WHEN td.source_usage='customer'
         AND td.destination_usage='internal'
        THEN td.quantity

        WHEN td.source_usage='inventory'
         AND td.destination_usage='internal'
        THEN td.quantity

        WHEN td.source_usage='internal'
         AND td.destination_usage='customer'
        THEN -td.quantity

        WHEN td.source_usage='internal'
         AND td.destination_usage='supplier'
        THEN -td.quantity

        WHEN td.source_usage='internal'
         AND td.destination_usage='inventory'
        THEN -td.quantity

        ELSE 0

    END AS net_change,

    CASE

        WHEN td.source_usage='supplier'
        THEN 'IN'

        WHEN td.destination_usage='customer'
        THEN 'OUT'

        WHEN td.source_usage='customer'
        THEN 'RETURN'

        WHEN td.source_usage='internal'
         AND td.destination_usage='internal'
        THEN 'TRANSFER'

        WHEN td.source_usage='inventory'
             OR td.destination_usage='inventory'
        THEN 'ADJUSTMENT'

        ELSE '-'

    END AS movement_direction,

    COALESCE(

        td.picking_number,

        td.move_reference,

        td.reference,

        td.origin

    ) AS document_number

FROM transaction_detail td

),

/*=============================================================
RUNNING BALANCE
PART 3 START HERE
=============================================================*/

running_balance AS (

SELECT

    m.*,

    COALESCE(ob.opening_balance,0) AS opening_balance,

    COALESCE(ob.opening_balance,0)

    +

    SUM(m.net_change)

    OVER(

        PARTITION BY

            m.owner_id,
            m.product_id

        ORDER BY

            m.date,
            m.move_line_id

    ) AS balance

FROM movement m

LEFT JOIN opening_balance ob

ON ob.owner_id=m.owner_id

AND ob.product_id=m.product_id

),

/*=============================================================
FINAL REPORT
=============================================================*/

final_report AS (

SELECT

    0 AS seq,

    NULL::INTEGER AS move_line_id,

    p.p_date_from::timestamp AS transaction_date,

    rb.owner_id,

    rb.owner_name,

    rb.product_id,

    rb.default_code,

    rb.product_name,

    NULL::TEXT AS document_number,

    'OPENING BALANCE' AS movement_type,

    NULL::TEXT AS movement_direction,

    NULL::TEXT AS source_location,

    NULL::TEXT AS destination_location,

    NULL::TEXT AS lot_number,

    NULL::DATE AS expiration_date,

    NULL::TEXT AS package_name,

    NULL::TEXT AS pallet,

    0::NUMERIC AS sack,

    0::NUMERIC AS qty_in,

    0::NUMERIC AS qty_out,

    0::NUMERIC AS net_change,

    MAX(rb.opening_balance) AS balance,

    NULL::TEXT AS vehicle,

    NULL::TEXT AS reference,

    NULL::TEXT AS origin

FROM running_balance rb

CROSS JOIN params p

GROUP BY

    p.p_date_from,

    rb.owner_id,

    rb.owner_name,

    rb.product_id,

    rb.default_code,

    rb.product_name

UNION ALL

SELECT

    1,

    rb.move_line_id,

    rb.date,

    rb.owner_id,

    rb.owner_name,

    rb.product_id,

    rb.default_code,

    rb.product_name,

    rb.document_number,

    rb.movement_type,

    rb.movement_direction,

    rb.source_location,

    rb.destination_location,

    rb.lot_number,

    rb.expiration_date,

    rb.package_name,

    CASE
        WHEN rb.gmi_pallet_assigned THEN 'YES'
        ELSE 'NO'
    END AS pallet,

    COALESCE(rb.x_studio_total_in_sack,0),

    rb.qty_in,

    rb.qty_out,

    rb.net_change,

    rb.balance,

    rb.x_studio_no_kendaraan,

    rb.reference,

    rb.origin

FROM running_balance rb

),

/*=============================================================
CLOSING BALANCE
=============================================================*/

closing_balance AS (

SELECT

    owner_id,

    product_id,

    MAX(balance) AS closing_balance

FROM running_balance

GROUP BY

    owner_id,

    product_id

)

SELECT

    fr.transaction_date              AS transaction_date,

    fr.owner_id,

    fr.owner_name,

    fr.product_id,

    fr.default_code                  AS product_code,

    fr.product_name,

    fr.document_number,

    fr.reference,

    fr.origin,

    fr.movement_type,

    fr.movement_direction,

    fr.source_location,

    fr.destination_location,

    fr.lot_number,

    fr.expiration_date,

    fr.package_name,

    fr.pallet,

    fr.sack,

    fr.qty_in,

    fr.qty_out,

    fr.net_change,

    fr.balance,

    cb.closing_balance,

    fr.vehicle

FROM final_report fr

LEFT JOIN closing_balance cb

ON cb.owner_id=fr.owner_id
AND cb.product_id=fr.product_id

ORDER BY

    fr.owner_name,

    fr.product_name,

    fr.transaction_date,

    fr.seq,

    fr.move_line_id;
SQL;

        $rows = DB::connection('pgsql')->select($query, [$selectedOwnerId, $targetProductId, $startDate, $endDate]);

        $formattedRows = array_map(function ($row) {
            return [
                'transaction_date' => $row->transaction_date,
                'owner_name' => $row->owner_name,
                'product_code' => $row->product_code,
                'product_name' => $row->product_name,
                'document_number' => $row->document_number,
                'reference' => $row->reference,
                'origin' => $row->origin,
                'movement_type' => $row->movement_type,
                'movement_direction' => $row->movement_direction,
                'source_location' => $row->source_location,
                'destination_location' => $row->destination_location,
                'lot_number' => $row->lot_number,
                'expiration_date' => $row->expiration_date,
                'package_name' => $row->package_name,
                'pallet' => $row->pallet,
                'sack' => (float) ($row->sack ?? 0),
                'qty_in' => (float) ($row->qty_in ?? 0),
                'qty_out' => (float) ($row->qty_out ?? 0),
                'net_change' => (float) ($row->net_change ?? 0),
                'running_balance' => (float) ($row->balance ?? 0),
                'closing_balance' => (float) ($row->closing_balance ?? 0),
                'vehicle' => $row->vehicle,
            ];
        }, $rows);

        $ownerName = $formattedRows[0]['owner_name'] ?? ($owners[0]['owner_name'] ?? null);
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
        ]);
    }
}
