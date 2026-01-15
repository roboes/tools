<?php

// WooCommerce - "Add to cart" accept legal warning terms
// Last update: 2026-01-13

if (function_exists('WC') && !is_admin()) {

    add_action(hook_name: 'woocommerce_before_add_to_cart_button', callback: 'woocommerce_add_terms_checkbox_legal_warning', priority: 10, accepted_args: 1);

    function woocommerce_add_terms_checkbox_legal_warning(): void
    {
        if (!is_product()) {
            return;
        }

        global $product;

        if (!$product instanceof WC_Product) {
            return;
        }

        $product_legal_details = $product->get_meta('product_legal_details', true);

        if (!empty($product_legal_details)) {

            // Settings
            $messages = [
                'legal-warning-checkbox' => [
                    'de' => 'Ich habe die Produktbeschreibung/Rechtliche Hinweise gelesen und bin mit den Bedingungen einverstanden.',
                    'en' => 'I have read the product description/legal notice and I agree with the terms.',
                ],
                'legal-warning-error' => [
                    'de' => 'Du musst mit den Bedingungen einverstanden sein, um fortzufahren.',
                    'en' => 'You must agree with the terms to proceed.',
                ],
            ];

            // Get current language
            $current_language = 'en';
            if (function_exists('pll_current_language')) {
                if (pll_current_language('slug') && in_array(needle: pll_current_language('slug'), haystack: pll_languages_list(['fields' => 'slug']), strict: true)) {
                    $current_language = pll_current_language('slug');
                }
            }

            $checkbox_text = $messages['legal-warning-checkbox'][$current_language]
                ?? array_first($messages['legal-warning-checkbox']);

            $error_text = $messages['legal-warning-error'][$current_language]
                ?? array_first($messages['legal-warning-error']);

            $html = '<div class="product-terms-checkbox" style="margin-bottom: 20px;">
					<label>
						<input type="checkbox" name="checkbox_legal_warning" id="checkbox_legal_warning" />
						<span style="line-height: 20px;">' . esc_html($checkbox_text) . '</span>
					</label>
				</div>';

            $html .= '<style>
					.checkbox-highlight {
						border: 2px solid red;
						background-color: #ffe6e6;
						padding: 5px;
					}
				</style>';

            $html .= '<script>
					jQuery(document).ready(function($) {
						const $checkbox = $(".product-terms-checkbox");
						const $singleVariation = $(".woocommerce-variation.single_variation");

						if ($checkbox.length && $singleVariation.length) {
							$singleVariation.after($checkbox);
						}

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
					});
				</script>';

            echo $html;
        }
    }
}
