<?php
// WooCommerce - Display product currency and price inside "Add to Cart" button
// Last update: 2024-09-17

if (class_exists('WooCommerce') && WC()) {

    add_filter($hook_name = 'woocommerce_product_single_add_to_cart_text', $callback = 'woocommerce_add_to_cart_product_price', $priority = 10, $accepted_args = 2);

    function woocommerce_add_to_cart_product_price($button_text, $product)
    {
        if (is_product()) {
            // Get product price
            $product_price = wc_get_price_to_display($product);

            // Check if the product is a variable product
            if ($product->is_type('variable')) {
                // Get variations data
                $variations_data = array_column($product->get_available_variations(), 'display_price', 'variation_id');
                ?>
                <script>
                jQuery(function($) {
                    // JSON data for variations
                    const jsonData = <?php echo json_encode($variations_data); ?>;
                    const inputVID = 'input.variation_id';
                    const quantityInput = 'input[name="quantity"]';
                    const currencySymbol = '<?php echo esc_html(get_woocommerce_currency_symbol()); ?>';
                    const currencyPosition = '<?php echo esc_html(get_option("woocommerce_currency_pos")); ?>';
                    const currencyDecimals = '<?php echo esc_html(wc_get_price_decimals()); ?>';
                    const locale = 'de-DE';

                    // Number formatter for price
                    const formatter = new Intl.NumberFormat(locale, {
                        minimumFractionDigits: parseInt(currencyDecimals),
                        maximumFractionDigits: parseInt(currencyDecimals)
                    });

                    // Function to format price based on currency position
                    function formatPrice(price) {
                        let formattedPrice = formatter.format(price);
                        switch(currencyPosition) {
                            case 'left':
                                return currencySymbol + formattedPrice;
                            case 'right':
                                return formattedPrice + currencySymbol;
                            case 'left_space':
                                return currencySymbol + ' ' + formattedPrice;
                            case 'right_space':
                                return formattedPrice + ' ' + currencySymbol;
                            default:
                                return currencySymbol + formattedPrice; // default to left if unknown position
                        }
                    }

                    // Function to update price
                    function updatePrice() {
                        const vid = $(inputVID).val();
                        const quantity = parseInt($(quantityInput).val()) || 1;
                        if (vid && jsonData[vid] !== undefined) {
                            const price = jsonData[vid] * quantity;
                            const priceHtml = formatPrice(price);
                            $("button.single_add_to_cart_button span[data-price='true']").remove();
                            $(".single_add_to_cart_button").append("<span data-price='true'> - " + priceHtml + "</span>"); // Append price to button
                        } else {
                            $("button.single_add_to_cart_button span[data-price='true']").remove(); // Remove existing price if variation is not selected
                        }
                    }

                    // Initial price update
                    updatePrice();
                    // Event listeners for variation ID and quantity changes
                    $(inputVID + ', ' + quantityInput).on('change', updatePrice);
                    // Event listener for quantity change using buttons
                    $('button.plus, button.minus').on('click', function() {
                        setTimeout(updatePrice, 0);
                    });
                });
                </script>
                <?php
            } else {
                // For single products, display regular product price
                ?>
                <script>
                jQuery(function($) {
                    const quantityInput = 'input[name="quantity"]';
                    const currencySymbol = '<?php echo esc_html(get_woocommerce_currency_symbol()); ?>';
                    const currencyPosition = '<?php echo esc_html(get_option("woocommerce_currency_pos")); ?>';
                    const currencyDecimals = '<?php echo esc_html(wc_get_price_decimals()); ?>';
                    const basePrice = <?php echo $product_price; ?>;
                    const locale = 'de-DE';

                    // Number formatter for price
                    const formatter = new Intl.NumberFormat(locale, {
                        minimumFractionDigits: parseInt(currencyDecimals),
                        maximumFractionDigits: parseInt(currencyDecimals)
                    });

                    // Function to format price based on currency position
                    function formatPrice(price) {
                        let formattedPrice = formatter.format(price);
                        switch(currencyPosition) {
                            case 'left':
                                return currencySymbol + formattedPrice;
                            case 'right':
                                return formattedPrice + currencySymbol;
                            case 'left_space':
                                return currencySymbol + ' ' + formattedPrice;
                            case 'right_space':
                                return formattedPrice + ' ' + currencySymbol;
                            default:
                                return currencySymbol + formattedPrice; // default to left if unknown position
                        }
                    }

                    // Function to update price
                    function updatePrice() {
                        const quantity = parseInt($(quantityInput).val()) || 1;
                        const price = basePrice * quantity;
                        const priceHtml = formatPrice(price);
                        $("button.single_add_to_cart_button span[data-price='true']").remove();
                        $(".single_add_to_cart_button").append("<span data-price='true'> - " + priceHtml + "</span>"); // Append price to button
                    }

                    // Initial price update
                    updatePrice();
                    // Event listeners for quantity changes
                    $(quantityInput).on('change', updatePrice);
                    // Event listener for quantity change using buttons
                    $('button.plus, button.minus').on('click', function() {
                        setTimeout(updatePrice, 0);
                    });
                });
                </script>
                <?php
            }

            return $button_text;
        }

        return $button_text;
    }

}
