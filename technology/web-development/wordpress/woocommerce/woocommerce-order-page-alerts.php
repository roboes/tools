<?php

// WooCommerce - Display multilingual top notices and inline badges based on product attributes on edit order page
// Last update: 2026-01-12

if (class_exists('WooCommerce') && WC()) {
    add_action('admin_notices', 'woocommerce_order_page_alerts');
    add_action('woocommerce_after_order_itemmeta', 'woocommerce_order_page_alerts', 10, 3);

    function woocommerce_order_page_alerts($item_id = null, $item = null, $product = null): void
    {
        if (! is_admin()) {
            return;
        }

        // Settings
        $alerts = [
            'green_coffee' => [
                'product-attribute-slugs' => ['coffee-processing-green-coffee-en', 'coffee-processing-green-coffee-de'],
                'alert-top'   => ['de' => '⚠️ Diese Bestellung enthält Rohkaffee.', 'en' => '⚠️ This order contains Green Coffee.'],
                'alert-badge' => ['de' => '⚠️ Rohkaffee', 'en' => '⚠️ Green Coffee'],
                'alert-color' => '#d63638'
            ],
        ];

        $current_language = substr(get_user_locale(), 0, 2);
        $current_filter = current_filter();

        // Top alert
        if ($current_filter === 'admin_notices') {

            if (get_current_screen()?->id !== 'woocommerce_page_wc-orders') {
                return;
            }

            $order = wc_get_order(absint($_GET['id'] ?? 0));

            if (! $order) {
                return;
            }

            $found_notices = [];

            foreach ($order->get_items() as $order_item) {
                foreach ($order_item->get_meta_data() as $meta) {
                    if (! str_contains($meta->key, 'coffee-processing')) {
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
            if (! $item) {
                return;
            }

            foreach ($item->get_meta_data() as $meta) {
                if (! str_contains($meta->key, 'coffee-processing')) {
                    continue;
                }

                foreach ($alerts as $alert) {
                    if (! in_array($meta->value, $alert['product-attribute-slugs'], true)) {
                        continue;
                    }

                    $alert_badge = $alert['alert-badge'][$current_language] ?? $alert['alert-badge']['en'];

                    echo "<style>tr.item[data-order_item_id='" . esc_attr($item_id) . "'] { background: #fff0f0 !important; }</style>";
                    echo "<div style='display:inline-block; margin-top:5px; padding:2px 8px; background:" . esc_attr($alert['alert-color']) . "; color:#fff; border-radius:3px; font-size:12px;'>" . esc_html($alert_badge) . "</div>";
                }
            }
        }
    }
}
