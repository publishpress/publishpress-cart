<?php

if (! defined('ABSPATH')) {
    exit;
}


$product_id = absint($product_id);
if (! $product_id || ! ppcart_is_product_post_type(get_post_type($product_id))) {
    return new WP_Error('ppcart_import_charge_product', __('This isn\'t a Cart product. Choose another product.', 'publishpress-cart'));
}

// The one-time payment plans a checkout of this product can store.
$one_time_options = [];
$options = ppcart_get_post_meta($product_id, 'pay_options', true);
foreach (is_array($options) ? $options : [] as $option) {
    if (is_array($option) && ! empty($option['option_id']) && in_array($option['product_type'] ?? '', [ '', 'one-time', 'pwyw' ], true)) {
        $one_time_options[] = $option;
    }
}
if (! $one_time_options) {
    return new WP_Error('ppcart_import_charge_plan', __('This product has no one-time payment plan. Add one, or choose another product.', 'publishpress-cart'));
}

$exists = self::find_imported_charge_order([ $payment_id ]);
if ($exists) {
    return $exists;
}

$stripe_mode = ppcart_normalize_stripe_mode('');
$charge = self::retrieve_import_charge($payment_id, $stripe_mode);
if (is_wp_error($charge)) {
    return $charge;
}

$charge_id = (string) self::get($charge, 'id', '');

// The customer's details come from the payment's billing details first,
// then from the Stripe customer.
$customer = self::get($charge, 'customer', null);
$customer_id = is_string($customer) ? $customer : (string) self::get($customer, 'id', '');
if ('' !== $customer_id && ! is_object($customer)) {
    try {
        $customer = self::get_client_for_mode($stripe_mode)->customers->retrieve($customer_id);
    } catch (Throwable $e) {
        $customer = null;
    }
}
if (! is_object($customer) || self::get($customer, 'deleted', false)) {
    $customer = null;
}

$billing = self::get($charge, 'billing_details', null);
$detail = function ($field) use ($billing, $customer) {
    $value = trim((string) self::get($billing, $field, ''));
    if ('' === $value && $customer) {
        $value = trim((string) self::get($customer, $field, ''));
    }

    return $value;
};

$email = strtolower(sanitize_email($detail('email')));
if ('' === $email) {
    $email = strtolower(sanitize_email((string) self::get($charge, 'receipt_email', '')));
}
if ('' === $email) {
    return new WP_Error('ppcart_import_charge_email', __('This payment has no customer email. Add an email to the customer in Stripe, then import again.', 'publishpress-cart'));
}

$name = sanitize_text_field($detail('name'));
$name_parts = '' !== $name ? explode(' ', $name, 2) : [ '', '' ];

$address = self::get($billing, 'address', null);
if (! $address || '' === trim((string) self::get($address, 'line1', '') . self::get($address, 'country', ''))) {
    $address = $customer ? self::get($customer, 'address', null) : null;
}

$currency = strtoupper((string) self::get($charge, 'currency', 'usd'));
$charge_amounts = self::import_charge_amounts($charge);
// Checkout stores plain numbers (floats), so the import does too.
$amount = self::stripe_amount_to_number($charge_amounts['cents'], $currency);
$amount_refunded = $charge_amounts['refunded_cents'];
$fully_refunded = $charge_amounts['fully_refunded'];

// Use the product's one-time plan with this price, or else its first
// one-time plan, as a checkout would store it.
$plan = null;
$on_sale = 0;
foreach ($one_time_options as $option) {
    foreach ([ 'sale_' => 1, '' => 0 ] as $prefix => $sale) {
        $price = $option[ $prefix . 'price' ] ?? '';
        if (is_numeric($price) && abs((float) $price - $amount) < 0.005) {
            $plan = ppcart_plan($option['option_id'], $sale, $product_id);
            $on_sale = $sale;
            break 2;
        }
    }
}
if (! is_object($plan)) {
    $plan = ppcart_plan($one_time_options[0]['option_id'], '', $product_id);
    $on_sale = 0;
}
$plan = is_object($plan) ? $plan : null;

