<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb, $ppcart_currency;

// leave if not on the post edit screen
if (! ppcart_is_order_post_type($post->post_type) || !is_admin()) {
    return;
}

if (
    ! isset($_POST['_wpnonce']) ||
    ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'update-post_' . $post_id)
) {
    return;
}

if (! current_user_can('edit_post', $post_id)) {
    return;
}

if (! isset($_POST['original_publish'])) {
    return;
}

if (wp_is_post_revision($post_id) || $post->post_status == 'auto-draft') {
    return;
}

remove_action('save_post_' . $post->post_type, [$this,'save_post_order'], 1);

$saving_user = wp_get_current_user();
$ppcart_status = isset($_POST['_ppcart_status']) ? sanitize_text_field(wp_unslash($_POST['_ppcart_status'])) : '';
$order_status = ($ppcart_status == 'pending') ? 'pending-payment' : $ppcart_status; // "pending" order status removed for gateway charges

// Check if this is a new post
$log_entries = ppcart_order_log($post_id);

if (empty($log_entries)) {
    $first_name = isset($_POST['_ppcart_firstname']) ? sanitize_text_field(wp_unslash($_POST['_ppcart_firstname'])) : '';
    $last_name = isset($_POST['_ppcart_lastname']) ? sanitize_text_field(wp_unslash($_POST['_ppcart_lastname'])) : '';
    $email = isset($_POST['_ppcart_email']) ? sanitize_email(wp_unslash($_POST['_ppcart_email'])) : '';
    $vat_number = isset($_POST['_ppcart_vat_number']) ? sanitize_text_field(wp_unslash($_POST['_ppcart_vat_number'])) : '';

    $_POST['first_name'] = $first_name;
    $_POST['last_name'] = $last_name;
    $_POST['email'] = $email;
    $_POST['vat-number'] = $vat_number;

    $fields = ['phone','country','address1','address2','city','state','zip','company'];
    foreach ($fields as $field) {
        $field_key = '_ppcart_' . $field;
        $posted_field = isset($_POST[ $field_key ]) ? sanitize_text_field(wp_unslash($_POST[ $field_key ])) : '';

        if ('' !== $posted_field) {
            $_POST[$field] = $posted_field;
        }
    }

    $ppcart_product_id = isset($_POST['_ppcart_product_id']) ? absint(wp_unslash($_POST['_ppcart_product_id'])) : 0;
    $ppcart_product_option = isset($_POST['_ppcart_item_name']) ? sanitize_text_field(wp_unslash($_POST['_ppcart_item_name'])) : '';

    $_POST['ppcart_product_id'] = $ppcart_product_id;
    $_POST['ppcart_product_option'] = $ppcart_product_option;

    // flag as on sale if plan id ends with "_sale"
    if (substr($ppcart_product_option, -(strlen('_sale'))) === '_sale') {
        $ppcart_product_option = substr($ppcart_product_option, 0, -strlen('_sale'));
        $_POST['ppcart_product_option'] = $ppcart_product_option;
        $_POST['on-sale'] = 1;
    }

    $cart_order = new PPCart_Order();
    $cart_order->load_from_post();
    $cart_order->set_invoice_number();

    $cart_order = apply_filters('ppcart_after_order_load_from_post', $cart_order);
    $cart_order->id = $post_id;
} else {
    $cart_order = new PPCart_Order($post_id);
    $first_name = isset($_POST['_ppcart_firstname']) ? sanitize_text_field(wp_unslash($_POST['_ppcart_firstname'])) : '';
    $last_name = isset($_POST['_ppcart_lastname']) ? sanitize_text_field(wp_unslash($_POST['_ppcart_lastname'])) : '';
    $email = isset($_POST['_ppcart_email']) ? sanitize_email(wp_unslash($_POST['_ppcart_email'])) : '';
    $vat_number = isset($_POST['_ppcart_vat_number']) ? sanitize_text_field(wp_unslash($_POST['_ppcart_vat_number'])) : '';

    $cart_order->first_name = $cart_order->firstname = $first_name;
    $cart_order->last_name = $cart_order->lastname = $last_name;
    $cart_order->email = $email;
    $cart_order->vat_number = $vat_number;

    $fields = ['phone','country','address1','address2','city','state','zip','company'];
    foreach ($fields as $field) {
        $field_key = '_ppcart_' . $field;
        $posted_field = isset($_POST[ $field_key ]) ? sanitize_text_field(wp_unslash($_POST[ $field_key ])) : '';

        if ('' !== $posted_field) {
            $cart_order->$field = $posted_field;
        } else {
            $cart_order->$field = null;
        }
    }
}

if ('' !== $ppcart_status) {
    $cart_order->payment_status = $order_status;
    $cart_order->status = $order_status;
}
$cart_order->user_account = 0;

if (isset($_POST['_ppcart_user_account'])) {
    $ppcart_user_account = absint(wp_unslash($_POST['_ppcart_user_account']));

    if ($ppcart_user_account) {
        $cart_order->user_account = $ppcart_user_account;
    }
}

$cart_order->store();

if (empty($log_entries)) {
    /* translators: %1$s is replaced with "string" */
    $log_entry = sprintf(__('New order manually created by %s', 'publishpress-cart'), $saving_user->user_login) ;
    ppcart_log_entry($post_id, sanitize_text_field($log_entry));
} elseif (!empty($order_status)) {
    /* translators: %s: username. */
    $log_entry = sprintf(__('Order updated by %s', 'publishpress-cart'), $saving_user->user_login) ;
    ppcart_log_entry($post_id, $log_entry);
}
add_action('save_post_' . $post->post_type, [$this,'save_post_order'], 1, 2);
