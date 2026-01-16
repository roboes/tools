<?php
// WooCommerce - Product stock status
// Last update: 2026-01-15

// Notes: Elementor's "Product Stock" widget only works with "Stock management" (i.e. for products where "Track stock quantity for this product" is activated)
// Usage: [woocommerce_product_stock_status language="en"]


if (function_exists('WC') && !is_admin()) {

    add_shortcode(tag: 'woocommerce_product_stock_status', callback: 'woocommerce_product_stock_status');

    function woocommerce_product_stock_status(array|string $atts = []): string
    {

        $atts = shortcode_atts(pairs: ['language' => null], atts: $atts, shortcode: 'woocommerce_product_stock_status');

        $product = wc_get_product(get_the_ID());

        if (!$product instanceof WC_Product) {
            return '';
        }

        // For variable products, check if all variations are out of stock
        if ($product->is_type('variable') && $product instanceof WC_Product_Variable) {
            $available_variations = $product->get_children();
            $all_out_of_stock = !array_any(
                $available_variations,
                static fn (int $variation_id): bool =>
                    ($variation = wc_get_product($variation_id)) instanceof WC_Product && $variation->is_in_stock()
            );

            if ($all_out_of_stock) {
                $product->set_stock_status('outofstock');
            }
        }

        $is_in_stock = $product->is_in_stock();
        $icon_color = $is_in_stock ? '#50C878' : '#b20000';

        // Check for specific overrides, otherwise use WP translation
        if ($atts['language'] === 'de') {
            $status_text = $is_in_stock ? 'Vorrätig' : 'Nicht vorrätig';
        } elseif ($atts['language'] === 'pt') {
            $status_text = $is_in_stock ? 'Em estoque' : 'Fora de estoque';
        } else {
            // This case handles 'en', null, or any other unspecified language via WooCommerce translations
            $status_text = $is_in_stock ? __('In stock', 'woocommerce') : __('Out of stock', 'woocommerce');
        }

        return '<div id="product-stock-status"><span class="product-stock-status-icon" style="margin-right:6px"><i class="fa-solid fa-circle" style="color:' . esc_attr($icon_color) . '"></i></span>' . esc_html($status_text) . '</div>';

    }

    add_action(hook_name: 'wp_footer', callback: 'woocommerce_product_stock_status_script', priority: 10, accepted_args: 0);

    function woocommerce_product_stock_status_script(): void
    {
        if (!is_product()) {
            return;
        }

        $in_stock_text = esc_js(__('In stock', 'woocommerce'));
        $out_of_stock_text = esc_js(__('Out of stock', 'woocommerce'));

        ?>
        <script>
        jQuery(($) => {
            const $form = $('form.variations_form');
            const $availability = $('#product-stock-status');
            const originalHTML = $availability.html();
            const icon = (color, text) => 
                `<span class="product-stock-status-icon" style="margin-right:6px"><i class="fa-solid fa-circle" style="color:${color}"></i></span>${text}`;
            
            $form.on('show_variation', (e, variation) => {
                if (variation.is_in_stock) {
                    $availability.html(icon('#50C878', '<?php echo $in_stock_text; ?>'));
                } else {
                    $availability.html(icon('#b20000', '<?php echo $out_of_stock_text; ?>'));
                }
            }).on('reset_data', () => {
                $availability.html(originalHTML);
            });
        });
        </script>
        <?php
    }

}
