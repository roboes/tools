// WooCommerce display product currency and price inside "Add to Cart" button

add_filter( $hook_name='woocommerce_product_single_add_to_cart_text', $callback='woocommerce_add_to_cart_product_price', $priority=10, $accepted_args=2 );

function woocommerce_add_to_cart_product_price( $button_text, $product ) {
	if ( WC() && is_product() ) {
		// Variable products
		if ( $product->is_type( 'variable' ) ) {
			// Shop and archives
			if ( !is_product() ) {
				$product_price = wc_price( wc_get_price_to_display( $product, array( 'price' => $product->get_variation_price() ) ) );
				return $button_text . ' - From ' . strip_tags( $product_price );
			}
			// Single product pages
			else {
				$variations_data = []; // Initialize variations data array

				// Loop through available variations
				foreach ( $product->get_available_variations() as $variation ) {
					// Set the corresponding price for each variation ID ( used in jQuery )
					$variations_data[$variation['variation_id']] = $variation['display_price'];
				}
				?>
				<script>
				 jQuery( function( $ ) {
					var jsonData = <?php echo json_encode( $variations_data ); ?>,
						inputVID = 'input.variation_id',
						quantityInput = 'input[name="quantity"]'; // Add this line

					// Function to update the price based on variation and quantity
					function updatePrice() {
						var vid = $( inputVID ).val(); // Variation ID
						var quantity = parseInt( $( quantityInput ).val() );

						if ( vid && jsonData.hasOwnProperty( vid ) ) {
							var price = jsonData[vid] * quantity;
							var formattedPrice = price.toFixed( 2 );

							// Retrieve currency symbol and position from WooCommerce settings
							var currencySymbol = '<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>';
							var currencyPosition = '<?php echo esc_html( $currency_position ); ?>';

							// Get thousand separator and decimal separator from WooCommerce settings
							var thousandSeparator = '<?php echo esc_html( wc_get_price_thousand_separator() ); ?>';
							var decimalSeparator = '<?php echo esc_html( wc_get_price_decimal_separator() ); ?>';

							// Format the price with separators
							formattedPrice = formattedPrice.replace( '.', decimalSeparator );
							formattedPrice = formattedPrice.replace( /\B( ?=( \d{3} )+( ?!\d ) )/g, thousandSeparator );

							// Change price dynamically
							$( "button.single_add_to_cart_button.button.alt span" ).remove();
							if ( currencyPosition === 'right' ) {
								$( ".single_add_to_cart_button" ).append( "<span>" + " - " + formattedPrice + currencySymbol + "</span>" );
							} else {
								$( ".single_add_to_cart_button" ).append( "<span>" + " - " + currencySymbol + formattedPrice + "</span>" );
							}
						} else {
							// No variation selected, remove the price
							$( "button.single_add_to_cart_button.button.alt span" ).remove();
						}
					}

					// Initial price update
					updatePrice();

					// Update price when variation or quantity changes
					$( inputVID + ', ' + quantityInput ).on( 'change', updatePrice );

					// Update price when plus or minus buttons are clicked
					$( 'button.plus, button.minus' ).on( 'click', function() {
						setTimeout( updatePrice, 0 ); // Delay execution to ensure immediate update
					} );

				 } );
				</script>
				<?php

				return $button_text;
			}
		}
		// All other product types
		else {
			$product_price = wc_price( wc_get_price_to_display( $product ) );
			return $button_text . ' — ' . strip_tags( $product_price );
		}
	}
}
