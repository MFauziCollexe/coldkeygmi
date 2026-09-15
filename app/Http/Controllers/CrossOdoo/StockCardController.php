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
        $customerQuery = <<<'SQL'
SELECT DISTINCT
    rp.id AS customer_id,
    rp.name AS customer_name
FROM product_template pt
JOIN res_partner rp
    ON rp.id = pt.x_studio_customer
WHERE pt.x_studio_customer IS NOT NULL
ORDER BY rp.name;
SQL;

        $customers = DB::connection('pgsql')->select($customerQuery);
        $customers = array_map(fn ($customer) => (array) $customer, $customers);

        $selectedCustomerId = $request->input('customer_id');
        if ($selectedCustomerId !== null && $selectedCustomerId !== '') {
            $selectedCustomerId = (int) $selectedCustomerId;
        } else {
            $selectedCustomerId = $customers[0]['customer_id'] ?? null;
        }

        $productQuery = <<<'SQL'
SELECT DISTINCT
    pp.id AS product_id,
    pp.default_code,
    pt.name->>'en_US' AS product_name
FROM product_product pp
JOIN product_template pt
    ON pt.id = pp.product_tmpl_id
JOIN res_partner rp
    ON rp.id = pt.x_studio_customer
WHERE pt.x_studio_customer = ?
ORDER BY pt.name->>'en_US';
SQL;

        $products = $selectedCustomerId !== null
            ? DB::connection('pgsql')->select($productQuery, [$selectedCustomerId])
            : [];
        $products = array_map(fn ($product) => (array) $product, $products);

        $selectedProductId = $request->input('product_id');
        if ($selectedProductId !== null && $selectedProductId !== '') {
            $selectedProductId = (int) $selectedProductId;
        } else {
            $selectedProductId = $products[0]['product_id'] ?? null;
        }

        $productName = null;
        foreach ($products as $product) {
            if ((int) $product['product_id'] === $selectedProductId) {
                $productName = $product['product_name'];
                break;
            }
        }

        $startDate = $request->input('start_date', '2026-01-01');
        $endDate = $request->input('end_date', '2026-12-31');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        $unionSql = <<<'SQL'
