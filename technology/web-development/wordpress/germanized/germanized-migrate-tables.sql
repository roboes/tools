/* Germanized - Migrate WooCommerce Germanized tables from one dataset to another */
/* Last update: 2024-06-23 */


/* Important: Import the Germanized tables before activating the shop in the new dataset - otherwise the "meta_id" could ver overlapped. */


/* Shippment tables:
wp_woocommerce_gzd_shipment_itemmeta
wp_woocommerce_gzd_shipment_items
wp_woocommerce_gzd_shipment_labelmeta
wp_woocommerce_gzd_shipment_labels
wp_woocommerce_gzd_shipmentmeta
wp_woocommerce_gzd_shipments */


/* Step 1 - Export the "wp_woocommerce_gzd_shipment_itemmeta" table from the "old" dataset without selecting "Add CREATE DATABASE / USE statement". Open the exported .sql file and edit the name of the table to "wp_woocommerce_gzd_shipment_itemmeta_old". Import it to the new dataset. */


/* Step 2 - Test if "meta_id" column is unique (NON_UNIQUE = 0 and INDEX_NAME = Primary) */
SELECT * FROM information_schema.statistics
WHERE table_schema = 'wordpress' AND table_name = 'wp_woocommerce_gzd_shipment_itemmeta' AND column_name = 'meta_id' AND non_unique = 0;

SELECT * FROM information_schema.statistics
WHERE table_schema = 'wordpress' AND table_name = 'wp_woocommerce_gzd_shipment_itemmeta_old' AND column_name = 'meta_id' AND non_unique = 0;


/* Step 3: Create a temporary table for testing purposes */
CREATE TABLE temp_wp_woocommerce_gzd_shipment_itemmeta LIKE wp_woocommerce_gzd_shipment_itemmeta;

INSERT INTO temp_wp_woocommerce_gzd_shipment_itemmeta
SELECT * FROM wp_woocommerce_gzd_shipment_itemmeta;


/* Step 4: Update the new table with data from the old table */
UPDATE temp_wp_woocommerce_gzd_shipment_itemmeta AS new
INNER JOIN wp_woocommerce_gzd_shipment_itemmeta_old AS old
    ON new.meta_id = old.meta_id
SET
    new.gzd_shipment_item_id = old.gzd_shipment_item_id,
    new.meta_value = old.meta_value;


/* Optional Step: Insert non-matching rows from old table */
/* INSERT INTO temp_wp_woocommerce_gzd_shipment_itemmeta (meta_id, gzd_shipment_item_id, meta_value)
SELECT old.meta_id, old.gzd_shipment_item_id, old.meta_value
FROM wp_woocommerce_gzd_shipment_itemmeta_old AS old
LEFT JOIN temp_wp_woocommerce_gzd_shipment_itemmeta AS new
ON old.meta_id = new.meta_id
WHERE new.meta_id IS NULL; */


/* Step 5: If the migration succeeded in the "temp_wp_woocommerce_gzd_shipment_itemmeta" table, re-run Step 4 removing the "temp_" pre-fix. */
