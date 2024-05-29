<?php
// WooCommerce - Display product currency and price inside "Add to Cart" button
// Last update: 2024-05-29

add_filter('woocommerce_product_single_add_to_cart_text', 'woocommerce_add_to_cart_product_price', 10, 2);

function woocommerce_add_to_cart_product_price($button_text, $product)
{
    // Check if WooCommerce is active and it's a product page
    if (WC() && is_product()) {
        // Check if the product is a variable product
        if ($product->is_type('variable')) {
            // Get variations data
            $variations_data = array_column($product->get_available_variations(), 'display_price', 'variation_id');
            ?>
            <script>
            jQuery(function($) {
                // JSON data for variations
                var jsonData = <?php echo json_encode($variations_data); ?>;
                var inputVID = 'input.variation_id';
                var quantityInput = 'input[name="quantity"]';
                var currencySymbol = '<?php echo esc_html(get_woocommerce_currency_symbol()); ?>';
                var currencyPosition = '<?php echo esc_html(get_option("woocommerce_currency_pos")); ?>';

                // Number formatter for price
                var formatter = new Intl.NumberFormat('de-DE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                // Function to update price
                function updatePrice() {
                    var vid = $(inputVID).val();
                    var quantity = parseInt($(quantityInput).val()) || 1;
                    if (vid && jsonData[vid] !== undefined) {
                        var price = jsonData[vid] * quantity;
                        var formattedPrice = formatter.format(price);
                        $("button.single_add_to_cart_button span[data-price='true']").remove();
                        // Build price HTML based on currency position
                        var priceHtml = currencyPosition === 'right' ? formattedPrice + currencySymbol : currencySymbol + formattedPrice;
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

            return $button_text;
        } else {
            // For other product types, display regular product price
            $product_price = wc_price(wc_get_price_to_display($product));
            return $button_text . ' — ' . strip_tags($product_price);
        }
    }

    return $button_text;
}
?>
