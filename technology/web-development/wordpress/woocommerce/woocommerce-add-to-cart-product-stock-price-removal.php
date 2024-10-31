<?php

// WooCommerce - Remove stock inventory count and price from "Add to Cart" session
// Last update: 2024-09-07

if (class_exists('WooCommerce') && WC()) {

    add_filter($hook_name = 'woocommerce_get_stock_html', $callback = function () {return '';}, $priority = 10, $accepted_args = 1);

    add_filter($hook_name = 'woocommerce_show_variation_price', $callback = '__return_false', $priority = 10, $accepted_args = 1);

    add_action($hook_name = 'wp_footer', $callback = 'hide_empty_variation_description', $priority = 10, $accepted_args = 1);

    function hide_empty_variation_description()
    {
        // Only enqueue this script on WooCommerce product pages
        if (is_product()) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // When the variation is selected
                $('form.variations_form').on('woocommerce_variation_has_changed', function() {
                    // Check if the variation description is empty
                    var variationDescription = $('.woocommerce-variation-description');
                    if (variationDescription.text().trim() === '') {
                        variationDescription.hide();  // Hide if empty
                    } else {
                        variationDescription.show();  // Show if it contains text
                    }
                });
            });
            </script>
            <?php
        }
    }

}
