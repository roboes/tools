<?php

// WooCommerce - "Add to Cart" accept legal warning terms
// Last update: 2026-02-15


if (function_exists('WC') && !is_admin()) {

    add_action(hook_name: 'woocommerce_before_add_to_cart_button', callback: 'woocommerce_add_terms_checkbox_legal_warning', priority: 10, accepted_args: 1);

    function woocommerce_add_terms_checkbox_legal_warning(): void
    {
        if (!is_product()) {
            return;
        }

        $product = wc_get_product(get_the_ID());

        if (!$product instanceof WC_Product) {
            return;
        }

        $product_legal_details = $product->get_meta('product_legal_details', true);

        if (!empty($product_legal_details)) {

            // Settings
            $messages = [
                'legal-warning-checkbox' => [
                    'en' => 'I have read the product description/legal notice and I agree with the terms.',
                    'de' => 'Ich habe die Produktbeschreibung/Rechtliche Hinweise gelesen und bin mit den Bedingungen einverstanden.',
                ],
                'legal-warning-error' => [
                    'en' => 'You must agree with the terms to proceed.',
                    'de' => 'Du musst mit den Bedingungen einverstanden sein, um fortzufahren.',
                ],
            ];

            // Get current language (Polylang/WPML)
            $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

            $checkbox_text = $messages['legal-warning-checkbox'][$browsing_language]
                ?? array_first($messages['legal-warning-checkbox']);

            $error_text = $messages['legal-warning-error'][$browsing_language]
                ?? array_first($messages['legal-warning-error']);

            $html = '<style>
                    .woocommerce div.product form.cart .single_variation_wrap .woocommerce-variation-add-to-cart { margin-top: 30px; }

                    form.cart { display: flex !important; flex-wrap: wrap !important; }

                    .checkbox-highlight {
                        border: 2px solid red;
                        background-color: #ffe6e6;
                        padding: 5px;
                    }
                </style>';

            $html .= '<div class="product-terms-checkbox" style="margin-bottom: 10px;">
                    <label>
                        <input type="checkbox" name="checkbox_legal_warning" id="checkbox_legal_warning" />
                        <span style="line-height: 20px;">' . esc_html($checkbox_text) . '</span>
                    </label>
                </div>';

            $html .= '<script>
                    jQuery(document).ready(function($) {
                        const $checkbox = $(".product-terms-checkbox");
                        const $singleVariation = $(".woocommerce-variation.single_variation");

                        if ($checkbox.length && $singleVariation.length) {
                            $singleVariation.after($checkbox);
                        }

                        // Handle form submit (for regular Add to Cart)
                        $("form.cart").on("submit", function(event) {
                            if ($("#checkbox_legal_warning").length && !$("#checkbox_legal_warning").prop("checked")) {
                                event.preventDefault();
                                const message = "' . esc_js($error_text) . '";
                                if (!$(".woocommerce-error").length) {
                                    $(".woocommerce-notices-wrapper").append("<ul class=\"woocommerce-error\" role=\"alert\"><li>" + message + "</li></ul>");
                                }
                                $("html, body").animate({ scrollTop: 0 }, "slow");
                                $("#checkbox_legal_warning").closest("label").addClass("checkbox-highlight");
                            }
                        });

                        // Handle external product link click (for View product)
                        $(document).on("click", "a.product_type_external", function(event) {
                            if ($("#checkbox_legal_warning").length && !$("#checkbox_legal_warning").prop("checked")) {
                                event.preventDefault();
                                const message = "' . esc_js($error_text) . '";
                                if (!$(".woocommerce-error").length) {
                                    $(".woocommerce-notices-wrapper").append("<ul class=\"woocommerce-error\" role=\"alert\"><li>" + message + "</li></ul>");
                                }
                                $("html, body").animate({ scrollTop: 0 }, "slow");
                                $("#checkbox_legal_warning").closest("label").addClass("checkbox-highlight");
                            }
                        });
                    });
                </script>';

            echo $html;
        }

    }

}
