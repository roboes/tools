<?php

// WooCommerce - Gift Card Redemption
// Last update: 2024-09-02

// Add these lines to wp-config.php file
// define('GOOGLE_APPS_SCRIPT_GIFT_CARD', 'https://script.google.com/macros/s/');


add_action($hook_name = 'woocommerce_before_add_to_cart_button', $callback = 'woocommerce_add_gift_card_checkbox', $priority = 10, $accepted_args = 1);

function woocommerce_add_gift_card_checkbox()
{
    global $product;

    $product_ids = array(22204, 31437);

    if (in_array($product->get_id(), $product_ids)) {

        $messages = [
            'gift-card' => [
                'de' => 'Ich möchte einen Gutschein einlösen.',
                'en' => 'I would like to redeem a gift card.',
            ],
        ];

        $current_language = function_exists('pll_current_language') ? pll_current_language('slug') : 'en';

        if ($current_language == 'de') {
            $cf7_url = site_url('/de/gutschein-einlosen/');
        } else {
            $cf7_url = site_url('/en/redeem-gift-card/');
        }

        $html = '<div class="gift-card-checkbox" style="margin-bottom: 20px;">
                    <label>
                        <input type="checkbox" name="checkbox_gift_card" id="checkbox_gift_card" />
                        <span style="line-height: 20px;">' . $messages['gift-card'][$current_language] . '</span>
                    </label>
                </div>';

        $html .= '<script>
            jQuery(document).ready(function($) {
                // Find the gift card checkbox
                const $giftCardCheckbox = $(".gift-card-checkbox");
                // Find the single variation element
                const $singleVariation = $(".woocommerce-variation.single_variation");

                // Check if the single variation element exists
                if ($singleVariation.length) {
                    // Place the gift card checkbox after the single variation element
                    $singleVariation.after($giftCardCheckbox);
                }

                // Handle form submit
                $("form.cart").on("submit", function(event) {
                    // Check if checkbox_legal_warning is present and not checked
                    if ($("#checkbox_legal_warning").length && !$("#checkbox_legal_warning").prop("checked")) {
                        event.preventDefault(); // Prevent default action
                        return;
                    }

                    if ($("#checkbox_gift_card").length && $("#checkbox_gift_card").prop("checked")) {
                        event.preventDefault(); // Prevent default action

                        const productId = ' . $product->get_id() . ';
                        const productVariationId = $("input[name=\'variation_id\']").val();
                        const productQuantity = $("input[name=\'quantity\']").val();

                        let cf7Url = "' . $cf7_url . '?product_id=" + productId;
                        cf7Url += "&product_variation_id=" + encodeURIComponent(productVariationId);
                        cf7Url += "&product_quantity=" + encodeURIComponent(productQuantity);

                        window.location.href = cf7Url;
                    }
                });
            });
        </script>';

        echo $html;
    }
}


add_action($hook_name = 'wp_footer', $callback = 'add_cf7_prefill_script', $priority = 10, $accepted_args = 1);

function add_cf7_prefill_script()
{
    if (is_page(array('gift-card-redemption', 'gutschein-einlosen'))) {
        global $wpdb;

        // Get URL parameters
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $product_variation_id = isset($_GET['product_variation_id']) ? intval($_GET['product_variation_id']) : 0;
        $product_quantity = isset($_GET['product_quantity']) ? intval($_GET['product_quantity']) : '';

        // Get product name
        $product_name = $product_id ? get_the_title($product_id) : '';

        // Initialize variables for additional attributes
        $product_variation_name = '';
        $product_variation_appointment = '';
        $product_variation_own_portafilter_machine = '';

        // Get variation name and additional attributes
        if ($product_variation_id) {
            $product_variation = new WC_Product_Variation($product_variation_id);
            $attributes = $product_variation->get_variation_attributes();
            $attribute_names = array();

            foreach ($attributes as $attribute => $value) {
                $taxonomy = str_replace('attribute_', '', $attribute);
                $term = get_term_by('slug', $value, $taxonomy);

                if ($term) {
                    $attribute_names[] = $term->name;
                } else {
                    $attribute_names[] = $value;
                }

                // Check for specific attributes
                if ($taxonomy === 'termin') {
                    $product_variation_appointment = $term ? $term->name : $value;
                }
                if ($taxonomy === 'pa_training-own-portafilter') {
                    $product_variation_own_portafilter_machine = $term ? $term->name : $value;
                }
            }

            $product_variation_name = implode(' - ', $attribute_names);
        }

        ?>
        <script>
        jQuery(document).ready(function($) {
            setTimeout(function() {

                // Log the values to the console
                // console.log('Product ID:', $('#product-id').val());
                // console.log('Product Name:', $('#product-name').val());
                // console.log('Product Variation ID:', $('#product-variation-id').val());
                // console.log('Product Variation Name:', $('#product-variation-name').val());
                // console.log('Product Quantity:', $('#quantity').val());
                // console.log('Product Variation Appointment:', $('#product-variation-appointment').val());
                // console.log('Product Variation Own Portafilter Machine:', $('#product-variation-own-portafilter-machine').val());

                // Populate form fields
                $('#product-id').val('<?php echo esc_js($product_id); ?>');
                $('#product-name').val('<?php echo esc_js($product_name); ?>');
                $('#product-variation-id').val('<?php echo esc_js($product_variation_id); ?>');
                $('#product-variation-name').val('<?php echo esc_js($product_variation_name); ?>');
                $('#product-quantity').val('<?php echo esc_js($product_quantity); ?>');
                $('#product-variation-appointment').val('<?php echo esc_js($product_variation_appointment); ?>').prop('readonly', true);
                $('#product-variation-own-portafilter-machine').val('<?php echo esc_js($product_variation_own_portafilter_machine); ?>').prop('readonly', true);
            }, 500);
        });
        </script>
        <?php
    }
}


