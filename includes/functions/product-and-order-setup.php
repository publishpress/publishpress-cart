<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Hydrate a product post into the checkout product object.
 *
 * @param int $id Product post ID.
 * @return object|void
 */
function ppcart_setup_product($id)
{
    $post_types = (array) apply_filters('ppcart_setup_product_post_type', ppcart_live_post_type('product'));
    if (!$id || !in_array(get_post_type($id), $post_types)) {
        return;
    }

    $default_atts = [
        'ID' => 0,
        'pay_options' => [],
        'form_action' => '',
        'thanks_url' => '',
        'ob_cb_label' => '',
        'button_text' => __('Order Now', 'publishpress-cart'),
    ];

    $arr  = ['ID' => $id];

    $meta = get_post_custom($id);
    if (! is_array($meta)) {
        $meta = [];
    }

    /**
     * Fill setup fields from leftover custom meta before canonical keys.
     * cart-compat owns leftover hydration; Free only reads `_ppcart_*`.
     *
     * @param array                $arr  Setup bag so far.
     * @param array<string, mixed> $meta get_post_custom() map.
     * @param int                  $id   Product post ID.
     */
    $arr = apply_filters('ppcart_setup_product_from_meta', $arr, $meta, $id);

    foreach ($meta as $k => $v) {
        if (strpos($k, '_ppcart_') === 0) {
            $v = array_shift($meta[$k]);
            $k = substr($k, 8);
            $arr[$k] = maybe_unserialize($v);
        }
    }

    if (! is_array($arr['pay_options'] ?? null)) {
        $arr['pay_options'] = [];
    }

    if (is_array($arr['pay_options']) && count($arr['pay_options']) > 0) {
        $prices = [];
        foreach ($arr['pay_options'] as $option) {
            if (is_array($option) && isset($option['price']) && is_numeric($option['price'])) {
                $prices[] = $option['price'];
            }
        }
        $arr['price'] = $prices !== []
            ? apply_filters('ppcart_product_price', min($prices))
            : 0;
    } else {
        $arr['price'] = 0;
    }

    $arr['single_plan'] = (is_array($arr['pay_options']) && count($arr['pay_options']) > 1) ? false : true;
    $arr['confirmation'] ??= 'message';

    if ($arr['confirmation'] == 'page') {
        $arr['thanks_url'] = get_permalink($arr['confirmation_page']);
    } else {
        $arr['thanks_url'] = get_permalink($id);
    }

    // 2-step option historically lived under leftover display meta; canonical is _ppcart_display.
    if (ppcart_get_post_meta($id, 'show_2_step', true)) {
        ppcart_update_post_meta($id, 'display', 'two_step');
        ppcart_delete_post_meta($id, 'show_2_step');
        $arr['display'] = 'two_step';
    }

    $arr = apply_filters('ppcart_setup_product_display_mode', $arr, $id);

    $arr['form_action'] = $arr['thanks_url'];
    $arr['upsell'] = false; // backwards compatibility
    $arr['upsell_path'] = false;
    $arr = apply_filters('ppcart_setup_product_upsell_path', $arr, $id);

    if ($arr['confirmation'] == 'redirect') {
        $arr['redirect_url'] = $arr['redirect'];
    }

    $arr['button_icon'] ??= false;
    $arr['step1_button_icon'] ??= false;

    $arr['button_icon'] = ($arr['button_icon'] && $arr['button_icon'] == 'none') ? false : $arr['button_icon'];
    if ($arr['button_icon']) {
        $arr['button_icon'] = ppcart_get_font_awesome_icon_html($arr['button_icon']);

        if (! $arr['button_icon']) {
            $arr['button_icon'] = false;
        }
    }

    $arr['step1_button_icon'] = ($arr['step1_button_icon'] && $arr['step1_button_icon'] == 'none') ? false : $arr['step1_button_icon'];
    if ($arr['step1_button_icon']) {
        $arr['step1_button_icon'] = ppcart_get_font_awesome_icon_html($arr['step1_button_icon']);

        if (! $arr['step1_button_icon']) {
            $arr['step1_button_icon'] = false;
        }
    }

    $arr['product_taxable'] ??= 'tax';

    if ($arr['product_taxable'] == 'non_tax') {
        $arr['product_taxable'] = false;
    } else {
        if (get_option('_ppcart_tax_enable', false) && $arr['product_taxable'] == 'tax') {
            $arr['tax_type'] = get_option('_ppcart_tax_type', 'inclusive_tax');
            $arr['product_taxable'] = true;
            $arr['price_show_with_tax'] = get_option('_ppcart_price_show_with_tax', 'exclude_tax');
        } else {
            $arr['product_taxable'] = false;
        }
    }

    $terms = get_option('_ppcart_terms_url');
    if (isset($arr['terms_setting']) && !empty($arr['terms_setting'])) {
        if ($arr['terms_setting'] == 'off') {
            $arr['terms_url'] = false;
        } else {
            $arr['terms_url'] ??= $terms;
        }
    } else {
        $arr['terms_url'] = $terms;
    }

    $privacy = get_option('_ppcart_privacy_url');
    if (isset($arr['privacy_setting']) && !empty($arr['privacy_setting'])) {
        if ($arr['privacy_setting'] == 'off') {
            $arr['privacy_url'] = false;
        } else {
            $arr['privacy_url'] ??= $privacy;
        }
    } else {
        $arr['privacy_url'] = $privacy;
    }

    $arr['twostep_heading_1'] = (isset($arr['twostep_heading_1'])) ? $arr['twostep_heading_1'] : __('Get it Now', 'publishpress-cart');
    $arr['twostep_heading_2'] = (isset($arr['twostep_heading_2'])) ? $arr['twostep_heading_2'] : __('Payment', 'publishpress-cart');
    $arr['twostep_subhead_1'] = (isset($arr['twostep_subhead_1'])) ? $arr['twostep_subhead_1'] : __('Your Info', 'publishpress-cart');
    $arr['twostep_subhead_2'] = (isset($arr['twostep_subhead_2'])) ? $arr['twostep_subhead_2'] : __('of your order', 'publishpress-cart');

    $arr['show_optin_cb'] = (isset($arr['show_optin_cb'])) ? $arr['show_optin_cb'] : false;

    // backwards compatibility
    if (isset($arr['default_fields']) && empty($arr['default_fields'])) {
        unset($arr['default_fields']);
    }

    $arr = wp_parse_args($arr, $default_atts);
    if (! is_array($arr['pay_options'])) {
        $arr['pay_options'] = [];
    }
    if (! isset($arr['button_text']) || '' === $arr['button_text']) {
        $arr['button_text'] = __('Order Now', 'publishpress-cart');
    }

    $arr = apply_filters('ppcart_product', $arr);

    return (object)$arr;
}

