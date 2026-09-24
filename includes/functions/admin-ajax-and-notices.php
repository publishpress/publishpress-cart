<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * AJAX handler: update the logged-in customer's profile fields from my-account.
 *
 * @return void
 */
function ppcart_update_user_profile()
{
    if (! is_user_logged_in()) {
        wp_send_json_error(__('Invalid request.', 'publishpress-cart'));
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce value is sanitized and verified immediately in this condition.
    if (!isset($_POST['nonce']) || !ppcart_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ppcart_ajax_nonce')) {
        wp_send_json_error(__('Invalid request.', 'publishpress-cart'));
    }

    $current_user = wp_get_current_user();
    if (! $current_user->ID || ! current_user_can('edit_user', $current_user->ID)) {
        wp_send_json_error(__('Invalid request.', 'publishpress-cart'), 403);
    }

    $response = [];

    // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Data is parsed after nonce verification then field-level validated/sanitized below.
    parse_str(wp_unslash($_POST['form_data'] ?? ''), $data);

    $first_name = isset($data['first_name']) ? sanitize_text_field($data['first_name']) : '';
    $last_name  = isset($data['last_name']) ? sanitize_text_field($data['last_name']) : '';
    $email      = isset($data['email']) ? sanitize_email($data['email']) : '';

    if (empty($first_name)) {
        $response['error'] = __('Please enter first name.', "publishpress-cart");
    }


    if (empty($email)) {
        $response['error'] = __('Please enter a valid email.', "publishpress-cart");
    } elseif (!is_email($email)) {
        $response['error'] = __('Enter a valid email', "publishpress-cart");
    }

    if (!empty($data['password'])) {
        if ($data['password'] != $data['new_password']) {
            $response['error'] = __('Password and confirm password should match.', "publishpress-cart");
        }
    }

    if (isset($response['error'])) {
        wp_send_json($response);
    }

    if (!empty($data['_ppcart_phone'])) {
        ppcart_update_user_meta($current_user->ID, 'phone', sanitize_text_field($data['_ppcart_phone']));
    }

    $address = [
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country',
    ];

    $subs = $plans = false;
    if (!empty($data['ppcart-all-subscription-address'])) {
        $subs = ppcart_get_user_subscriptions($current_user->ID, $status = 'active', $type = null);
        $plans = ppcart_get_user_subscriptions($current_user->ID, $status = 'active', $type = 'installment');
    }

    foreach ($address as $field) {
        if (!empty($data['_ppcart_' . $field])) {
            $val = sanitize_text_field($data['_ppcart_' . $field]);

            if ($subs) {
                foreach ($subs as $sub) {
                    ppcart_update_post_meta($sub->id, $field, $val);
                }
            }

            if ($plans) {
                foreach ($plans as $plan) {
                    ppcart_update_post_meta($plan->id, $field, $val);
                }
            }

            $field = ($field == 'address1') ? 'address_1' : $field;
            $field = ($field == 'address2') ? 'address_2' : $field;

            ppcart_update_user_meta($current_user->ID, $field, $val);
        } elseif ($field == 'address2') {
            ppcart_delete_user_meta($current_user->ID, 'address_2');
            if ($subs) {
                foreach ($subs as $sub) {
                    ppcart_delete_post_meta($sub->id, $field);
                }
            }

            if ($plans) {
                foreach ($plans as $plan) {
                    ppcart_delete_post_meta($plan->id, $field);
                }
            }
        }
    }

    ppcart_update_user_meta($current_user->ID, 'address', 1);

    wp_update_user([
        'ID' => $current_user->ID,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'user_email' => $email,
    ]);

    if (!empty($data['password'])) {
        // Change password.
        wp_set_password($data['password'], $current_user->ID);
    }

    wp_send_json(['success' => true,'message' => esc_html__('Profile details have been saved.', 'publishpress-cart')]);
}

function ppcart_get_webhook_url($payment_slug)
{
    $webhook_url_type = apply_filters('ppcart_webhook_url_type', '');
    if ($webhook_url_type == 'plain') {
        $url = get_site_url() . '/?ppcart-api=' . $payment_slug;
    } else {
        $url = get_site_url() . '/ppcart-webhook/' . $payment_slug;
    }
    return $url;
}

function ppcart_is_checkout_context()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Request flags are used only for routing checks.
    if (ppcart_is_product_post_type(get_post_type()) || isset($_POST['ppcart_purchase_amount']) || isset($_GET['ppcart-plan']) || (isset($_GET['ppcart-order']) && isset($_GET['step']))) {
        return true;
    }
    return false;
}

