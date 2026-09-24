<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Build item rows from an order created before order-item records existed.
 *
 * @param object $order Legacy order object.
 * @param bool   $qty_col Whether quantities are rendered in their own column.
 * @return array|false
 */
function ppcart_get_items_from_legacy_order($order, $qty_col = true)
{
    $items = [];
    if ($order->plan && $order->main_offer_amt) {
        $arr = [
            'product_id' => $order->product_id,
            'price_id' => $order->option_id,
            'item_type' => 'main',
            'product_name' => ($order->quantity > 1 || $qty_col) ? $order->product_name : sprintf('%s x %s', $order->product_name, $order->quantity),
            'price_name' => $order->item_name,
            'total_amount' => $order->amount,
            'tax_amount' => $order->tax_amount,
            'unit_price' => $order->main_offer_amt / $order->quantity,
            'quantity' => intval($order->quantity),
            'subtotal' => $order->main_offer_amt,
        ];

        if ($order->subscription_id) {
            $sub = new PPCart_Subscription($order->subscription_id);
            if ($order->product_id == $sub->product_id) {
                $sub = $sub->get_data();
                $arr['subscription_id'] = $sub['ID'];
                $arr['sub_summary'] = apply_filters('ppcart_format_subscription_order_detail', $sub['sub_payment_terms_plain'], $sub['sub_payment_terms_plain'], $order->plan, $sub['free_trial_days'], $sub['sign_up_fee'], $sub['sub_discount'], $sub['sub_discount_duration']);
            }
        }

        if ($order->purchase_note) {
            $arr['purchase_note'] = $order->purchase_note;
        }

        $items[] = $arr;

        if (isset($order->custom_prices)) {
            foreach ($order->custom_prices as $id => $price) {
                $field = $order->custom_fields[$id];
                $arr = [
                    'product_id'     => $order->product_id,
                    'price_id'       => $id,
                    'item_type'      => 'line item',
                    'product_name'   => $order->product_name,
                    'price_name'     => (!$qty_col && $field['value'] > 1) ? $field['label'] : sprintf('%s x %s', $field['label'], $field['value']),
                    'unit_price'     => $price / $field['value'],
                    'quantity'       => intval($field['value']),
                    'subtotal'       => $price,
                    'total_amount'   => $price,
                ];
                $items[] = $arr;
            }
        }

        if (!empty($order->order_bumps) && is_array($order->order_bumps)) {
            foreach ($order->order_bumps as $order_bump) {
                $arr = [
                    'product_id'     => $order_bump['id'],
                    'price_id'       => $order_bump['plan']->option_id ?? 'bump',
                    'item_type'      => 'bump',
                    'product_name'   => $order_bump['name'],
                    'price_name'     => $order_bump['plan']->name ?? __('Order Bump', 'publishpress-cart'),
                    'unit_price'     => $order_bump['amount'],
                    'quantity'       => 1,
                    'subtotal'       => $order_bump['amount'],
                    'total_amount'   => $order_bump['amount'],
                ];

                if (isset($order_bump['plan']) && isset($order_bump['plan']->type) && $order_bump['plan']->type == 'recurring' && $order->subscription_id) {
                    $sub = new PPCart_Subscription($order->subscription_id);
                    if ($order_bump['id'] == $sub->product_id) {
                        $arr['subscription_id'] = $order->subscription_id;
                        $sub = $sub->get_data();
                        $arr['sub_summary'] = apply_filters('ppcart_format_subscription_order_detail', $sub['sub_payment_terms_plain'], $sub['sub_payment_terms_plain'], $order->plan, $sub['free_trial_days'], $sub['sign_up_fee'], $sub['sub_discount'], $sub['sub_discount_duration']);
                    }
                }

                if ($order_bump['purchase_note']) {
                    $arr['purchase_note'] = $order_bump['purchase_note'];
                }

                $items[] = $arr;
            }
        } elseif (isset($order->bump_id) && $order->bump_id) {
            $arr = [
                'product_id'     => $order->bump_id,
                'price_id'       => 'bump',
                'item_type'      => 'bump',
                'product_name'   => ppcart_get_public_product_name($order->bump_id),
                'price_name'     => __('Order Bump', 'publishpress-cart'),
                'unit_price'     => $order->bump_amt,
                'quantity'       => 1,
                'subtotal'       => $order->bump_amt,
                'total_amount'   => $order->bump_amt,
            ];
            $items[] = $arr;
        }

        return $items;
    }
    return false;
}

/**
 * Return normalized item rows for an order.
 *
 * @param int|PPCart_Order $order Order ID or order object.
 * @param bool               $qty_col Whether quantities are rendered in their own column.
 * @param bool               $show_hidden Whether bundled/hidden items should be included.
 * @return array
 */
