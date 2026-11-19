<?php

// WooCommerce - Converts an unused 100% course coupon into a fixed-amount Mehrzweckgutschein coupon
// Last update: 2026-11-19

function convert_course_coupon_to_gift_card(int $coupon_id, ?float $amount = null): string
{
    $coupon = new WC_Coupon($coupon_id);

    if (!$coupon->get_id()) {
        return "Error: Coupon #{$coupon_id} not found.";
    }
    if ($coupon->get_discount_type() !== 'percent') {
        return "Error: Coupon #{$coupon_id} is not a course (percent) coupon.";
    }
    if ($coupon->get_usage_count() > 0) {
        return "Error: Coupon #{$coupon_id} has already been redeemed.";
    }
    $expires = $coupon->get_date_expires();
    if ($expires && $expires->getTimestamp() < time()) {
        return "Error: Coupon #{$coupon_id} has expired.";
    }

    // Find the originating order item to get the value paid and the right exclusions
    $order_id = $coupon->get_meta('_coupon_purchased_on_order_id');
    $order    = $order_id ? wc_get_order($order_id) : null;
    $gift_card = get_coupon_variation_validation(44185);
    $excluded  = $gift_card->config['excluded_redeemable_ids'] ?? [44185, 44187];

    if ($order) {
        foreach ($order->get_items() as $item) {
            $product_id  = $item->get_variation_id() ?: $item->get_product_id();
            $coupon_data = get_coupon_variation_validation($product_id);
            if (!$coupon_data) {
                continue;
            }

            $suffix = $coupon_data->config['meta_key_suffix'];
            if (strtoupper($order->get_meta('_coupon_code_' . $suffix)) !== strtoupper($coupon->get_code())) {
                continue;
            }

            if ($amount === null) {
                // Gross: what the customer actually paid
                $amount = (float) $item->get_total() + (float) $item->get_total_tax();
            }
            break;
        }

    }

    if ($amount === null || $amount <= 0) {
        return "Error: Could not determine the amount. Pass it explicitly.";
    }

    // Keep a record of the original settings in case it needs to be reverted
    $coupon->update_meta_data('_coupon_converted_from', wp_json_encode([
        'discount_type' => $coupon->get_discount_type(),
        'amount'        => $coupon->get_amount(),
        'product_ids'   => $coupon->get_product_ids(),
        'converted_on'  => current_time('mysql'),
    ]));

    $coupon->set_discount_type('fixed_cart');
    $coupon->set_amount($amount);
    $coupon->set_usage_limit(0);
    $coupon->set_limit_usage_to_x_items(0);
    $coupon->set_product_ids([]);
    $coupon->set_excluded_product_ids($excluded);
    $coupon->update_meta_data('_coupon_value_initial', $amount);
    if (class_exists('WooCommerce_Germanized')) {
        $coupon->update_meta_data('is_voucher', 'yes');
    }
    $coupon->save();

    if ($order) {
        $order->add_order_note("Coupon {$coupon->get_code()} converted to a fixed-amount gift card ({$amount}).");
    }

    return "Success: Coupon {$coupon->get_code()} is now a fixed-amount coupon with a balance of {$amount}.";
}


echo convert_course_coupon_to_gift_card(coupon_id: 1234, amount: 99.00);
