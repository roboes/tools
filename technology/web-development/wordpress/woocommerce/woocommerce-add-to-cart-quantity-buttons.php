<?php
// WooCommerce - "Add to Cart" quantity buttons
// Last update: 2026-02-15


// Notes:
// - Use this snippet code together with the plugin "WC Variations Radio Buttons" (https://github.com/8manos/wc-variations-radio-buttons)
// - To disable the "Add to Cart" quantity buttons, use this filter: add_filter(hook_name: 'woocommerce_before_add_to_cart_quantity_disable', callback: '__return_true', priority: 10, accepted_args: 0);

if (function_exists('WC') && !is_admin()) {

    // Disable buttons for "sold individually" products
    add_filter(
        hook_name: 'woocommerce_before_add_to_cart_quantity_disable',
        callback: function ($disable) {
            if (!is_product()) {
                return $disable;
            }

            global $product;

            // If the main product or the selected variation is sold individually, disable buttons
            if ($product && $product->is_sold_individually()) {
                return true;
            }

            return $disable;
        },
        priority: 10,
        accepted_args: 1,
    );

    // Add - button before quantity input
    add_action(
        hook_name: 'woocommerce_before_add_to_cart_quantity',
        callback: function (): void {
            if (is_product() && !apply_filters('woocommerce_before_add_to_cart_quantity_disable', false)) {
                echo '<button type="button" class="minus" aria-label="Decrease quantity">-</button>';
            }
        },
        priority: 10,
        accepted_args: 0,
    );

    // Add + button after quantity input
    add_action(
        hook_name: 'woocommerce_after_add_to_cart_quantity',
        callback: function (): void {
            if (is_product() && !apply_filters('woocommerce_before_add_to_cart_quantity_disable', false)) {
                echo '<button type="button" class="plus" aria-label="Increase quantity">+</button>';
            }
        },
        priority: 10,
        accepted_args: 0,
    );

    // JavaScript to manage plus/minus functionality
    add_action(
        hook_name: 'wp_footer',
        callback: function (): void {
            if (!is_product()) {
                return;
            }
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {

                // Manage plus/minus click functionality
                $('body').on('click', 'button.plus, button.minus', function(e) {
                    e.preventDefault();
                    let $quantity = $(this).siblings('.qty').add($(this).parent().find('input.qty')).first();

                    if ($quantity.length) {
                        const val = parseFloat($quantity.val()) || 1;
                        const max = parseFloat($quantity.attr('max')) || 999;
                        const min = parseFloat($quantity.attr('min')) || 1;
                        const step = parseFloat($quantity.attr('step')) || 1;

                        let newVal = $(this).hasClass('plus') ? Math.min(val + step, max) : Math.max(val - step, min);
                        $quantity.val(newVal).trigger('change');
                    }
                });

                // Hide/Show buttons based on variation selection
                $(document).on('found_variation', 'form.cart', function(event, variation) {
                    const $buttons = $(this).find('button.plus, button.minus');
                    if (variation.is_sold_individually === 'yes') {
                        $buttons.hide();
                    } else {
                        $buttons.show();
                    }
                });

                // Force buttons and quantity div to show when "Clear" (Reset) is clicked
                $(document).on('reset_data', 'form.cart', function() {
                    // Find buttons and the quantity container specifically within this form
                    const $thisForm = $(this);
                    $thisForm.find('button.plus, button.minus').show();
                    $thisForm.find('.quantity').show(); 
                });

                $('.qty').prop('disabled', false);
            });
            </script>
            <?php
        },
        priority: 10
    );

    // Force show quantity input
    add_filter(
        hook_name: 'woocommerce_quantity_input_args',
        callback: function (array $args, \WC_Product $product): array {
            if (!is_product()) {
                return $args;
            }

            if ($product->is_sold_individually()) {
                return $args;
            }

            if ($product->get_min_purchase_quantity() === 1 && $product->get_max_purchase_quantity() === 1) {
                $args['max_value'] = 999;
            }

            return $args;
        },
        priority: 10,
        accepted_args: 2
    );

}