function ppcart_get_order_items($order, $qty_col = true, $show_hidden = false)
{

    if (is_numeric($order)) {
        $order =  new PPCart_Order($order);
    }
    $sub = false;

    if ($order->subscription_id) {
        $sub = new PPCart_Subscription($order->subscription_id);
        $sub = $sub->get_data();
    }

    $itemList = [];

    // add order items
    if ($orderItems = $order->get_items()) {
        foreach ($orderItems as $item) {
            if (!$show_hidden && $item->item_type == 'bundled') {
                continue;
            }

            $item = $item->get_data();

            if (!$qty_col && $item['quantity'] > 1) {
                $item['product_name'] = sprintf('%s x %s', $item['product_name'], $item['quantity']);
            }

            if ($sub && $sub['product_id'] == $item['product_id'] && $sub['option_id'] == $item['price_id']) {
                $item['subscription_id'] = $sub['id'];
                $item['sub_summary'] = apply_filters('ppcart_format_subscription_order_detail', $sub['sub_payment_terms_plain'], $sub['sub_payment_terms_plain'], $order->plan, $sub['free_trial_days'], $sub['sign_up_fee'], $sub['sub_discount'], $sub['sub_discount_duration']);
                $sub = false;
            }

            $itemList[] = $item;
        }
    } elseif ($arr = ppcart_get_items_from_legacy_order($order, $qty_col)) {
        // add main product
        $itemList = array_merge($itemList, $arr);
    }

    return $itemList;
}

/**
 * Build grouped order line items, totals, taxes, discounts, and shipping rows.
 *
 * @param int  $order_id Order ID.
 * @param bool $full Whether child orders and detailed tax values should be included.
 * @param bool $qty_col Whether quantities are rendered in their own column.
 * @return array
 */
function ppcart_get_item_list($order_id, $full = true, $qty_col = false)
{

    $order = apply_filters('ppcart_order', new PPCart_Order($order_id));
    $itemList['items'] = ppcart_get_order_items($order, $qty_col);
    $total = $order->amount;
    $subtotal = $order->pre_tax_amount;

    $shipping_amount = $order->shipping_amount;

    if ($order->coupon && $order->coupon['discount_amount']) {
        $itemList['discounts'][] = [
            /* translators: %s: coupon code. */
            'product_name'  => sprintf(__('Coupon: %s', 'publishpress-cart'), '<span class="ppcart-badge">' . esc_html(strtoupper((string) $order->coupon_id)) . '</span>'),
            'total_amount'  => $order->coupon['discount_amount'],
            'subtotal'      => $order->coupon['discount_amount'],
            'item_type'     => 'discount',
        ];
    }

    // child orders
    if ($full) {
        if ($children = $order->get_children(true)) {
            foreach ($children as $child) {
                $list = ppcart_get_order_items($child);
                $total += $child->amount;
                $subtotal += $child->pre_tax_amount;
                $shipping_amount += $child->shipping_amount;
                $order->shipping_tax += $child->shipping_tax;

                if ($child->coupon && $child->coupon['discount_amount']) {
                    $itemList['discounts'][] = [
                        /* translators: %s: coupon code. */
                        'product_name'  => sprintf(__('Coupon: %s', 'publishpress-cart'), '<span class="ppcart-badge">' . esc_html(strtoupper((string) $child->coupon_id)) . '</span>'),
                        'total_amount'  => $child->coupon['discount_amount'],
                        'subtotal'      => $child->coupon['discount_amount'],
                        'item_type'     => 'discount',
                    ];
                }
                $itemList['items'] = array_merge($itemList['items'], $list);
            }

            if (!is_object($order->tax_data) && is_object($child->tax_data)) {
                $order->tax_data = $child->tax_data;
                $order->tax_desc = $child->tax_desc;
                $order->tax_rate = $child->tax_rate;
            }
        }
    }

    $itemList['subtotal'] = [
        'product_name'  => __('Subtotal', 'publishpress-cart'),
        'total_amount'  => $subtotal,
        'subtotal'      => $subtotal,
        'item_type'     => 'subtotal',
    ];

    if ($shipping_amount) {
        $itemList['shipping'] = [
            'product_name'  => __('Shipping', 'publishpress-cart'),
            'total_amount'  => $shipping_amount,
            'subtotal'      => $shipping_amount,
            'item_type'     => 'shipping',
        ];
        if ($order->shipping_tax) {
            $itemList['shipping']['tax_amount'] = $order->shipping_tax;
        }
    }

    if ($full) {
        $tax_amount = 0;
        foreach ($itemList as $key => $group) {
            if ($key == 'items') {
                foreach ($group as $k => $item) {
                    if (isset($item['tax_amount'])) {
                        $tax_amount += floatval($item['tax_amount']);
                    }
                }
            } elseif (method_exists($order, 'get_items') && isset($group['tax_amount'])) {
                $tax_amount += floatval($group['tax_amount']);
            }
        }
    } else {
        $tax_amount = floatval($order->tax_amount);
    }

    if (is_object($order->tax_data)) {
        $redeem_tax = false;
        if (isset($order->tax_data->redeem_vat) && $order->tax_data->redeem_vat) :
            $redeem_tax = true;
            if ($order->tax_data->type != 'inclusive') :
                $order->tax_amount = 0;
                $order->tax_rate = 0;
            endif;
        endif;

        $order->tax_rate .= '%';

        if ($tax_amount && $order->tax_data->type == 'inclusive') {
            $order->tax_rate .= ' ' . __('incl.', 'publishpress-cart');
        }

        if ($order->tax_data->type == 'inclusive' && $redeem_tax) {
            $title = get_option('_ppcart_vat_reverse_charge', __('VAT Reversal', 'publishpress-cart'));
        } else {
            if ($tax_amount) {
                $title = $order->tax_desc . ' (' . $order->tax_rate . ')';
            } else {
                $title = $order->tax_desc;
            }
        }

        $itemList['tax'] = [
            'product_name'  => $title,
            'total_amount'  => $tax_amount,
            'subtotal'      => $tax_amount,
            'item_type'     => 'tax',
        ];
    }

    $itemList['total'] = [
        'product_name'  => __('Total', 'publishpress-cart'),
        'total_amount'  => $total,
        'subtotal'      => $total,
        'item_type'     => 'total',
    ];

    return $itemList;
}

