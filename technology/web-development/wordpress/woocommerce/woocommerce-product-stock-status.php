<?php
// WooCommerce - Product stock status
// Last update: 2024-11-26

// Notes: Elementor's "Product Stock" widget only works with "Stock management" (i.e. for products where "Track stock quantity for this product" is activated)

if (class_exists('WooCommerce') && WC()) {

    add_shortcode($tag = 'woocommerce_product_stock_status', $callback = 'product_stock_status');

    function product_stock_status()
    {
        global $product;

        // Ensure $product is set
        if (!$product) {
            $product = wc_get_product(get_the_ID());
        }

        if (!$product) {
            return '';
        }

        // Check if the product is a variable product
        if ($product->is_type('variable')) {
            $all_out_of_stock = true;

            // Loop through variations to check stock status
            $available_variations = $product->get_children();
            foreach ($available_variations as $variation_id) {
                $variation_obj = wc_get_product($variation_id);
                if ($variation_obj && $variation_obj->is_in_stock()) {
                    $all_out_of_stock = false;
                    break; // No need to check further if any variation is in stock
                }
            }

            // If all variations are out of stock
            if ($all_out_of_stock) {
                $product->set_stock_status('outofstock');
            }
        }

        // Load the translation files
        // load_plugin_textdomain($domain='woocommerce', $deprecated=false, $plugin_rel_path=(dirname(plugin_basename(__FILE__)) . '/languages'));

        // Initial availability
        if ($product->is_in_stock()) {
            $availability = '<span class="product-stock-status-icon" style="margin-right: 6px"><i class="fa-solid fa-circle" style="color: #50C878;"></i></span>';
            $availability .= __($text = 'In stock', $domain = 'woocommerce');
            // $availability .= 'Vorrätig';
        } else {
            $availability = '<span class="product-stock-status-icon" style="margin-right: 6px"><i class="fa-solid fa-circle" style="color: #b20000;"></i></span>';
            $availability .= __($text = 'Out of stock', $domain = 'woocommerce');
            // $availability .= 'Nicht vorrätig';
        }

        return '<div id="product-stock-status">' . $availability . '</div>';
    }

    // Dynamically update the product stock status for variable products based on the selected product variation
    add_action($hook_name = 'wp_footer', $callback = 'product_stock_status_script', $priority = 10, $accepted_args = 1);

    function product_stock_status_script()
    {
        if (is_product()) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('form.variations_form').on('found_variation', function(event, variation) {
                        const availability = $('#product-stock-status');
                        if (variation.is_in_stock) {
                            availability.html('<span class="product-stock-status-icon" style="margin-right: 6px"><i class="fa-solid fa-circle" style="color: #50C878;"></i></span>' + '<?php echo __($text = 'In stock', $domain = 'woocommerce'); ?>');
                        } else {
                            availability.html('<span class="product-stock-status-icon" style="margin-right: 6px"><i class="fa-solid fa-circle" style="color: #b20000;"></i></span>' + '<?php echo __($text = 'Out of stock', $domain = 'woocommerce'); ?>');
                        }
                    });

                    $('form.variations_form').on('reset_data', function() {
                        const availability = $('#product-stock-status');
                        availability.html('<?php echo product_stock_status(); ?>');
                    });
                });
            </script>
            <?php
        }
    }

}
