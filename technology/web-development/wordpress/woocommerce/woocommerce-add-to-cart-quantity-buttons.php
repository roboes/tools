<?php
// WooCommerce - "Add to cart" quantity buttons
// Last update: 2024-05-29

// Notes: Use this snippet code together with the plugin "WC Variations Radio Buttons" (https://github.com/8manos/wc-variations-radio-buttons)

add_action($hook_name = 'woocommerce_after_add_to_cart_quantity', $callback = 'ts_quantity_plus_sign', $priority = 10, $accepted_args = 1);

function ts_quantity_plus_sign()
{
    if (WC() && is_product()) {
        echo '<button type="button" class="plus" >+</button>';
    }
}


add_action($hook_name = 'woocommerce_before_add_to_cart_quantity', $callback = 'ts_quantity_minus_sign', $priority = 10, $accepted_args = 1);

function ts_quantity_minus_sign()
{
    if (WC() && is_product()) {
        echo '<button type="button" class="minus" >-</button>';
    }
}


add_action($hook_name = 'wp_footer', $callback = 'ts_quantity_plus_minus', $priority = 10, $accepted_args = 1);

function ts_quantity_plus_minus()
{
    if (WC() && is_product()) {
        ?>
		<script type="text/javascript">
		 jQuery(document).ready(function ($) {
			$("form.cart").on("click", "button.plus, button.minus", function () {
			 // Get current quantity values
			 var qty = $(this).closest("form.cart").find(".qty");
			 var val = parseFloat(qty.val());
			 var max = parseFloat(qty.attr("max"));
			 var min = parseFloat(qty.attr("min"));
			 var step = parseFloat(qty.attr("step"));

			 // Change the value if plus or minus
			 if ($(this).is(".plus")) {
				if (max && max <= val) {
				 qty.val(max);
				} else {
				 qty.val(val + step);
				}
			 } else {
				if (min && min >= val) {
				 qty.val(min);
				} else if (val > 1) {
				 qty.val(val - step);
				}
			 }
			});
		 });
		</script>
		<?php
    }
}
