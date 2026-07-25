import fs from "fs";
import path from "path";
const filePath = path.join(
    process.cwd(),
    "app",
    "Http",
    "Controllers",
    "CrossOdoo",
    "StockCardController.php",
);
let text = fs.readFileSync(filePath, "utf8");
const startMarker = "        $query = <<<'SQL'";
const endMarker = "\nSQL;";
const startIndex = text.indexOf(startMarker);
if (startIndex === -1) {
    throw new Error("Start marker not found");
}
const endIndex = text.indexOf(endMarker, startIndex);
if (endIndex === -1) {
    throw new Error("End marker not found");
}
const replacement = `        $query = <<<'SQL'
WITH params AS (
    SELECT
        DATE '2026-01-01' AS date_from,
        DATE '2026-01-31' AS date_to,
        NULL::INTEGER AS owner_id,
        NULL::INTEGER AS product_id,
        NULL::INTEGER AS warehouse_id
),

opening_balance AS (

    SELECT
        sml.owner_id,
        sml.product_id,

        SUM(
            CASE
                WHEN dst.usage='internal' THEN sml.quantity
                ELSE -sml.quantity
            END
        ) AS opening_qty

    FROM stock_move_line sml
    JOIN stock_location src ON src.id=sml.location_id
    JOIN stock_location dst ON dst.id=sml.location_dest_id

    CROSS JOIN params p

    WHERE sml.state='done'
      AND sml.date < p.date_from

      AND (p.owner_id IS NULL OR sml.owner_id=p.owner_id)
      AND (p.product_id IS NULL OR sml.product_id=p.product_id)

    GROUP BY
        sml.owner_id,
        sml.product_id
),

trx AS (

    SELECT

        sml.id,

        sml.owner_id,

        rp.ref,
        rp.name AS customer,

        sml.product_id,

        pp.default_code,

        pt.name->>'en_US' AS product_name,

        sml.date,

        sp.name AS picking,

        sp.origin,

        sp.x_studio_no_kendaraan,

        sm.name,

        sm.description_picking,

        CASE
            WHEN dst.usage='internal'
            THEN sml.quantity
            ELSE 0
        END qty_in,

        CASE
            WHEN src.usage='internal'
            THEN sml.quantity
            ELSE 0
        END qty_out

    FROM stock_move_line sml

    JOIN stock_move sm
      ON sm.id=sml.move_id

    LEFT JOIN stock_picking sp
      ON sp.id=sml.picking_id

    LEFT JOIN res_partner rp
      ON rp.id=sml.owner_id

    JOIN product_product pp
      ON pp.id=sml.product_id

    JOIN product_template pt
      ON pt.id=pp.product_tmpl_id

    JOIN stock_location src
      ON src.id=sml.location_id

    JOIN stock_location dst
      ON dst.id=sml.location_dest_id

    CROSS JOIN params p

    WHERE sml.state='done'
      AND sml.date BETWEEN p.date_from AND p.date_to

      AND (p.owner_id IS NULL OR sml.owner_id=p.owner_id)
      AND (p.product_id IS NULL OR sml.product_id=p.product_id)

),

result AS (

    SELECT

        t.*,

        COALESCE(ob.opening_qty,0) opening_qty,

        COALESCE(ob.opening_qty,0)
        +
        SUM(t.qty_in-t.qty_out)
        OVER(
            PARTITION BY t.owner_id,t.product_id
            ORDER BY t.date,t.id
        ) balance_qty

    FROM trx t

    LEFT JOIN opening_balance ob

        ON ob.owner_id IS NOT DISTINCT FROM t.owner_id
       AND ob.product_id=t.product_id

)

SELECT *

FROM result

ORDER BY
customer,
product_name,
date,
id;
SQL;`;
text =
    text.slice(0, startIndex) +
    replacement +
    text.slice(endIndex + endMarker.length);

const ownerNameOld =
    "$ownerName = $formattedRows[0]['owner_name'] ?? ($owners[0]['owner_name'] ?? null);";
const ownerNameNew =
    "$ownerName = $formattedRows[0]['customer'] ?? ($owners[0]['owner_name'] ?? null);";
text = text.replace(ownerNameOld, ownerNameNew);

const productNameOld =
    "$productName = $formattedRows[0]['product_name'] ?? null;";
const productNameNew =
    "$productName = $formattedRows[0]['product_name'] ?? null;";
text = text.replace(productNameOld, productNameNew);

fs.writeFileSync(filePath, text, "utf8");
console.log("Query block replaced successfully");
