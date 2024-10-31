<?php
// WooCommerce - Variable products price update after variable selection for Elementor's "Product Price" widget
// Last update: 2024-10-16


if (class_exists('WooCommerce') && WC()) {

    add_action($hook_name = 'wp_footer', $callback = 'custom_variation_price_update_script', $priority = 10, $accepted_args = 1);

    function custom_variation_price_update_script()
    {

        if (is_product()) {

            global $product;

            if ($product->is_type('variable')) {
                ?>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    console.log('Variation price update script loaded.');

                    // Decode HTML entities for currency symbol
                    const decodeHtmlEntities = (str) => {
                        const txt = document.createElement("textarea");
                        txt.innerHTML = str;
                        return txt.value;
                    };

                    // Retrieve WooCommerce settings
                    const currencySymbol = decodeHtmlEntities('<?php echo esc_js(get_woocommerce_currency_symbol()); ?>');
                    const currencyPosition = '<?php echo esc_js(get_option("woocommerce_currency_pos")); ?>';
                    const currencyDecimals = '<?php echo esc_js(wc_get_price_decimals()); ?>';
                    const locale = 'de-DE';

                    // Number formatter for price
                    const formatter = new Intl.NumberFormat(locale, {
                        minimumFractionDigits: parseInt(currencyDecimals),
                        maximumFractionDigits: parseInt(currencyDecimals)
                    });

                    // Function to format price based on currency position
                    function formatPrice(price) {
                        if (price === undefined) return ''; // Handle undefined price
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

                    // Listen to variation changes
                    $('form.variations_form').on('found_variation', function(event, variation) {
                        console.log('Variation found:', variation);

                        // Use display_price for formatting
                        const displayPrice = formatPrice(variation.display_price);
                        const priceContainer = $('.elementor-widget-woocommerce-product-price .elementor-widget-container p.price');

                        // Clear existing content in price container
                        priceContainer.html(''); // Use html() instead of text() to avoid stripping markup

                        // Append the formatted price
                        if (displayPrice) {
                            priceContainer.html(displayPrice);
                        }

                        // Handle sale price if necessary
                        if (variation.display_price !== variation.display_regular_price) {
                            console.log('Price with sale detected.');
                            const salePrice = formatPrice(variation.display_price);
                            const regularPrice = formatPrice(variation.display_regular_price);
                            console.log('Sale price formatted:', salePrice);
                            console.log('Regular price formatted:', regularPrice);

                            // Display sale price and regular price in HTML with WooCommerce structure
                            priceContainer.html('<del>' + regularPrice + '</del> <ins>' + salePrice + '</ins>');
                        } else {
                            // No sale price, show regular price
                            priceContainer.html(displayPrice);
                        }
                    });
                });
                </script>
                <?php
            }
        }
    }

}
