<?php
// WooCommerce - Variable products price update after variable selection for Elementor's "Product Price" widget
// Last update: 2026-01-15


if (function_exists('WC') && !is_admin()) {

    add_action(hook_name: 'wp_footer', callback: 'custom_variation_price_update_script', priority: 10, accepted_args: 0);

    function custom_variation_price_update_script(): void
    {
        if (!is_product()) {
            return;
        }

        $product = wc_get_product(get_queried_object_id());

        if (!$product instanceof WC_Product || !$product->is_type(type: 'variable')) {
            return;
        }

        try {
            $currency_symbol = get_woocommerce_currency_symbol();
            $currency_pos = get_option(option: 'woocommerce_currency_pos');
            $decimals = (int) wc_get_price_decimals();

            // Get current language (Polylang/WPML)
            $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

            $locale = match ($browsing_language) {
                'de' => 'de-DE',
                default => 'en-US',
            };

            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                const priceContainer = $('.elementor-widget-woocommerce-product-price .elementor-widget-container p.price');
                if (!priceContainer.length) return;

                // Cache original price range
                const originalPriceHtml = priceContainer.html();

                const decodeHtmlEntities = (str) => {
                    const txt = document.createElement("textarea");
                    txt.innerHTML = str;
                    return txt.value;
                };

                const currencySymbol = decodeHtmlEntities('<?php echo esc_js($currency_symbol); ?>');
                const currencyPosition = '<?php echo esc_js($currency_pos); ?>';
                const currencyDecimals = <?php echo $decimals; ?>;
                const locale = '<?php echo esc_js($locale); ?>';

                const formatter = new Intl.NumberFormat(locale, {
                    minimumFractionDigits: currencyDecimals,
                    maximumFractionDigits: currencyDecimals
                });

                function formatPrice(price) {
                    if (price === undefined || price === null) return '';
                    let formattedPrice = formatter.format(price);
                    switch(currencyPosition) {
                        case 'left': return currencySymbol + formattedPrice;
                        case 'right': return formattedPrice + currencySymbol;
                        case 'left_space': return currencySymbol + ' ' + formattedPrice;
                        case 'right_space': return formattedPrice + ' ' + currencySymbol;
                        default: return currencySymbol + formattedPrice;
                    }
                }

                $('form.variations_form')
                    .on('found_variation', function(event, variation) {
                        let htmlContent = '';
                        if (variation.display_price !== variation.display_regular_price) {
                            const salePrice = formatPrice(variation.display_price);
                            const regularPrice = formatPrice(variation.display_regular_price);
                            htmlContent = '<del aria-hidden="true">' + regularPrice + '</del> <ins>' + salePrice + '</ins>';
                        } else {
                            htmlContent = formatPrice(variation.display_price);
                        }
                        priceContainer.html(htmlContent);
                    })
                    .on('reset_data', function() {
                        priceContainer.html(originalPriceHtml);
                    });
            });
            </script>
            <?php
        } catch (Throwable $error) {
            if (defined(constant_name: 'WP_DEBUG') && WP_DEBUG) {
                error_log(message: $error->getMessage());
            }
        }
    }
}
