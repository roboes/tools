<?php

// WooCommerce - Automated course voucher system
// Last update: 2026-01-18


if (function_exists('WC')) {

    function get_coupon_variation_validation(int $variation_id): object|bool
    {

        // Settings
        $coupon_settings = [
            [
                'product_ids'          => [22204, 31437],
                'coupon_variation_ids' => [44043, 44044],
                'coupon_prefix'        => 'KA-Training-Home-Barista-'
            ],
        ];

        $messages = [
            'en' => 'Only one coupon allowed per order.',
            'de' => 'Nur ein Gutschein pro Bestellung erlaubt.',
        ];

        foreach ($coupon_settings as $group) {
            if (in_array(needle: $variation_id, haystack: $group['coupon_variation_ids'], strict: true)) {

                // Get language of the specific variation
                $product_lang = 'en';
                if (function_exists('pll_get_post_language')) {
                    $slug = pll_get_post_language(post_id: $variation_id, output: 'slug');
                    if ($slug && in_array(needle: $slug, haystack: pll_languages_list(args: ['fields' => 'slug']), strict: true)) {
                        $product_lang = $slug;
                    }
                }

                return (object) [
                    'is_coupon'     => true,
                    'error_message' => $messages[$product_lang] ?? $messages['en'],
                    'language'      => $product_lang,
                    'config'        => $group
                ];
            }
        }

        return false;
    }

    if (!is_admin()) {

        // Cart validation: Force quantity to 1 on product page
        add_filter(hook_name: 'woocommerce_quantity_input_args', callback: function ($args, $product) {
            if (get_coupon_variation_validation(variation_id: $product->get_id())) {
                return array_merge($args, ['min_value' => 1, 'max_value' => 1, 'input_value' => 1]);
            }
            return $args;
        }, priority: 10, accepted_args: 2);


        // Cart validation: Block adding more than one to cart
        add_filter(hook_name: 'woocommerce_add_to_cart_validation', callback: function ($passed, $product_id, $quantity, $variation_id = 0) {
            $target_id   = $variation_id ?: $product_id;
            $coupon_data = get_coupon_variation_validation(variation_id: $target_id);

            if ($passed && $coupon_data) {
                foreach (WC()->cart->get_cart() as $cart_item) {
                    $item_id = $cart_item['variation_id'] ?: $cart_item['product_id'];
                    if (get_coupon_variation_validation(variation_id: $item_id)) {
                        wc_add_notice(message: $coupon_data->error_message, notice_type: 'error');
                        return false;
                    }
                }
            }
            return $passed;
        }, priority: 10, accepted_args: 4);

        // Cart validation: Block quantity updates in the Cart page
        add_filter(hook_name: 'woocommerce_update_cart_validation', callback: function ($passed, $cart_item_key, $values, $quantity) {
            $target_id   = $values['variation_id'] ?: $values['product_id'];
            $coupon_data = get_coupon_variation_validation(variation_id: $target_id);

            if ($passed && $quantity > 1 && $coupon_data) {
                wc_add_notice(message: $coupon_data->error_message, notice_type: 'error');
                return false;
            }
            return $passed;
        }, priority: 10, accepted_args: 4);

    }


    // Generate Coupon on Purchase
    add_action(hook_name: 'woocommerce_order_status_completed', callback: 'generate_coupon_on_purchase', priority: 10, accepted_args: 1);

    function generate_coupon_on_purchase(int $order_id): void
    {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order(the_order: $order_id);
        if (!$order instanceof WC_Order) {
            return;
        }

        $customer_name = "{$order->get_billing_first_name()} {$order->get_billing_last_name()}";
        $customer_email = $order->get_billing_email();

        $purchase_date = $order->get_date_created();

        foreach ($order->get_items() as $item) {
            $variation_id = (int) $item->get_variation_id();
            $coupon_data  = get_coupon_variation_validation(variation_id: $variation_id);

            if ($coupon_data) {
                $config = $coupon_data->config;

                // Setup Data
                $random_part    = strtoupper(string: wp_generate_password(length: 6, special_chars: false));
                $coupon_code    = "{$config['coupon_prefix']}{$random_part}";
                $product_parent_name   = get_the_title(post: $item->get_product_id());
                $product_variation_name = $item->get_name();
                $description    = "{$product_parent_name} - {$product_variation_name} - Purchased on {$purchase_date->date(format: 'Y-m-d H:i:s')}";

                // Create coupon
                $coupon = new WC_Coupon();
                $coupon->set_code(code: $coupon_code);
                $coupon->set_amount(amount: 100);
                $coupon->set_discount_type(discount_type: 'percent');
                $coupon->set_description(description: $description);
                $coupon->set_product_ids(product_ids: $config['product_ids']);
                $coupon->set_usage_limit(usage_limit: 1);
                $coupon->set_individual_use(is_individual_use: true);
                $coupon->set_email_restrictions(emails: [$order->get_billing_email()]);

                $coupon_expiry_date = new DateTime(datetime: ($purchase_date->format('Y') + 3) . '-12-31 23:59:59', timezone: wp_timezone());

                $coupon->set_date_expires(date: $coupon_expiry_date->getTimestamp());
                $coupon->save();

                // Log to order
                $order->add_order_note(note: "Coupon created: {$coupon_code}. Valid for: {$product_parent_name}. Expires: {$coupon_expiry_date->format(format: 'Y-m-d')}");

                // Send email
                send_coupon_email(
                    product_name: $product_parent_name,
                    customer_name: $customer_name,
                    customer_email: $customer_email,
                    coupon_code: $coupon_code,
                    coupon_expiry_date: $coupon_expiry_date,
                    language: $coupon_data->language,
                );

                break;
            }
        }
    }

    // Send coupon per email
    function send_coupon_email(string $product_name, string $customer_name, string $customer_email, string $coupon_code, DateTime $coupon_expiry_date, string $language): void
    {

        $email_data = [
            'en' => [
                'subject' => get_option(option: 'blogname') . " - {$product_name} Voucher",
                'heading' => "{$product_name} Voucher",
                'body'    => "Hello {$customer_name},<br><br>Thank you for your order! Your voucher code for the training is now active.<br><br>Coupon: <strong>{$coupon_code}</strong><br>Valid until: <strong>{$coupon_expiry_date->format(format: get_option(option: 'date_format'))}</strong><br><br>Simply use this code at checkout for your next booking."
            ],
            'de' => [
                'subject' => get_option(option: 'blogname') . " - {$product_name} Gutschein",
                'heading' => "{$product_name} Gutschein",
                'body'    => "Hallo {$customer_name},<br><br>vielen Dank für deine Bestellung! Dein Gutscheincode für das Training ist jetzt aktiv.<br><br>Gutschein: <strong>{$coupon_code}</strong><br>Gültig bis: <strong>{$coupon_expiry_date->format(format: get_option(option: 'date_format'))}</strong><br><br>Nutze diesen Code einfach bei deiner nächsten Buchung im Warenkorb."
            ],
        ];

        $content = $email_data[$language] ?? $email_data['en'];

        // Send email
        $mailer  = WC()->mailer();
        $mailer->send(to: $customer_email, subject: $content['subject'], message: $mailer->wrap_message(email_heading: $content['heading'], message: $content['body']), headers: ["Content-Type: text/html; charset=UTF-8"]);

    }

}