$user = get_user_by('email', $email);
$product_name = ppcart_get_public_product_name($product_id);

$no_integrations = '__return_false';
add_filter('ppcart_trigger_order_integrations', $no_integrations, 99);

try {
    $imported_order = PPCart_Stripe_Sync_Context::run(
        function () use ($charge, $charge_id, $customer_id, $email, $name, $name_parts, $detail, $address, $currency, $amount, $amount_refunded, $fully_refunded, $plan, $on_sale, $user, $product_id, $product_name) {
            $order = new PPCart_Order();
            $order->pay_method = 'stripe';
            $order->gateway_mode = self::get($charge, 'livemode', false) ? 'live' : 'test';
            $order->transaction_id = $charge_id;
            $order->customer_id = $customer_id;
            $order->email = $email;
            $order->customer_name = $name;
            $order->first_name = $name_parts[0];
            $order->firstname = $name_parts[0];
            $order->last_name = $name_parts[1] ?? '';
            $order->lastname = $name_parts[1] ?? '';
            $order->phone = sanitize_text_field($detail('phone'));
            foreach ([ 'address1' => 'line1', 'address2' => 'line2', 'city' => 'city', 'state' => 'state', 'zip' => 'postal_code', 'country' => 'country' ] as $field => $stripe_field) {
                $order->$field = $address ? sanitize_text_field((string) self::get($address, $stripe_field, '')) : '';
            }
            $order->user_account = $user ? (int) $user->ID : 0;
            $order->product_id = $product_id;
            $order->product_name = $product_name;
            $order->plan = $plan;
            $order->option_id = $plan ? $plan->option_id : null;
            $order->plan_id = $plan ? $plan->stripe_id : null;
            $order->item_name = $plan ? $plan->name : $product_name;
            $order->on_sale = $on_sale;
            $order->currency = $currency;
            $order->amount = $amount;
            $order->invoice_total = $amount;
            $order->invoice_subtotal = $amount;
            $order->pre_tax_amount = $amount;
            $order->main_offer_amt = $amount;
            $order->tax_amount = 0;
            $order->quantity = 1;
            $order->payment_status = $fully_refunded ? 'refunded' : 'paid';
            $order->status = $fully_refunded ? 'refunded' : 'paid';
            $order->items = [
                [
                    'product_id'   => $product_id,
                    'price_id'     => $plan ? (string) $plan->option_id : '',
                    'product_name' => $product_name,
                    'price_name'   => $plan ? $plan->name : '',
                    'unit_price'   => $amount,
                    'item_type'    => 'main',
                    'quantity'     => 1,
                    'subtotal'     => $amount,
                    'total_amount' => $amount,
                    'tax_amount'   => 0,
                ],
            ];
            $order->store(false);

            if (! $order->id) {
                return false;
            }

            if ($amount_refunded) {
                ppcart_update_post_meta($order->id, 'refund_log', self::build_refund_log_from_charge($charge, $currency));
                ppcart_update_post_meta($order->id, 'refund_amount', ppcart_format_stripe_number($amount_refunded, $currency));
            }

            $order->set_date_from_timestamp(absint(self::get($charge, 'created', time())));

            return $order;
        }
    );
} finally {
    remove_filter('ppcart_trigger_order_integrations', $no_integrations, 99);
}

if (! $imported_order || ! $imported_order->id) {
    return new WP_Error('ppcart_import_charge_store', __('The order could not be saved.', 'publishpress-cart'));
}

/* translators: %s: Stripe charge ID */
ppcart_log_entry($imported_order->id, sprintf(__('Imported from Stripe payment %s.', 'publishpress-cart'), $charge_id));

return new PPCart_Order($imported_order->id);