function ppcart_get_tax_data($item_tax_rate)
{
    $tax_rate = get_option('_ppcart_tax_rates', []);
    $tax_rate = apply_filters('ppcart_tax_rates', $tax_rate);
    if (! empty($tax_rate) && ! empty($tax_rate['_ppcart_tax_rate_slug']) && is_array($tax_rate['_ppcart_tax_rate_slug'])) {
        $fields = ['_ppcart_tax_rate_title','_ppcart_tax_rate_slug','_ppcart_tax_rate'];
        $count = count($tax_rate['_ppcart_tax_rate_slug']);
        for ($i = 0; $i < $count; $i++) {
            if ($tax_rate['_ppcart_tax_rate_slug'][$i] == $item_tax_rate) {
                $inner_val = [];
                foreach ($fields as $field) :
                    $field_title = str_replace('_ppcart_', '', $field);
                    $inner_val[$field_title] = $tax_rate[$field][$i];
                endforeach;
            }
        }
        return $inner_val;
    }
}

function ppcart_remove_repeater_blank($value)
{
    if (is_array($value)) {
        foreach ($value as $key => $val) :
            if (empty($val)) {
                unset($value[$key]);
            }
        endforeach;
    }
    return $value;
}

/**
 * Expand order_child meta rows into order arrays and bump invoice_total.
 *
 * @param array $rows          Raw meta values for order_child.
 * @param float $invoice_total Running invoice total (by reference).
 * @return array<int, array<string, mixed>>
 */
