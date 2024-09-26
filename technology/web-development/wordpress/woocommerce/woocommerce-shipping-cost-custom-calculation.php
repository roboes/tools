<?php

// WooCommerce - Shipping cost custom calculation based on the best-fitting shipping package for the cart items based on their dimensions, updates the cart item dimensions and total weight to reflect the selected package
// Last update: 2024-09-20


// add_filter($hook_name = 'woocommerce_add_to_cart_validation', $callback = 'validate_package_fit_on_add_to_cart', $priority = 10, $accepted_args = 4);
// add_action($hook_name = 'woocommerce_before_cart_totals', $callback = 'check_cart_for_packages', $priority = 10, $accepted_args = 2);
// add_action($hook_name = 'woocommerce_after_cart_item_quantity_update', $callback = 'check_cart_for_packages', $priority = 10, $accepted_args = 2);
// add_action($hook_name = 'woocommerce_cart_item_removed', $callback = 'check_cart_for_packages', $priority = 10, $accepted_args = 2);
// add_action($hook_name = 'woocommerce_cart_item_restored', $callback = 'check_cart_for_packages', $priority = 10, $accepted_args = 2);


// Modify shipping package
// add_filter($hook_name = 'woocommerce_cart_shipping_packages', $callback = 'modify_shipping_package', $priority = 10, $accepted_args = 1);
// add_filter($hook_name = 'woocommerce_get_cart_item_from_session', $callback = 'override_cart_item_dimensions', $priority = 10, $accepted_args = 3);


// Add best package fit inside WooCommerce orders using a custom field - run action once (run on WP Console)
// $orders = wc_get_orders(['status' => ['processing', 'completed'], 'limit' => -1]);
// foreach ($orders as $order) {
// calculate_and_store_package_best_fit($order->get_id());
// }


// Test
// calculate_package_best_fit(array(array('quantity' => 1, 'length' => 8, 'width' => 11, 'height' => 23, 'weight' => 518), array('quantity' => 1, 'length' => 8, 'width' => 11, 'height' => 23, 'weight' => 518)));


// Add best package fit inside WooCommerce orders using a custom field
add_action($hook_name = 'woocommerce_order_status_processing', $callback = 'calculate_and_store_package_best_fit', $priority = 10, $accepted_args = 1);
add_action($hook_name = 'woocommerce_admin_order_data_after_order_details', $callback = 'display_custom_order_meta', $priority = 10, $accepted_args = 1);


function get_cart_items()
{

    $cart_items = [];

    // Loop through all items in the cart
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = wc_get_product($cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id']);
        $quantity = $cart_item['quantity']; // Use the cart's quantity

        // Add the product dimensions and weight for each unit in the cart
        for ($i = 0; $i < $quantity; $i++) {
            $cart_items[] = [
                'length' => (float) $product->get_length(),
                'width' => (float) $product->get_width(),
                'height' => (float) $product->get_height(),
                'weight' => (float) $product->get_weight(),
                'quantity' => $quantity,
            ];
        }
    }

    return $cart_items;
}