add_action($hook_name = 'wpcf7_mail_sent', $callback = 'cf7_to_google_sheets', $priority = 10, $accepted_args = 1);

function cf7_to_google_sheets($contact_form)
{
    // Array of form IDs to handle
    $form_ids = array(38604, 38645);

    if (!in_array($contact_form->id(), $form_ids)) {
        return;
    }

    // Extract form data
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $data = $submission->get_posted_data();

        $product_id = isset($data['product-id']) ? intval($data['product-id']) : 0;
        $product_variation_id = isset($data['product-variation-id']) ? intval($data['product-variation-id']) : 0;
        $product_quantity = isset($data['product-quantity']) ? intval($data['product-quantity']) : 0;
        $product_name = isset($data['product-name']) ? $data['product-name'] : '';
        $product_variation_appointment = isset($data['product-variation-appointment']) ? $data['product-variation-appointment'] : '';
        $product_variation_own_portafilter_machine = isset($data['product-variation-own-portafilter-machine']) ? $data['product-variation-own-portafilter-machine'] : '';
        $gift_card_id = isset($data['gift-card-id']) ? $data['gift-card-id'] : '';
        $customer_name = isset($data['customer-name']) ? $data['customer-name'] : '';
        $customer_email = isset($data['customer-email']) ? $data['customer-email'] : '';
        $customer_phone = isset($data['customer-phone']) ? $data['customer-phone'] : '';
        $customer_order_notes = isset($data['customer-order-notes']) ? $data['customer-order-notes'] : '';

        // Extract date and time from product-variation-appointment
        $product_variation_appointment_datetime = explode(' - ', $product_variation_appointment);
        $product_variation_appointment_date = isset($product_variation_appointment_datetime[0]) ? date('Y-m-d', strtotime($product_variation_appointment_datetime[0])) : '';
        $product_variation_appointment_time = isset($product_variation_appointment_datetime[1]) ? $product_variation_appointment_datetime[1] : '';

        // Perform regex replacements
        $product_name = preg_replace('/Kaffeetraining /', '', $product_name);
        $product_name = preg_replace('/Coffee Training /', '', $product_name);
        $product_name = preg_replace('/Homebarista/', 'Home Barista', $product_name);


        $product_variation_own_portafilter_machine = preg_replace('/Mit/', 'With', $product_variation_own_portafilter_machine);
        $product_variation_own_portafilter_machine = preg_replace('/Ohne/', 'Without', $product_variation_own_portafilter_machine);

        // Get the current date and time (date of submission)
        $inserted_date = (new DateTime('now', wp_timezone()))->format('Y-m-d H:i:s');

        // Prepare data for Google Sheets
        $data_array = array(
            $inserted_date,                      // Inserted Date
            $product_variation_appointment_date,                               // Date
            $product_variation_appointment_time,                               // Time
            $product_name,                       // Training
            $product_quantity,                           // Quantity
            $product_variation_own_portafilter_machine,  // Own Portafilter Machine
            $gift_card_id,
            $customer_name,                   // Customer
            $customer_email,                  // Email
            $customer_phone,                  // Phone
            $customer_order_notes,                    // Notes
        );

        // Send data to Google Sheets
        send_to_google_sheets($data_array);

        // Reduce stock quantity for the product and variation
        if ($product_id > 0 && $product_quantity > 0) {
            $variation = wc_get_product($product_variation_id);

            if ($variation && $variation->exists()) {
                wc_update_product_stock($variation, $product_quantity, 'decrease');
            }
        }
    }
}


