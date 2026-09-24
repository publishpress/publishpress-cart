<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product, $ppcart_currency;
$posted_pwyw_amount = [];
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only extraction for amount validation.
if (isset($_POST['pwyw_amount']) && is_array($_POST['pwyw_amount'])) {
    $posted_pwyw_amount = ppcart_parse_pwyw_amounts(wp_unslash($_POST['pwyw_amount']), false); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Parser sanitizes by field after unslash at the read site.
}

if ($order === false) {
    return false;
}

$sub = new self();
$keys = $sub->order_attrs;
foreach ($keys as $key) {
    if (isset($order->$key) && $order->$key) {
        $sub->$key = $order->$key;
    }
}

$plan = $order->plan;

if ($plan->type == 'recurring') {
    $sub->plan_id              = $plan->stripe_id;
    $sub->option_id            = $plan->option_id;
    $sub->item_name            = $plan->name;
    $sub_amount = (isset($plan->recurring_pwyw) && $plan->recurring_pwyw == '1' && isset($plan->name_your_own_price_text_recurring) && isset($posted_pwyw_amount[$plan->option_id]) && $posted_pwyw_amount[$plan->option_id] >= $plan->price) ? $plan->initial_payment : $plan->price;
    $sub->amount               = $sub_amount * $order->quantity;
    $sub->sub_amount           = $sub_amount * $order->quantity;
    $sub->sub_item_name        = $plan->name;
    $sub->sub_installments     = $plan->installments;
    $sub->sub_interval         = $plan->interval;
    $sub->sub_frequency        = $plan->frequency;
    $sub->sub_next_bill_date   = $plan->next_bill_date;
    $sub->quantity             = $order->quantity;

    if (!empty($plan->db_cancel_at)) {
        $sub->sub_end_date     = $plan->db_cancel_at;
        $sub->cancel_at        = $plan->cancel_at;
    }

    do_action('ppcart_subscription_apply_coupon', $sub, $order);

    if (!empty($plan->trial_days)) {
        $sub->free_trial_days = $plan->trial_days;
    }

    if (!empty($plan->fee)) {
        $sub->sign_up_fee = $plan->fee * $sub->quantity;
    }
}

// process order bumps
if (is_array($order->order_bumps)) {
    foreach ($order->order_bumps as $k => $bump) {
        // does this order bump have a subscription?
        if (isset($bump['plan']) && $bump['plan']->type == 'recurring') {
            // add bump plan info if main product purchase isn't a subscription
            if ($plan->type != 'recurring') {
                $plan = $bump['plan'];
                $sub->product_id           = $bump['id'];
                $sub->product_name         = $bump['name'];
                $sub->plan_id              = $plan->stripe_id;
                $sub->option_id            = $plan->option_id;
                $sub->item_name            = $plan->name;
                $sub_amount = (isset($bump['plan']->recurring_pwyw) && $bump['plan']->recurring_pwyw == '1' && isset($bump['plan']->name_your_own_price_text_recurring) && isset($posted_pwyw_amount[$bump['plan']->option_id]) && $posted_pwyw_amount[$bump['plan']->option_id] >= $bump['plan']->price) ? $bump['plan']->initial_payment : $bump['plan']->price;
                $sub->sub_amount           = $sub_amount;
                $sub->amount               = $sub_amount;
                $sub->sub_item_name        = $plan->name;
                $sub->sub_installments     = $plan->installments;
                $sub->sub_interval         = $plan->interval;
                $sub->sub_frequency        = $plan->frequency;
                $sub->sub_next_bill_date   = $plan->next_bill_date;

                if (!empty($plan->db_cancel_at)) {
                    $sub->sub_end_date     = $plan->db_cancel_at;
                    $sub->cancel_at        = $plan->cancel_at;
                }
                if (!empty($plan->trial_days)) {
                    $sub->free_trial_days = $plan->trial_days;
                }

                if (!empty($plan->fee)) {
                    $sub->sign_up_fee = $plan->fee;
                }
            } else {
                // Add sign up fee
                if (isset($bump['plan']->fee)) {
                    $sub->sign_up_fee += $bump['plan']->fee;
                }
                // Add to sub amount
                $sub_amount = (!empty($bump['plan']->name_your_own_price_text_recurring) && isset($posted_pwyw_amount[$bump['plan']->option_id]) && $posted_pwyw_amount[$bump['plan']->option_id] >= $bump['plan']->price) ? $bump['plan']->initial_payment : $bump['plan']->price;
                $sub->sub_amount += $sub_amount;
            }
        }
    }
}
if ($sub->tax_data) {
    if (isset($sub->tax_data->redeem_vat) && $sub->tax_data->redeem_vat) {
        if ($sub->tax_data->type == 'inclusive') {
            $sub->tax_amount = $sub->tax_rate * $sub->amount / (100 + $sub->tax_rate);
            $sub->sub_amount -= $sub->tax_amount;
        } else {
            $sub->sign_up_fee = $sub->sign_up_fee + ($sub->sign_up_fee * $sub->tax_rate / 100);
            $sub->tax_amount = $sub->tax_rate * $sub->amount / 100;
        }
    } else {
        if ($sub->tax_data->type == 'inclusive') {
            $sub->tax_amount = $sub->tax_rate * $sub->amount / (100 + $sub->tax_rate);
        } else {
            $sub->sign_up_fee = $sub->sign_up_fee + ($sub->sign_up_fee * $sub->tax_rate / 100);
            $sub->tax_amount = $sub->tax_rate * $sub->amount / 100;
            $sub->sub_amount += $sub->tax_amount;
        }
    }
}

do_action('ppcart_after_subscription_load_from_order', $sub, $order);

return $sub;
