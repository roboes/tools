<?php

// WooCommerce - "Add to cart" accept terms
// Last update: 2024-06-03

add_action($hook_name = 'woocommerce_before_add_to_cart_button', $callback = 'woocommerce_add_terms_checkbox', $priority = 10, $accepted_args = 1);

function woocommerce_add_terms_checkbox()
{

    if (WC()) {

        global $product;

        // Settings
        $messages = [
            'legal-warning-message' => [
                'de' => 'Ich habe die Produktbeschreibung/Rechtliche Hinweise gelesen und bin mit den Bedingungen einverstanden.',
                'en' => 'I have read the product description/legal notice and I agree with the terms.',
            ],
        ];

        // Get current language
        $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';

        // Get the custom field value
        $product_legal_details = get_post_meta($product->get_id(), 'product_legal_details', true);

        // Check if the custom field is not empty and language is supported
        if (!empty($product_legal_details) && isset($messages['legal-warning-message'][$current_language])) {
            $html = '<div class="product-terms-checkbox" style="margin-bottom: 20px;">
                <label>
                    <input type="checkbox" name="terms_conditions_checkbox" id="terms_conditions_checkbox" />
                    <span style="line-height: 20px;">' . $messages['legal-warning-message'][$current_language] . '</span>
                </label>
            </div>';

            $html .= '<script>
                jQuery(document).ready(function($) {
                    // Find the elements to be rearranged
                    const $checkbox = $(".product-terms-checkbox");
                    const $singleVariation = $(".woocommerce-variation.single_variation");

                    // Check if both elements exist
                    if ($checkbox.length && $singleVariation.length) {
                        // Move the checkbox after the single_variation element
                        $singleVariation.after($checkbox);
                    }
                });
            </script>';

            echo $html;
        }
    }
}

add_filter($hook_name = 'woocommerce_add_to_cart_validation', $callback = 'woocommerce_add_to_cart_accept_terms_validator', $priority = 10, $accepted_args = 3);

function woocommerce_add_to_cart_accept_terms_validator($passed, $product_id, $quantity)
{

    // Settings
    $messages = [
        'legal-warning-error' => [
            'de' => 'Sie müssen mit den Bedingungen einverstanden sein, um fortzufahren.',
            'en' => 'You must agree with the terms to proceed.',
        ],
    ];

    // Get current language
    $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';

    $product_legal_details = get_post_meta($product_id, 'product_legal_details', true);

    if (!empty($product_legal_details) && empty($_POST['terms_conditions_checkbox'])) {
        // Replace wc_add_notice with the language-specific message
        wc_add_notice($messages['legal-warning-error'][$current_language], 'error');
        return false;
    }
    return $passed;
}