function ppcart_expand_order_child_meta_rows($rows, &$invoice_total)
{
    $orders = [];

    foreach ((array) $rows as $order) {
        $order = maybe_unserialize($order);
        if (! is_array($order) || empty($order['id'])) {
            continue;
        }

        $order['product_id'] = ppcart_get_post_meta($order['id'], 'product_id', true);
        $order['amount']     = ppcart_get_post_meta($order['id'], 'amount', true);
        $invoice_total      += (float) $order['amount'];

        if (! isset($order['product_name'])) {
            $order['product_name'] = ppcart_get_public_product_name($order['product_id']);
            ppcart_update_post_meta($order['id'], 'product_name', $order['product_name']);
        }

        // see if there are any other orders created under this subscription
        if (ppcart_is_subscription_post_type(get_post_type($order['id']))) {
            $args = [
                'post_type'   => ppcart_query_post_types('order'),
                'post_status' => 'any',
                'order'       => 'asc',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Lookup by subscription meta is required for upgrade-path sync.
                'meta_query'  => [
                    ppcart_meta_query_for('subscription_id', $order['id']),
                ],
            ];
            $inv         = get_posts($args);
            $order['id'] = $inv[0]->ID ?? false;
        }

        $orders[] = $order;
    }

    return $orders;
}

/**
 * Hydrate an order or subscription post into checkout order data.
 *
 * @param int  $id    Order or subscription post ID.
 * @param bool $array True to return an array instead of an object.
 * @return object|array|void
 */
