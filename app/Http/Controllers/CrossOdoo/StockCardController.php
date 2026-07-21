<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class StockCardController extends Controller
{
    public function index(Request $request): Response
    {
        $query = <<<'SQL'
SELECT
    sp.date_done::date               AS transaction_date,
    sp.name                          AS reference,
    rp.name                          AS customer,
    pp.default_code                  AS product_code,
    pt.name                          AS product_name,
    sml.lot_name,
    CASE
        WHEN dest.usage = 'internal'
             AND src.usage <> 'internal'
        THEN sml.quantity
        ELSE 0
    END AS qty_in,
    CASE
        WHEN src.usage = 'internal'
             AND dest.usage <> 'internal'
        THEN sml.quantity
        ELSE 0
    END AS qty_out
FROM stock_move_line sml
INNER JOIN stock_move sm
        ON sm.id = sml.move_id
INNER JOIN stock_picking sp
        ON sp.id = sm.picking_id
INNER JOIN stock_location src
        ON src.id = sml.location_id
INNER JOIN stock_location dest
        ON dest.id = sml.location_dest_id
INNER JOIN product_product pp
        ON pp.id = sm.product_id
INNER JOIN product_template pt
        ON pt.id = pp.product_tmpl_id
LEFT JOIN res_partner rp
       ON rp.id = sp.partner_id
WHERE rp.name = ?
ORDER BY
    sp.date_done,
    sp.name;
SQL;

        $rows = DB::connection('pgsql')->select($query, ['BPK. RUMADI (BOYOLALI)']);

        return Inertia::render('GMISL/CrossOdoo/StockCard/Index', [
            'rows' => array_map(fn ($row) => (array) $row, $rows),
        ]);
    }
}
