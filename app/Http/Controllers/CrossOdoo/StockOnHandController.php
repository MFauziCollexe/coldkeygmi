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

        $productQuery = <<<'SQL'
SELECT DISTINCT
    pp.id AS product_id,
    pp.default_code,
    pt.name->>'en_US' AS product_name
FROM stock_quant sq
JOIN product_product pp
    ON pp.id = sq.product_id
LEFT JOIN product_template pt
    ON pt.id = pp.product_tmpl_id
WHERE sq.product_id IS NOT NULL
ORDER BY pp.default_code, pt.name->>'en_US';
SQL;

        $warehouseQuery = <<<'SQL'
SELECT
    id AS warehouse_id,
    code,
    name
FROM stock_warehouse
ORDER BY code;
SQL;

        $owners = DB::connection('pgsql')->select($ownerQuery);
        $owners = array_map(fn ($owner) => (array) $owner, $owners);

        $products = DB::connection('pgsql')->select($productQuery);
        $products = array_map(fn ($product) => (array) $product, $products);

        $warehouses = DB::connection('pgsql')->select($warehouseQuery);
        $warehouses = array_map(fn ($warehouse) => (array) $warehouse, $warehouses);

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

        $selectedWarehouseId = $request->input('warehouse_id');
        if ($selectedWarehouseId !== null && $selectedWarehouseId !== '') {
            $selectedWarehouseId = (int) $selectedWarehouseId;
        } else {
            $selectedWarehouseId = null;
        }

        $startDate = $request->input('start_date', '2026-01-01');
        $endDate = $request->input('end_date', '2026-12-31');

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
        } catch (\Exception $exception) {
            $start = new \DateTime('2026-01-01');
            $end = new \DateTime('2026-12-31');
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

        $query = <<<'SQL'
WITH params AS (

    SELECT
        ?::date AS date_from,
        ?::date AS date_to,
        ?::INTEGER AS owner_id,
        ?::INTEGER AS product_id,
        ?::INTEGER AS warehouse_id

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

      AND sq.in_date::date BETWEEN p.date_from AND p.date_to

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

    wh.code,
    rp.name,
    pp.default_code,
    lot.name;
SQL;

        $bindings = [$startDate, $endDate, $selectedOwnerId, $selectedProductId, $selectedWarehouseId];
        $rows = DB::connection('pgsql')->select($query, $bindings);

        $formattedRows = array_map(function ($row) {
            return [
                'warehouse_code' => $row->KD_GUDANG,
                'customer_code' => $row->KD_CUST,
                'customer_name' => $row->NM_CUST,
                'product_code' => $row->KD_BRG,
                'product_name' => $row->NM_BRG,
                'lot' => $row->LOT,
                'exp_date' => $row->EXP_DATE,
                'qty_on_hand' => (float) ($row->QTY_ON_HAND ?? 0),
                'reserved_qty' => (float) ($row->RESERVED_QTY ?? 0),
                'available_qty' => (float) ($row->AVAILABLE_QTY ?? 0),
                'uom' => $row->UOM,
                'location' => $row->LOCATION,
                'in_date' => $row->IN_DATE,
            ];
        }, $rows);

        return Inertia::render('GMISL/CrossOdoo/SOH/Index', [
            'rows' => $formattedRows,
            'owners' => $owners,
            'products' => $products,
            'warehouses' => $warehouses,
            'selectedOwnerId' => $selectedOwnerId,
            'selectedProductId' => $selectedProductId,
            'selectedWarehouseId' => $selectedWarehouseId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
