<?php
// WooCommerce - Product variation external URL with price display
// Last update: 2026-02-09

if (function_exists('WC')) {

    if (is_admin()) {

        // Add external URL field to variations
        add_action(
            hook_name: 'woocommerce_product_after_variable_attributes',
            callback: function ($loop, $variation_data, $variation) {
                woocommerce_wp_text_input([
                    'id' => "variation_external_url_{$loop}",
                    'name' => "product_variation_external_url[{$loop}]",
                    'label' => __('External URL', 'woocommerce'),
                    'value' => get_post_meta(
                        post_id: $variation->ID,
                        key: '_product_variation_external_url',
                        single: true
                    ),
                    'wrapper_class' => 'form-row form-row-full',
                ]);
            },
            priority: 10,
            accepted_args: 3
        );

        // Save external URL
        add_action(
            hook_name: 'woocommerce_save_product_variation',
            callback: function ($variation_id, $loop) {
                if (!isset($_POST['product_variation_external_url'][$loop])) {
                    return;
                }

                $external_url = esc_url_raw($_POST['product_variation_external_url'][$loop]);
                update_post_meta($variation_id, '_product_variation_external_url', $external_url);

                // Sync to all language translations (Polylang/WPML)
                $product_variation_languages = apply_filters('wpml_active_languages', null);

                if (!$product_variation_languages) {
                    return;
                }

                foreach ($product_variation_languages as $language_code => $language) {
                    $translated_id = apply_filters('wpml_object_id', $variation_id, 'product_variation', false, $language_code);
                    if ($translated_id && $translated_id != $variation_id) {
                        update_post_meta($translated_id, '_product_variation_external_url', $external_url);
                    }
                }
            },
            priority: 10,
            accepted_args: 2
        );

    }

    if (!is_admin()) {

        // Pass external URL to frontend
        add_filter(
            hook_name: 'woocommerce_available_variation',
            callback: function ($data, $product, $variation) {
                $url = get_post_meta(
                    post_id: $variation->get_id(),
                    key: '_product_variation_external_url',
                    single: true
                );
                if ($url) {
                    $data['external_url'] = esc_url(url: $url);
                }
                return $data;
            },
            priority: 10,
            accepted_args: 3
        );

        // Convert button to external link AND handle price display
        add_action(
            hook_name: 'wp_footer',
            callback: function () {

                if (!is_product()) {
                    return;
                }

                // Get current language (Polylang/WPML)
                // $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';
                $browsing_language = 'de';

                $currency_symbol = get_woocommerce_currency_symbol();
                $currency_pos = get_option('woocommerce_currency_pos');
                $decimals = wc_get_price_decimals();
                $locale = match($browsing_language) {
                    'pt'    => 'pt-BR',
                    'de'    => 'de-DE',
                    default => 'en-US'
                };
                ?>
                <script>
                jQuery(function($) {
                    var originalButtonHtml = '';

                    // Re-use your format logic
                    function formatPriceInternal(price) {
                        const formatter = new Intl.NumberFormat(<?php echo wp_json_encode($locale); ?>, {
                            minimumFractionDigits: <?php echo $decimals; ?>,
                            maximumFractionDigits: <?php echo $decimals; ?>
                        });
                        const formattedPrice = formatter.format(price);
                        const symbol = <?php echo wp_json_encode($currency_symbol); ?>;

                        switch(<?php echo wp_json_encode($currency_pos); ?>) {
                            case "left": return symbol + formattedPrice;
                            case "right": return formattedPrice + symbol;
                            case "left_space": return symbol + " " + formattedPrice;
                            case "right_space": return formattedPrice + " " + symbol;
                            default: return symbol + formattedPrice;
                        }
                    }

                    $('form.variations_form')
                        .on('found_variation', function(e, variation) {
                            var $button = $('.single_add_to_cart_button, a.product_type_external');

                            if (!originalButtonHtml && $button.hasClass('single_add_to_cart_button')) {
                                originalButtonHtml = $button.prop('outerHTML');
                            }

                            if (variation.external_url) {
                                var buttonText = '<?php echo esc_js(get_option('woocommerce_external_products_button_text') ?: __('View product', 'woocommerce')); ?>';

                                // Calculate price for the external link
                                var quantity = parseInt($('input[name="quantity"]').val(), 10) || 1;
                                var priceHtml = ' - ' + formatPriceInternal(variation.display_price * quantity);

                                $button.replaceWith(
                                    '<a href="' + variation.external_url + '" class="single_add_to_cart_button button alt wp-element-button product_type_external" rel="nofollow" target="_blank">' + 
                                    buttonText + '<span data-price>' + priceHtml + '</span>' + 
                                    '</a>'
                                );
                            }
                        })
                        .on('reset_data', function() {
                            if (originalButtonHtml) {
                                $('.single_add_to_cart_button, a.product_type_external').replaceWith(originalButtonHtml);
                            }
                        });
                });
                </script>
                <?php

            },
            priority: 10,
            accepted_args: 0
        );

    }

}
