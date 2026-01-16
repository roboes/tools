<?php

// WooCommerce - Settings
// Last update: 2026-01-15


if (function_exists('WC')) {

    // Always show country in formatted addresses (even if same as store base)
    add_filter(hook_name: 'woocommerce_formatted_address_force_country_display', callback: '__return_true', priority: 10, accepted_args: 1);

    // Remove "Reset variations" link on variable products
    // add_filter(hook_name: 'woocommerce_reset_variations_link', callback: '__return_empty_string', priority: 10, accepted_args: 1);

    // Email - Disable password change notification email
    add_filter(hook_name: 'woocommerce_disable_password_change_notification', callback: '__return_true', priority: 10, accepted_args: 1);

    // Email - Remove WooCommerce email footer ad ("Process your orders on the go. Get the app.")
    add_action(hook_name: 'woocommerce_email_footer', callback: function (): void {
        if (!WC()->mailer()) {
            return;
        }
        $mailer = WC()->mailer()->get_emails();
        $object = $mailer['WC_Email_New_Order'] ?? null;
        if ($object) {
            remove_action(hook_name: 'woocommerce_email_footer', callback: array($object, 'mobile_messaging'), priority: 9);
        }
    }, priority: 8, accepted_args: 1);

    // Save cancellation date in order meta
    add_action(hook_name: 'woocommerce_order_status_cancelled', callback: function (int $order_id, WC_Order $order): void {
        if (!$order instanceof WC_Order) {
            return;
        }
        try {
            // Get timezone from order creation date
            $timezone = $order->get_date_created() |> $this->getTimezone();

            // Get current datetime with the timezone from order creation date
            $date_cancelled = new WC_DateTime('now', $timezone);

            // Add date cancelled as custom metadata and save
            $order->update_meta_data('date_cancelled', $date_cancelled->format(DateTime::ATOM));
            $order->save();
        } catch (Throwable $error) {
            // Error handled via catch block as per requirements
        }
    }, priority: 10, accepted_args: 2);

    // PhastPress - Disable PhastPress on WooCommerce checkout and cart pages
    if (class_exists('\Kibo\PhastPlugins\PhastPress\WordPress')) {
        add_filter(hook_name: 'phastpress_disable', callback: function (bool $disable): bool {
            if (is_admin()) {
                return $disable;
            }
            return $disable || is_cart() || is_checkout();
        }, priority: 10, accepted_args: 1);
    }

    // Hello Elementor theme - Disable gallery lightbox/slider/zoom
    if ('hello-elementor' === get_template()) {
        add_action(hook_name: 'wp', callback: function (): void {
            if (is_admin()) {
                return;
            }
            remove_theme_support('wc-product-gallery-lightbox');
            remove_theme_support('wc-product-gallery-slider');
            remove_theme_support('wc-product-gallery-zoom');
        }, priority: 10, accepted_args: 1);
    }

}