/**
 * Get Customers
 */
function ppcart_get_customers($customer_id = 0)
{

    global $wpdb;
    if ($customer_id > 0) {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ppcart_sql_in_meta_keys() and ppcart_sql_in_post_types() return prepared placeholder lists.
        $query = $wpdb->prepare("SELECT {$wpdb->prefix}posts.ID,{$wpdb->prefix}postmeta.meta_value FROM {$wpdb->prefix}posts INNER JOIN {$wpdb->prefix}postmeta ON ( {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id ) WHERE 1=1 AND ( {$wpdb->prefix}postmeta.meta_key IN (" . ppcart_sql_in_meta_keys('user_account') . ") AND {$wpdb->prefix}postmeta.meta_value = %d ) AND {$wpdb->prefix}posts.post_type IN (" . ppcart_sql_in_post_types('order') . ") AND (({$wpdb->prefix}posts.post_status <> 'trash' AND {$wpdb->prefix}posts.post_status <> 'auto-draft')) ORDER BY `{$wpdb->prefix}posts`.`post_date` DESC", $customer_id);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Legacy customer lookup query uses prepared customer filter.
        $result = $wpdb->get_results($query);
    } else {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Legacy customer listing query uses prepared helper-generated IN() lists for canonical keys/types.
        $result = $wpdb->get_results("SELECT {$wpdb->prefix}posts.ID,{$wpdb->prefix}postmeta.meta_value FROM {$wpdb->prefix}posts INNER JOIN {$wpdb->prefix}postmeta ON ( {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id ) WHERE 1=1 AND ( {$wpdb->prefix}postmeta.meta_key IN (" . ppcart_sql_in_meta_keys('user_account') . ") ) AND {$wpdb->prefix}posts.post_type IN (" . ppcart_sql_in_post_types('order') . ") AND (({$wpdb->prefix}posts.post_status <> 'trash' AND {$wpdb->prefix}posts.post_status <> 'auto-draft')) ORDER BY `{$wpdb->prefix}posts`.`post_date` DESC");
    }

    $customers = [];

    $result = is_array($result) ? $result : [];

    foreach ($result as $key => $post) {
        $userdata = get_userdata($post->meta_value);
        if (! $userdata || empty($userdata->user_email)) {
            continue;
        }
        $user_email = $userdata->user_email;
        $status = PPCart_Status_Labels::edit_select_value(PPCart_Status_Labels::logical_from_post($post->ID));
        $refundedarray = [];
        if (ppcart_get_post_meta($post->ID, 'payment_status', true) == 'refunded') {
            $refund_logs_entrie = ppcart_get_post_meta($post->ID, 'refund_log', true);
            $total_amount = ppcart_get_post_meta($post->ID, 'amount', true);
            if (is_array($refund_logs_entrie)) {
                $refund_amount_values = array_map(
                    'floatval',
                    array_column($refund_logs_entrie, 'amount')
                );
                $refund_amount = array_sum($refund_amount_values);
                $total_amount = ppcart_get_post_meta($post->ID, 'amount', true) - $refund_amount;
                $total_amount = $total_amount;
                $refundedarray[] = $refund_amount;
            }
        } else {
            if ($status == 'paid') {
                $total_amount =  ppcart_get_post_meta($post->ID, 'amount', true);
            }
        }

        if ($status == 'paid') {
            $customers[$user_email][] = ['id' => $post->ID, 'total_amount' => $total_amount, ];
        } else {
            $customers[$user_email][] = ['id' => $post->ID, 'total_amount' => 0, ];
        }
    }

    return $customers;
}

