<?php
// WooCommerce - Remove stock inventory count and price from "Add to Cart" session
// Last update: 2026-01-15


if (function_exists('WC') && !is_admin()) {

    add_filter(hook_name: 'woocommerce_get_stock_html', callback: fn (): string => '', priority: 10, accepted_args: 1);

    add_filter(hook_name: 'woocommerce_show_variation_price', callback: '__return_false', priority: 10, accepted_args: 1);

    add_action(hook_name: 'wp_footer', callback: 'hide_empty_variation_description', priority: 10, accepted_args: 1);

    function hide_empty_variation_description(): void
    {
        if (!is_product()) {
            return;
        }

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('form.variations_form').on('show_variation', function(event, variation) {
                var variationDescription = $('.woocommerce-variation-description');
                if (variation.variation_description.trim() === '') {
                    variationDescription.hide();
                } else {
                    variationDescription.show();
                }
            });
        });
        </script>
        <?php
    }

}
