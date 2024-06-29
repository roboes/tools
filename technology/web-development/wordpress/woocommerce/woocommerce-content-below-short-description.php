<?php

// WooCommerce - Add content below short description/above quantities and "Add to Cart" button
// Last update: 2024-05-29

if (WC()) {

    add_action($hook_name = 'woocommerce_single_variation', $callback = 'woocommerce_content_below_short_description', $priority = 10, $accepted_args = 1);

    function woocommerce_content_below_short_description()
    {

        echo '<div class="woocommerce-variation">Place your content here!</div>';
        echo '<div class="woocommerce-variation">&nbsp;</div>';

    }

}