function ppcart_check_currency_setting()
{
    $thousand_sep = get_option('_ppcart_thousand_separator');
    $formatted = get_option('ppcart_price_formatted');
    if ($thousand_sep && $thousand_sep != ',' && $formatted != 'yes') {
        $scheduled_time = wp_next_scheduled('ppcart_run_price_formatting', []);

        if (!$scheduled_time) {
            $scheduled_time = wp_next_scheduled('ppcart_run_price_formatting', []);
        }

        if (!$scheduled_time) {
            add_action('admin_notices', 'ppcart_db_update_notice');
            wp_schedule_single_event(time(), 'ppcart_run_price_formatting', [], true);
        } elseif (time() > ($scheduled_time + 60 * 60)) {
            add_action('admin_notices', 'ppcart_db_update_manually_notice');
        }
    }

    if ($formatted == 'yes') {
        add_action('admin_notices', 'ppcart_db_update_complete_notice');
    }
}

add_action('ppcart_run_price_formatting', 'ppcart_run_price_formatting');

function ppcart_db_update_manually_notice()
{
    $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    $url = add_query_arg('price_format', 'yes', $request_uri);
    $url = wp_nonce_url($url, 'ppcart_price_format', '_ppcart_price_format_nonce');
    ?>
    <div class="notice notice-warning">
        <p><?php
            echo wp_kses(
                sprintf(
                    /* translators: %s: manual update URL. */
                    __('<b>PublishPress Cart Data Updater</b> - The database update is taking longer than expected. Click <a href="%s">here</a> to run it manually.', 'publishpress-cart'),
                    esc_url($url)
                ),
                [
                    'b' => [],
                    'a' => [
                        'href' => [],
                    ],
                ]
            );
    ?></p>
    </div>
    <?php
}

function ppcart_db_update_complete_notice()
{
    if (get_option('ppcart_dismissed_ppcart_price_formatted', false)) {
        return;
    }
    ?>
    <div class="notice notice-success notice-ppcart-db-update is-dismissible" data-notice="ppcart_price_formatted">
        <p><?php echo wp_kses(__('<b>PublishPress Cart Data Updater</b> - The database update process is complete.', 'publishpress-cart'), [ 'b' => [] ]); ?></p>
    </div>
    <?php
}

function ppcart_db_update_notice()
{
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo wp_kses(__('<b>PublishPress Cart Data Updater</b> - Version 2.6 database update is in progress.', 'publishpress-cart'), [ 'b' => [] ]); ?></p>
    </div>
    <?php
}

function ppcart_run_price_formatting()
{
    require_once dirname(__DIR__) . '/class-ppcart-price-format.php';
    $priceFormat = new PPCart_Price_Format();
}

if (is_admin()) {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- This upgrade trigger reads the nonce value before verifying it below.
    $price_format_requested = isset($_GET['price_format']) && 'yes' === sanitize_text_field(wp_unslash($_GET['price_format']));
    $price_format_nonce = isset($_GET['_ppcart_price_format_nonce']) ? sanitize_text_field(wp_unslash($_GET['_ppcart_price_format_nonce'])) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    if (
        $price_format_requested
        && 'yes' !== get_option('ppcart_price_formatted')
        && current_user_can('manage_options')
        && '' !== $price_format_nonce
        && ppcart_verify_nonce($price_format_nonce, 'ppcart_price_format')
    ) {
        require_once dirname(__DIR__) . '/class-ppcart-price-format.php';
        $priceFormat = new PPCart_Price_Format();
        // delete scheduled db update since we just ran it manually
        wp_clear_scheduled_hook('ppcart_run_price_formatting', []);
        wp_clear_scheduled_hook('ppcart_run_price_formatting', []);
    }

    ppcart_check_currency_setting();

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin notice display uses query flag only.
    if (isset($_GET['format_err'])) {
        add_action('admin_notices', 'ppcart_price_format_error');
    }
}