function ppcart_setup_order($id, $array = false)
{

    if (! ppcart_is_order_post_type(get_post_type($id)) && ! ppcart_is_subscription_post_type(get_post_type($id))) {
        return;
    }

    $arr  = [
        'ID' => $id,
        'invoice_total' => 0,
        'date' => get_the_date('', $id),
        'status' => 'pending',
    ];
    $meta = get_post_custom($id);

    if (is_array($meta)) {
        /**
         * Fill setup fields from leftover custom meta before canonical keys.
         * cart-compat owns leftover hydration (including leftover order_child).
         *
         * @param array                $arr  Setup bag so far.
         * @param array<string, mixed> $meta get_post_custom() map.
         * @param int                  $id   Order/subscription post ID.
         */
        $arr = apply_filters('ppcart_setup_order_from_meta', $arr, $meta, $id);

        foreach ($meta as $k => $v) {
            if (strpos($k, '_ppcart_') !== 0) {
                continue;
            }

            if (ppcart_is_meta_field_id($k, 'order_child')) {
                $arr['order_child'] = ppcart_expand_order_child_meta_rows($v, $arr['invoice_total']);
                continue;
            }

            $v = array_shift($meta[$k]);
            $k = substr($k, 8);
            $arr[$k] = maybe_unserialize($v);
        }
    }

    $arr['product_id'] ??= '';


    // add Cart plan to order info
    $option_id = $arr['option_id'] ?? $arr['plan_id'] ?? '';
    $arr['plan'] = ppcart_plan($option_id, isset($arr['on_sale']), $arr['product_id']);
    if (ppcart_is_subscription_post_type(get_post_type($arr['ID'])) && $arr['plan'] && $arr['plan']->type == 'recurring') {
        if ((!isset($arr['sub_end_date']) && $arr['sub_installments'] > 1) || (isset($arr['sub_end_date']) && $arr['sub_end_date'] == '1970-01-01')) {
            if ($arr['sub_installments'] > 1) {
                $duration = $arr['sub_installments'] * $arr['sub_frequency'];
                $cancel_at = $duration . ' ' . $arr['sub_interval'];

                if ($arr['sub_trial_days']) {
                    $cancel_at .= " + " . $arr['sub_trial_days'] . __(" day", 'publishpress-cart');
                }
                $arr['sub_end_date'] = gmdate("Y-m-d", strtotime($arr['date'] . ' + ' . $cancel_at));
                ppcart_update_post_meta($arr['ID'], 'sub_end_date', $arr['sub_end_date']);
            } else {
                unset($arr['sub_end_date']);
                ppcart_delete_post_meta($arr['ID'], 'sub_end_date', $arr['sub_end_date']);
            }
        }
    }

    if (ppcart_is_subscription_post_type(get_post_type($arr['ID'])) && !isset($arr['subscription_id'])) {
        $arr['subscription_id'] = ppcart_get_subscription_txn_id($arr['ID']);
    } elseif (ppcart_is_order_post_type(get_post_type($arr['ID'])) && !isset($arr['transaction_id'])) {
        $arr['transaction_id'] = ppcart_get_transaction_id($arr['ID']);
    }

    if (isset($arr['firstname']) && isset($arr['lastname'])) {
        $arr['customer_name'] = $arr['firstname'] . ' ' . $arr['lastname'];
    }

    if (!isset($arr['product_name'])) {
        $arr['product_name'] = ppcart_get_public_product_name($arr['product_id']);
        ppcart_update_post_meta($arr['ID'], 'product_name', $arr['product_name']);
    }

    if (isset($arr['sub_end_date']) && isset($arr['product_replaced'])) {
        $arr['product_name'] = ppcart_get_public_product_name($arr['bump_id']);
        $arr['amount'] = $arr['bump_amt'];
        $option_id = $arr['bump_option_id'];
        unset($arr['item_name'], $arr['bump_id']);
    } else {
        $option_id = $arr['option_id'] ?? $arr['plan_id'] ?? '';
    }

    $arr['product_name_plan'] = $arr['product_name'];
    if (isset($arr['item_name'])) {
        $arr['product_name_plan'] .= ' - ' . $arr['item_name'];
    }

    // add Cart plan to order info
    $arr['plan'] = ppcart_plan($option_id, isset($arr['on_sale']), $arr['product_id']);

    if (ppcart_is_subscription_post_type(get_post_type($arr['ID'])) && $arr['plan'] && $arr['plan']->type == 'recurring') {
        if (isset($arr['sub_end_date']) && $arr['sub_end_date'] == '1970-01-01') {
            $duration = $arr['sub_installments'] * $arr['sub_frequency'];
            $cancel_at = $duration . ' ' . $arr['sub_interval'];

            if ($arr['sub_trial_days']) {
                $cancel_at .= " + " . $arr['sub_trial_days'] . __(" day", 'publishpress-cart');
            }
            $arr['sub_end_date'] = gmdate("Y-m-d", strtotime($arr['date'] . ' + ' . $cancel_at));
            ppcart_update_post_meta($arr['ID'], 'sub_end_date', $arr['sub_end_date']);
        }
    }

    if ($arr['status'] == 'initiated' || $arr['status'] == 'pending payment') {
        $arr['status'] = 'pending';
    }

    $arr['amount'] = (isset($arr['amount']) && $arr['amount']) ? (float) $arr['amount'] : 0;
    $arr['invoice_total'] = ($arr['invoice_total']) ? (float) $arr['invoice_total'] : 0;
    $arr['main_offer_amt'] = $arr['amount']; // amount paid for main offer including discount
    $arr['invoice_total'] += (float) $arr['amount']; // total amount paid including child orders and discount
    $arr['invoice_subtotal'] = $arr['invoice_total'];
    if (isset($arr['discount_details'])) {
        $arr['invoice_subtotal'] += (float) $arr['discount_details']['discount_amt'];
    }

    if (isset($arr['tax_amount'])) {
        $arr['main_offer_amt'] -= $arr['tax_amount'];
    }

    if (!empty($arr['bump_amt']) && !empty($arr['bump_id']) && empty($arr['order_bumps'])) {
        $arr['order_bumps'] = [];
        if (is_array($arr['bump_id'])) {
            for ($j = 0; $j < count($arr['bump_id']); $j++) :
                $arr['order_bumps'][] = ['id' => $arr['bump_id'][$j],'amount' => $arr['bump_amt'][$j],'name' => ppcart_get_public_product_name($arr['bump_id'][$j])];
            endfor;
        } else {
            $arr['order_bumps'][] = ['id' => $arr['bump_id'],'amount' => $arr['bump_amt'],'name' => ppcart_get_public_product_name($arr['bump_id'])];
        }
    }

    if (isset($arr['plan_price']) && isset($arr['discount_details']['discount_amt'])) {
        $arr['main_offer_amt'] = floatval($arr['plan_price']) - floatval($arr['discount_details']['discount_amt']);
    }

    if (!empty($arr['order_bumps']) && is_array($arr['order_bumps'])) :
        foreach ($arr['order_bumps'] as $order_bump) :
            $arr['main_offer_amt'] -= floatval($order_bump['amount']);
        endforeach;
    endif;

    if (isset($arr['custom_prices'])) {
        $fields = $arr['custom_fields'];
        foreach ($arr['custom_prices'] as $k => $v) {
            $custom[$k]['label'] = $fields[$k]['label'];
            $custom[$k]['qty'] = $fields[$k]['value'];
            $custom[$k]['price'] = $v;
            if (!isset($arr['plan_price'])) {
                $arr['main_offer_amt'] -= floatval($v);
            }
        }
        $arr['custom_prices'] = $custom;
    }

    if (!isset($arr['plan_price'])) {
        $arr['plan_price'] = $arr['main_offer_amt'];
        ppcart_update_post_meta($arr['ID'], 'plan_price', $arr['plan_price']);
    }

    if (ppcart_is_subscription_post_type(get_post_type($id))) {
        $arr['sub_payment'] = '<span class="ppcart-Price-amount amount">' . ppcart_format_price($arr['sub_amount']) . '</span> / ';

        // payment without html around currency symbol
        $arr['sub_payment_plain'] = ppcart_format_price($arr['sub_amount'], false) . ' / ';

        if ($arr['sub_frequency'] > 1) {
            $arr['sub_payment'] .= ($arr['sub_frequency'] . ' ' . ppcart_pluralize_interval($arr['sub_interval']));
            $arr['sub_payment_plain'] .= ($arr['sub_frequency'] . ' ' . ppcart_pluralize_interval($arr['sub_interval']));
        } else {
            $arr['sub_payment'] .= ppcart_singularize_interval($arr['sub_interval']);
            $arr['sub_payment_plain'] .= ppcart_singularize_interval($arr['sub_interval']);
        }

        $arr['sub_payment_terms'] = $arr['sub_payment'];
        if ($arr['sub_installments'] > 1) {
            $arr['sub_payment_terms'] .= ' x ' . $arr['sub_installments'];
        }

        // terms without html around currency symbol
        $arr['sub_payment_terms_plain'] = $arr['sub_payment_plain'];
        if ($arr['sub_installments'] > 1) {
            $arr['sub_payment_terms_plain'] .= ' x ' . $arr['sub_installments'];
        }
    }

    if (!$array) {
        return (object) $arr;
    } else {
        return $arr;
    }
}

