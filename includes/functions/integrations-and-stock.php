<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Fire integration hooks for an order status change.
 *
 * @param string $status     Order or subscription status.
 * @param array  $order_info Order data.
 * @return void
 */
function ppcart_trigger_integrations($status, $order_info)
{

    $event_type = 'order';

    if ($status == 'lead') {
        do_action('ppcart_order_lead', $status, $order_info, 'main');
    } else {
        // setup order object
        if (is_numeric($order_info) || (is_array($order_info) && !isset($order_info['option_id']))) {
            // reset array to just the ID
            if (is_array($order_info)) {
                $order_info = $order_info['ID'];
            }

            if (ppcart_is_order_post_type(get_post_type($order_info))) {
                $order = new PPCart_Order($order_info);
                $order = apply_filters('ppcart_order', $order);
            } else {
                $order = new PPCart_Subscription($order_info);
            }

            $order_info = $order->get_data();
        } else {
            // setup $order object, but use $order_info as sent to this function, don't reset
            $order = new PPCart_Order($order_info['ID']);
        }

        // set event type
        if (isset($order_info['renewal_order']) && $order_info['renewal_order']) {
            $event_type = 'renewal';
        } elseif (ppcart_is_subscription_post_type(get_post_type($order_info['ID']))) {
            $event_type = 'subscription';
        }

        // set order type
        if (isset($order_info['us_parent'])) {
            $order_info['order_type'] = 'upsell';
        } elseif (isset($order_info['ds_parent'])) {
            $order_info['order_type'] = 'downsell';
        }

        // also check subscription (if exists) to see if this is an upsell (we shouldn't need this anymore)
        if ((!isset($order_info['order_type']) || $order_info['order_type'] == 'main') && isset($order_info['transaction_id']) && isset($order_info['subscription_id'])) {
            $sub_id = $order_info['subscription_id'];
            if (ppcart_get_post_meta($sub_id, 'us_parent', true)) {
                $order_info['order_type'] = 'upsell';
            } elseif (ppcart_get_post_meta($sub_id, 'ds_parent', true)) {
                $order_info['order_type'] = 'downsell';
            }
        }

        if ($order_info['pay_method'] == 'cod' && $status == 'pending-payment') {
            $status = 'pending';
        }

        $order_type = 'main';

        // get correct action
        switch ($status) {
            case 'pending':
                $action = 'ppcart_order_pending';
                break;
            case 'paid':
                if ($event_type == 'renewal') {
                    $action = 'ppcart_renewal_payment';
                    $status = 'renewal';
                } else {
                    $action = 'ppcart_order_complete';
                    do_action('ppcart_order_before_complete_integrations', $order_info['ID'], $order_info);
                }
                break;
            case 'completed':
                if ($event_type == 'subscription') {
                    $action = 'ppcart_subscription_completed';
                } else {
                    $action = 'ppcart_order_marked_complete';
                }
                break;
            case 'trialing':
                $action = 'ppcart_subscription_trialing';
                break;
            case 'active':
                $action = 'ppcart_subscription_active';
                break;
            case 'canceled':
                $action = 'ppcart_subscription_canceled';
                break;
            case 'paused':
                $action = 'ppcart_subscription_paused';
                break;
            case 'past_due':
                $action = 'ppcart_subscription_past_due';
                break;
            case 'trialing':
                $action = 'ppcart_subscription_trialing';
                break;
            case 'renewal':
                $action = 'ppcart_renewal_payment';
                break;
            case 'failed' && $event_type == 'renewal':
                $action = 'ppcart_renewal_failed';
                break;
            case 'uncollectible' && $event_type == 'renewal':
                $action = 'ppcart_renewal_uncollectible';
                break;
            case 'refunded':
                $action = 'ppcart_order_refunded';
                break;
            default:
                $action = false;
                break;
        }

        if ($action) {
            $renewal_types = ['ppcart_renewal_payment','ppcart_renewal_failed','ppcart_renewal_uncollectible'];

            // set order amount
            if (!isset($order_info['amount'])) {
                $order_info['amount'] = ppcart_get_post_meta($order_info['ID'], 'amount', true);
            }

            // set order type
            if (isset($order_info['order_type']) && !in_array($action, $renewal_types)) {
                $order_type = $order_info['order_type'];
            }

            do_action($action, $status, $order_info, $order_type);

            do_action('ppcart_order_after_primary_integration_action', $action, $status, $order_info, $order_type, $event_type, $order, $renewal_types);
        }
    }

    // hook for after integrations have run (emails, webhooks, etc.)
    do_action('ppcart_run_after_integrations', $status, $order_info, $event_type);
}