WITH params AS (
    SELECT
        ?::text AS customer_name,
        ?::text AS product_name,
        ?::date AS date_from,
        ?::date AS date_to
),
saldo_awal AS (
    SELECT
        sm.id,
        r.name                                        AS customer,
        pt.name->>'en_US'                             AS namabarang,
        sml.reference                                 AS trans,
        sm.date,
        sm.quantity                                   AS done_qty,
        loc_src.usage                                 AS src_usage,
        loc_dest.usage                                AS dest_usage,
        CASE
            -- Jika asal dari luar dan tujuan ke internal -> POSITIF
            WHEN loc_src.usage != 'internal' AND loc_dest.usage = 'internal' THEN sm.quantity
            -- Jika asal dari internal dan tujuan ke luar -> NEGATIF
            WHEN loc_src.usage = 'internal' AND loc_dest.usage != 'internal' THEN -sm.quantity
            -- Untuk internal transfer -> 0
            ELSE 0
        END                                           AS total_stock_movement,
        CASE
            WHEN loc_src.usage != 'internal' AND loc_dest.usage = 'internal' THEN sml.quantity
            WHEN loc_src.usage = 'internal' AND loc_dest.usage != 'internal' THEN -sml.quantity
            ELSE 0
        END                                           AS saldoawal,
        0::numeric                                    AS qtyin,
        0::numeric                                    AS qtyout
    FROM stock_move sm
    JOIN product_product pp
        ON pp.id = sm.product_id
    JOIN product_template pt
        ON pt.id = pp.product_tmpl_id
    JOIN stock_location loc_src
        ON loc_src.id = sm.location_id
    JOIN stock_location loc_dest
        ON loc_dest.id = sm.location_dest_id
    JOIN stock_move_line sml
        ON sml.move_id = sm.id
    JOIN stock_lot sl
        ON sl.id = sml.lot_id
    JOIN res_partner r
        ON r.id = pt.x_studio_customer
    CROSS JOIN params p
    WHERE sm.state = 'done'
      AND sm.date::date < p.date_from
      AND r.name = p.customer_name
      AND pt.id IN (
          SELECT id
          FROM product_template
          WHERE EXISTS (
              SELECT 1
              FROM jsonb_each_text(name) AS lang(key, value)
              WHERE lang.value ILIKE p.product_name
          )
      )
),
transaksi AS (
    SELECT
        sm.id,
        r.name                                        AS customer,
        pt.name->>'en_US'                             AS namabarang,
        sml.reference                                 AS trans,
        sm.date,
        sm.quantity                                   AS done_qty,
        loc_src.usage                                 AS src_usage,
        loc_dest.usage                                AS dest_usage,
        CASE
            WHEN loc_src.usage != 'internal' AND loc_dest.usage = 'internal' THEN sm.quantity
            WHEN loc_src.usage = 'internal' AND loc_dest.usage != 'internal' THEN -sm.quantity
            ELSE 0
        END                                           AS total_stock_movement,
        0::numeric                                    AS saldoawal,
        CASE
            -- Jika asal dari internal dan tujuan ke luar -> NEGATIF
            WHEN loc_src.usage = 'internal' AND loc_dest.usage != 'internal' THEN -sm.quantity
            ELSE 0
        END                                           AS qtyin,
        CASE
            -- Jika asal dari luar dan tujuan ke internal -> POSITIF
            WHEN loc_src.usage != 'internal' AND loc_dest.usage = 'internal' THEN sml.quantity
            ELSE 0
        END                                           AS qtyout
    FROM stock_move sm
    JOIN product_product pp
        ON pp.id = sm.product_id
    JOIN product_template pt
        ON pt.id = pp.product_tmpl_id
    JOIN stock_location loc_src
        ON loc_src.id = sm.location_id
    JOIN stock_location loc_dest
        ON loc_dest.id = sm.location_dest_id
    JOIN stock_move_line sml
        ON sml.move_id = sm.id
    JOIN stock_lot sl
        ON sl.id = sml.lot_id
    JOIN res_partner r
        ON r.id = pt.x_studio_customer
    CROSS JOIN params p
    WHERE sm.state = 'done'
      AND sm.date::date BETWEEN p.date_from AND p.date_to
      AND r.name = p.customer_name
      AND pt.id IN (
          SELECT id
          FROM product_template
          WHERE EXISTS (
              SELECT 1
              FROM jsonb_each_text(name) AS lang(key, value)
              WHERE lang.value ILIKE p.product_name
          )
      )
)
SELECT * FROM saldo_awal
UNION ALL
SELECT * FROM transaksi
SQL;

        $offset = ($page - 1) * $perPage;

        $selectedCustomerName = null;
        foreach ($customers as $customer) {
            if ((int) $customer['customer_id'] === $selectedCustomerId) {
                $selectedCustomerName = $customer['customer_name'];
                break;
            }
        }

        $bindings = [$selectedCustomerName, $productName, $startDate, $endDate];

        $countQuery = "SELECT COUNT(*) AS total_count FROM ({$unionSql}) AS data";
        $countResult = DB::connection('pgsql')->selectOne($countQuery, $bindings);
        $totalRows = $countResult->total_count ?? 0;

        $rowsQuery = "SELECT * FROM ({$unionSql}) AS data ORDER BY data.date, data.id LIMIT ? OFFSET ?";
        $rows = DB::connection('pgsql')->select($rowsQuery, array_merge($bindings, [$perPage, $offset]));

        $formattedRows = array_map(function ($row) {
            return [
                'customer' => $row->customer,
                'product_name' => $row->namabarang,
                'trans' => $row->trans,
                'transaction_date' => $row->date,
                'done_qty' => (float) $row->done_qty,
                'src_usage' => $row->src_usage,
                'dest_usage' => $row->dest_usage,
                'total_movement' => (float) $row->total_stock_movement,
                'saldo_awal' => (float) $row->saldoawal,
                'qty_in' => (float) $row->qtyin,
                'qty_out' => (float) $row->qtyout,
            ];
        }, $rows);

        $customerName = $selectedCustomerName ?? $formattedRows[0]['customer'] ?? ($customers[0]['customer_name'] ?? null);

        return Inertia::render('GMISL/CrossOdoo/StockCard/Index', [
            'rows' => $formattedRows,
            'customers' => $customers,
            'products' => $products,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => $selectedProductId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'customerName' => $customerName,
            'productName' => $productName,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
        ]);
    }
}