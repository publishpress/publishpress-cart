<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb, $ppcart_currency, $ppcart_stripe;

if ($ppcart_stripe) {
    $this->stripe = ppcart_stripe_client($ppcart_stripe['sk']);
    $stripe = $this->stripe;
} else {
    $this->stripe = false;
}

$post_title = get_the_title($post_id);
$stripe_product = $this->get_stripe_product($post_id);

$pay_options_key = ppcart_meta_key('pay_options');
if (! isset($objects[ $pay_options_key ]) || ! is_array($objects[ $pay_options_key ])) {
    return;
}

foreach ($objects[ $pay_options_key ] as $key => $option) {
    $option['option_name'] ??= '';
    $option['price'] ??= '';
    $normal_price = [
        'name' => $post_title . ' - ' . $option['option_name'],
        'id' => $option['option_id'],
        'price' => $option['price'] ?? '',
        'amount' => (float)$option['price'] ?? '',
        'interval' => $option['interval'] ?? '',
        'plan_id' => $option['stripe_plan_id'] ?? '',
        'frequency' => $option['frequency'] ?? '',
        'field_name' => 'stripe_plan_id',
    ];

    $sale_price = false;
    if (!empty($option['sale_price'])) {
        $option['sale_option_name'] ??= '';
        $sale_price = [
            'name' => $post_title . ' - ' . $option['sale_option_name'],
            'id' => $option['option_id'] . '_sale',
            'price' => $option['sale_price'] ?? '',
            'amount' => (float)$option['sale_price'] ?? '',
            'interval' => $option['sale_interval'] ?? '',
            'plan_id' => $option['sale_stripe_plan_id'] ?? '',
            'frequency' => $option['sale_frequency'] ?? '',
            'field_name' => 'sale_stripe_plan_id',
        ];
    }

    $plans = [$normal_price, $sale_price];

    foreach ($plans as $plan) {
        if ($plan) {
            $_stripe_id = $plan['plan_id'];
            $option['product_type'] ??= '';

            // Create Stripe Plan
            if ($option['product_type'] == "recurring" && $this->stripe && $stripe_product !== false) {
                try {
                    $retrieve_plan = $stripe->prices->retrieve($_stripe_id);
                    $plan_price_non_decimal = (string)ppcart_price_in_cents($plan['amount'], $ppcart_currency);
                    $resolved_product_id = ppcart_stripe_metadata($retrieve_plan, 'ppcart_product_id');
                    $canonical_product_id = ppcart_stripe_metadata_raw($retrieve_plan, 'ppcart_product_id');
                    $identity_mismatch = ! ppcart_stripe_metadata_is_empty($resolved_product_id)
                        && (string) $resolved_product_id !== (string) $post_id;
                    if (
                        ($retrieve_plan->unit_amount != $plan_price_non_decimal) ||
                        ($retrieve_plan->recurring->interval != $plan['interval']) ||
                        ($retrieve_plan->recurring->interval_count != $plan['frequency']) ||
                        $identity_mismatch ||
                        ($retrieve_plan->product != $stripe_product->id)
                    ) {
                        $plan_id = $this->create_plan($plan, $post_id, $stripe_product);
                        if ($plan_id) {
                            $objects[ $pay_options_key ][$key][$plan['field_name']] = $plan_id;
                        }
                    } else {
                        $objects[ $pay_options_key ][$key][$plan['field_name']] = $_stripe_id;
                        if (ppcart_stripe_metadata_is_empty($canonical_product_id)) {
                            try {
                                $stripe->prices->update(
                                    $_stripe_id,
                                    [
                                        'metadata' => ['ppcart_product_id' => $post_id, 'origin' => get_site_url()],
                                    ]
                                );
                            } catch (Exception $e) {
                                // Keep the existing price id when metadata backfill fails.
                            }
                        }
                    }
                } catch (Exception $e) {
                    $plan_id = $this->create_plan($plan, $post_id, $stripe_product->id);
                    if ($plan_id) {
                        $objects[ $pay_options_key ][$key][$plan['field_name']] = $plan_id;
                    }
                }
            } else {
                $objects[ $pay_options_key ][$key][$plan['field_name']] = $plan['id'];
            }
        }
    }
}

ppcart_update_post_meta($post_id, $pay_options_key, $objects[ $pay_options_key ]);

do_action('ppcart_product_save_stripe_meta', $post_id, $objects, $this->stripe, $ppcart_currency);