function ppcart_consent_services()
{
    return apply_filters('ppcart_show_optin_checkbox_services', ['activecampaign', 'mailchimp', 'mailpoet', 'sendfox', 'fluentcrm']);
}

function ppcart_matched_plan($order_data, $plan_ids, $order_ids = [])
{

    $matched_plan = false;
    $order_ids = (array) $order_ids;

    if (is_object($order_data)) {
        $order_data = $order_data->get_data();
    }

    if ($order_items = ppcart_get_order_items($order_data['ID'])) {
        $price_ids = wp_list_pluck($order_items, 'price_id');
        $item_types = wp_list_pluck($order_items, 'item_type');
        $order_ids = array_merge($order_ids, $price_ids, $item_types);
        $matched_plan = ppcart_match_plan($plan_ids, $order_ids);
    } else {
        $order_ids[] = $order_data['plan_id']; // Stripe ID, remove in 3.0
        $order_ids[] = $order_data['option_id'];
        $matched_plan = ppcart_match_plan($plan_ids, $order_ids);
    }

    return $matched_plan;
}

function ppcart_match_plan($plan_ids, $order_price_ids)
{
    $matched_plan = false;
    if (empty($plan_ids) || in_array('', $plan_ids)) {
        return true;
    } else {
        foreach ($order_price_ids as $id) {
            if (in_array($id, $plan_ids) || in_array($id . '_sale', $plan_ids)) {
                return true;
            }
        }
    }
    return $matched_plan;
}

// Notification on Low or Out of Stock
add_action('ppcart_after_update_stock', 'ppcart_check_and_send_stock_notifications', 10, 1);

function ppcart_check_and_send_stock_notifications($product_id)
{
    if (ppcart_get_post_meta($product_id, 'manage_stock', true) == '1') {
        $stock_limit = intval(ppcart_get_post_meta($product_id, 'limit', true));
        $product_name = get_the_title($product_id);

        // Make the low stock threshold filterable
        $low_stock_threshold = apply_filters('ppcart_low_stock_threshold', 5, $product_id);

        $admin_email = get_option('ppcart_admin_email');
        $subject = '';
        $message = '';

        if ($stock_limit === $low_stock_threshold) {
            /* translators: %s: product name. */
            $subject = sprintf(__('Low Stock Alert: %s', 'publishpress-cart'), $product_name);
            /* translators: 1: product name, 2: product ID, 3: current stock level. */
            $message = sprintf(__('The stock for product "%1$s" (ID: %2$d) is running low. Current stock: %3$d', 'publishpress-cart'), $product_name, $product_id, $low_stock_threshold);
        } elseif ($stock_limit === 0) {
            /* translators: %s: product name. */
            $subject = sprintf(__('Out of Stock Alert: %s', 'publishpress-cart'), $product_name);
            /* translators: 1: product name, 2: product ID. */
            $message = sprintf(__('The product "%1$s" (ID: %2$d) is now out of stock.', 'publishpress-cart'), $product_name, $product_id);
        }

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail -- Transactional stock alerts are expected plugin behavior.
        wp_mail($admin_email, $subject, $message, $headers);
    }
}

/**
 * Run configured CRM/email integrations for a product order.
 *
 * @param int    $ppcart_product_id Product post ID.
 * @param array  $order             Order data.
 * @param string $trigger           Integration trigger (purchased, pending, …).
 * @return void
 */