require_once dirname(__DIR__) . '/order-items/order-details-renderer.php';

add_shortcode('ppcart_order_detail', 'ppcart_order_detail');
/**
 * Render one order field for the order detail shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return mixed|string|void
 */
function ppcart_order_detail($atts)
{

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request values are used for frontend order routing.
    $oto = intval($_GET['ppcart-oto'] ?? 0);
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request values are used for frontend order routing.
    $oto2 = intval($_GET['ppcart-oto-2'] ?? 0);
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request values are used for frontend order routing.
    $step = intval($_GET['step'] ?? 1);

    $order = false;
    $resolved_from_public_order_id = false;

    // main order
    // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Order detail resolver reads request routing parameters.
    if (isset($_POST['ppcart_order_id']) || isset($_GET['ppcart-order']) && !isset($_GET['ppcart-oto'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Order detail resolver reads request routing parameters.
        $order_id = intval($_POST['ppcart_order_id'] ?? $_GET['ppcart-order']);
        $order = new PPCart_Order($order_id);
        $resolved_from_public_order_id = true;
        // downsell
    } elseif ($oto2) {
        $order = new PPCart_Order($oto2);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request value is used for frontend order routing.
    } elseif (isset($_GET['ppcart-oto']) && !$oto && $step > 1) {
        $downsell = $order->get_downsell($step);
        if ($downsell) {
            $order = $downsell;
        }
        // upsell
    } elseif ($oto) {
        $order = new PPCart_Order($oto);
    }

    if (!$order) {
        return;
    }

    if ($resolved_from_public_order_id && ! PPCart_Order::visitor_can_view(absint($order->id))) {
        return;
    }

    $order_info = $order->get_data();

    if (array_key_exists($atts['field'], $order_info)) {
        return wp_kses_post((string) $order_info[$atts['field']]);
    } else {
        $str = '{' . $atts['field'] . '}';
        return wp_kses_post((string) ppcart_personalize($str, $order_info, false, true, true));
    }
}

add_shortcode('ppcart_plan', 'ppcart_plan_detail');
/**
 * Render a payment-plan field for the current or selected product.
 *
 * @param array $atts Shortcode attributes.
 * @return mixed|string|null
 */
function ppcart_plan_detail($atts)
{

    global $post;

    if (!isset($atts['product_id']) || !$atts['product_id']) {
        $atts['product_id'] = $post->ID;
    }

    extract(shortcode_atts([
        'id' => $atts['product_id'],
        'plan_id' => $atts['plan_id'],
        'field' => 'name',
    ], $atts));

    $plan = ppcart_plan($plan_id, $on_sale = 'current', $id);

    if (isset($plan->$field)) {
        if ($field == 'price') {
            return wp_kses_post(ppcart_format_price($plan->price));
        }
        return esc_html((string) $plan->$field);
    } else {
        return '';
    }
}

add_shortcode('ppcart_product', 'ppcart_product_detail');
/**
 * Render a product field for the current or selected product.
 *
 * @param array $atts Shortcode attributes.
 * @return mixed|string|null
 */
function ppcart_product_detail($atts)
{

    global $ppcart_product;

    if (isset($atts['id']) && $atts['id']) {
        $prod = ppcart_setup_product($atts['id']);
    } elseif ($ppcart_product) {
        $prod = $ppcart_product;
    } else {
        return;
    }

    extract(shortcode_atts([
        'field' => 'name',
    ], $atts));

    if ($prod && $field == 'name') {
        return esc_html(ppcart_get_public_product_name($prod->ID));
    } elseif ($prod && $field == 'limit') {
        return esc_html((string) $prod->$field);
    } else {
        return '';
    }
}

/**
 * Return test order data used by legacy template previews.
 *
 * @return array
 */
function ppcart_test_order_data()
{
    $order_info = new PPCart_Order();
    $order_info->id = '{order_id}';
    $order_info->status            = 'paid';
    $order_info->product_name      = '{product_name}';
    $order_info->item_name         = '{item_name}';
    $order_info->plan              = '{plan}';
    $order_info->plan_id           = '{plan_id}';
    $order_info->option_id         = '{option_id}';
    $order_info->amount            = '10.00';
    $order_info->main_offer_amt    = '10.00';
    $order_info->pre_tax_amount    = '{}';
    $order_info->tax_amount        = '1.00';
    $order_info->subscription_id   = 0;
    $order_info->firstname         = '{firstname}';
    $order_info->lastname          = '{lastname}';
    $order_info->first_name        = '{first_name}';
    $order_info->last_name         = '{last_name}';
    $order_info->customer_name     = '{customer_name}';
    $order_info->email             = '{email}';
    $order_info->invoice_link      = '{invoice_link}';
    $order_info->phone             = '{phone}';
    $order_info->country           = '{country}';
    $order_info->address1          = '{address1}';
    $order_info->address2          = '{address2}';
    $order_info->city              = '{city}';
    $order_info->state             = '{state}';
    $order_info->zip               = '{zip}';
    $order_info->tax_desc         = 'Tax';
    $order_info->tax_rate               = 1;
    $order_info->tax_data = (object) ['type' => 'inclusive'];
    $order_info->refund_log = [['refundID' => '{last_refund_id}', 'date' => gmdate('Y-m-d'), 'amount' => '10.00']];
    $order_info = $order_info->get_data();
    $order_info['date']              = '{date}';
    return $order_info;
}

/**
     * Render an order table for notification templates.
     *
     * @param string $type Template context.
     * @param mixed  $order Order object or order-like data.
     * @return void
     */
function ppcart_do_order_table($type, $order)
{

    if ($order['ID'] == '{order_id}') {
        $order = (object) $order;
        $order->id = $order->ID;
        $items = [
          "items" => [
              [
                "product_name" => "{product_name}",
                "quantity" => "1",
                "subtotal" => "10",
                "total_amount" => "10",
                "tax_amount" => 0,
                "unit_price" => 10,
              ],
          ],
          "subtotal" => [
            "product_name" => "Subtotal",
            "subtotal" => "10",
            "total_amount" => "10",
            "item_type" => 'subtotal',
          ],
          "total" => [
            "product_name" => "Total",
            "subtotal" => "10",
            "total_amount" => "10",
            "item_type" => 'total',
          ],
        ];
        $args = ['type' => $type,'items' => $items, 'order' => $order, 'sub' => false];
    } else {
        $order = new PPCart_Order($order['ID']);
        $order = apply_filters('ppcart_order', $order);
        $args = ['type' => $type,'items' => ppcart_get_item_list($order->id), 'order' => $order, 'sub' => $order->get_subscription()];
    }
    ppcart_helper()->renderTemplate('email/order-table', $args);
}


/**
     * Capture the complete order-details markup for use in email personalization.
     *
     * @param array|object $order Order object or order data.
     * @param string        $type Email template context.
     * @return string
     */
function ppcart_get_order_details_html($order, $type = 'confirmation')
{
    if (is_object($order)) {
        $order = (array) $order;
    }

    if (! is_array($order) || empty($order['ID']) || ! function_exists('ppcart_get_order_details_email_markup')) {
        return '';
    }

    $html = ppcart_get_order_details_email_markup();
    if (function_exists('ppcart_personalize')) {
        $html = ppcart_personalize($html, $order, false, false, true);
    }

    return is_string($html) ? $html : '';
}
