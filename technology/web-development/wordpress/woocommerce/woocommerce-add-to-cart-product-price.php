<?php
// WooCommerce - Display product currency and price inside "Add to Cart" button
// Last update: 2025-10-08

if (class_exists('WooCommerce') && WC()) {

    add_filter($hook_name = 'woocommerce_product_single_add_to_cart_text', $callback = 'woocommerce_add_to_cart_product_price', $priority = 10, $accepted_args = 2);

    function woocommerce_add_to_cart_product_price($button_text, $product)
    {
        if (!is_product()) {
            return $button_text;
        }

        // Common configuration for both product types
        $currency_config = sprintf(
            'const currencySymbol = %s;
            const currencyPosition = %s;
            const currencyDecimals = %s;
            const locale = "de-DE";
            
            const formatter = new Intl.NumberFormat(locale, {
                minimumFractionDigits: parseInt(currencyDecimals),
                maximumFractionDigits: parseInt(currencyDecimals)
            });
            
            function formatPrice(price) {
                const formattedPrice = formatter.format(price);
                switch(currencyPosition) {
                    case "left": return currencySymbol + formattedPrice;
                    case "right": return formattedPrice + currencySymbol;
                    case "left_space": return currencySymbol + " " + formattedPrice;
                    case "right_space": return formattedPrice + " " + currencySymbol;
                    default: return currencySymbol + formattedPrice;
                }
            }',
            wp_json_encode(get_woocommerce_currency_symbol()),
            wp_json_encode(get_option('woocommerce_currency_pos')),
            wp_json_encode(wc_get_price_decimals())
        );

        if ($product->is_type('variable')) {
            $variations_data = array_column($product->get_available_variations(), 'display_price', 'variation_id');
            ?>
            <script>
            jQuery(function($) {
                const jsonData = <?php echo wp_json_encode($variations_data); ?>;
                const $button = $(".single_add_to_cart_button");
                const $varInput = $("input.variation_id");
                const $qtyInput = $('input[name="quantity"]');
                
                <?php echo $currency_config; ?>
                
                function updatePrice() {
                    const vid = $varInput.val();
                    const quantity = parseInt($qtyInput.val()) || 1;
                    
                    $button.find('span[data-price]').remove();
                    
                    if (vid && jsonData[vid] !== undefined) {
                        const priceHtml = formatPrice(jsonData[vid] * quantity);
                        $button.append('<span data-price> - ' + priceHtml + '</span>');
                    }
                }
                
                updatePrice();
                $varInput.add($qtyInput).on('change', updatePrice);
                $('button.plus, button.minus').on('click', () => setTimeout(updatePrice, 0));
            });
            </script>
            <?php
        } else {
            $product_price = wc_get_price_to_display($product);
            ?>
            <script>
            jQuery(function($) {
                const basePrice = <?php echo $product_price; ?>;
                const $button = $(".single_add_to_cart_button");
                const $qtyInput = $('input[name="quantity"]');
                
                <?php echo $currency_config; ?>
                
                function updatePrice() {
                    const quantity = parseInt($qtyInput.val()) || 1;
                    const priceHtml = formatPrice(basePrice * quantity);
                    
                    $button.find('span[data-price]').remove();
                    $button.append('<span data-price> - ' + priceHtml + '</span>');
                }
                
                updatePrice();
                $qtyInput.on('change', updatePrice);
                $('button.plus, button.minus').on('click', () => setTimeout(updatePrice, 0));
            });
            </script>
            <?php
        }

        return $button_text;
    }
}
