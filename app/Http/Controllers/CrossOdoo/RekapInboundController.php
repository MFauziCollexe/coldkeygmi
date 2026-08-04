<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RekapInboundController extends Controller
{
    public function index(Request $request): Response
    {
        $query = <<<'SQL'
WITH params AS (

SELECT

    DATE '2026-01-01' AS date_from,
    DATE '2026-01-31' AS date_to,
    ?::INTEGER AS owner_id,
    ?::INTEGER AS product_id,
    NULL::INTEGER AS warehouse_id

)

SELECT

    sml.date::date                                AS tanggal,

    wh.code                                       AS kd_gudang,

    rp.ref                                        AS kd_customer,

    rp.name                                       AS nm_customer,

    sp.name                                       AS no_receipt,

    sp.origin                                     AS no_po,

    ar.x_studio_vehicle_plate_number              AS no_mobil,

    pp.default_code                               AS kd_barang,

    pt.name->>'en_US'                             AS nm_barang,

    sml.quantity                                  AS qty,

    lot.name                                      AS lot,

    lot.expiration_date::date                     AS expired_date

FROM stock_move_line sml

JOIN stock_move sm
ON sm.id=sml.move_id

JOIN stock_picking sp
ON sp.id=sml.picking_id

JOIN stock_picking_type spt
ON spt.id=sp.picking_type_id

LEFT JOIN approval_request ar
ON ar.id=sp.x_studio_approval_id

LEFT JOIN res_partner rp
ON rp.id=COALESCE(sml.owner_id,sp.partner_id)

LEFT JOIN product_product pp
ON pp.id=sml.product_id

LEFT JOIN product_template pt
ON pt.id=pp.product_tmpl_id

LEFT JOIN stock_lot lot
ON lot.id=sml.lot_id

LEFT JOIN stock_location sl
ON sl.id=sml.location_dest_id

LEFT JOIN stock_warehouse wh
ON wh.id=sl.warehouse_id

CROSS JOIN params p

WHERE

sml.state='done'

AND sml.date BETWEEN p.date_from
                 AND p.date_to

AND spt.code='incoming'

AND (
        p.owner_id IS NULL
        OR sml.owner_id=p.owner_id
)

AND (
        p.product_id IS NULL
        OR sml.product_id=p.product_id
)

AND (
        p.warehouse_id IS NULL
        OR sl.warehouse_id=p.warehouse_id
)

ORDER BY

tanggal,
no_receipt,
kd_barang;
SQL;

        // fetch owners (customers) for filter
        $ownerQuery = <<<'SQL'
SELECT DISTINCT
    rp.id AS owner_id,
    rp.name AS owner_name
FROM stock_move_line sml
LEFT JOIN stock_picking sp
    ON sp.id = sml.picking_id
LEFT JOIN res_partner rp
    ON rp.id = COALESCE(sml.owner_id, sp.partner_id)
WHERE rp.id IS NOT NULL
ORDER BY rp.name;
SQL;

        $productQuery = <<<'SQL'
SELECT DISTINCT
    pp.id AS product_id,
    pp.default_code,
    pt.name->>'en_US' AS product_name
FROM stock_move_line sml
JOIN product_product pp
    ON pp.id = sml.product_id
LEFT JOIN product_template pt
    ON pt.id = pp.product_tmpl_id
WHERE sml.product_id IS NOT NULL
ORDER BY pp.default_code, pt.name->>'en_US';
SQL;

        $owners = DB::connection('pgsql')->select($ownerQuery);
        $owners = array_map(fn ($o) => (array) $o, $owners);

        $products = DB::connection('pgsql')->select($productQuery);
        $products = array_map(fn ($p) => (array) $p, $products);

        $selectedOwnerId = $request->input('owner_id');
        if ($selectedOwnerId !== null && $selectedOwnerId !== '') {
            $selectedOwnerId = (int) $selectedOwnerId;
        } else {
            $selectedOwnerId = null;
        }

        $selectedProductId = $request->input('product_id');
        if ($selectedProductId !== null && $selectedProductId !== '') {
            $selectedProductId = (int) $selectedProductId;
        } else {
            $selectedProductId = null;
        }

        $bindings = [$selectedOwnerId, $selectedProductId];

        $rows = DB::connection('pgsql')->select($query, $bindings);
        $rows = array_map(fn ($row) => (array) $row, $rows);

        return Inertia::render('GMISL/CrossOdoo/RekapInbound/Index', [
            'rows' => $rows,
            'owners' => $owners,
            'products' => $products,
            'selectedOwnerId' => $selectedOwnerId,
            'selectedProductId' => $selectedProductId,
        ]);
    }
}
