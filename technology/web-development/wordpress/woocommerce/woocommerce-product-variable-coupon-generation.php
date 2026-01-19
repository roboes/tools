<?php

// WooCommerce - Automated course voucher system
// Last update: 2026-01-18


// Requires Dompdf 3.1.4 (https://github.com/dompdf/dompdf) installed via Composer:
// cd /home/website.com/public_html/wp-content/
// composer require dompdf/dompdf




use Dompdf\Dompdf;
use Dompdf\Options;

require_once WP_CONTENT_DIR . '/vendor/autoload.php';


if (function_exists('WC')) {

    function get_coupon_variation_validation(int $variation_id): object|bool
    {

        // Settings
        $coupon_settings = [
            [
                'coupon_prefix'        => 'KA-Training-',
                'product_ids'          => [22204, 31437],
                'coupon_variation_ids' => [44043, 44044],
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
                    if (pll_get_post_language(post_id: $variation_id, field: 'slug') && in_array(needle: pll_get_post_language(post_id: $variation_id, field: 'slug'), haystack: pll_languages_list(args: ['fields' => 'slug']), strict: true)) {
                        $product_lang = pll_get_post_language(post_id: $variation_id, field: 'slug');
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
                $random_part    = strtoupper(string: wp_generate_password(length: 10, special_chars: false));
                $coupon_code    = "{$config['coupon_prefix']}{$random_part}";
                $product_parent_name   = get_the_title(post: $item->get_product_id());
                $product_variation_name = $item->get_name();
                $description    = "{$product_parent_name} - Purchased on {$purchase_date->date(format: 'Y-m-d H:i:s')}";

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

                $coupon_expiry_date = new DateTimeImmutable(datetime: ($purchase_date->format('Y') + 3) . '-12-31 23:59:59', timezone: wp_timezone());

                $coupon->set_date_expires(date: $coupon_expiry_date->getTimestamp());
                $coupon->save();

                $order->update_meta_data('_voucher_code_' . $variation_id, $coupon_code);
                $order->update_meta_data('_voucher_expiry_' . $variation_id, $coupon_expiry_date->getTimestamp());
                $order->save();

                // Log to order
                $order->add_order_note(note: "Coupon created: {$coupon_code}. Valid for: {$product_parent_name}. Expires: {$coupon_expiry_date->format(format: 'Y-m-d')}");

                // Send email
                send_coupon_email(order: $order, variation_id: $variation_id);

                break;
            }
        }
    }

    // Send coupon per email
    function send_coupon_email(WC_Order $order, int $variation_id): void
    {

        // Setup
        $customer_name  = $order->get_billing_first_name();
        $customer_email = $order->get_billing_email();
        $product_name   = get_the_title($variation_id);
        $coupon_code    = $order->get_meta('_voucher_code_' . $variation_id);
        $coupon_expiry_date = DateTimeImmutable::createFromFormat(format: 'U', datetime: $order->get_meta('_voucher_expiry_' . $variation_id));

        // Get order language
        $language = 'en';
        if (function_exists('pll_get_post_language')) {
            if (pll_get_post_language($order->get_id(), 'slug') && in_array(needle: pll_get_post_language($order->get_id(), 'slug'), haystack: pll_languages_list(['fields' => 'slug']), strict: true)) {
                $language = pll_get_post_language($order->get_id(), 'slug');
            }
        }

        $email_data = [
            'en' => [
                'subject' => get_option(option: 'blogname') . " - {$product_name} Voucher",
                'heading' => "{$product_name} Voucher",
                'body'    => "Hello {$customer_name},<br><br>Thank you for your order! Your voucher code for the training is now active.<br><br>Coupon: <strong>{$coupon_code}</strong><br>Valid until: <strong>{$coupon_expiry_date->format(format: get_option(option: 'date_format'))}</strong><br><br>Simply use this coupon at checkout for your next booking."
            ],
            'de' => [
                'subject' => get_option(option: 'blogname') . " - {$product_name} Gutschein",
                'heading' => "{$product_name} Gutschein",
                'body'    => "Hallo {$customer_name},<br><br>vielen Dank für deine Bestellung! Dein Gutscheincode für das Training ist jetzt aktiv.<br><br>Gutschein: <strong>{$coupon_code}</strong><br>Gültig bis: <strong>{$coupon_expiry_date->format(format: get_option(option: 'date_format'))}</strong><br><br>Nutze diesen Gutschein einfach bei deiner nächsten Buchung im Warenkorb."
            ],
        ];

        $content = $email_data[$language] ?? $email_data['en'];

        $attachment_pdf_path = generate_kaffeeart_voucher_pdf($order, $variation_id);

        // Send email
        $mailer  = WC()->mailer();
        $mailer->send(to: $customer_email, subject: $content['subject'], message: $mailer->wrap_message(email_heading: $content['heading'], message: $content['body']), headers: ["Content-Type: text/html; charset=UTF-8"], attachments: array($attachment_pdf_path));

        unlink($attachment_pdf_path);

    }

    function generate_kaffeeart_voucher_pdf($order, $variation_id)
    {
        if (!$order instanceof WC_Order) {
            return;
        }

        // 1. Data & Language Retrieval
        $customer_email = $order->get_billing_email();
        $purchase_date  = $order->get_date_created();
        $site_url       = get_option(option: 'siteurl');
        $blog_name      = get_option(option: 'blogname');

        // Get order language
        $language = 'en';
        if (function_exists('pll_get_post_language')) {
            if (pll_get_post_language($order->get_id(), 'slug') && in_array(needle: pll_get_post_language($order->get_id(), 'slug'), haystack: pll_languages_list(['fields' => 'slug']), strict: true)) {
                $language = pll_get_post_language($order->get_id(), 'slug');
            }
        }

        $coupon_code    = $order->get_meta('_voucher_code_' . $variation_id);
        $expiry_ts      = $order->get_meta('_voucher_expiry_' . $variation_id);
        $product_name   = get_the_title($variation_id);

        // 2. Translations
        if ($language === 'de') {
            $text_for      = "Gutschein für";
            $text_code     = "Gutschein-Code";
            $text_valid    = "Dieser Gutschein ist gültig für ein";
            $text_at       = "bei";
            $text_bought   = "Gekauft am";
            $text_until    = "Gültig bis";
            $text_redeem   = "Einlösbar unter";
        } else {
            $text_for      = "Voucher for";
            $text_code     = "Voucher Code";
            $text_valid    = "This voucher is valid for a";
            $text_at       = "at";
            $text_bought   = "Purchased on";
            $text_until    = "Valid until";
            $text_redeem   = "Redeemable at";
        }

        // 3. Setup Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        $image_url = esc_url($site_url . '/wp-content/uploads/' . 'kaffeeart-roastery-1.jpg');
        $logo_url  = esc_url($site_url . '/wp-content/uploads/'. 'kaffeeart-logo.svg');

        ob_start();
        ?>
		<!DOCTYPE html>
		<html>
		<head>
			<style>
				@page { margin: 0; }
				body { 
					margin: 0; padding: 0; 
					font-family: 'Helvetica', sans-serif; 
					background-color: #8a694e; 
					color: #ffffff;
					line-height: 1.4;
				}
				.header-image {
					width: 100%;
					height: 440px;
					background: url('<?php echo $image_url; ?>') no-repeat center center;
					background-size: cover;
					display: block;
				}
				.content {
					text-align: center;
					padding: 60px 60px 0 60px;
					position: relative;
				}
				.logo { width: 220px; margin-bottom: 50px; }
				.label { text-transform: uppercase; letter-spacing: 2px; font-size: 13px; opacity: 0.8; margin-bottom: 8px; }
				.voucher-title { font-size: 34px; font-weight: bold; text-transform: uppercase; line-height: 1.1; margin-bottom: 30px; }
				.product-highlight {
					font-size: 20px;
					margin: 40px 0;
					border-top: 1px solid rgba(255,255,255,0.3);
					border-bottom: 1px solid rgba(255,255,255,0.3);
					padding: 12px 0;
					text-transform: uppercase;
					letter-spacing: 2px;
				}
				.coupon-container {
					background: rgba(0, 0, 0, 0.1);
					border: 2px dashed #ffffff;
					padding: 20px;
					margin: 20px auto;
					width: 80%;
				}
				.coupon-code { font-size: 20px; font-weight: bold; letter-spacing: 2px; white-space: nowrap; }
				.meta-info { margin-top: 25px; font-size: 12px; color: rgba(255,255,255,0.8); }
				.footer-branding {
					position: absolute;
					bottom: 30px;
					width: 100%;
					text-align: center;
					font-size: 11px;
					opacity: 0.7;
				}
				a { color: #ffffff; text-decoration: none; }
			</style>
		</head>
		<body>
			<div class="header-image"></div>

			<div class="content">
				<img src="<?php echo $logo_url; ?>" class="logo">
				
				<div class="label"><?php echo esc_html($text_for); ?></div>
				<div class="voucher-title"><?php echo esc_html($product_name); ?></div>

				<div class="product-highlight"><?php echo esc_html($text_code); ?></div>

				<div style="font-size: 15px; margin-bottom: 20px;">
					<?php echo esc_html($text_valid); ?> <strong><?php echo esc_html($product_name); ?></strong> <?php echo esc_html($text_at); ?> <?php echo esc_html($blog_name); ?>.
				</div>

				<div class="coupon-container">
					<span class="coupon-code"><?php echo esc_html($coupon_code); ?></span>
				</div>

				<div class="meta-info">
					<?php echo esc_html($text_bought); ?>: <?php echo $purchase_date->format('d.m.Y'); ?> &nbsp; | &nbsp; <?php echo esc_html($text_until); ?>: <?php echo date('d.m.Y', $expiry_ts); ?><br>
					<?php echo esc_html($text_redeem); ?> <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html(str_replace(['https://','http://'], '', $site_url)); ?></a>
				</div>
			</div>

			<div class="footer-branding">
				<?php echo esc_html($blog_name); ?><br>
				<?php echo esc_html($site_url); ?>
			</div>
		</body>
		</html>
		<?php
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $attachment_pdf_path = sys_get_temp_dir() . '/' . 'Kaffeeart_Gutschein_' . $coupon_code . '.pdf';
        file_put_contents(filename: $attachment_pdf_path, data: $dompdf->output());

        return $attachment_pdf_path;

    }

}
