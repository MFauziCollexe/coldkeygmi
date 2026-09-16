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
        $customerQuery = <<<'SQL'
SELECT DISTINCT
    rp.id AS customer_id,
    rp.name AS customer_name
FROM stock_quant sq
JOIN product_product pp
    ON pp.id = sq.product_id
JOIN product_template pt
    ON pt.id = pp.product_tmpl_id
JOIN stock_location loc
    ON loc.id = sq.location_id
LEFT JOIN res_partner rp
    ON rp.id = COALESCE(sq.owner_id, pt.x_studio_customer)
WHERE loc.usage = 'internal'
  AND sq.quantity > 0
  AND rp.id IS NOT NULL
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
FROM stock_quant sq
JOIN product_product pp
    ON pp.id = sq.product_id
JOIN product_template pt
    ON pt.id = pp.product_tmpl_id
JOIN stock_location loc
    ON loc.id = sq.location_id
LEFT JOIN res_partner rp
    ON rp.id = COALESCE(sq.owner_id, pt.x_studio_customer)
WHERE loc.usage = 'internal'
  AND sq.quantity > 0
  AND rp.id IS NOT NULL
ORDER BY rp.name, pt.name->>'en_US';
SQL;

        $products = DB::connection('pgsql')->select($productQuery);
        $products = array_map(fn ($product) => (array) $product, $products);

        $selectedCustomerProducts = array_values(array_filter(
            $products,
            fn ($product) => (int) $product['customer_id'] === (int) $selectedCustomerId
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

        $selectedCustomerName = null;
        foreach ($customers as $customer) {
            if ((int) $customer['customer_id'] === (int) $selectedCustomerId) {
                $selectedCustomerName = $customer['customer_name'];
                break;
            }
        }
        $selectedCustomerName = $selectedCustomerName ?? ($customers[0]['customer_name'] ?? null);

        $endDate = $request->input('end_date');
        if ($endDate === null || trim($endDate) === '') {
            $endDate = date('Y-m-d');
        }
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        $cteSql = <<<'SQL'
WITH params AS (
    SELECT
        ?::text AS var_customer,
        ?::text AS var_productname,
        ?::date AS var_tgl2
),
move_after_cutoff AS (
    SELECT
        COALESCE(sml.package_id, sml.result_package_id) AS package_id,
        sml.product_id,
        sml.lot_id,
        SUM(
            CASE
                WHEN loc_src.usage != 'internal' AND loc_dest.usage = 'internal' THEN sml.quantity
                WHEN loc_src.usage = 'internal' AND loc_dest.usage != 'internal' THEN -sml.quantity
                ELSE 0
            END
        ) AS qty_post_cutoff
    FROM stock_move_line sml
    JOIN stock_move sm
        ON sml.move_id = sm.id
    JOIN stock_location loc_src
        ON sml.location_id = loc_src.id
    JOIN stock_location loc_dest
        ON sml.location_dest_id = loc_dest.id
    CROSS JOIN params p
    WHERE sm.state = 'done'
      AND CAST(sm.date AS date) > p.var_tgl2
    GROUP BY
        COALESCE(sml.package_id, sml.result_package_id),
        sml.product_id,
        sml.lot_id
),
soh AS (
    SELECT

        r_owner.name                       AS "Owner",

        loc.complete_name                  AS "Location",

        sqp.name                           AS "Destination package",

        pt.default_code                    AS "Koder barang",

        pt.name->>'en_US'                  AS "Nama barang",

        TO_CHAR(sl.expiration_date, 'DD/MM/YYYY') AS "Expired",

        SUM(sq.quantity - COALESCE(mac.qty_post_cutoff, 0)) AS "SOH Available",

        SUM((sq.quantity - COALESCE(mac.qty_post_cutoff, 0)) * COALESCE(pt.weight, 1)) AS "QTY KG",

        sl.name                            AS "Lot",

        COALESCE(
            sq.ns_plate_number,
            sqp.ns_plate_number,
            (
                SELECT sp.x_studio_no_kendaraan
                FROM stock_move_line sml_sub
                JOIN stock_picking sp
                    ON sml_sub.picking_id = sp.id
                WHERE sml_sub.product_id = sq.product_id
                  AND sml_sub.lot_id = sq.lot_id
                  AND sp.x_studio_no_kendaraan IS NOT NULL
                LIMIT 1
            )
        )                                  AS "Nopol"

    FROM stock_quant sq

    JOIN product_product pp
        ON sq.product_id = pp.id

    JOIN product_template pt
        ON pp.product_tmpl_id = pt.id

    JOIN stock_location loc
        ON sq.location_id = loc.id

    LEFT JOIN stock_lot sl
        ON sq.lot_id = sl.id

    LEFT JOIN stock_quant_package sqp
        ON sq.package_id = sqp.id

    LEFT JOIN res_partner r_owner
        ON COALESCE(sq.owner_id, pt.x_studio_customer) = r_owner.id

    LEFT JOIN move_after_cutoff mac
        ON sq.package_id = mac.package_id
       AND sq.product_id = mac.product_id
       AND sq.lot_id IS NOT DISTINCT FROM mac.lot_id

    CROSS JOIN params p

    WHERE loc.usage = 'internal'
      AND r_owner.name = p.var_customer
      AND pt.id IN (
          SELECT id
          FROM product_template
          WHERE EXISTS (
              SELECT 1
              FROM jsonb_each_text(name) AS lang(key, value)
              WHERE lang.value ILIKE p.var_productname
          )
      )

    GROUP BY
        r_owner.name,
        loc.complete_name,
        sqp.name,
        pt.default_code,
        pt.name->>'en_US',
        sl.expiration_date,
        sl.name,
        sq.product_id,
        sq.lot_id,
        sq.ns_plate_number,
        sqp.ns_plate_number

    HAVING SUM(sq.quantity - COALESCE(mac.qty_post_cutoff, 0)) > 0
)
SQL;

        $bindings = [$selectedCustomerName, $productName, $endDate];

        $countQuery = "{$cteSql} SELECT COUNT(*) AS total_count FROM soh";
        $countResult = DB::connection('pgsql')->selectOne($countQuery, $bindings);
        $totalRows = $countResult->total_count ?? 0;

        $offset = ($page - 1) * $perPage;
        $rowsQuery = "{$cteSql} SELECT * FROM soh ORDER BY \"Destination package\" ASC LIMIT ? OFFSET ?";
        $rows = DB::connection('pgsql')->select($rowsQuery, array_merge($bindings, [$perPage, $offset]));

        $formattedRows = array_map(fn ($row) => (array) $row, $rows);

        return Inertia::render('GMISL/CrossOdoo/SOH/Index', [
            'rows' => $formattedRows,
            'customers' => $customers,
            'products' => $products,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => $selectedProductId,
            'customerName' => $selectedCustomerName,
            'productName' => $productName,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => (int) $totalRows,
        ]);
    }
}