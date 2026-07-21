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
WITH params AS (
    SELECT
        ?::integer AS target_partner_id,
        ?::integer AS target_product_id
),
customer_stock AS (
    SELECT
        COALESCE(sm.partner_id, sp.partner_id) AS id_customer,
        rp.name AS nama_customer,
        p.id AS id_product,
        p.default_code AS kode_produk,
        pt.name->>'en_US' AS nama_product,
        sml.id AS move_line_id,
        sml.date AS tanggal,
        sml.reference AS no_referensi,
        sp.name AS no_picking,
        sm.origin AS no_so_origin,
        lot.name AS lot_number,
        sl_src.complete_name AS dari_lokasi,
        sl_dest.complete_name AS ke_lokasi,
        CASE WHEN sl_dest.usage = 'customer' THEN sml.quantity ELSE 0 END AS qty_dikirim,
        CASE WHEN sl_src.usage = 'customer' THEN sml.quantity ELSE 0 END AS qty_diretur,
        CASE
            WHEN sl_dest.usage = 'customer' THEN sml.quantity
            WHEN sl_src.usage = 'customer' THEN -sml.quantity
            ELSE 0
        END AS net_qty
    FROM public.stock_move_line sml
    CROSS JOIN params pr
    JOIN public.product_product p ON sml.product_id = p.id
    JOIN public.product_template pt ON p.product_tmpl_id = pt.id
    JOIN public.stock_location sl_src ON sml.location_id = sl_src.id
    JOIN public.stock_location sl_dest ON sml.location_dest_id = sl_dest.id
    LEFT JOIN public.stock_move sm ON sml.move_id = sm.id
    LEFT JOIN public.stock_picking sp ON sml.picking_id = sp.id
    LEFT JOIN public.res_partner rp ON COALESCE(sm.partner_id, sp.partner_id) = rp.id
    LEFT JOIN public.stock_lot lot ON sml.lot_id = lot.id
    WHERE sml.state = 'done'
      AND COALESCE(sm.partner_id, sp.partner_id) = pr.target_partner_id
      AND (pr.target_product_id IS NULL OR sml.product_id = pr.target_product_id)
      AND (sl_src.usage = 'customer' OR sl_dest.usage = 'customer')
)
SELECT
    id_customer,
    nama_customer,
    id_product,
    kode_produk,
    nama_product,
    tanggal,
    no_referensi,
    no_picking,
    no_so_origin,
    lot_number,
    dari_lokasi,
    ke_lokasi,
    qty_dikirim,
    qty_diretur,
    SUM(net_qty) OVER (
        PARTITION BY id_customer, id_product
        ORDER BY tanggal ASC, move_line_id ASC
    ) AS saldo_stok_customer
FROM customer_stock
ORDER BY tanggal ASC, move_line_id ASC;
SQL;

        $rows = DB::connection('pgsql')->select($query, [$targetPartnerId, $targetProductId]);

        $formattedRows = array_map(function ($row) {
            return [
                'id_customer' => $row->id_customer,
                'nama_customer' => $row->nama_customer,
                'id_product' => $row->id_product,
                'kode_produk' => $row->kode_produk,
                'nama_product' => $row->nama_product,
                'transaction_date' => $row->tanggal,
                'reference' => $row->no_referensi,
                'picking_number' => $row->no_picking,
                'so_origin' => $row->no_so_origin,
                'lot_number' => $row->lot_number,
                'from_location' => $row->dari_lokasi,
                'to_location' => $row->ke_lokasi,
                'qty_delivered' => (float) ($row->qty_dikirim ?? 0),
                'qty_returned' => (float) ($row->qty_diretur ?? 0),
                'running_balance' => (float) ($row->saldo_stok_customer ?? 0),
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
