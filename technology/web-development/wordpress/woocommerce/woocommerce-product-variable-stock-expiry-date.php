<?php

// WooCommerce - Product variation external URL
// Last update: 2026-02-09

if (function_exists('WC')) {

    if (is_admin()) {

        // Add Expiry Date field to variations
        add_action(hook_name: 'woocommerce_product_after_variable_attributes', callback: function ($loop, $variation_data, $variation) {

            woocommerce_wp_text_input([
                'id' => "_product_variation_expiry_date[{$loop}]",
                'name' => "_product_variation_expiry_date[{$loop}]",
                'label' => __('Expiry Date', 'woocommerce'),
                'type' => 'date',
                'value' => get_post_meta($variation->ID, '_product_variation_expiry_date', true),
                'wrapper_class' => 'form-row form-row-full',
            ]);

        }, priority: 10, accepted_args: 3);

        // Save the Expiry Date field
        add_action(hook_name: 'woocommerce_save_product_variation', callback: function ($variation_id, $loop) {

            if (!isset($_POST['_product_variation_expiry_date'][$loop])) {
                return;
            }

            $expiry_date = sanitize_text_field($_POST['_product_variation_expiry_date'][$loop]);
            update_post_meta($variation_id, '_product_variation_expiry_date', $expiry_date);

            // Sync to all language translations (Polylang/WPML)
            $product_variation_languages = apply_filters('wpml_active_languages', null);

            if (!$product_variation_languages) {
                return;
            }

            foreach ($product_variation_languages as $language_code => $language) {
                $translated_id = apply_filters('wpml_object_id', $variation_id, 'product_variation', false, $language_code);
                if ($translated_id && $translated_id != $variation_id) {
                    update_post_meta($translated_id, '_product_variation_expiry_date', $expiry_date);
                }
            }

        }, priority: 10, accepted_args: 2);

    }

    add_filter(hook_name: 'woocommerce_product_variation_get_stock_status', callback: function ($stock_status, $product) {

        $expiry_date = get_post_meta($product->get_id(), '_product_variation_expiry_date', true);

        if ($expiry_date) {
            $expiry = DateTimeImmutable::createFromFormat(format: 'Y-m-d', datetime: $expiry_date, timezone: wp_timezone());
            if ($expiry && $expiry->setTime(23, 59, 59) < new DateTimeImmutable(datetime: 'now', timezone: wp_timezone())) {
                return 'outofstock';
            }
        }

        return $stock_status;

    }, priority: 10, accepted_args: 2);

    // Hide expired variations from the frontend
    add_filter(hook_name: 'woocommerce_available_variation', callback: function ($variation_data, $product, $variation) {

        $expiry_date = get_post_meta($variation->get_id(), '_product_variation_expiry_date', true);

        if ($expiry_date) {
            $expiry = DateTimeImmutable::createFromFormat(format: 'Y-m-d', datetime: $expiry_date, timezone: wp_timezone());
            if ($expiry && $expiry->setTime(23, 59, 59) < new DateTimeImmutable(datetime: 'now', timezone: wp_timezone())) {
                $variation_data['is_purchasable'] = false;
                $variation_data['is_in_stock'] = false;
            }
        }

        return $variation_data;

    }, priority: 10, accepted_args: 3);

}
