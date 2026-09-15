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
    pt.name->>'en_US' AS product_name,
    rp.id AS customer_id,
    rp.name AS customer_name
FROM product_product pp
JOIN product_template pt
    ON pt.id = pp.product_tmpl_id
JOIN res_partner rp
    ON rp.id = pt.x_studio_customer
WHERE pt.x_studio_customer IS NOT NULL
ORDER BY customer_name, product_name;
SQL;

        $products = DB::connection('pgsql')->select($productQuery);
        $products = array_map(fn ($product) => (array) $product, $products);

        $selectedCustomerProducts = array_values(array_filter(
            $products,
            fn ($product) => (int) $product['customer_id'] === $selectedCustomerId
        ));

        $requestedProductId = $request->input('product_id');
        $requestedProduct = null;
        foreach ($selectedCustomerProducts as $product) {
            if ((int) $product['product_id'] === (int) $requestedProductId) {
                $requestedProduct = $product;
                break;
            }
        }

        $selectedProduct = $requestedProduct ?? ($selectedCustomerProducts[0] ?? null);
        $selectedProductId = $selectedProduct['product_id'] ?? null;
        $productName = $selectedProduct['product_name'] ?? null;

        $startDate = $request->input('start_date', '2026-01-01');
        $endDate = $request->input('end_date', '2026-12-31');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        $cteSql = <<<'SQL'
WITH params AS (
    SELECT
        ?::text AS customer_name,
        ?::text AS product_name,
        ?::date AS date_from,
        ?::date AS date_to
),
opening AS (
    SELECT COALESCE(SUM(
        CASE
            -- Jika asal dari luar dan tujuan ke internal -> POSITIF
            WHEN loc_src.usage != 'internal' AND loc_dest.usage = 'internal' THEN sml.quantity
            -- Jika asal dari internal dan tujuan ke luar -> NEGATIF
            WHEN loc_src.usage = 'internal' AND loc_dest.usage != 'internal' THEN -sml.quantity
            -- Untuk internal transfer -> 0
            ELSE 0
        END
    ), 0) AS opening_balance
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
    LEFT JOIN stock_lot sl
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
        sm.date,
        sml.reference                                   AS trans,
        sl.expiration_date::date                        AS expired,
        SUM(
            CASE
                WHEN loc_src.usage != 'internal' AND loc_dest.usage = 'internal' THEN sml.quantity
                ELSE 0::numeric
            END
        )                                               AS qty_in,
        SUM(
            CASE
                WHEN loc_src.usage = 'internal' AND loc_dest.usage != 'internal' THEN sml.quantity
                ELSE 0::numeric
            END
        )                                               AS qty_out
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
    LEFT JOIN stock_lot sl
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
    GROUP BY
        r.name,
        pt.name->>'en_US',
        sl.name,
        sl.expiration_date,
        sml.reference,
        sm.date
),
paged AS (
    SELECT
        t.date,
        t.trans,
        t.expired,
        t.qty_in,
        t.qty_out,
        o.opening_balance,
        o.opening_balance
            + SUM(t.qty_in - t.qty_out)
              OVER (ORDER BY t.date, t.trans, t.expired ROWS UNBOUNDED PRECEDING) AS saldo
    FROM transaksi t
    CROSS JOIN opening o
)
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

        $openingResult = DB::connection('pgsql')->selectOne("{$cteSql} SELECT opening_balance FROM opening", $bindings);
        $openingBalance = (float) ($openingResult->opening_balance ?? 0);

        $countQuery = "{$cteSql} SELECT COUNT(*) AS total_count FROM paged";
        $countResult = DB::connection('pgsql')->selectOne($countQuery, $bindings);
        $totalRows = $countResult->total_count ?? 0;

        $rowsQuery = "{$cteSql} SELECT * FROM paged ORDER BY date, trans, expired LIMIT ? OFFSET ?";
        $rows = DB::connection('pgsql')->select($rowsQuery, array_merge($bindings, [$perPage, $offset]));

        $formattedRows = array_map(function ($row) {
            return [
                'transaction_date' => $row->date,
                'trans' => $row->trans,
                'expired' => $row->expired,
                'qty_in' => (float) $row->qty_in,
                'qty_out' => (float) $row->qty_out,
                'saldo' => (float) $row->saldo,
            ];
        }, $rows);

        $customerName = $selectedCustomerName ?? ($customers[0]['customer_name'] ?? null);

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
            'openingBalance' => $openingBalance,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
        ]);
    }
}