function calculate_package_best_fit($items)
{

    // Settings
    $packages = [
        ['name' => 'Box S1', 'length' => 14, 'width' => 14, 'height' => 15, 'weight' => 90],
        ['name' => 'Box S2', 'length' => 14, 'width' => 14, 'height' => 25, 'weight' => 101],
        ['name' => 'Box S3', 'length' => 14, 'width' => 14, 'height' => 35, 'weight' => 118],
        ['name' => 'Box M', 'length' => 35, 'width' => 25, 'height' => 15, 'weight' => 172],
        ['name' => 'Box L', 'length' => 38, 'width' => 38, 'height' => 20, 'weight' => 316],
    ];

    // Initialize an array to hold the results
    $packages_fit = [];

    // Calculate the total dimensions and volume of the items
    $total_quantity = 0;
    foreach ($items as $item) {
        $total_quantity += $item['quantity'];
    }

    // Get dimensions for a single item
    $item_single_length = $items[0]['length'];
    $item_single_width = $items[0]['width'];
    $item_single_height = $items[0]['height'];

    // Calculate the total volume of the items
    $item_volume = $item_single_length * $item_single_width * $item_single_height;
    $total_volume = $item_volume * $total_quantity;

    // Check each package to see if items fit
    foreach ($packages as $package) {
        // Calculate the volume of the package
        $package_volume = $package['length'] * $package['width'] * $package['height'];

        // If total item volume is less than package volume, check dimensions
        if ($total_volume <= $package_volume) {
            // Check possible orientations
            $fits = 0;

            // Orientation 1
            $fits += floor($package['length'] / $item_single_length) *
                     floor($package['width'] / $item_single_width) *
                     floor($package['height'] / $item_single_height);

            // Orientation 2
            $fits += floor($package['length'] / $item_single_height) *
                     floor($package['width'] / $item_single_width) *
                     floor($package['height'] / $item_single_length);

            // Orientation 3
            $fits += floor($package['length'] / $item_single_width) *
                     floor($package['width'] / $item_single_length) *
                     floor($package['height'] / $item_single_height);

            // Store results if it fits
            if ($fits >= $total_quantity) {
                $packages_fit[] = $package; // Store the whole package row
            }
        }
    }

    // Return only the first fitting package or null
    return !empty($packages_fit) ? $packages_fit[0] : null;
}


function modify_shipping_package($packages)
{
    $cart_items = get_cart_items();
    $package_best_fit = calculate_package_best_fit($cart_items);

    // Logs
    error_log('Cart Items: ' . json_encode($cart_items));
    error_log('Best fit package: ' . json_encode($package_best_fit));

    if ($package_best_fit) {
        // Calculate the total weight (package + products)
        $total_products_weight = array_sum(array_column($cart_items, 'weight')); // Sum of all product weights
        $total_weight = $total_products_weight + $package_best_fit['weight'];

        // Logs
        error_log('modify_shipping_package total_products_weight: ' . $total_products_weight);
        error_log('modify_shipping_package package_best_fit: ' . $package_best_fit['weight']);
        error_log('modify_shipping_package total_weight: ' . $total_weight);

        // Replace package weight and dimensions with the selected package's values
        foreach ($packages as &$package) {
            $package['contents_cost'] = WC()->cart->get_subtotal();

            // Replace package dimensions with the package dimensions
            $package['length'] = $package_best_fit['length'];
            $package['width'] = $package_best_fit['width'];
            $package['height'] = $package_best_fit['height'];

            // Set the total weight (package + products)
            $package['weight'] = $total_weight;

        }

        // Logs
        error_log('Updated package: ' . json_encode($package));

    } else {
        // Logs
        error_log('No suitable package found.');
    }

    return $packages;
}


function override_cart_item_dimensions($cart_item, $values, $key)
{
    static $first_item_processed = false;

    // Get all cart items
    $cart_items = get_cart_items();

    // Calculate the best-fitting package
    $package_best_fit = calculate_package_best_fit($cart_items);

    // Retrieve product data for logging
    $product_id = $cart_item['product_id'];
    $product_name = wc_get_product($product_id)->get_name();

    // Store original values
    $original_length = $cart_item['data']->get_length();
    $original_width = $cart_item['data']->get_width();
    $original_height = $cart_item['data']->get_height();
    $original_weight = $cart_item['data']->get_weight();

    if ($package_best_fit) {
        if (!$first_item_processed) {
            // Override the dimensions and weight for the first item
            $cart_item['data']->set_length($package_best_fit['length']);
            $cart_item['data']->set_width($package_best_fit['width']);
            $cart_item['data']->set_height($package_best_fit['height']);

            // Calculate the total weight (package + products)
            $total_products_weight = array_sum(array_column($cart_items, 'weight')); // Sum of all product weights
            $total_weight = $total_products_weight + $package_best_fit['weight'];
            $cart_item['data']->set_weight($total_weight);

            // Mark that the first item has been processed
            $first_item_processed = true;

            // Log the updated dimensions and weight for the first item
            error_log('First item processed - Product ID: ' . $product_id . ', Name: ' . $product_name);
            error_log('Original item dimensions: Length: ' . $original_length . ', Width: ' . $original_width . ', Height: ' . $original_height);
            error_log('Original item weight: ' . $original_weight);
            error_log('Updated item dimensions for first item: Length: ' . $cart_item['data']->get_length() . ', Width: ' . $cart_item['data']->get_width() . ', Height: ' . $cart_item['data']->get_height());
            error_log('Updated item weight for first item: ' . $cart_item['data']->get_weight());
        } else {
            // For all other items, set dimensions and weight to zero
            $cart_item['data']->set_length(0);
            $cart_item['data']->set_width(0);
            $cart_item['data']->set_height(0);
            $cart_item['data']->set_weight(0);

            // Log the updated dimensions and weight for other items
            error_log('Other item - Product ID: ' . $product_id . ', Name: ' . $product_name);
            error_log('Original item dimensions: Length: ' . $original_length . ', Width: ' . $original_width . ', Height: ' . $original_height);
            error_log('Original item weight: ' . $original_weight);
            error_log('Updated item dimensions for other item: Length: ' . $cart_item['data']->get_length() . ', Width: ' . $cart_item['data']->get_width() . ', Height: ' . $cart_item['data']->get_height());
            error_log('Updated item weight for other item: ' . $cart_item['data']->get_weight());
        }
    }

    return $cart_item;
}


