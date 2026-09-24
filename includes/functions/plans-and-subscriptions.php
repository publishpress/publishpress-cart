<?php

if (! defined('ABSPATH')) {
    exit;
}


function ppcart_next_bill_time($sub, $date = null)
{
    if (is_numeric($sub)) {
        $sub = ppcart_setup_order($sub);
    }
    $sub = (object) $sub;
    $created = get_the_time("Y-m-d h:i:s", $sub->ID);

    if (strtotime($date) !== false) {
        $next_bill_date = gmdate("Y-m-d h:i:s", strtotime($date));
    } else {
        $next_bill_date = gmdate("Y-m-d h:i:s");
    }

    if (isset($sub->free_trial_days)) {
        $free_trial_days = $sub->free_trial_days;
        $start_date =  gmdate("Y-m-d h:i:s", strtotime($created . "+" . $free_trial_days . " day"));
        if (gmdate("Y-m-d h:i:s") < $start_date) {
            return $start_date;
        }
    }

    $next = strtotime($next_bill_date . "+" . $sub->sub_frequency . " " . $sub->sub_interval);
    $old_next = ppcart_get_post_meta($sub->ID, 'sub_next_bill_date', true);
    return max($next, $old_next);
}

/**
 * Resolve a product payment plan option into a plan object or array.
 *
 * @param string     $option_id  Plan option ID.
 * @param string     $sale       Sale prefix, or empty.
 * @param int|string $product_id Product post ID, or empty to use the global product.
 * @param bool       $array      True to return an array instead of an object.
 * @return object|array|false
 */
function ppcart_plan($option_id, $sale = '', $product_id = '', $array = false)
{
    global $ppcart_product;

    if (!$option_id) {
        return false;
    }

    if (!$product_id) {
        if (!$ppcart_product) {
            return false;
        }
        $plans = $ppcart_product->pay_options;
    } else {
        $plans = ppcart_get_post_meta($product_id, 'pay_options', true);
    }

    if (!$plans) {
        return false;
    }

    foreach ($plans as $val) {
        $val['stripe_plan_id'] ??= '';
        $val['sale_stripe_plan_id'] ??= '';
        if ($option_id == $val['option_id'] ||  $option_id == $val['stripe_plan_id'] ||  $option_id == $val['sale_stripe_plan_id']) {
            $option = $val;
            break;
        }
        if (isset($val['url_slug']) && $val['url_slug'] === $option_id) {
            $option = $val;
            break;
        }
    }

    if (!isset($option) || !$option) {
        return false;
    }

    $option['product_type'] ??= '';

    if ($sale === 'current') {
        $sale = ppcart_is_prod_on_sale($product_id);
    }
    $sale = ($sale) ? 'sale_' : '';

    $option = apply_filters('ppcart_plan', $option, $sale);

    $plan = [];
    $plan['type'] = ($option['product_type'] == '') ? 'one-time' : $option['product_type'];

    $plan['option_id']  = $option['option_id'];
    $plan['name']       = $option[$sale . 'option_name'] ?? '';
    $plan['stripe_id']  = $option[$sale . 'stripe_plan_id'];
    $plan['price']      = ($option['product_type'] == 'free') ? 'free' : ($option[$sale . 'price'] ?? '');
    $plan['initial_payment'] = (float) $plan['price'];
    $plan['cancel_immediately'] = $option['cancel_immediately'] ?? '';
    $plan['tax_rate'] = $option['tax_rate'] ?? '';
    $plan['recurring_pwyw'] = $option['recurring_pwyw'] ?? '';
    $plan['name_your_own_price_text_recurring'] = (isset($option['recurring_pwyw']) && $option['recurring_pwyw'] == '1' && isset($option['name_your_own_price_text_recurring'])) ? $option['name_your_own_price_text_recurring'] : '';

    if ($plan['type'] == 'free') {
        $plan['initial_payment'] = 0;
    } elseif ($plan['type'] == 'recurring') {
        $plan['installments']  = $option[$sale . 'installments'];
        $plan['interval']      = $option[$sale . 'interval'];
        $plan['frequency']     = $option[$sale . 'frequency'] ?? 1;

        $plan = apply_filters('ppcart_plan_subscription_features', $plan, $option, $sale);

        $plan['trial_days'] ??= '';
        $plan['fee'] ??= '';

        if ($plan['trial_days']) {
            $plan['next_bill_date'] = strtotime(gmdate("Y-m-d", strtotime("+" . $plan['trial_days'] . " day")));
        } else {
            $plan['next_bill_date'] = strtotime(gmdate("Y-m-d", strtotime("+" . $plan['frequency'] . " " . $plan['interval'])));
        }

        if ($plan['installments'] > 1) {
            $duration = $plan['installments'] * $plan['frequency'];
            $cancel_at = $duration . ' ' . $plan['interval'];

            if ($plan['trial_days']) {
                $cancel_at .= " + " . $plan['trial_days'] . " day";
            }

            $plan['cancel_at'] = strtotime($cancel_at);
            $plan['db_cancel_at'] = gmdate("Y-m-d", strtotime($cancel_at));
        } else {
            $plan['cancel_at'] = null;
            $plan['db_cancel_at'] = null;
        }

        if ($plan['frequency'] > 1) {
            $text = ppcart_format_price($plan['price']) . ' / ' . $plan['frequency'] . ' ' . ppcart_pluralize_interval($plan['interval']);
        } else {
            $text = ppcart_format_price($plan['price']) . ' / ' . $plan['interval'];
        }

        $installments = $plan['installments'];
        if ($installments > 1) {
            $text .=  ' x ' . $installments;
        }

        if ($plan['trial_days']) {
            /* translators: %s: number of trial days. */
            $text .= ' ' . sprintf(__('with a %s-day free trial', 'publishpress-cart'), $plan['trial_days']);
        }

        if ($plan['fee']) {
            /* translators: %s: sign-up fee amount. */
            $text .= ' ' . sprintf(__('and a %s sign-up fee', 'publishpress-cart'), ppcart_format_price($plan['fee']));
        }

        $plan['text'] = $text;
    }

    $plan = apply_filters('_ppcart_plan', $plan, $option, $sale);
    if (!$array) {
        return (object) $plan;
    } else {
        return $plan;
    }
}


