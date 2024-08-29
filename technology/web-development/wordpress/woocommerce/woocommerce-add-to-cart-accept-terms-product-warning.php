<?php

// WooCommerce - "Add to cart" accept product warning terms
// Last update: 2024-07-24

if (class_exists('WooCommerce') && WC()) {

    add_action($hook_name = 'wp_footer', $callback = 'woocommerce_add_terms_checkbox_product_warning', $priority = 10, $accepted_args = 1);

    function woocommerce_add_terms_checkbox_product_warning()
    {
        if (is_product()) {

            // Settings
            $messages = [
                'product-warning-checkbox' => [
                    'de' => 'Ich bestätige, dass ich hiermit Rohkaffee bestelle.',
                    'en' => 'I confirm that I hereby order green coffee.',
                ],
                'product-warning-error' => [
                    'de' => 'Sie müssen mit den Bedingungen einverstanden sein, um fortzufahren.',
                    'en' => 'You must agree with the terms to proceed.',
                ],
            ];

            // Get current language
            $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';

            ?>
            <style>
                .checkbox-highlight {
                    border: 2px solid red;
                    background-color: #ffe6e6;
                    padding: 5px;
                }
            </style>

            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Function to handle found_variation event
                    function handleVariation(event, variation) {
                        const allowedValues = ["coffee-processing-green-coffee-de", "coffee-processing-green-coffee-en"];
                        const attributeValue = variation.attributes["attribute_pa_coffee-processing"];
                        const checkboxHtml = `
                            <div class="product-terms-checkbox" style="margin-bottom: 20px;">
                                <label>
                                    <input type="checkbox" name="checkbox_product_warning" id="checkbox_product_warning" />
                                    <span style="line-height: 20px;"><?php echo isset($messages['product-warning-checkbox'][$current_language]) ? $messages['product-warning-checkbox'][$current_language] : ''; ?></span>
                                </label>
                            </div>
                        `;
                        const $checkbox = $(checkboxHtml);
                        const $singleVariation = $(".woocommerce-variation.single_variation");

                        if (allowedValues.includes(attributeValue)) {
                            // Add checkbox only if it doesn't exist
                            if (!$('#checkbox_product_warning').length) {
                                $singleVariation.after($checkbox);
                            }
                            $checkbox.show();
                        } else {
                            // Remove checkbox if it exists
                            $('#checkbox_product_warning').parent().parent().remove();
                        }
                    }

                    // Handle found_variation event
                    $("form.variations_form").on("found_variation", handleVariation);

                    // Handle reset_variations event
                    $("form.variations_form").on("reset_data", function() {
                        // Remove checkbox if it exists
                        $('#checkbox_product_warning').parent().parent().remove();
                    });

                    // Handle form submit event
                    $("form.variations_form").on("submit", function(event) {
                        if ($('#checkbox_product_warning').length && !$('#checkbox_product_warning').prop('checked')) {
                            // Prevent form submission only if the checkbox exists
                            event.preventDefault();
                            // Show notification
                            const message = '<?php echo isset($messages['product-warning-error'][$current_language]) ? $messages['product-warning-error'][$current_language] : ''; ?>';
                            // Add the notice to the page
                            if (!$('.woocommerce-error').length) {
                                $('.woocommerce-notices-wrapper').append('<ul class="woocommerce-error" role="alert"><li>' + message + '</li></ul>');
                            }
                            // Scroll to the top of the page
                            $('html, body').animate({ scrollTop: 0 }, 'slow');

                            // Highlight the checkbox or text
                            $('#checkbox_product_warning').closest('label').addClass('checkbox-highlight');
                        }
                    });

                });
            </script>
            <?php
        }
    }

}
