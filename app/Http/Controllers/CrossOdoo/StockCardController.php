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
        $targetPartnerId = (int) $request->input('partner_id', 29);
        $targetProductId = $request->input('product_id');

        if ($targetProductId !== null && $targetProductId !== '') {
            $targetProductId = (int) $targetProductId;
        } else {
            $targetProductId = 423;
        }

        $query = <<<'SQL'
WITH
params AS (
    SELECT
        ?::INTEGER AS p_owner_id,
        ?::INTEGER AS p_product_id,
        DATE '2026-01-01' AS p_date_from,
        DATE '2026-12-31' AS p_date_to
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
    JOIN stock_location src ON src.id=sml.location_id
    JOIN stock_location dst ON dst.id=sml.location_dest_id
    CROSS JOIN params p

    WHERE sml.state='done'
      AND sml.owner_id=p.p_owner_id
      AND sml.product_id=p.p_product_id
      AND sml.date < p.p_date_from

    GROUP BY
        sml.owner_id,
        sml.product_id
),

movement AS (

    SELECT

        sml.id,
        sml.date,

        sml.owner_id,
        rp.name AS owner_name,

        sml.product_id,
        pp.default_code,
        pt.name->>'en_US' AS product_name,

        lot.name AS lot_number,

        sml.expiration_date,

        src.complete_name AS source_location,
        dst.complete_name AS destination_location,

        sp.name AS picking_no,
        sm.origin,

        sp.x_studio_no_kendaraan,

        sml.gmi_pallet_assigned,

        sml.x_studio_total_in_sack,

        CASE

            WHEN src.usage='supplier'
             AND dst.usage='internal'
            THEN 'RECEIPT'

            WHEN src.usage='internal'
             AND dst.usage='customer'
            THEN 'DELIVERY'

            WHEN src.usage='customer'
             AND dst.usage='internal'
            THEN 'CUSTOMER RETURN'

            WHEN src.usage='internal'
             AND dst.usage='supplier'
            THEN 'VENDOR RETURN'

            WHEN src.usage='internal'
             AND dst.usage='internal'
            THEN 'TRANSFER'

            WHEN src.usage='inventory'
            THEN 'ADJUSTMENT IN'

            WHEN dst.usage='inventory'
            THEN 'ADJUSTMENT OUT'

            ELSE 'OTHER'

        END AS movement_type,

        CASE
            WHEN dst.usage='internal'
            THEN sml.quantity
            ELSE 0
        END qty_in,

        CASE
            WHEN src.usage='internal'
            THEN sml.quantity
            ELSE 0
        END qty_out,

        CASE
            WHEN dst.usage='internal'
            THEN sml.quantity

            WHEN src.usage='internal'
            THEN -sml.quantity

            ELSE 0
        END net_qty

    FROM stock_move_line sml

    JOIN stock_move sm
      ON sm.id=sml.move_id

    LEFT JOIN stock_picking sp
      ON sp.id=sml.picking_id

    JOIN stock_location src
      ON src.id=sml.location_id

    JOIN stock_location dst
      ON dst.id=sml.location_dest_id

    JOIN res_partner rp
      ON rp.id=sml.owner_id

    JOIN product_product pp
      ON pp.id=sml.product_id

    JOIN product_template pt
      ON pt.id=pp.product_tmpl_id

    LEFT JOIN stock_lot lot
      ON lot.id=sml.lot_id

    CROSS JOIN params p

    WHERE sml.state='done'
      AND sml.owner_id=p.p_owner_id
      AND sml.product_id=p.p_product_id
      AND sml.date BETWEEN p.p_date_from AND p.p_date_to

)

SELECT

    m.date,

    m.owner_name,

    m.product_name,

    m.default_code,

    m.lot_number,

    m.expiration_date,

    m.movement_type,

    m.source_location,

    m.destination_location,

    m.qty_in,

    m.qty_out,

    COALESCE(o.opening_qty,0)
    +
    SUM(m.net_qty)
    OVER(
        ORDER BY m.date,m.id
    ) AS balance,

    m.x_studio_total_in_sack,

    m.gmi_pallet_assigned,

    m.x_studio_no_kendaraan

FROM movement m

LEFT JOIN opening_balance o
ON o.owner_id=m.owner_id
AND o.product_id=m.product_id

ORDER BY
m.date,
m.id;
SQL;

        $rows = DB::connection('pgsql')->select($query, [$targetPartnerId, $targetProductId]);

        $formattedRows = array_map(function ($row) {
            return [
                'transaction_date' => $row->date,
                'owner_name' => $row->owner_name,
                'product_name' => $row->product_name,
                'product_code' => $row->default_code,
                'lot_number' => $row->lot_number,
                'expiration_date' => $row->expiration_date,
                'movement_type' => $row->movement_type,
                'source_location' => $row->source_location,
                'destination_location' => $row->destination_location,
                'qty_in' => (float) ($row->qty_in ?? 0),
                'qty_out' => (float) ($row->qty_out ?? 0),
                'running_balance' => (float) ($row->balance ?? 0),
                'x_studio_total_in_sack' => $row->x_studio_total_in_sack,
                'gmi_pallet_assigned' => $row->gmi_pallet_assigned,
                'x_studio_no_kendaraan' => $row->x_studio_no_kendaraan,
            ];
        }, $rows);

        $customerName = $formattedRows[0]['nama_customer'] ?? null;
        $productName = $formattedRows[0]['nama_product'] ?? null;

        return Inertia::render('GMISL/CrossOdoo/StockCard/Index', [
            'rows' => $formattedRows,
            'targetPartnerId' => $targetPartnerId,
            'targetProductId' => $targetProductId,
            'customerName' => $customerName,
            'productName' => $productName,
        ]);
    }
}
