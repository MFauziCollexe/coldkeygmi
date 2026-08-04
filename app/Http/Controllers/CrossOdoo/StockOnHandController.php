<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockOnHandController extends Controller
{
    public function index(Request $request): Response
    {
        $query = <<<'SQL'
WITH params AS (

    SELECT
        ?::INTEGER AS owner_id,
        NULL::INTEGER AS product_id,
        NULL::INTEGER AS warehouse_id

    ),

stock_onhand AS (

    SELECT

        sq.id,

        sq.owner_id,

        sq.product_id,

        sq.location_id,

        sq.lot_id,

        sq.quantity,

        sq.reserved_quantity,

        sq.in_date,

        sl.usage,

        sl.complete_name,

        sl.warehouse_id

    FROM stock_quant sq

    JOIN stock_location sl
        ON sl.id = sq.location_id

    CROSS JOIN params p

    WHERE sq.quantity <> 0

      AND sl.usage='internal'

      AND (
            p.owner_id IS NULL
            OR sq.owner_id=p.owner_id
      )

      AND (
            p.product_id IS NULL
            OR sq.product_id=p.product_id
      )

      AND (
            p.warehouse_id IS NULL
            OR sl.warehouse_id=p.warehouse_id
      )

)

SELECT

    wh.code                                        AS "KD_GUDANG",

    rp.ref                                         AS "KD_CUST",

    rp.name                                        AS "NM_CUST",

    pp.default_code                                AS "KD_BRG",

    pt.name->>'en_US'                              AS "NM_BRG",

    lot.name                                       AS "LOT",

    lot.expiration_date::date                      AS "EXP_DATE",

    soh.quantity                                   AS "QTY_ON_HAND",

    soh.reserved_quantity                          AS "RESERVED_QTY",

    (soh.quantity-soh.reserved_quantity)           AS "AVAILABLE_QTY",

    uom.name->>'en_US'                             AS "UOM",

    soh.complete_name                              AS "LOCATION",

    soh.in_date                                    AS "IN_DATE"

FROM stock_onhand soh

LEFT JOIN res_partner rp
    ON rp.id=soh.owner_id

LEFT JOIN product_product pp
    ON pp.id=soh.product_id

LEFT JOIN product_template pt
    ON pt.id=pp.product_tmpl_id

LEFT JOIN stock_lot lot
    ON lot.id=soh.lot_id

LEFT JOIN stock_warehouse wh
    ON wh.id=soh.warehouse_id

LEFT JOIN uom_uom uom
    ON uom.id=pt.uom_id

ORDER BY
    soh.in_date DESC,
    wh.code,
    rp.name,
    pp.default_code,
    lot.name;
SQL;

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

        $selectedOwnerId = $request->input('owner_id');
        $selectedOwnerId = ($selectedOwnerId === null || $selectedOwnerId === '') ? null : (int) $selectedOwnerId;

        if ($selectedOwnerId === null && count($owners) > 0) {
            $selectedOwnerId = (int) $owners[0]['owner_id'];
        }

        $rows = DB::connection('pgsql')->select($query, [$selectedOwnerId]);
        $rows = array_map(fn ($row) => (array) $row, $rows);

        return Inertia::render('GMISL/CrossOdoo/SOH/Index', [
            'rows' => $rows,
            'owners' => $owners,
            'selectedOwnerId' => $selectedOwnerId,
        ]);
    }
}