function ppcart_price_format_error()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin notice reads a display-only query parameter.
    $format_error = isset($_GET['format_err']) ? sanitize_text_field(wp_unslash($_GET['format_err'])) : '';
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php
            /* translators: %s: invalid price format. */
            echo esc_html(sprintf(__('Error: Invalid price format - %s. Price format should match price format setting.', 'publishpress-cart'), $format_error));
    ?></p>
    </div>
    <?php
}

function ppcart_resend_purchase_confirmation_email_ajax()
{

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce value is sanitized and verified immediately in this condition.
    if (!isset($_POST['nonce']) || !ppcart_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ppcart_resend_purchase_confirmation_email')) {
        wp_send_json_error(__('Invalid request.', 'publishpress-cart'));
    }

    if (! current_user_can('manage_options') && ! ppcart_user_can('manage_orders')) {
        wp_send_json_error(__('Unauthorized.', 'publishpress-cart'), 403);
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Order ID is consumed only after ppcart_verify_nonce() succeeds above.
    $order_id = isset($_POST['order_id']) ? absint(wp_unslash($_POST['order_id'])) : 0;
    $order_info = ppcart_setup_order($order_id);

    if (!$order_info) {
        wp_send_json_error('Invalid order ID.');
    }

    // Convert to array if it's an object
    if (is_object($order_info)) {
        $order_info = (array) $order_info;
    }

    // Trigger email sending
    ppcart_notification_send('paid', $order_info);

    // Add a new log entry
    $current_user = wp_get_current_user();
    /* translators: %s: user login. */
    $log_entry = sprintf(__('Purchase confirmation email resent by %s', 'publishpress-cart'), $current_user->user_login);
    ppcart_log_entry($order_id, $log_entry);

    wp_send_json_success('Purchase confirmation email resent successfully.');
}
add_action('wp_ajax_ppcart_resend_purchase_confirmation_email', 'ppcart_resend_purchase_confirmation_email_ajax');

add_action('wp_ajax_ppcart_dismissed_notice_handler', 'ppcart_ajax_notice_handler');
function ppcart_ajax_notice_handler()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce value is sanitized and verified immediately in this condition.
    if (!isset($_POST['nonce']) || !ppcart_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ppcart_ajax_nonce')) {
        wp_send_json_error(__('Invalid request.', 'publishpress-cart'));
    }

    if (! current_user_can('manage_options') && ! ppcart_user_can('manager_option')) {
        wp_send_json_error(__('You are not allowed to dismiss this notice.', 'publishpress-cart'), 403);
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Notice type is consumed only after ppcart_verify_nonce() succeeds above.
    $type = sanitize_key(wp_unslash($_POST['type'] ?? ''));
    if ('' === $type) {
        wp_send_json_error(__('Invalid request.', 'publishpress-cart'), 400);
    }

    update_option('ppcart_dismissed_' . $type, true);
    wp_send_json_success();
}

/**
     * Returns the top-level PublishPress Cart admin menu slug.
     *
     * @return string
     */
function ppcart_admin_menu_slug()
{
    return class_exists('PPCart_Admin_Screens')
        ? PPCart_Admin_Screens::menu_slug()
        : 'ppcart';
}


add_action('admin_enqueue_scripts', 'ppcart_alert_print_scripts');
function ppcart_alert_print_scripts()
{
    $handle = 'ppcart-admin-notices';

    if (! wp_script_is($handle, 'registered')) {
        wp_register_script($handle, false, [ 'jquery' ], defined('PPCART_VERSION') ? PPCART_VERSION : null, false);
    }

    wp_enqueue_script($handle);

    wp_add_inline_script(
        $handle,
        'jQuery(function($){$(document).on("click",".notice-ppcart-db-update .notice-dismiss",function(){var type=$(this).closest(".notice-ppcart-db-update").data("notice");$.ajax(ajaxurl,{type:"POST",data:{action:"ppcart_dismissed_notice_handler",type:type,nonce:' . wp_json_encode(wp_create_nonce('ppcart_ajax_nonce')) . '}});});});'
    );
}