function ppcart_maybe_do_subscription_complete($subscription_id)
{
    $end_date = ppcart_get_post_meta($subscription_id, 'sub_end_date', true);
    if ($end_date == gmdate("Y-m-d")) {
        $order_info = ppcart_setup_order($subscription_id, $array = true);

        ppcart_log_entry($subscription_id, __('Installment plan completed', 'publishpress-cart'));
        ppcart_trigger_integrations('completed', $order_info);

        wp_update_post([ 'ID' => $subscription_id, 'post_status' => 'completed']);
        ppcart_update_post_meta($subscription_id, 'sub_status', 'completed');
    }
}

function ppcart_redirect($url)
{
    $url = trim((string) $url);
    if ('' === $url) {
        return false;
    }

    nocache_headers();
    // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Post-purchase redirects may use merchant-configured external URLs.
    wp_redirect($url);
    exit;
}

add_filter('ppcart_format_subscription_order_detail', 'ppcart_filter_format_subcription_terms_text', 10, 7);
function ppcart_filter_format_subcription_terms_text($text, $terms, $plan, $trial_days = false, $sign_up_fee = false, $discount = false, $discount_duration = false)
{
    if (!$terms) {
        return $text;
    }

    if ($trial_days && $trial_days > 0) {
        /* translators: %d: number of trial days. */
        $txt = __('with a %d-day trial', 'publishpress-cart');
        $txt = apply_filters('ppcart_plan_text_day_free_trial', $txt);
        $terms .= ' ' . sprintf($txt, $trial_days);
    }

    if ($sign_up_fee && floatval($sign_up_fee) > 0) {
        /* translators: %s: sign-up fee amount. */
        $txt = __('and a %s sign-up fee', 'publishpress-cart');
        $txt = apply_filters('ppcart_plan_text_sign_up_fee', $txt);
        $terms .= ' ' . sprintf($txt, ppcart_format_price($sign_up_fee));
    }

    if ($discount && $discount_duration && $discount > 0) {
        $terms .= '<br><strong>' . __('With coupon:', 'publishpress-cart') . ' </strong> ' . ppcart_format_price($plan->price - $discount) . ' ';
        /* translators: 1: discount amount, 2: number of months. */
        $terms .= sprintf(__('(%1$s off for %2$d months)', 'publishpress-cart'), ppcart_format_price($discount), $discount_duration);
    }

    return $terms;
}