// Function to check if items fit into a predefined package and display a notice if they do not
function check_cart_for_packages($cart_item_key = null, $new_quantity = null)
{
    $cart = WC()->cart;
    $cart_items = get_cart_items();
    $package_best_fit = calculate_package_best_fit($cart_items);

    // Logs
    error_log('Best package fit: ' . json_encode($package_best_fit));
    error_log('Cart items: ' . json_encode($cart_items));

    // If the package is not suitable, revert to previous quantity or remove product
    if (!$package_best_fit) {
        // Get the cart item and previous quantity
        $cart_item = isset($cart->cart_contents[$cart_item_key]) ? $cart->cart_contents[$cart_item_key] : null;
        $product_cart_quantity = $cart_item ? $cart_item['quantity'] : null;

        // Get current language
        $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';

        if ($cart_item_key && $new_quantity !== null) {
            if ($product_cart_quantity !== null) {
                // Calculate the adjusted quantity or remove product if necessary
                $adjusted_quantity = max(1, $product_cart_quantity - $new_quantity);

                if ($adjusted_quantity > 0) {
                    $cart->cart_contents[$cart_item_key]['quantity'] = $adjusted_quantity;
                    if ($current_language === 'pt') {
                        $message = __('A quantidade do item foi ajustada para caber num pacote de envio. Ajuste seu carrinho removendo alguns itens ou alterando as quantidades e tente novamente. Se você tiver algum pedido especial que não esteja listado em nossa loja online, entre em contato conosco.');
                    } else {
                        $message = __('The item quantity has been adjusted to fit in a shipping package. Please adjust your cart by removing some items or changing the quantities and try again. If you have any special requests that are not listed in our online shop, please feel free to contact us.');
                    }
                } else {
                    $cart->remove_cart_item($cart_item_key);
                    if ($current_language === 'pt') {
                        $message = __('O produto foi removido do seu carrinho porque o total de itens do carrinho não pode ser acomodado em nenhuma das caixas de envio disponíveis. Ajuste seu carrinho removendo alguns itens ou alterando as quantidades e tente novamente. Se você tiver algum pedido especial que não esteja listado em nossa loja online, entre em contato conosco.');
                    } else {
                        $message = __('The product was removed from your cart because the total cart items cannot be accommodated in any available shipping boxes. Please adjust your cart by removing some items or changing the quantities and try again. If you have any special requests that are not listed in our online shop, please feel free to contact us.');
                    }
                }

                wc_add_notice($message, 'error');

            }
        }
    }
}


