<?php

// WooCommerce - Settings
// Last update: 2025-10-08


if (class_exists('WooCommerce') && WC()) {

    // Default sort
    add_action($hook_name = 'current_screen', $callback = function ($current_screen) {
        global $is_wc_order_screen;
        $is_wc_order_screen = ($current_screen->id === 'edit-shop_order');
    }, $priority = 10, $accepted_args = 1);

    // Always show country in formatted addresses (even if same as store base)
    add_filter($hook_name = 'woocommerce_formatted_address_force_country_display', $callback = '__return_true', $priority = 10, $accepted_args = 1);

    // Remove "Reset variations" link on variable products
    // add_filter($hook_name = 'woocommerce_reset_variations_link', $callback = '__return_empty_string', $priority = 10, $accepted_args = 1);

    // Email - Disable password change notification email
    add_filter($hook_name = 'woocommerce_disable_password_change_notification', $callback = '__return_true', $priority = 10, $accepted_args = 1);

    // Email - Remove WooCommerce email footer ad ("Process your orders on the go. Get the app.")
    add_action($hook_name = 'woocommerce_email_footer', $callback = function () {
        $mailer = WC()->mailer()->get_emails();
        $object = $mailer['WC_Email_New_Order'];
        remove_action($hook_name = 'woocommerce_email_footer', $callback = array($object, 'mobile_messaging'), $priority = 9);
    }, $priority = 8, $accepted_args = 1);

    // Save cancellation date in order meta
    add_action($hook_name = 'woocommerce_order_status_cancelled', $callback = function ($order_id, $order) {
        // Get timezone from order creation date
        $timezone = $order->get_date_created()->getTimezone();

        // Get current datetime with the timezone from order creation date
        $date_cancelled = new WC_DateTime('now', $timezone);

        // Add date cancelled as custom metadata and save
        $order->update_meta_data('date_cancelled', $date_cancelled->format(DateTime::ATOM));
        $order->save();
    }, $priority = 10, $accepted_args = 2);

    // PhastPress - Disable PhastPress on WooCommerce checkout and cart pages
    if (class_exists('\Kibo\PhastPlugins\PhastPress\WordPress')) {
        add_filter($hook_name = 'phastpress_disable', $callback = function ($disable) {
            return $disable || is_cart() || is_checkout();
        }, $priority = 10, $accepted_args = 1);
    }

    // Hello Elementor theme - Disable gallery lightbox/slider/zoom
    if ('hello-elementor' === get_template()) {
        add_action($hook_name = 'wp', $callback = function () {
            remove_theme_support('wc-product-gallery-lightbox');
            remove_theme_support('wc-product-gallery-slider');
            remove_theme_support('wc-product-gallery-zoom');
        }, $priority = 10, $accepted_args = 1);
    }

}
