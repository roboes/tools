<?php

// WooCommerce - Rearrange the reset variations link below the <tbody> argument
// Last update: 2024-07-24

if (class_exists('WooCommerce') && WC()) {

    add_action($hook_name = 'wp_footer', $callback = 'woocommerce_reset_variations_link_rearrange', $priority = 10, $accepted_args = 1);

    function woocommerce_reset_variations_link_rearrange()
    {

        if (is_product()) {

            global $product;

            if ($product && $product->is_type('variable')) {
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        // Cache the jQuery selectors
                        const $checkbox = $(".product-terms-checkbox");
                        const $singleVariation = $(".woocommerce-variation.single_variation");
                        const $resetLink = $(".reset_variations");
                        const $variationsTableBody = $("table.variations tbody");

                        // Move the checkbox after the single_variation element if both exist
                        if ($checkbox.length && $singleVariation.length) {
                            $singleVariation.after($checkbox);
                        }

                        // Move the "Leeren" link below the tbody if both exist
                        if ($resetLink.length && $variationsTableBody.length) {
                            $resetLink.css('display', 'block'); // Ensure the link is block-level for proper placement
                            $variationsTableBody.after($resetLink);
                        }
                    });
                </script>
                <?php
            }
        }
    }

}
