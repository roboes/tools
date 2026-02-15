<?php
// WooCommerce - Gift Card Redemption
// Last update: 2026-02-15


// Add this line to wp-config.php file
// define('GOOGLE_APPS_SCRIPT_GIFT_CARD', 'https://script.google.com/macros/s/');

/*
// Manually send training confirmation email
send_training_confirmation_email(
    product_id: 22204,
    customer_email: 'email@website.com',
    customer_name: 'Customer Name',
    product_variation_own_portafilter_machine: 'Mit',
    product_variation_appointment_date: '2026-05-09',
    product_variation_appointment_time: '14:30',
    product_quantity: 1,
    language: 'de'
);
*/

if (function_exists('WC')) {

    add_action(hook_name: 'woocommerce_order_status_completed', callback: 'order_completed_gift_card_redemption_tools', priority: 10, accepted_args: 1);
    add_action(hook_name: 'wpcf7_mail_sent', callback: 'cf7_gift_card_redemption_tools', priority: 10, accepted_args: 1);

    if (!is_admin()) {
        add_action(hook_name: 'woocommerce_before_add_to_cart_button', callback: 'woocommerce_add_gift_card_checkbox', priority: 10, accepted_args: 1);
        add_action(hook_name: 'wp_footer', callback: 'cf7_prefill_script_add', priority: 10, accepted_args: 1);
    }

    function woocommerce_add_gift_card_checkbox(): void
    {

        if (is_product()) {

            $product = wc_get_product(get_the_ID());
            if (!$product instanceof WC_Product) {
                return;
            }

            // Settings
            $product_ids = [22204, 31437];
            $product_variation_ids_exception = [44043, 44044];

            if (in_array($product->get_id(), $product_ids, strict: true)) {

                $messages = [
                    'gift-card-online' => [
                        'en' => 'I would like to redeem an online gift card.',
                        'de' => 'Ich möchte einen Online-Gutschein einlösen.',
                    ],
                    'gift-card-on-site' => [
                        'en' => 'I would like to redeem a gift card (purchased on-site).',
                        'de' => 'Ich möchte eine Gutschein-Karte (vor Ort gekauft) einlösen.',
                    ],
                ];

                // Get current language (Polylang/WPML)
                $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

                if ($browsing_language === 'de') {
                    $cf7_url = site_url('/de/gutschein-einlosen/');
                } elseif ($browsing_language === 'en') {
                    $cf7_url = site_url('/en/gift-card-redemption/');
                } else {
                    $cf7_url = site_url('/en/gift-card-redemption/');
                }

                $html = '<style>
                            .woocommerce div.product form.cart .single_variation_wrap .woocommerce-variation-add-to-cart { margin-top: 30px; }
                        </style>';

                // "Gift Card Online" checkbox
                $html .= '<div class="gift-card-online-checkbox" style="margin-bottom: 10px;">
                            <label style="cursor: pointer;">
                                <input type="checkbox" name="checkbox_online_gift_card" id="checkbox_online_gift_card" />
                                <span style="line-height: 20px;">' . ($messages['gift-card-online'][$browsing_language] ?? $messages['gift-card-online']['en']) . '</span>
                            </label>
                        </div>';

                // "Gift Card On-site" checkbox
                $html .= '<div class="gift-card-on-site-checkbox" style="margin-bottom: 10px;">
                            <label style="cursor: pointer;">
                                <input type="checkbox" name="checkbox_gift_card" id="checkbox_gift_card" />
                                <span style="line-height: 20px;">' . ($messages['gift-card-on-site'][$browsing_language] ?? $messages['gift-card-on-site']['en']) . '</span>
                            </label>
                        </div>';

                $html .= '<script>
                    jQuery(document).ready(function($) {
                        const $onlineContainer = $(".gift-card-online-checkbox");
                        const $giftCardContainer = $(".gift-card-on-site-checkbox");
                        const $giftCardInput = $("#checkbox_gift_card");
                        const $onlineInput = $("#checkbox_online_gift_card");
                        const productVariationIdsException = ' . json_encode($product_variation_ids_exception) . ';

                        // Hide/Show both for training variations
                        $(document).on("found_variation", "form.cart", function(event, variation) {
                            if (productVariationIdsException.includes(variation.variation_id)) {
                                $giftCardContainer.hide();
                                $onlineContainer.hide();
                                $giftCardInput.prop("checked", false);
                                $onlineInput.prop("checked", false);
                            } else {
                                $giftCardContainer.show();
                                $onlineContainer.show();
                            }
                        });
                        
                        $(document).on("reset_data", "form.cart", function() {
                            $giftCardContainer.show();
                            $onlineContainer.show();
                        });

                        // Position checkboxes after variation info
                        const $singleVariation = $(".woocommerce-variation.single_variation");
                        if ($singleVariation.length) {
                            $singleVariation.after($giftCardContainer);
                            $singleVariation.after($onlineContainer);
                        }

                        // Mutual exclusion (select only one)
                        $giftCardInput.on("change", function() {
                            if ($(this).prop("checked")) $onlineInput.prop("checked", false);
                        });

                        $onlineInput.on("change", function() {
                            if ($(this).prop("checked")) $giftCardInput.prop("checked", false);
                        });

                        // Form submission logic
                        $("form.cart").on("submit", function(event) {
                            // Legal check
                            if ($("#checkbox_legal_warning").length && !$("#checkbox_legal_warning").prop("checked")) {
                                event.preventDefault();
                                return;
                            }

                            // If "Gift Card On-site" is checked, redirect to CF7
                            if ($giftCardInput.is(":visible") && $giftCardInput.prop("checked")) {
                                event.preventDefault();

                                const productId = ' . (int) $product->get_id() . ';
                                const productVariationId = $("input[name=\'variation_id\']").val();
                                const productQuantity = $("input[name=\'quantity\']").val();

                                let cf7Url = "' . esc_js(esc_url($cf7_url)) . '?product_id=" + productId;
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


    function cf7_prefill_script_add(): void
    {
        if (is_page(['gift-card-redemption', 'gutschein-einlosen'])) {

            // Get URL parameters
            $product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]) ?? 0;
            $product_variation_id = filter_input(INPUT_GET, 'product_variation_id', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]) ?? 0;
            $product_quantity = filter_input(INPUT_GET, 'product_quantity', FILTER_SANITIZE_NUMBER_INT) ?? '';

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
                $attribute_names = [];

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


    function cf7_gift_card_redemption_tools(WPCF7_ContactForm $contact_form): void
    {
        // Array of form IDs to handle
        $form_ids = [38604, 38645];

        // Get the current form ID
        $form_id = (int) $contact_form->id();

        // Validate form ID
        if (!in_array($form_id, $form_ids, strict: true)) {
            return;
        }

        // Get current language from form ID
        if ($form_id === 38604) {
            $browsing_language = 'de';
        } elseif ($form_id === 38645) {
            $browsing_language = 'en';
        } else {
            $browsing_language = 'en';
        }

        // Extract form data
        $submission = WPCF7_Submission::get_instance();
        if ($submission) {
            $data = $submission->get_posted_data();

            $product_id = (int) ($data['product-id'] ?? 0);
            $product_variation_id = (int) ($data['product-variation-id'] ?? 0);
            $product_quantity = (int) ($data['product-quantity'] ?? 0);
            $product_name = (string) ($data['product-name'] ?? '');
            $product_variation_appointment = (string) ($data['product-variation-appointment'] ?? '');
            $product_variation_own_portafilter_machine = (string) ($data['product-variation-own-portafilter-machine'] ?? '');
            $gift_card_id = (string) ($data['gift-card-id'] ?? '');
            $customer_name = (string) ($data['customer-name'] ?? '');
            $customer_email = (string) ($data['customer-email'] ?? '');
            $customer_phone = (string) ($data['customer-phone'] ?? '');
            $customer_order_notes = (string) ($data['customer-order-notes'] ?? '');

            // Extract date and time from product-variation-appointment
            $product_variation_appointment_datetime = explode(' - ', $product_variation_appointment);
            $product_variation_appointment_date = isset($product_variation_appointment_datetime[0]) ? date('Y-m-d', strtotime($product_variation_appointment_datetime[0])) : '';
            $product_variation_appointment_time = $product_variation_appointment_datetime[1] ?? '';

            // Get the current date and time (date of submission)
            $inserted_date = (new DateTimeImmutable(datetime: 'now', timezone: wp_timezone()))->format('Y-m-d H:i:s');

            // Send training confirmation per email
            send_training_confirmation_email(product_id: $product_id, customer_email: $customer_email, customer_name: $customer_name, product_variation_own_portafilter_machine: $product_variation_own_portafilter_machine, product_variation_appointment_date: $product_variation_appointment_date, product_variation_appointment_time: $product_variation_appointment_time, product_quantity: $product_quantity, language: $browsing_language);

            // Perform English version for Google Sheets
            $product_name = preg_replace(pattern: '/Kaffeetraining /', replacement: '', subject: $product_name);
            $product_name = preg_replace(pattern: '/Coffee Training /', replacement: '', subject: (string)$product_name);
            $product_name = preg_replace(pattern: '/Homebarista/', replacement: 'Home Barista', subject: (string)$product_name);

            $product_variation_own_portafilter_machine = preg_replace(pattern: '/Mit/', replacement: 'With', subject: $product_variation_own_portafilter_machine);
            $product_variation_own_portafilter_machine = preg_replace(pattern: '/Ohne/', replacement: 'Without', subject: (string)$product_variation_own_portafilter_machine);

            // Prepare data for Google Sheets
            $data_array = [
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
            ];

            // Send data to Google Sheets
            send_to_google_sheets(data_array: $data_array);

            // Reduce stock quantity for the product and variation
            if ($product_id > 0 && $product_quantity > 0) {
                $variation = wc_get_product($product_variation_id);

                if ($variation instanceof WC_Product && $variation->exists() && $variation->get_manage_stock()) {
                    $current_stock = (int) $variation->get_stock_quantity();
                    $new_stock = max(0, $current_stock - $product_quantity);

                    wc_update_product_stock($variation->get_id(), $new_stock, 'set');
                }
            }
        }
    }


    function order_completed_gift_card_redemption_tools($order_id, $send_training_confirmation_email_skip = false): void
    {
        if (!$order_id) {
            return;
        }

        // Settings
        $product_ids = [22204, 31437, 17739, 31438];
        $product_variation_ids_exception = [44043, 44044];

        // Get the order object
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return;
        }

        // Get current language (Polylang/WPML)
        $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

        // Initialize an empty array to hold product data
        $data_array = [];

        // Loop through each item in the order
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product instanceof WC_Product) {
                continue;
            }

            $product_id = (int) $item->get_product_id();  // Get the parent product ID
            $product_variation_id = (int) $item->get_variation_id();  // Get the variation ID if it exists

            if (in_array($product_variation_id, $product_variation_ids_exception, true)) {
                continue;
            }

            // Check if the parent product ID is in the array of specified product IDs
            if (!in_array($product_id, $product_ids, strict: true)) {
                continue; // Skip this product if it's not in the specified list
            }

            $product_name = $product->get_name();
            $product_quantity = (int) $item->get_quantity();

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
            $product_variation_appointment_time = $product_variation_appointment_datetime[1] ?? '';

            // Get customer details
            $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $customer_email = $order->get_billing_email();
            $customer_phone = $order->get_billing_phone();
            $customer_order_notes = $order->get_customer_note();

            // Get the current date and time (date of order completion)
            $inserted_date = (new DateTimeImmutable(datetime: 'now', timezone: wp_timezone()))->format('Y-m-d H:i:s');

            // Send training confirmation per email
            if (!$send_training_confirmation_email_skip) {
                send_training_confirmation_email(product_id: $product_id, customer_email: $customer_email, customer_name: $customer_name, product_variation_own_portafilter_machine: $product_variation_own_portafilter_machine, product_variation_appointment_date: $product_variation_appointment_date, product_variation_appointment_time: $product_variation_appointment_time, product_quantity: $product_quantity, language: $browsing_language);
            }

            // Perform English version for Google Sheets
            $product_name = preg_replace('/Kaffeetraining /', '', $product_name);
            $product_name = preg_replace('/Coffee Training /', '', (string)$product_name);
            $product_name = preg_replace('/Homebarista/', 'Home Barista', (string)$product_name);

            $product_variation_own_portafilter_machine = preg_replace('/Mit/', 'With', $product_variation_own_portafilter_machine);
            $product_variation_own_portafilter_machine = preg_replace('/Ohne/', 'Without', (string)$product_variation_own_portafilter_machine);

            // Prepare data for Google Sheets
            $data_array = [
                $inserted_date,
                $product_variation_appointment_date,
                $product_variation_appointment_time,
                $product_name,
                $product_quantity,
                $product_variation_own_portafilter_machine,
                (string)$order_id,
                '', // Gift Card ID (not applicable in this context)
                $customer_name,
                $customer_email,
                $customer_phone,
                $customer_order_notes,
            ];

            // Send data to Google Sheets
            send_to_google_sheets(data_array: $data_array);

        }
    }


    function send_to_google_sheets(array $data_array): void
    {

        // Check if constant is defined
        if (!defined('GOOGLE_APPS_SCRIPT_GIFT_CARD')) {
            error_log('GOOGLE_APPS_SCRIPT_GIFT_CARD constant is not defined');
            return;
        }

        $web_app_url = GOOGLE_APPS_SCRIPT_GIFT_CARD;

        $response = wp_remote_post($web_app_url, [
            'method' => 'POST',
            'body' => json_encode([
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
            ], JSON_THROW_ON_ERROR),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            error_log('Error sending data to Google Apps Script: ' . $response->get_error_message());
        }
    }


    function send_training_confirmation_email(int $product_id, string $customer_email, string $customer_name, string $product_variation_own_portafilter_machine, string $product_variation_appointment_date, string $product_variation_appointment_time, int $product_quantity, string $language = 'en'): void
    {

        // Retrieve custom meta for training location
        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product) {
            return;
        }

        $product_name = $product->get_name();

        $product_training_location = $product->get_meta('product_training_location', true);
        if (!$product_training_location) {
            $product_training_location = 'Address Location';
        }

        // Settings
        $messages = [
            'subject' => [
                'en' => 'Confirmation of your booking at ' . get_option(option: 'blogname'),
                'de' => 'Bestätigung deiner Buchung bei ' . get_option(option: 'blogname'),
            ],
            'heading' => [
                'en' => 'Thank you for your booking',
                'de' => 'Vielen Dank für deine Buchung',
            ],
            'body' => [
                'en' => sprintf('Hello %s,<br><br>You have successfully registered for the following training:<br><br><strong>Training:</strong> %s<br><strong>Date:</strong> %s<br><strong>Time:</strong> %s<br><strong>Quantity:</strong> %s<br><strong>Location:</strong> %s<br><br><a href="%s">Product information and legal notice</a><br><br>Thank you for registering!', $customer_name, !empty($product_variation_own_portafilter_machine) ? $product_name . ' (Own portafilter machine: ' . $product_variation_own_portafilter_machine . ')' : $product_name, DateTimeImmutable::createFromFormat(format: 'Y-m-d', datetime: $product_variation_appointment_date, timezone: wp_timezone())->format(get_option(option: 'date_format')), $product_variation_appointment_time, $product_quantity, $product_training_location, get_permalink($product_id)),
                'de' => sprintf('Hallo %s,<br><br>Du hast dich erfolgreich für das folgende Training angemeldet:<br><br><strong>Training:</strong> %s<br><strong>Datum:</strong> %s<br><strong>Uhrzeit:</strong> %s<br><strong>Menge:</strong> %s<br><strong>Ort:</strong> %s<br><br><a href="%s">Produktinformationen und rechtliche Hinweise</a><br><br>Vielen Dank für deine Anmeldung!', $customer_name, !empty($product_variation_own_portafilter_machine) ? $product_name . ' (Eigene Siebträgermaschine: ' . $product_variation_own_portafilter_machine . ')' : $product_name, DateTimeImmutable::createFromFormat(format: 'Y-m-d', datetime: $product_variation_appointment_date, timezone: wp_timezone())->format(get_option(option: 'date_format')), $product_variation_appointment_time, $product_quantity, $product_training_location, get_permalink($product_id)),
            ],
        ];

        // Retrieve custom meta for training duration
        $product_training_duration_minutes = $product->get_meta('product_training_duration_minutes', true);
        if ($product_training_duration_minutes && is_numeric($product_training_duration_minutes)) {
            $product_training_duration_minutes = (int) $product_training_duration_minutes;
        } else {
            $product_training_duration_minutes = 60;
        }

        // Generate the .ics content
        $ics_content = calendar_event_ics_generator(product_name: $product_name, product_training_location: $product_training_location, product_variation_appointment_date: $product_variation_appointment_date, product_variation_appointment_time: $product_variation_appointment_time, appointment_duration: $product_training_duration_minutes, calendar_notification: 2880, timezone: wp_timezone_string());

        // Create a unique temporary folder
        $temp_folder = sys_get_temp_dir() . '/' . sanitize_title($product_name) . '-' . time();
        mkdir($temp_folder);

        // Create the .ics file inside the unique temporary folder
        $ics_attachment = $temp_folder . '/' . sanitize_title($product_name) . '.ics';
        file_put_contents($ics_attachment, $ics_content);

        // Send the email with the named file as an attachment
        $attachments = [$ics_attachment];

        // Email headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option(option: 'woocommerce_email_from_name') . ' <' . get_option(option: 'woocommerce_email_from_address') . '>'
        ];

        // Send email
        $mailer = WC()->mailer();
        $mailer->send(to: $customer_email, subject: $messages['subject'][$language], message: $mailer->wrap_message(email_heading: $messages['heading'][$language], message: $messages['body'][$language]), headers: $headers, attachments: $attachments);

        // Remove the temporary file after sending
        unlink($ics_attachment);

        // Remove the temporary folder
        rmdir($temp_folder);
    }


    // Generate the .ics calendar event content without saving to a file
    function calendar_event_ics_generator(string $product_name, string $product_training_location, string $product_variation_appointment_date, string $product_variation_appointment_time, int $appointment_duration = 60, int $calendar_notification = 2880, string $timezone = 'UTC'): string
    {

        // Define the start and end times for the event
        $start_time = new DateTimeImmutable(datetime: $product_variation_appointment_date . ' ' . $product_variation_appointment_time, timezone: new DateTimeZone($timezone));
        $start_time_str = $start_time->format('Ymd\THis');

        // Define the end time
        $end_time = $start_time->modify("+$appointment_duration minutes");
        $end_time_str = $end_time->format('Ymd\THis');

        // Set meeting notification
        $calendar_notification_time = $start_time->modify('-' . $calendar_notification . ' minutes');
        $calendar_notification_time_str = $calendar_notification_time->format('Ymd\THis');

        // ICS format content
        $ics_content = "BEGIN:VCALENDAR\n";
        $ics_content .= "VERSION:2.0\n";
        $ics_content .= "METHOD:PUBLISH\n";
        $ics_content .= "BEGIN:VEVENT\n";
        $ics_content .= "UID:" . uniqid('', true) . "\n";
        $ics_content .= "ORGANIZER;CN=" . get_option('blogname') . ":MAILTO:" . get_option(option: 'woocommerce_email_from_address') . "\n";
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


    // Schedule cron job to sync missing orders to Google Sheets
    add_action(hook_name: 'init', callback: function (): void {

        if (!wp_next_scheduled(hook: 'cron_job_sync_missing_trainings_to_google_sheets', args: [])) {

            // Settings
            $start_datetime = new DateTimeImmutable(datetime: 'next sunday 04:00:00', timezone: wp_timezone());

            wp_schedule_event(timestamp: $start_datetime->getTimestamp(), recurrence: 'weekly', hook: 'cron_job_sync_missing_trainings_to_google_sheets', args: [], wp_error: false);

        }

    }, priority: 10, accepted_args: 0);


    add_action(hook_name: 'cron_job_sync_missing_trainings_to_google_sheets', callback: 'sync_missing_trainings_to_google_sheets', priority: 10, accepted_args: 0);

    function sync_missing_trainings_to_google_sheets(): void
    {
        global $wpdb;

        // Settings
        $product_ids = [22204, 31437, 17739, 31438];
        $product_variation_ids_exception = [44043, 44044];

        // Get all existing order numbers from Google Sheets in one call
        $existing_order_ids = get_all_order_ids_from_google_sheets();
        if ($existing_order_ids === false) {
            error_log('Failed to fetch existing order IDs from Google Sheets');
            return;
        }

        error_log('Found ' . count($existing_order_ids) . ' existing orders in Google Sheets');

        // Get all completed orders from the last 3 months using wpdb (HPOS compatible)
        $order_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT o.id 
            FROM {$wpdb->prefix}wc_orders o
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.id = oi.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            WHERE o.status = 'wc-completed'
            AND o.date_created_gmt >= %s
            AND oim.meta_key = '_product_id'
            AND oim.meta_value IN (" . implode(',', array_fill(0, count($product_ids), '%d')) . ")
            ORDER BY o.id ASC",
            array_merge([date('Y-m-d H:i:s', strtotime('-3 months'))], $product_ids)
        ));

        error_log('Found ' . count($order_ids) . ' potential orders from WooCommerce');

        $synced = 0;
        $skipped = 0;

        foreach ($order_ids as $order_id) {
            $order_id_str = (string)$order_id;

            // Skip if already in Google Sheets (local array check - super fast!)
            if (in_array($order_id_str, $existing_order_ids, true)) {
                $skipped++;
                continue;
            }

            // Load order and validate
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }

            // Check if order has valid training products (not exception variations)
            $has_valid_training_product = false;
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $variation_id = $item->get_variation_id();

                // Skip if it's an exception variation
                if (in_array($variation_id, $product_variation_ids_exception, true)) {
                    continue;
                }

                if (in_array($product_id, $product_ids, true)) {
                    $has_valid_training_product = true;
                    break;
                }
            }

            if (!$has_valid_training_product) {
                continue;
            }

            // Sync to Google Sheets
            order_completed_gift_card_redemption_tools(order_id: $order_id, send_training_confirmation_email_skip: true);
            $synced++;
            error_log("Synced order: $order_id");

            // Small delay to avoid overwhelming Google Sheets API
            sleep(1);
        }

        error_log("Sync complete: $synced synced, $skipped already existed");
    }

    function get_all_order_ids_from_google_sheets(): array|false
    {
        if (!defined('GOOGLE_APPS_SCRIPT_GIFT_CARD')) {
            return false;
        }

        $url = add_query_arg('action', 'getAllOrderNumbers', GOOGLE_APPS_SCRIPT_GIFT_CARD);
        $response = wp_remote_get($url, ['timeout' => 30]);

        if (is_wp_error($response)) {
            error_log('Error fetching order IDs from Google Sheets: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $order_ids = json_decode($body, true);

        if (!is_array($order_ids)) {
            error_log('Invalid response from Google Sheets: ' . $body);
            return false;
        }

        return $order_ids;
    }

}