function ppcart_format_order_address($order)
{
    $address = false;
    if (isset($order->address1) || isset($order->city) || isset($order->state) || isset($order->zip) || isset($order->country)) {
        $address = '';
        if ($order->address1) {
            $address .= esc_html($order->address1) . '<br/>';
        }
        if ($order->address2) {
            $address .= esc_html($order->address2) . '<br/>';
        }
        if ($order->city || $order->state || $order->zip) {
            $str = '';
            if ($order->city) {
                $str .= esc_html($order->city);
            }
            if ($order->state) {
                if ($str != '') {
                    $str .= ', ';
                }
                $str .= esc_html($order->state);
            }
            if ($order->zip) {
                if ($str != '') {
                    $str .= ' ';
                }
                $str .= esc_html($order->zip);
            }
            if ($str != '') {
                $str .= '<br>';
            }
            $address .= $str;
            if ($order->country) {
                $address .= esc_html($order->country) . '<br/>';
            }
        }
    }
    return $address;
}

function ppcart_pluralize_interval($int)
{
    switch ($int) {
        case 'day':
            $return = __('days', 'publishpress-cart');
            break;
        case 'week':
            $return = __('weeks', 'publishpress-cart');
            break;
        case 'month':
            $return = __('months', 'publishpress-cart');
            break;
        case 'year':
            $return = __('years', 'publishpress-cart');
            break;
        default:
            $return = false;
    }
    return $return;
}

function ppcart_singularize_interval($int)
{
    switch ($int) {
        case 'day':
            $return = __('day', 'publishpress-cart');
            break;
        case 'week':
            $return = __('week', 'publishpress-cart');
            break;
        case 'month':
            $return = __('month', 'publishpress-cart');
            break;
        case 'year':
            $return = __('year', 'publishpress-cart');
            break;
        default:
            $return = $int;
    }
    return $return;
}
