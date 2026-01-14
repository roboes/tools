<?php
// WooCommerce - "Add to cart" quantity buttons
// Last update: 2026-01-14


// Notes: Use this snippet code together with the plugin "WC Variations Radio Buttons" (https://github.com/8manos/wc-variations-radio-buttons)


if (function_exists('WC') && !is_admin()) {

    // Add - button before quantity input
    add_action(
        hook_name: 'woocommerce_before_add_to_cart_quantity',
        callback: function (): void {
            if (!is_product()) {
                return;
            }
            echo '<button type="button" class="minus" aria-label="Decrease quantity">-</button>';
        },
        priority: 10
    );

    // Add + button after quantity input
    add_action(
        hook_name: 'woocommerce_after_add_to_cart_quantity',
        callback: function (): void {
            if (!is_product()) {
                return;
            }
            echo '<button type="button" class="plus" aria-label="Increase quantity">+</button>';
        },
        priority: 10
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
                $('body').on('click', 'button.plus, button.minus', function(e) {
                    e.preventDefault();
                    
                    let $qty = $(this).siblings('.qty');
                    if (!$qty.length) {
                        $qty = $(this).closest('.quantity').find('.qty');
                    }
                    if (!$qty.length) {
                        $qty = $(this).parent().find('input.qty');
                    }
                    
                    if ($qty.length) {
                        const val = parseFloat($qty.val()) || 1;
                        const max = parseFloat($qty.attr('max')) || 999;
                        const min = parseFloat($qty.attr('min')) || 1;
                        const step = parseFloat($qty.attr('step')) || 1;
                        
                        let newVal;
                        if ($(this).hasClass('plus')) {
                            newVal = Math.min(val + step, max);
                        } else {
                            newVal = Math.max(val - step, min);
                        }
                        
                        $qty.val(newVal).trigger('change');
                    }
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

            if ($product->get_min_purchase_quantity() === 1 && $product->get_max_purchase_quantity() === 1) {
                $args['max_value'] = 999;
            }

            return $args;
        },
        priority: 10,
        accepted_args: 2
    );

}
