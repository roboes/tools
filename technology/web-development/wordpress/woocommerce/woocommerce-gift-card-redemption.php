<?php

// WooCommerce - Gift Card Redemption
// Last update: 2025-01-22

// Add these lines to wp-config.php file
// define('GOOGLE_APPS_SCRIPT_GIFT_CARD', 'https://script.google.com/macros/s/');


if (class_exists('WooCommerce') && WC()) {

    add_action($hook_name = 'woocommerce_before_add_to_cart_button', $callback = 'woocommerce_add_gift_card_checkbox', $priority = 10, $accepted_args = 1);
    add_action($hook_name = 'wp_footer', $callback = 'cf7_prefill_script_add', $priority = 10, $accepted_args = 1);
    add_action($hook_name = 'wpcf7_mail_sent', $callback = 'cf7_gift_card_redemption_tools', $priority = 10, $accepted_args = 1);
    add_action($hook_name = 'woocommerce_order_status_completed', $callback = 'order_completed_gift_card_redemption_tools', $priority = 10, $accepted_args = 1);


    function woocommerce_add_gift_card_checkbox()
    {

        if (is_product()) {

            global $product;

            $product_ids = array(22204, 31437);

            if (in_array($product->get_id(), $product_ids)) {

                $messages = [
                    'gift-card' => [
                        'de_DE' => 'Ich möchte einen Gutschein einlösen.',
                        'de_DE_formal' => 'Ich möchte einen Gutschein einlösen.',
                        'en_US' => 'I would like to redeem a gift card.',
                    ],
                ];

                // Get current language
                $current_language = (function_exists('pll_current_language') && in_array(pll_current_language('locale'), pll_languages_list(array('fields' => 'locale')))) ? pll_current_language('locale') : 'en_US';

                if ($current_language == 'de_DE' || $current_language == 'de_DE_formal') {
                    $cf7_url = site_url('/de/gutschein-einlosen/');
                } else {
                    $cf7_url = site_url('/en/gift-card-redemption/');
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
    }


    function cf7_prefill_script_add()
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


    function cf7_gift_card_redemption_tools($contact_form)
    {
        // Array of form IDs to handle
        $form_ids = array(38604, 38645);

        // Get the current form ID
        $form_id = $contact_form->id();

        // Get current language from form ID
        if ($form_id == 38604) {
            $current_language = 'de_DE_formal';
        } elseif ($form_id == 38645) {
            $current_language = 'en_US';
        } else {
            $current_language = 'en_US';
        }

        if (!in_array($form_id, $form_ids)) {
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
                $inserted_date,
                $product_variation_appointment_date,
                $product_variation_appointment_time,
                $product_name,
                $product_quantity,
                $product_variation_own_portafilter_machine,
                '', // Order ID (not applicable in this context)
                $gift_card_id,
                $customer_name,
                $customer_email,
                $customer_phone,
                $customer_order_notes,
            );

            // Send training confirmation per email
            send_training_confirmation_email($product_id = $product_id, $customer_email = $customer_email, $customer_name = $customer_name, $product_name = $product_name, $product_variation_own_portafilter_machine = $product_variation_own_portafilter_machine, $product_variation_appointment_date = $product_variation_appointment_date, $product_variation_appointment_time = $product_variation_appointment_time, $product_quantity = $product_quantity, $language = $current_language);

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


    function order_completed_gift_card_redemption_tools($order_id)
    {
        // Define the product IDs to check for
        $product_ids = array(22204, 31437, 17739, 31438);

        // Get the order object
        $order = wc_get_order($order_id);

        // Get current language
        $current_language = (function_exists('pll_get_post_language') && in_array(pll_get_post_language($order_id, 'locale'), pll_languages_list(array('fields' => 'locale')))) ? pll_get_post_language($order_id, 'locale') : 'en_US';

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
                $inserted_date,
                $product_variation_appointment_date,
                $product_variation_appointment_time,
                $product_name,
                $product_quantity,
                $product_variation_own_portafilter_machine,
                $order_id,
                '', // Gift Card ID (not applicable in this context)
                $customer_name,
                $customer_email,
                $customer_phone,
                $customer_order_notes,
            );
        }

        // Send training confirmation per email and each product's data to Google Sheets
        foreach ($data_array as $data) {
            send_training_confirmation_email($product_id = $product_id, $customer_email = $customer_email, $customer_name = $customer_name, $product_name = $product_name, $product_variation_own_portafilter_machine = $product_variation_own_portafilter_machine, $product_variation_appointment_date = $product_variation_appointment_date, $product_variation_appointment_time = $product_variation_appointment_time, $product_quantity = $product_quantity, $language = $current_language);

            // Send data to Google Sheets
            send_to_google_sheets($data);
        }
    }


    function send_to_google_sheets($data_array)
    {
        $web_app_url = GOOGLE_APPS_SCRIPT_GIFT_CARD;

        $response = wp_remote_post($web_app_url, array(
            'method' => 'POST',
            'body' => json_encode(array(
                'inserted_date' => $data_array[0],
                'product_variation_appointment_date' => $data_array[1],
                'product_variation_appointment_time' => $data_array[2],
                'product_name' => $data_array[3],
                'product_quantity' => $data_array[4],
                'product_variation_own_portafilter_machine' => $data_array[5],
                'order_number' => $data_array[6],
                'gift_card_id' => $data_array[7],
                'customer_name' => $data_array[8],
                'customer_email' => $data_array[9],
                'customer_phone' => $data_array[10],
                'customer_order_notes' => $data_array[11]
            )),
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            error_log('Error sending data to Google Apps Script: ' . $response->get_error_message());
        }
    }


    function send_training_confirmation_email($product_id, $customer_email, $customer_name, $product_name, $product_variation_own_portafilter_machine, $product_variation_appointment_date, $product_variation_appointment_time, $product_quantity, $language = 'en_US')
    {

        // Retrieve custom meta for training location
        $product_training_location = get_post_meta($product_id, 'product_training_location', true);
        if (!$product_training_location) {
            $product_training_location = 'Address Location';
        }

        // Settings
        $messages = [
            'subject' => [
                'de_DE' => 'Bestätigung deiner Buchung bei ' . get_option('blogname'),
                'de_DE_formal' => 'Bestätigung Ihrer Buchung bei ' . get_option('blogname'),
                'en_US' => 'Confirmation of your booking at ' . get_option('blogname'),
            ],
            'heading' => [
                'de_DE' => 'Vielen Dank für deine Buchung',
                'de_DE_formal' => 'Vielen Dank für Ihre Buchung',
                'en_US' => 'Thank you for your booking',
            ],
            'body' => [
                'de_DE' => sprintf('Hallo %s,<br><br>Du hast dich erfolgreich für das folgende Training angemeldet:<br><br><strong>Training:</strong> %s<br><strong>Datum:</strong> %s<br><strong>Uhrzeit:</strong> %s<br><strong>Menge:</strong> %s<br><strong>Ort:</strong> %s<br><br><a href="%s">Produktinformationen und rechtliche Hinweise</a><br><br>Vielen Dank für deine Anmeldung!', $customer_name, !empty($product_variation_own_portafilter_machine) ? $product_name . ' (Eigene Siebträgermaschine: ' . $product_variation_own_portafilter_machine . ')' : $product_name, DateTime::createFromFormat('Y-m-d', $product_variation_appointment_date)->format(get_option('date_format')), $product_variation_appointment_time, $product_quantity, $product_training_location, get_permalink($product_id)),
                'de_DE_formal' => sprintf('Hallo %s,<br><br>Sie haben sich erfolgreich für das folgende Training angemeldet:<br><br><strong>Training:</strong> %s<br><strong>Datum:</strong> %s<br><strong>Uhrzeit:</strong> %s<br><strong>Menge:</strong> %s<br><strong>Ort:</strong> %s<br><br><a href="%s">Produktinformationen und rechtliche Hinweise</a><br><br>Vielen Dank für Ihre Anmeldung!', $customer_name, !empty($product_variation_own_portafilter_machine) ? $product_name . ' (Eigene Siebträgermaschine: ' . $product_variation_own_portafilter_machine . ')' : $product_name, DateTime::createFromFormat('Y-m-d', $product_variation_appointment_date)->format(get_option('date_format')), $product_variation_appointment_time, $product_quantity, $product_training_location, get_permalink($product_id)),
                'en_US' => sprintf('Hello %s,<br><br>You have successfully registered for the following training:<br><br><strong>Training:</strong> %s<br><strong>Date:</strong> %s<br><strong>Time:</strong> %s<br><strong>Quantity:</strong> %s<br><strong>Location:</strong> %s<br><br><a href="%s">Product information and legal notice</a><br><br>Thank you for registering!', $customer_name, !empty($product_variation_own_portafilter_machine) ? $product_name . ' (Own portafilter machine: ' . $product_variation_own_portafilter_machine . ')' : $product_name, DateTime::createFromFormat('Y-m-d', $product_variation_appointment_date)->format(get_option('date_format')), $product_variation_appointment_time, $product_quantity, $product_training_location, get_permalink($product_id)),
            ],
        ];
        $timezone = get_option('timezone_string');

        // Retrieve custom meta for training duration
        $product_training_duration_minutes = get_post_meta($product_id, 'product_training_duration_minutes', true);
        if ($product_training_duration_minutes && is_numeric($product_training_duration_minutes)) {
            $product_training_duration_minutes = (int) $product_training_duration_minutes;
        } else {
            $product_training_duration_minutes = 60;
        }

        // Generate the .ics content
        $ics_content = calendar_event_ics_generator($product_name = $product_name, $product_training_location = $product_training_location, $product_variation_appointment_date = $product_variation_appointment_date, $product_variation_appointment_time = $product_variation_appointment_time, $appointment_duration = $product_training_duration_minutes, $calendar_notification = 2880, $timezone);

        // Create a unique temporary folder
        $temp_folder = sys_get_temp_dir() . '/' . sanitize_title($product_name) . '-' . time();
        mkdir($temp_folder);

        // Create the .ics file inside the unique temporary folder
        $ics_attachment = $temp_folder . '/' . sanitize_title($product_name) . '.ics';
        file_put_contents($ics_attachment, $ics_content);

        // Send the email with the named file as an attachment
        $attachments = array($ics_attachment);

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('woocommerce_email_from_name') . ' <' . get_option('woocommerce_email_from_address') . '>'
        );

        $email = WC()->mailer();
        $message = $email->wrap_message($messages['heading'][$language], $messages['body'][$language]);

        // Send the email using WooCommerce mailer
        // wp_mail($to = $customer_email, $subject = $messages['subject'][$language], $message = $message, $headers = $headers, $attachments = $attachments);

        // Send the email using WooCommerce's email sending method
        $email = new WC_Email();
        $email->send($to = $customer_email, $subject = $messages['subject'][$language], $message = $message, $headers = $headers, $attachments = $attachments);

        // Remove the temporary file after sending
        unlink($ics_attachment);

        // Remove the temporary folder
        rmdir($temp_folder);
    }


    // Generate the .ics calendar event content without saving to a file
    function calendar_event_ics_generator($product_name, $product_training_location, $product_variation_appointment_date, $product_variation_appointment_time, $appointment_duration = 60, $calendar_notification = 2880, $timezone = 'UTC')
    {

        // Define the start and end times for the event
        $start_time = new DateTime($product_variation_appointment_date . ' ' . $product_variation_appointment_time, new DateTimeZone($timezone));
        $start_time_str = $start_time->format('Ymd\THis');

        // Define the end time
        $end_time = clone $start_time;
        $end_time->modify('+' . $appointment_duration . ' minutes');
        $end_time_str = $end_time->format('Ymd\THis');

        // Set meeting notification
        $calendar_notification_time = clone $start_time;
        $calendar_notification_time->modify('-' . $calendar_notification . ' minutes');
        $calendar_notification_time_str = $calendar_notification_time->format('Ymd\THis');

        // ICS format content
        $ics_content = "BEGIN:VCALENDAR\n";
        $ics_content .= "VERSION:2.0\n";
        $ics_content .= "BEGIN:VEVENT\n";
        $ics_content .= "UID:" . uniqid() . "\n";
        $ics_content .= "SUMMARY:{$product_name}\n";
        $ics_content .= "DTSTART;TZID={$timezone}:{$start_time_str}\n";
        $ics_content .= "DTEND;TZID={$timezone}:{$end_time_str}\n";
        // $ics_content .= "DESCRIPTION:Course registration for {$product_name}\n";
        $ics_content .= "LOCATION:{$product_training_location}\n";
        $ics_content .= "STATUS:CONFIRMED\n";
        $ics_content .= "SEQUENCE:0\n";
        $ics_content .= "BEGIN:VALARM\n";
        $ics_content .= "TRIGGER;TZID={$timezone}:{$calendar_notification_time_str}\n";
        $ics_content .= "ACTION:DISPLAY\n";
        $ics_content .= "DESCRIPTION:Reminder\n";
        $ics_content .= "END:VALARM\n";
        $ics_content .= "END:VEVENT\n";
        $ics_content .= "END:VCALENDAR";

        return $ics_content;
    }

}