function ppcart_do_integrations($ppcart_product_id, $order, $trigger = 'purchased')
{

    $customerEmail = $order['email'];
    $phone = $order['phone'];
    $first_name = $order['first_name'];
    $last_name = $order['last_name'];
    $order_type = $order['order_type'] ?? 'main';

    if ($trigger != 'lead') {
        $order_id = $order['id'];
        $plan_id = $order['plan_id'];
        $option_id = $order['option_id'] ?? ppcart_get_post_meta($order_id, 'option_id', true);

        if (!isset($order['amount'])) {
            $order['amount'] = ppcart_get_post_meta($order['id'], 'amount', true);
        }
    }

    $integrations = ppcart_get_post_meta($ppcart_product_id, 'integrations', true); //get integration meta mailchimp

    if ($integrations) {
        foreach ($integrations as $ind => $intg) {
            $ppcart_services = $intg['services'] ?? ""; //mailchimp/converkit

            // do we need consent to run this integration?
            if (ppcart_get_post_meta($ppcart_product_id, 'show_optin_cb', true)) {
                $consent = (strtolower($order['consent']) == 'yes') ? true : false;
                $consent_services = ppcart_consent_services();
                if (in_array($intg['services'], $consent_services) && isset($intg['require_optin']) && !$consent) {
                    continue;
                }
            }

            $ppcart_service_trigger = isset($intg['service_trigger']) ? (array) $intg['service_trigger'] : []; //purchase or refund
            $ppcart_service_action = $intg['service_action'] ?? "";    //subscribe or unsubscribe
            $ppcart_plan_ids = (isset($intg['int_plan']) && $trigger != 'lead') ? (array) $intg['int_plan'] : [];  //payment plan

            $ppcart_mail_list = $intg['mail_list'] ?? "";  //mailchimp list ID
            $ppcart_mail_tags = $intg['mail_tags'] ?? "";  //mailchimp list ID
            $ppcart_mail_groups = $intg['mail_groups'] ?? "";  //mailchimp list ID
            $activecampaign_lists = $intg['activecampaign_lists'] ?? "";    //activecampaign list ID
            $activecampaign_tags = $intg['activecampaign_tags'] ?? "";  //activecampaign tags

            $member_vault_course_id = $intg['member_vault_course_id'] ?? ""; //member_vault_course_id
            $membervault_action = $intg['membervault_action'] ?? "add_user";

            // match plan
            $matched_plan = false;
            if (empty($ppcart_plan_ids) || in_array('', $ppcart_plan_ids)) {
                $matched_plan = true;
            } elseif (in_array($plan_id, $ppcart_plan_ids)) {
                $matched_plan = true;
            } elseif (in_array($option_id, $ppcart_plan_ids) || in_array($option_id . '_sale', $ppcart_plan_ids)) {
                $matched_plan = true;
            } elseif (in_array($order_type, $ppcart_plan_ids)) {
                $matched_plan = true;
            }

            if (in_array($trigger, $ppcart_service_trigger) && $matched_plan) {
                $intg['service_trigger'] = $trigger;

                //check if mailchimp list id exist
                if ($ppcart_services == "mailchimp" && $ppcart_mail_list != "") {
                    ppcart_add_remove_mailchimp_subscriber($order_id, $ppcart_services, $ppcart_service_action, $ppcart_mail_list, $ppcart_mail_tags, $ppcart_mail_groups, $customerEmail, $phone, $first_name, $last_name, $intg, $order);
                }

                //check if activecampaign list id exist
                if ($ppcart_services == "activecampaign") {
                    $fieldmap = $intg['activecampaign_field_map'];
                    ppcart_add_remove_activecampaign_subscriber($order_id, $ppcart_services, $ppcart_service_action, $activecampaign_lists, $activecampaign_tags, $ppcart_mail_groups = '', $customerEmail, $phone, $first_name, $last_name, $fieldmap);
                }

                //check if sendfox
                if ($ppcart_services == "sendfox") {
                    $sendfox_list = $intg['sendfox_list'];
                    ppcart_add_remove_sendfox_subscriber($order_id, $ppcart_services, $ppcart_service_action, $sendfox_list, $customerEmail, $first_name, $last_name);
                }

                //check if mailpoet
                if ($ppcart_services == "mailpoet") {
                    $mailpoet_list = $intg['mailpoet_list'];
                    ppcart_add_remove_mailpoet_subscriber($order_id, $ppcart_services, $ppcart_service_action, $mailpoet_list, $customerEmail, $first_name, $last_name);
                }

                // create WP user
                if ($ppcart_services == "create user") {
                    $order['user_id'] = ppcart_create_user($order_id, $customerEmail, $first_name, $last_name, $intg['user_role']);
                }

                // update WP user
                if ($ppcart_services == "update user") {
                    $order['user_id'] = ppcart_create_user($order_id, $customerEmail, $first_name, $last_name, $intg['user_role'], null, $intg['previous_user_role']);
                }

                //check if membervault
                if ($ppcart_services == "membervault" && !empty($member_vault_course_id)) {
                    ppcart_add_remove_membervault_subscriber($order_id, $ppcart_services, $membervault_action, $member_vault_course_id, $customerEmail, $phone = '', $first_name, $last_name);
                }

                // do 3rd party integrations
                do_action('ppcart_' . $ppcart_services . '_integrations', $intg, $ppcart_product_id, $order);
            }
        }

        do_action('ppcart_' . $trigger . '_integrations', $ppcart_product_id, $order);
    }
}