add_action($hook_name = 'woocommerce_order_status_completed', $callback = 'order_completed_to_google_sheets', $priority = 10, $accepted_args = 1);

function order_completed_to_google_sheets($order_id)
{
    // Define the product IDs to check for
    $product_ids = array(22204, 31437);

    // Get the order object
    $order = wc_get_order($order_id);

    // Initialize an empty array to hold product data
    $data_array = array();

    // Loop through each item in the order
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $product_id = $item->get_product_id();  // Get the parent product ID
        $product_variation_id = $item->get_variation_id();  // Get the variation ID if it exists

        // Check if the parent product ID is in the array of specified product IDs
        if (!in_array($product_id, $product_ids)) {
            continue; // Skip this product if it's not in the specified list
        }

        $product_name = $product->get_name();
        $product_quantity = $item->get_quantity();

        // Initialize variables for variation attributes
        $product_variation_appointment = '';
        $product_variation_own_portafilter_machine = '';

        // Check if the item is a product variation
        if ($product_variation_id) {
            $variation = new WC_Product_Variation($product_variation_id);
            $attributes = $variation->get_variation_attributes();

            foreach ($attributes as $attribute => $value) {
                $taxonomy = str_replace('attribute_', '', $attribute);
                $term = get_term_by('slug', $value, $taxonomy);

                // Check for specific attributes
                if ($taxonomy === 'termin') {
                    $product_variation_appointment = $term ? $term->name : $value;
                }
                if ($taxonomy === 'pa_training-own-portafilter') {
                    $product_variation_own_portafilter_machine = $term ? $term->name : $value;
                }
            }
        }

        // Extract date and time from product-variation-appointment
        $product_variation_appointment_datetime = explode(' - ', $product_variation_appointment);
        $product_variation_appointment_date = isset($product_variation_appointment_datetime[0]) ? date('Y-m-d', strtotime($product_variation_appointment_datetime[0])) : '';
        $product_variation_appointment_time = isset($product_variation_appointment_datetime[1]) ? $product_variation_appointment_datetime[1] : '';

        // Perform regex replacements
        $product_name = preg_replace('/Kaffeetraining /', '', $product_name);
        $product_name = preg_replace('/Coffee Training /', '', $product_name);
        $product_name = preg_replace('/Homebarista/', 'Home Barista', $product_name);

        $product_variation_own_portafilter_machine = preg_replace('/Mit/', 'With', $product_variation_own_portafilter_machine);
        $product_variation_own_portafilter_machine = preg_replace('/Ohne/', 'Without', $product_variation_own_portafilter_machine);

        // Get customer details
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_email = $order->get_billing_email();
        $customer_phone = $order->get_billing_phone();
        $customer_order_notes = $order->get_customer_note();

        // Get the current date and time (date of order completion)
        $inserted_date = (new DateTime('now', wp_timezone()))->format('Y-m-d H:i:s');

        // Prepare data for Google Sheets
        $data_array[] = array(
            $inserted_date,                                    // Inserted Date
            $product_variation_appointment_date,               // Date
            $product_variation_appointment_time,               // Time
            $product_name,                                     // Training
            $product_quantity,                                 // Quantity
            $product_variation_own_portafilter_machine,        // Own Portafilter Machine
            '',                                                // Gift Card ID (not applicable in this context)
            $customer_name,                                    // Customer
            $customer_email,                                   // Email
            $customer_phone,                                   // Phone
            $customer_order_notes,                             // Notes
        );
    }

    // Send each product's data to Google Sheets
    foreach ($data_array as $data) {
        send_to_google_sheets($data);
    }
}


function send_to_google_sheets($data_array)
{
    $web_app_url = GOOGLE_APPS_SCRIPT_GIFT_CARD;

    $response = wp_remote_post($web_app_url, array(
        'method'    => 'POST',
        'body'      => json_encode(array(
            'inserted_date' => $data_array[0],
            'product_variation_appointment_date' => $data_array[1],
            'product_variation_appointment_time' => $data_array[2],
            'product_name' => $data_array[3],
            'product_quantity' => $data_array[4],
            'product_variation_own_portafilter_machine' => $data_array[5],
            'gift_card_id' => $data_array[6],
            'customer_name' => $data_array[7],
            'customer_email' => $data_array[8],
            'customer_phone' => $data_array[9],
            'customer_order_notes' => $data_array[10]
        )),
        'headers'   => array(
            'Content-Type' => 'application/json'
        )
    ));

    if (is_wp_error($response)) {
        error_log('Error sending data to Google Apps Script: ' . $response->get_error_message());
    }
}
