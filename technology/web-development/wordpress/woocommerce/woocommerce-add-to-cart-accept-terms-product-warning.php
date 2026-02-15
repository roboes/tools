<?php
// WooCommerce - "Add to Cart" accept product warning terms
// Last update: 2026-02-15


if (function_exists('WC') && !is_admin()) {

    add_action(hook_name: 'wp_footer', callback: 'woocommerce_add_terms_checkbox_product_warning', priority: 10, accepted_args: 1);

    function woocommerce_add_terms_checkbox_product_warning(): void
    {
        if (!is_product()) {
            return;
        }

        $product = wc_get_product(get_the_ID());

        if (!$product instanceof WC_Product) {
            return;
        }

        // Settings
        $messages = [
            'product-warning-checkbox' => [
                'en' => 'I confirm that I hereby order green coffee.',
                'de' => 'Ich bestätige, dass ich hiermit Rohkaffee bestelle.',
            ],
            'product-warning-error' => [
                'en' => 'You must agree with the terms to proceed.',
                'de' => 'Du musst mit den Bedingungen einverstanden sein, um fortzufahren.',
            ],
        ];
        $attributes_allowed = ["coffee-processing-green-coffee-de", "coffee-processing-green-coffee-en"];

        // Get current language (Polylang/WPML)
        $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

        $has_allowed_attribute = false;

        foreach ($product->get_attributes() as $attribute) {
            $terms = wp_get_post_terms($product->get_id(), $attribute->get_name());

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                if (is_object($term) && in_array($term->slug, $attributes_allowed, true)) {
                    $has_allowed_attribute = true;
                    break 2;
                }
            }
        }

        if ($has_allowed_attribute) {
            ?>
            <style>
                form.cart { display: flex !important; flex-wrap: wrap !important; }
                
                .checkbox-highlight {
                    border: 2px solid red;
                    background-color: #ffe6e6;
                    padding: 5px;
                }
            </style>

            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    if (!$('#checkbox-spacing-style').length) {
                        $('head').append(`
                            <style id="checkbox-spacing-style">
                                .woocommerce div.product form.cart .single_variation_wrap .woocommerce-variation-add-to-cart { margin-top: 30px; }
                            </style>
                        `);
                    }
                    function handleVariation(event, variation) {
                        const allowedValues = ["coffee-processing-green-coffee-de", "coffee-processing-green-coffee-en"];
                        const attributeValue = variation.attributes["attribute_pa_coffee-processing"];
                        const checkboxHtml = `
                            <div class="product-terms-checkbox">
                                <label>
                                    <input type="checkbox" name="checkbox_product_warning" id="checkbox_product_warning" />
                                    <span style="line-height: 20px;"><?php echo esc_js($messages['product-warning-checkbox'][$browsing_language] ?? ''); ?></span>
                                </label>
                            </div>
                        `;
                        const $checkbox = $(checkboxHtml);
                        const $singleVariation = $(".woocommerce-variation.single_variation");

                        if (allowedValues.includes(attributeValue)) {
                            if (!$('#checkbox_product_warning').length) {
                                $singleVariation.after($checkbox);
                            }
                            $checkbox.show();
                        } else {
                            $('#checkbox_product_warning').closest('.product-terms-checkbox').remove();
                        }
                    }

                    $("form.variations_form").on("found_variation", handleVariation);

                    $("form.variations_form").on("reset_data", function() {
                        $('#checkbox_product_warning').closest('.product-terms-checkbox').remove();
                    });

                    $("form.variations_form").on("submit", function(event) {
                        if ($('#checkbox_product_warning').length && !$('#checkbox_product_warning').prop('checked')) {
                            event.preventDefault();
                            const message = '<?php echo esc_js($messages['product-warning-error'][$browsing_language] ?? ''); ?>';
                            if (!$('.woocommerce-error').length) {
                                $('.woocommerce-notices-wrapper').first().append('<ul class="woocommerce-error" role="alert"><li>' + message + '</li></ul>');
                            }
                            $('html, body').animate({ scrollTop: 0 }, 'slow');
                            $('#checkbox_product_warning').closest('label').addClass('checkbox-highlight');
                        }
                    });
                });
            </script>
            <?php
        }
    }
}