/**
 * Build a webhook payload from an order object.
 *
 * @param object $order          Order-like object.
 * @param string $type           Event type.
 * @param bool   $price_in_cents Whether amounts should be integer cents.
 * @return array
 */
function ppcart_webhook_order_body($order, $type = '', $price_in_cents = false)
{

    global $ppcart_currency;
    $keys = [
        'amount',
        'order_amount',
        'tax_amount',
        'pre_tax_amount',
        'invoice_total',
        'invoice_subtotal',
        'subscription_amount',
        'total_amount',
        'shipping_amount',
        'discount',
        'unit_price',
        'subtotal',
        'discount_amount',
        'sign_up_fee',
    ];

    $invoices = false;

    if (is_numeric($order)) {
        if (ppcart_is_subscription_post_type(get_post_type($order))) {
            $order = new PPCart_Subscription($order);
            $invoices = $order->orders();
        } else {
            $order = new PPCart_Order($order);
        }

        $order = $order->get_data();
    }

    $body = [];

    if ($type) {
        $body['trigger']           = $type;
    }

    if ($type != 'lead') {
        $body['id']                = $order['id'];
    }

    $body['customer_firstname']    = $order['firstname'];
    $body['customer_lastname']     = $order['lastname'];
    $body['customer_name']         = $order['firstname'] . ' ' . $order['lastname'];
    $body['customer_email']        = $order['email'];
    $body['customer_phone']        = $order['phone'];
    $body['customer_address']      = $order['address1'];
    $body['customer_address_2']    = $order['address2'];
    $body['customer_city']         = $order['city'];
    $body['customer_state']        = $order['state'];
    $body['customer_zip']          = $order['zip'];
    $body['customer_country']      = $order['country'];
    $body['product_id']            = $order['product_id'];
    $body['product_name']          = $order['product_name'];
    $body['gateway']               = $order['pay_method'];
    $body['custom_fields']         = $order['custom_fields'];
    $body['shipping_amount']       = $order['shipping_amount'] ?? '';
    $body['ip_address']            = $order['ip_address'];
    $body['date']                  = gmdate("Y-m-d", strtotime('now'));
    $body['date_time']             = get_gmt_from_date(gmdate("Y-m-d H:i:s", strtotime('now')));

    if ($type != 'lead') {
        $body['payment_plan']      = $order['item_name'];
        $body['payment_plan_id']   = $order['option_id'];
        $body['order_amount']      = (float) $order['amount'];
        $body['tax_amount']        = (float) $order['tax_amount'];
        $body['pre_tax_amount']    = (isset($order['pre_tax_amount'])) ? (float) $order['pre_tax_amount'] : '';
        $body['tax_rate']          = $order['tax_rate'];
        $body['tax_type']          = $order['tax_type'];
        $body['tax_desc']          = $order['tax_desc'];
        $body['vat_number']        = $order['vat_number'];
        $body['date']              = get_the_date('Y-m-d', $order['id']);
        $body['date_time']         = get_gmt_from_date(get_the_date('Y-m-d H:i:s', $order['id']));

        $body['coupon'] = $order['coupon_id'];
        $body['currency'] = $order['currency'];

        if (isset($order['coupon']['amount'])) {
            $body['discount'] = $order['coupon']['amount'];
        }

        if (!isset($order['sub_amount'])) {
            // orders
            $body['transaction_id']     = $order['transaction_id'];

            if (isset($order['subscription_id'])) {
                $body['subscription_id'] = $order['subscription_id'];
            }

            $body['status']            = $order['status'];
            $body['order_status']      = $order['status']; // remove
            $body['order_id']          = $order['id'];

            if ($items = ppcart_get_item_list($order['id'], false, true)) {
                $body['items'] = $items['items'];

                if ($price_in_cents) {
                    foreach ($body['items'] as $i => $item) {
                        foreach ($item as $k => $v) {
                            if (in_array($k, $keys)) {
                                $body['items'][$i][$k] = ppcart_price_in_cents($v, $ppcart_currency);
                            }
                        }
                    }
                }
            }

            if (ppcart_get_post_meta($order['product_id'], 'show_optin_cb', true)) {
                $body['signup_consent'] = $order['consent'] ?? 'No' ;
            }

            $body['invoice_total']     = (float) $order['invoice_total'];
            $body['invoice_subtotal']  = (float) $order['invoice_subtotal'];
        } else {
            // subscriptions
            $body['sub_amount']      = (float) $order['sub_amount'];
            $body['subscription_id']          = $order['subscription_id'];
            $body['status']                   = $order['status'];
            $body['amount']                   = $order['sub_amount'];
            if ($order['sub_installments'] > 1) {
                $body['installments'] = $order['sub_installments'];
            }
            $body['interval'] = $order['sub_interval'];
            $body['frequency'] = $order['sub_frequency'];
            $body['trial_days'] = $order['free_trial_days'];
            $body['sign_up_fee'] = $order['sign_up_fee'];

            if ($order['sub_next_bill_date']) {
                $body['next_bill_date'] = ppcart_maybe_format_date($order['sub_next_bill_date'], 'Y-m-d');
                $body['next_bill_date_time'] = ppcart_maybe_format_date($order['sub_next_bill_date'], 'Y-m-d H:i:s');
            }

            if ($order['sub_end_date']) {
                $body["end_date"] = ppcart_maybe_format_date($order['sub_end_date'], 'Y-m-d');
                $body["end_date_time"] = ppcart_maybe_format_date($order['sub_end_date'], 'Y-m-d H:i:s');
            }

            if ($order['cancel_date']) {
                $body["cancel_date"] = ppcart_maybe_format_date($order['cancel_date'], 'Y-m-d');
                $body["cancel_date_time"] = ppcart_maybe_format_date($order['cancel_date'], 'Y-m-d H:i:s');
            }
        }
    }

    $body['website_url']           = get_site_url();
    $body['order_url']             = $order['page_url'];

    $body = array_filter($body, function ($v) {
        return !is_null($v) && $v !== '';
    });

    if ($price_in_cents) {
        foreach ($body as $k => $v) {
            if (in_array($k, $keys)) {
                $body[$k] = ppcart_price_in_cents($v, $ppcart_currency);
            }
        }
    }

    if ($invoices) {
        $invoice_arr = [];
        foreach ($invoices as $order) {
            $invoice_arr[] = [
                'id' => $order->id,
                'status' => $order->status,
                'order_amount' => ($price_in_cents) ? ppcart_price_in_cents($order->amount, $ppcart_currency) : $order->amount,
                'date' => get_the_date('Y-m-d', $order->id),
                'date_time' => get_the_date("Y-m-d H:i:s", $order->id),
            ];
        }
        $body['invoices'] = $invoice_arr;
    }

    return apply_filters('ppcart_webhook_order_data', $body);
}
