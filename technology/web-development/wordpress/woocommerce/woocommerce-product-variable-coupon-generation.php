<?php

// WooCommerce - Automated course coupon system
// Last update: 2026-01-27


// Notes:
// - Custom product gift card amount purchase: Adds a numerical input field to specific products that allows users to choose a gift card value integer with 1 step.
// - Price overriding: Dynamically sets the product price in the cart to match the Custom product gift card amount purchase by the user.
// - Quantity restrictions: Forces the quantity of these products to exactly one per order and blocks users from adding multiple gift cards of the same type to the cart.
// - Automated coupon creation: When an order is paid, it automatically generates a unique WooCommerce coupon code using a random character string and a specific prefix (e.g. KA-Gift- or KA-Training-).
// - Smart coupon expiry: Sets all generated coupons to expire on December 31st of the third year following the purchase.
// - PDF generation & emailing: Creates a custom PDF gift card using the Dompdf library and emails it to the customer automatically upon payment.
// - Voucher balance tracking: For "fixed amount" coupons, the system tracks the remaining balance. If a coupon is partially used, it calculates the new balance and allows the code to be used again by any user until the value reaches zero.
// - Multilingual support: Uses Polylang to detect the order language and provides all notifications, emails, and PDF text in either German or English.
// - Usage restrictions: Restricts coupons to specific products if configured (e.g., training courses), but allows them to be used by any customer at checkout.


// Requires Dompdf 3.1.4 (https://github.com/dompdf/dompdf) installed via Composer:
// cd /home/website.com/public_html/wp-content/
// composer require dompdf/dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

require_once WP_CONTENT_DIR . '/vendor/autoload.php';


