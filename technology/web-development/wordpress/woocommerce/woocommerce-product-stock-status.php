// WooCommerce product stock status using Polylang
// Elementor's "Product Stock" widget only works with "Stock management" (i.e. for products where "Track stock quantity for this product" is activated)

add_shortcode($tag='woocommerce_product_stock_status', $callback='stock_status');

function stock_status($atts) {
    global $product;

    if ( WC() && $product ) {

		$stock_status = $product->get_stock_status();

		// Get the current language slug
		$current_language = pll_current_language('slug');

		// Load the translation domain for your plugin
		$plugin_domain = 'woocommerce';

		// Define the path to the languages directory within your plugin
		$languages_dir = dirname(plugin_basename(__FILE__)) . '/languages';

		// Load the translation files
		load_plugin_textdomain($plugin_domain, false, $languages_dir);

		if ('instock' == $stock_status) {
			// Get the translated string for "In stock"
			return __('In stock', $plugin_domain);
		} else {
			// Get the translated string for "Out of stock"
			return __('Out of stock', $plugin_domain);
		}
	}
}
