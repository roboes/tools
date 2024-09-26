<?php
// WooCommerce - "Add to cart" quantity buttons
// Last update: 2024-09-08

// Notes: Use this snippet code together with the plugin "WC Variations Radio Buttons" (https://github.com/8manos/wc-variations-radio-buttons)

if (class_exists('WooCommerce') && WC()) {

    // Add "+" button after quantity input
    add_action($hook_name = 'woocommerce_after_add_to_cart_quantity', $callback = 'ts_quantity_plus_sign', $priority = 10, $accepted_args = 1);

    function ts_quantity_plus_sign()
    {
        if (is_product()) {
            echo '<button type="button" class="plus">+</button>';
        }
    }

    // Add "-" button before quantity input
    add_action($hook_name = 'woocommerce_before_add_to_cart_quantity', $callback = 'ts_quantity_minus_sign', $priority = 10, $accepted_args = 1);

    function ts_quantity_minus_sign()
    {
        if (is_product()) {
            echo '<button type="button" class="minus">-</button>';
        }
    }

    // JavaScript to manage plus/minus functionality
    add_action($hook_name = 'wp_footer', $callback = 'ts_quantity_plus_minus_script', $priority = 10, $accepted_args = 1);

    function ts_quantity_plus_minus_script()
    {
        if (is_product()) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function ($) {
                // Event handler for + and - buttons
                $("form.cart").on("click", "button.plus, button.minus", function () {
                    const qty = $(this).closest("form.cart").find(".qty");
                    const val = parseFloat(qty.val());
                    const max = parseFloat(qty.attr("max"));
                    const min = parseFloat(qty.attr("min"));
                    const step = parseFloat(qty.attr("step"));

                    if ($(this).is(".plus")) {
                        if (max && val >= max) {
                            qty.val(max);
                        } else {
                            qty.val(val + step);
                        }
                    } else {
                        if (min && val <= min) {
                            qty.val(min);
                        } else if (val > 1) {
                            qty.val(val - step);
                        }
                    }
                });

                // Ensure quantity field is always shown and enabled
                $(".qty").each(function () {
                    $(this).prop('disabled', false);
                });
            });
            </script>
            <?php
        }
    }

    // Ensure quantity input is not hidden, even if product quantity is 1
    add_filter($hook_name = 'woocommerce_quantity_input_args', $callback = 'ts_force_show_quantity_input', $priority = 10, $accepted_args = 2);

    function ts_force_show_quantity_input($args, $product)
    {
        if (is_product() && $product->get_min_purchase_quantity() == 1 && $product->get_max_purchase_quantity() == 1) {
            $args['min_value'] = 1; // Ensure min value is 1
            $args['max_value'] = 999; // Set a higher max value to prevent WooCommerce from hiding the field
        }
        return $args;
    }
}