// Function to validate package fit on cart item addition
function validate_package_fit_on_add_to_cart($passed, $product_id, $quantity, $variation_id = '')
{

    // Get current cart items
    $cart_items = get_cart_items();

    // Create a new product object
    $product = wc_get_product($variation_id ? $variation_id : $product_id);

    // Get the dimensions and weight of the product to be added
    $product_dimensions = [
        'length' => (float) $product->get_length(),
        'width' => (float) $product->get_width(),
        'height' => (float) $product->get_height(),
        'weight' => (float) $product->get_weight(),
        'quantity' => $quantity,
    ];

    // Add the new product's dimensions and weight to the cart items array
    for ($i = 0; $i < $quantity; $i++) {
        $cart_items[] = $product_dimensions;
    }

    // Calculate the best package that can fit the items
    $package_best_fit = calculate_package_best_fit($cart_items);

    // Logs
    error_log('Cart items: ' . json_encode($cart_items));
    error_log('Best package fit: ' . json_encode($package_best_fit));

    if (!$package_best_fit) {
        // No suitable package found, display an error message
        $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';

        if ($current_language === 'pt') {
            $message = __('O produto selecionado não pôde ser adicionado ao seu carrinho porque o total de itens do carrinho não pode ser acomodado em nenhuma das caixas de envio disponíveis. Ajuste seu carrinho removendo alguns itens ou alterando as quantidades e tente novamente. Se você tiver algum pedido especial que não esteja listado em nossa loja online, entre em contato conosco.');
        } else {
            $message = __('The selected product could not be added to your cart because it does not fit in any available shipping boxes. Please adjust your cart by removing some items or changing the quantities and try again. If you have any special requests that are not listed in our online shop, please feel free to contact us.');
        }

        wc_add_notice($message, 'error');
        return false;
    }

    // Return true if the product can be added to the cart
    return $passed;
}


function calculate_and_store_package_best_fit($order_id)
{
    $order = wc_get_order($order_id);
    $cart_items = [];

    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        $quantity = $item->get_quantity();

        // Retrieve product data, including variations
        $product = wc_get_product($variation_id ? $variation_id : $product_id);

        for ($i = 0; $i < $quantity; $i++) {
            $cart_items[] = [
                'length' => (float) $product->get_length(),
                'width' => (float) $product->get_width(),
                'height' => (float) $product->get_height(),
                'weight' => (float) $product->get_weight(),
                'quantity' => $quantity,
            ];
        }
    }

    // Logs
    error_log('Order ID: ' . $order_id . ' - Items: ' . print_r($cart_items, true));

    $package_best_fit = calculate_package_best_fit($cart_items);

    if ($package_best_fit) {
        // Store package best fit in order meta as a JSON object
        update_post_meta($order_id, 'order_package_best_fit', json_encode($package_best_fit));

    }

    // Logs
    error_log('Order ID: ' . $order_id . ' - Package Best Fit: ' . json_encode($package_best_fit));

}


function display_custom_order_meta($order)
{
    $package_best_fit = get_post_meta($order->get_id(), 'order_package_best_fit', true);
    if ($package_best_fit) {
        $package_best_fit = json_decode($package_best_fit, true);

        $package_best_fit_name = isset($package_best_fit['name']) ? esc_html($package_best_fit['name']) : '';
        $package_best_fit_length = isset($package_best_fit['length']) ? esc_html($package_best_fit['length']) : '';
        $package_best_fit_width = isset($package_best_fit['width']) ? esc_html($package_best_fit['width']) : '';
        $package_best_fit_height = isset($package_best_fit['height']) ? esc_html($package_best_fit['height']) : '';
        $package_best_fit_weight = isset($package_best_fit['weight']) ? esc_html($package_best_fit['weight']) : '';

        echo '<div>';
        echo '<p>&nbsp;</p>';
        echo '<p><h3>Package Best Fit</h3></p>';
        echo '<p>Package Dimensions (L×W×H) (cm)<br>';
        echo esc_html($package_best_fit_name) . ' (' . esc_html($package_best_fit_length) . 'x' . esc_html($package_best_fit_width) . 'x'. esc_html($package_best_fit_height) . ')</p>';
        echo '<p>Package Weight (g)<br>';
        echo esc_html($package_best_fit_weight) . '</p>';
        echo '</div>';
    }
}
