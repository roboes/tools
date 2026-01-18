<?php

// WooCommerce - Display multilingual top notices and inline badges based on product attributes on edit order page
// Last update: 2026-01-15


if (function_exists('WC') && is_admin()) {

    add_action(hook_name: 'admin_notices', callback: 'woocommerce_order_page_alerts', priority: 10, accepted_args: 1);
    add_action(hook_name: 'woocommerce_after_order_itemmeta', callback: 'woocommerce_order_page_alerts', priority: 10, accepted_args: 3);

    function woocommerce_order_page_alerts(mixed $item_id = null, ?WC_Order_Item $item = null, ?WC_Product $product = null): void
    {
        // Settings
        $alerts = [
            'green_coffee' => [
                'product-attribute-slugs' => ['coffee-processing-green-coffee-en', 'coffee-processing-green-coffee-de'],
                'alert-top'   => ['en' => '⚠️ This order contains Green Coffee.', 'de' => '⚠️ Diese Bestellung enthält Rohkaffee.',],
                'alert-badge' => ['en' => '⚠️ Green Coffee', 'de' => '⚠️ Rohkaffee',],
                'alert-color' => '#d63638'
            ],
        ];

        // Get current language
        $current_language = substr(get_user_locale(), 0, 2);
        $current_filter = current_filter();

        // Top alert
        if ($current_filter === 'admin_notices') {

            if (get_current_screen()?->id !== 'woocommerce_page_wc-orders') {
                return;
            }

            $order_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? 0;
            $order = wc_get_order($order_id);

            if (!$order instanceof WC_Order) {
                return;
            }

            $found_notices = [];

            foreach ($order->get_items() as $order_item) {
                foreach ($order_item->get_meta_data() as $meta) {
                    if (!str_contains((string) $meta->key, 'coffee-processing')) {
                        continue;
                    }

                    foreach ($alerts as $key => $alert) {
                        if (in_array($meta->value, $alert['product-attribute-slugs'], true)) {
                            $found_notices[$key] = $alert['alert-top'][$current_language] ?? $alert['alert-top']['en'];
                        }
                    }
                }
            }

            foreach ($found_notices as $message) {
                echo '<div class="notice notice-error" style="border-left-width: 10px;"><p style="font-size: 14px">' . esc_html($message) . '</p></div>';
            }
        }

        // Badge alert
        if ($current_filter === 'woocommerce_after_order_itemmeta') {
            if (!$item instanceof WC_Order_Item) {
                return;
            }

            foreach ($item->get_meta_data() as $meta) {
                if (!str_contains((string) $meta->key, 'coffee-processing')) {
                    continue;
                }

                foreach ($alerts as $alert) {
                    if (!in_array($meta->value, $alert['product-attribute-slugs'], true)) {
                        continue;
                    }

                    $alert_badge = $alert['alert-badge'][$current_language] ?? $alert['alert-badge']['en'];

                    echo "<style>tr.item[data-order_item_id='" . esc_attr((string)$item_id) . "'] { background: #fff0f0 !important; }</style>";
                    echo "<div style='display:inline-block; margin-top:5px; padding:2px 8px; background:" . esc_attr($alert['alert-color']) . "; color:#fff; border-radius:3px; font-size:12px;'>" . esc_html($alert_badge) . "</div>";
                }
            }
        }
    }
}