if (function_exists('WC')) {

    function get_coupon_variation_validation(int $item_id): object|bool
    {

        // Settings
        $settings_coupon = [
            [
                'coupon_prefix'          => 'KA-Training-',
                'meta_key_suffix'        => 'training_home_barista',
                'purchasable_ids'        => [44043, 44044], // The product or product variation IDs that, when purchased, trigger the generation of a coupon
                'redeemable_product_ids' => [22204, 31437], // Specific products to which the these coupons can be applied to
                'coupon_is_fixed_amount' => false,
            ],
            [
                'coupon_prefix'          => 'KA-Gift-',
                'meta_key_suffix'        => 'gift_card',
                'purchasable_ids'        => [44185, 44187],
                'redeemable_product_ids' => [],
                'coupon_is_fixed_amount' => true,
            ],
        ];
        $settings_messages = [
            'en' => 'Only one coupon allowed per order.',
            'de' => 'Nur ein Gutschein pro Bestellung erlaubt.',
        ];

        foreach ($settings_coupon as $group) {
            if (in_array(needle: $item_id, haystack: $group['purchasable_ids'], strict: true)) {

                // Get language of the specific variation
                $product_language = 'en';
                if (function_exists('pll_get_post_language')) {
                    if (pll_get_post_language(post_id: wp_get_post_parent_id($item_id) ?: $item_id, field: 'slug') && in_array(needle: pll_get_post_language(post_id: wp_get_post_parent_id($item_id) ?: $item_id, field: 'slug'), haystack: pll_languages_list(args: ['fields' => 'slug']), strict: true)) {
                        $product_language = pll_get_post_language(post_id: wp_get_post_parent_id($item_id) ?: $item_id, field: 'slug');
                    }
                }

                return (object) [
                    'is_coupon'     => true,
                    'error_message' => $settings_messages[$product_language] ?? $settings_messages['en'],
                    'language'      => $product_language,
                    'config'        => $group,
                    'meta_suffix'   => $group['meta_key_suffix'],
                ];
            }
        }

        return false;
    }

    if (!is_admin()) {

        // Cart validation: Force quantity to 1 on product page
        add_filter(hook_name: 'woocommerce_quantity_input_args', callback: function ($args, $product) {
            if (get_coupon_variation_validation(item_id: $product->get_id())) {
                return array_merge($args, ['min_value' => 1, 'max_value' => 1, 'input_value' => 1]);
            }
            return $args;
        }, priority: 10, accepted_args: 2);


        // Cart validation: Block adding more than one to cart
        add_filter(hook_name: 'woocommerce_add_to_cart_validation', callback: function ($passed, $product_id, $quantity, $variation_id = 0) {
            $target_id   = $variation_id ?: $product_id;
            $coupon_data = get_coupon_variation_validation(item_id: $target_id);

            if ($passed && $coupon_data) {
                foreach (WC()->cart->get_cart() as $cart_item) {
                    $item_id = $cart_item['variation_id'] ?: $cart_item['product_id'];
                    $existing_coupon_data = get_coupon_variation_validation($item_id);
                    if ($existing_coupon_data && $existing_coupon_data->meta_suffix === $coupon_data->meta_suffix) {
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
            $coupon_data = get_coupon_variation_validation(item_id: $target_id);

            if ($passed && $quantity > 1 && $coupon_data) {
                wc_add_notice(message: $coupon_data->error_message, notice_type: 'error');
                return false;
            }
            return $passed;
        }, priority: 10, accepted_args: 4);

    }


    // Generate Coupon on Purchase
    add_action(hook_name: 'woocommerce_order_status_paid', callback: 'generate_coupon_on_purchase', priority: 10, accepted_args: 1);

    function generate_coupon_on_purchase(int $order_id): void
    {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order(the_order: $order_id);
        if (!$order instanceof WC_Order) {
            return;
        }

        // Get order language
        $language = 'en';
        if (function_exists('pll_get_post_language')) {
            if (pll_get_post_language($order->get_id(), 'slug') && in_array(needle: pll_get_post_language($order->get_id(), 'slug'), haystack: pll_languages_list(['fields' => 'slug']), strict: true)) {
                $language = pll_get_post_language($order->get_id(), 'slug');
            }
        }

        $customer_name = "{$order->get_billing_first_name()} {$order->get_billing_last_name()}";
        $customer_email = $order->get_billing_email();

        $purchase_date = $order->get_date_created();

        foreach ($order->get_items() as $line_item) {
            $item_id = $line_item->get_variation_id() ?: $line_item->get_product_id();
            $coupon_data = get_coupon_variation_validation(item_id: $item_id);

            if ($coupon_data) {
                if ($order->get_meta('_coupon_code_' . $coupon_data->meta_suffix)) {
                    continue;
                }

                $config = $coupon_data->config;

                // Setup Data
                $random_part    = strtoupper(string: wp_generate_password(length: 10, special_chars: false));
                $coupon_code    = "{$config['coupon_prefix']}{$random_part}";
                $coupon_is_fixed_amount = (isset($config['coupon_is_fixed_amount']) && $config['coupon_is_fixed_amount'] === true);
                $coupon_amount = $line_item->get_total();
                $coupon_amount_formatted = html_entity_decode(wp_strip_all_tags(wc_price($coupon_amount)));
                $product_parent_id = wp_get_post_parent_id($item_id) ?: $item_id;
                $product_parent_name   = get_the_title($product_parent_id);
                $description    = "{$product_parent_name} - Purchased on {$purchase_date->date(format: 'Y-m-d H:i:s')}";

                // Create coupon
                $coupon = new WC_Coupon();
                $coupon->set_code(code: $coupon_code);

                if ($coupon_is_fixed_amount) {
                    // Fixed amount
                    $coupon->set_amount($coupon_amount);
                    $coupon->set_discount_type('fixed_cart');
                    if ($language == 'de') {
                        $product_display_name = $product_parent_name . ' im Wert von ' . $coupon_amount_formatted;
                    } else {
                        $product_display_name = $product_parent_name . ' with a value of ' . $coupon_amount_formatted;
                    }
                } else {
                    // 100% training voucher
                    $coupon->set_amount(amount: 100);
                    $coupon->set_discount_type(discount_type: 'percent');
                    if ($language == 'de') {
                        $product_display_name = 'Gutschein ' . $product_parent_name;
                    } else {
                        $product_display_name = 'Gift card ' . $product_parent_name;
                    }
                }
                $coupon->set_description(description: $description);
                $coupon->update_meta_data('_coupon_purchased_on_order_id', $order_id);
                $coupon->set_product_ids(product_ids: $config['redeemable_product_ids']);
                $coupon->set_usage_limit(usage_limit: 1);
                $coupon->set_individual_use(is_individual_use: true);

                $coupon_expiry_date = new DateTimeImmutable(datetime: ($purchase_date->format('Y') + 3) . '-12-31 23:59:59', timezone: wp_timezone());

                $coupon->set_date_expires(date: $coupon_expiry_date->getTimestamp());
                $coupon->save();

                $order->update_meta_data('_coupon_code_' . $coupon_data->meta_suffix, $coupon_code);
                $order->update_meta_data('_coupon_expiry_' . $coupon_data->meta_suffix, $coupon_expiry_date->getTimestamp());
                $order->save();

                // Log to order
                $order->add_order_note(note: "Coupon created: {$coupon_code}. Valid for: {$product_parent_name}. Expires: {$coupon_expiry_date->format(format: 'Y-m-d')}");

                // Send email
                send_coupon_email(order: $order, product_parent_name: $product_parent_name, product_display_name: $product_display_name, meta_suffix: $coupon_data->meta_suffix, coupon_is_fixed_amount: $coupon_is_fixed_amount, coupon_amount_formatted: $coupon_amount_formatted, coupon_code: $coupon_code, language: $language);

            }
        }
    }

    // Send coupon per email
    function send_coupon_email(WC_Order $order, string $product_parent_name, string $product_display_name, string $meta_suffix, bool $coupon_is_fixed_amount, string $coupon_amount_formatted, string $coupon_code, string $language): void
    {

        // Setup
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_email = $order->get_billing_email();
        $coupon_expiry_date = DateTimeImmutable::createFromFormat(format: 'U', datetime: $order->get_meta('_coupon_expiry_' . $meta_suffix));

        $email_coupon_code_style = 'font-family: "Courier New", Courier, monospace;';

        $email_data = [
            'en' => [
                'subject' => get_option(option: 'blogname') . " - {$product_display_name}",
                'heading' => "{$product_display_name}",
                'body' => "Hello {$customer_name},<br><br>Thank you for your order! Your <strong>{$product_display_name}</strong> is now active.<br><br>Gift card: <strong style='{$email_coupon_code_style}'>{$coupon_code}</strong>" .
                    ($coupon_is_fixed_amount ? "<br>Amount: <strong>" . $coupon_amount_formatted . "</strong>" : "") .
                    "<br>Valid until: <strong>{$coupon_expiry_date->format(get_option('date_format'))}</strong><br><br>Simply use this coupon at checkout for your next booking."
            ],
            'de' => [
                'subject' => get_option(option: 'blogname') . " - {$product_display_name}",
                'heading' => "{$product_display_name}",
                'body' => "Hallo {$customer_name},<br><br>vielen Dank für deine Bestellung! Dein <strong>{$product_display_name}</strong> ist jetzt aktiv.<br><br>Gutschein: <strong style='{$email_coupon_code_style}'>{$coupon_code}</strong>" .
                    ($coupon_is_fixed_amount ? "<br>Wert: <strong>" . $coupon_amount_formatted . "</strong>" : "") .
                    "<br>Gültig bis: <strong>{$coupon_expiry_date->format(get_option('date_format'))}</strong><br><br>Nutze diesen Gutschein einfach bei deiner nächsten Buchung im Warenkorb."
            ],
        ];

        $content = $email_data[$language] ?? $email_data['en'];

        $attachment_pdf_path = generate_gift_card_pdf(order: $order, product_parent_name: $product_parent_name, meta_suffix: $meta_suffix, coupon_is_fixed_amount: $coupon_is_fixed_amount, coupon_amount_formatted: $coupon_amount_formatted, language: $language);

        // Send email
        $mailer  = WC()->mailer();
        $mailer->send(to: $customer_email, subject: $content['subject'], message: $mailer->wrap_message(email_heading: $content['heading'], message: $content['body']), headers: ["Content-Type: text/html; charset=UTF-8"], attachments: array($attachment_pdf_path));

        if (file_exists($attachment_pdf_path)) {
            unlink($attachment_pdf_path);
        }


    }

    function generate_gift_card_pdf(WC_Order $order, string $product_parent_name, string $meta_suffix, bool $coupon_is_fixed_amount, string $coupon_amount_formatted, string $language): string
    {
        // Setup
        $customer_email = $order->get_billing_email();
        $purchase_date  = $order->get_date_created();
        $site_url       = get_option(option: 'siteurl');
        $blog_name      = get_option(option: 'blogname');
        $coupon_code    = $order->get_meta('_coupon_code_' . $meta_suffix);
        $coupon_expiry_date_timestamp      = $order->get_meta('_coupon_expiry_' . $meta_suffix);

        // Translations
        if ($language === 'de') {
            $text_for      = "Gutschein für";
            $text_code     = "Gutschein-Code";
            $text_title = $coupon_is_fixed_amount ? "Wert: " . $coupon_amount_formatted : $product_parent_name;
            $text_valid    = $coupon_is_fixed_amount ? "Dieser Gutschein hat einen Wert von <strong>{$coupon_amount_formatted}</strong> und ist einlösbar" : "Dieser Gutschein ist gültig für ein <strong>{$product_parent_name}</strong>";
            $text_at       = "bei";
            $text_bought   = "Gekauft am";
            $text_until    = "Gültig bis";
            $text_redeem   = "Einlösbar unter";
        } else {
            $text_for      = "Gift card for";
            $text_code     = "Gift card code";
            $text_title = $coupon_is_fixed_amount ? "Value: " . $coupon_amount_formatted : $product_parent_name;
            $text_valid    = $coupon_is_fixed_amount ? "This gift card has a value of <strong>{$coupon_amount_formatted}</strong> and is redeemable" : "This gift card is valid for a <strong>{$product_parent_name}</strong>";
            $text_at       = "at";
            $text_bought   = "Purchased on";
            $text_until    = "Valid until";
            $text_redeem   = "Redeemable at";
        }

        // Setup Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        $image_url = esc_url(wp_upload_dir()['baseurl'] . '/' . 'kaffeeart-roastery-1.jpg');
        $logo_url  = esc_url(wp_upload_dir()['baseurl'] . '/' . 'kaffeeart-logo.svg');

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
                .gift-card-title { font-size: 34px; font-weight: bold; text-transform: uppercase; line-height: 1.1; margin-bottom: 30px; }
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
                .coupon-code { font-family: 'Courier', 'Courier New', monospace; font-size: 20px; font-weight: bold; letter-spacing: 2px; white-space: nowrap; }
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
                
                <div class="label"><?php echo $text_for; ?></div>
                <div class="gift-card-title"><?php echo $text_title; ?></div>

                <div class="product-highlight"><?php echo esc_html($text_code); ?></div>

                <div style="font-size: 15px; margin-bottom: 20px;">
                    <?php echo $text_valid; ?> <?php echo $text_at; ?> <?php echo esc_html($blog_name); ?>.
                </div>

                <div class="coupon-container">
                    <span class="coupon-code"><?php echo esc_html($coupon_code); ?></span>
                </div>

                <div class="meta-info">
                    <?php echo esc_html($text_bought); ?>: <?php echo $purchase_date->format('d.m.Y'); ?> &nbsp; | &nbsp; <?php echo esc_html($text_until); ?>: <?php echo date('d.m.Y', (int)$coupon_expiry_date_timestamp); ?><br>
                    <?php echo esc_html($text_redeem); ?> <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_url); ?></a>
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

        $folder_path = wp_upload_dir()['basedir'] . '/coupons';

        // Create the folder if it doesn't exist
        if (! file_exists($folder_path)) {
            wp_mkdir_p($folder_path);
        }

        $attachment_pdf_path = $folder_path . '/gift_card_' . $coupon_code . '.pdf';
        file_put_contents(filename: $attachment_pdf_path, data: $dompdf->output());

        return $attachment_pdf_path;

    }


    // Deducts used amount from coupon balance and tracks redemption history
    add_action(hook_name: 'woocommerce_order_status_processed', callback: 'coupon_balance', priority: 10, accepted_args: 1);
    add_action(hook_name: 'woocommerce_order_status_completed', callback: 'coupon_balance', priority: 10, accepted_args: 1);

    function coupon_balance(int $order_id): void
    {
        $order = wc_get_order($order_id);

        // Safety check: Prevents re-running if status changes from "Processed" to "Completed"
        if (!$order || $order->get_meta('_coupon_balance_processed') === 'yes') {
            return;
        }

        $coupons_applied = $order->get_coupon_codes();
        if (empty($coupons_applied)) {
            return;
        }

        foreach ($coupons_applied as $coupon_code) {
            $coupon = new WC_Coupon($coupon_code);

            // Target all fixed_cart coupons
            if ($coupon->get_id() !== 0 && $coupon->get_discount_type() === 'fixed_cart') {

                // Get the specific discount this coupon provided in this order
                $discount_applied = 0;
                foreach ($order->get_coupons() as $order_coupon) {
                    if (strcasecmp($order_coupon->get_code(), $coupon_code) === 0) {
                        $discount_applied = (float) $order_coupon->get_discount();
                        break;
                    }
                }

                // Update redemption history
                $history = $coupon->get_meta('_coupon_redeemed_in_order_ids');
                if (!is_array($history)) {
                    $history = [];
                }

                if (!in_array($order_id, $history)) {
                    $history[] = $order_id;
                    $coupon->update_meta_data('_coupon_redeemed_in_order_ids', $history);
                }

                // Subtract saldo
                $coupon_current_balance = (float) $coupon->get_amount();
                $coupon_new_balance = $coupon_current_balance - $discount_applied;

                if ($coupon_new_balance > 0) {
                    $coupon->set_amount($coupon_new_balance);
                    // Reduce usage count so the coupon remains "Unused" (active)
                    $coupon->set_usage_count($coupon->get_usage_count() - 1);
                }

                $coupon->save();
            }
        }

        // Lock the order so the balance is not subtracted again
        $order->update_meta_data('_coupon_balance_processed', 'yes');
        $order->save();
    }

    // Gift card product logic
    add_action(hook_name: 'woocommerce_before_add_to_cart_form', callback: 'add_custom_coupon_amount_field', priority: 10, accepted_args: 0);

    function add_custom_coupon_amount_field(): void
    {
        $product = wc_get_product();

        // Settings
        static $product_ids = [44185, 44187];
        $messages = [
            'en' => [
                'label' => 'Gift Card Amount',
                'range' => '€15,00 - €250,00',
                'error_empty' => 'Please enter a gift card amount between €15 and €250.',
                'error_range' => 'The gift card amount must be between €15 and €250.'
            ],
            'de' => [
                'label' => 'Gutscheinwert',
                'range' => '€15,00 - €250,00',
                'error_empty' => 'Bitte gib einen Gutscheinbetrag zwischen €15 und €250 ein.',
                'error_range' => 'Der Gutscheinbetrag muss zwischen €15 und €250 liegen.'
            ]
        ];

        if (!$product || !in_array($product->get_id(), $product_ids)) {
            return;
        }

        $language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'de';

        $text = $messages[$language] ?? $messages['de'];

        // Pull WooCommerce settings for JavaScript
        $currency_params = [
            'symbol'    => get_woocommerce_currency_symbol(),
            'pos'       => get_option('woocommerce_currency_pos'),
            'decimals'  => wc_get_price_decimals(),
            'locale'    => ($language === 'de' ? 'de-DE' : 'en-US')
        ];

        ?>
		<style>
			.coupon-amount-table {
				width: 80%;
				max-width: 80%;
				table-layout: fixed;
			}
			#coupon_amount {
				width: 100%;
				max-width: none;
				border: 1px solid #262626;
				border-radius: 0;
				padding: 8px 12px;
				background: transparent;
				color: #262626;
				font-family: 'Inter', sans-serif;
				font-size: 15px;
				box-sizing: border-box;
				text-align: center; 
			}
			#coupon_amount:focus {
				outline: none;
				border-color: #262626;
			}
		</style>
		<form class="variations_form cart" method="post" enctype="multipart/form-data">
			<table class="variations coupon-amount-table">
				<tbody>
					<tr>
						<th class="label"><label for="coupon_amount"><?php echo esc_html($text['label']); ?></label></th>
						<td class="value">
							<input type="number" id="coupon_amount" name="coupon_amount" min="15" max="250" step="1" value="" placeholder="<?php echo esc_attr($text['range']); ?>" required>
						</td>
					</tr>
				</tbody>
			</table>
		</form> 

		<script type="text/javascript">
		jQuery(function($) {
			const params = <?php echo wp_json_encode($currency_params); ?>;
			const $input = $('#coupon_amount');
			const $button = $(".single_add_to_cart_button");
			const $wcForm = $('form.cart').not($input.closest('form')); 

			// Add hidden field to the real WooCommerce form
			$wcForm.append('<input type="hidden" name="coupon_amount" id="coupon_amount_hidden" value="">');
			const $hiddenInput = $('#coupon_amount_hidden');

			const formatter = new Intl.NumberFormat(params.locale, {
				minimumFractionDigits: parseInt(params.decimals),
				maximumFractionDigits: parseInt(params.decimals)
			});

			function formatPrice(price) {
				const formatted = formatter.format(price);
				switch(params.pos) {
					case "left": return params.symbol + formatted;
					case "right": return formatted + params.symbol;
					case "left_space": return params.symbol + " " + formatted;
					case "right_space": return formatted + " " + params.symbol;
					default: return params.symbol + formatted;
				}
			}

			// Round to nearest integer on input and sync to hidden field
			$input.on('input', function() {
				const val = parseFloat($(this).val());
				if (!isNaN(val)) {
					const rounded = Math.round(val);
					$(this).val(rounded);
					$hiddenInput.val(rounded);
				} else {
					$hiddenInput.val('');
				}
			});

			// Round on blur to ensure integer value
			$input.on('blur', function() {
				const val = parseFloat($(this).val());
				if (!isNaN(val)) {
					const rounded = Math.round(val);
					$(this).val(rounded);
					$hiddenInput.val(rounded);
				}
			});

			// Sync on change as well
			$input.on('change', function() {
				$hiddenInput.val($(this).val());
			});

			function updateDisplay() {
				const val = parseFloat($input.val()) || 0;
				const qty = parseInt($('input[name="quantity"]').val(), 10) || 1;
				const totalPrice = val * qty;
				const formattedPrice = formatPrice(totalPrice)
				
				// Update the "Add to Cart" button
				$button.find('span[data-price]').remove();
				if (val > 0) {
					$button.append('<span data-price> - ' + formattedPrice + '</span>');
				}

				// Update the Elementor "Price" widget
				const $priceDisplay = $('.elementor-widget-woocommerce-product-price .price .woocommerce-Price-amount bdi');
				
				if ($priceDisplay.length && val > 0) {
					$priceDisplay.html(formattedPrice);
				} else if ($priceDisplay.length) {
					// Reset to 0 if input is empty
					$priceDisplay.html(formatPrice(0));
				}
			}
			
			$input.on('input change', updateDisplay);
			updateDisplay();
		});
		</script>
		<?php
    }

    // Validation with localized error messages
    add_filter(hook_name: 'woocommerce_add_to_cart_validation', callback: 'validate_custom_coupon_amount', priority: 10, accepted_args: 3);

    function validate_custom_coupon_amount(bool $passed, int $product_id, int $quantity): bool
    {

        // Settings
        static $product_ids = [44185, 44187];
        $messages = [
            'en' => [
                'error_empty' => 'Please enter a gift card amount between €15 and €250.',
                'error_range' => 'The gift card amount must be between €15 and €250.'
            ],
            'de' => [
                'error_empty' => 'Bitte gib einen Gutscheinbetrag zwischen €15 und €250 ein.',
                'error_range' => 'Der Gutscheinbetrag muss zwischen €15 und €250 liegen.'
            ]
        ];

        if (in_array($product_id, $product_ids)) {
            $language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'de';

            $text = $messages[$language] ?? $messages['de'];

            if (!isset($_POST['coupon_amount']) || $_POST['coupon_amount'] === '') {
                wc_add_notice($text['error_empty'], 'error');
                return false;
            }

            $amount = round(floatval($_POST['coupon_amount']));
            if ($amount < 15 || $amount > 250) {
                wc_add_notice($text['error_range'], 'error');
                return false;
            }

            // Update POST with rounded value
            $_POST['coupon_amount'] = $amount;
        }
        return $passed;
    }


    // Store amount in cart
    add_filter(hook_name: 'woocommerce_add_cart_item_data', callback: 'store_custom_coupon_amount', priority: 10, accepted_args: 3);

    function store_custom_coupon_amount(array $cart_item_data, int $product_id, int $variation_id): array
    {
        // Settings
        static $product_ids = [44185, 44187];

        if (in_array($product_id, $product_ids) && !empty($_POST['coupon_amount'])) {
            $cart_item_data['coupon_amount'] = round(floatval($_POST['coupon_amount']));
        }
        return $cart_item_data;
    }

    // Set price in cart
    add_filter(hook_name: 'woocommerce_add_cart_item', callback: 'set_custom_coupon_price', priority: 10, accepted_args: 1);

    function set_custom_coupon_price(array $cart_item): array
    {
        if (isset($cart_item['coupon_amount'])) {
            $cart_item['data']->set_price((float) $cart_item['coupon_amount']);
        }
        return $cart_item;
    }

    // Persist price
    add_action(hook_name: 'woocommerce_before_calculate_totals', callback: 'persist_custom_coupon_price_in_cart', priority: 10, accepted_args: 1);

    function persist_custom_coupon_price_in_cart(WC_Cart $cart): void
    {
        if ((is_admin() && !defined('DOING_AJAX')) || did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['coupon_amount'])) {
                $cart_item['data']->set_price((float) $cart_item['coupon_amount']);
            }
        }
    }

}
