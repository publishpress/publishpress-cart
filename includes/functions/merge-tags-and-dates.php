<?php

if (! defined('ABSPATH')) {
    exit;
}


function ppcart_merge_tag_list()
{
    return [
        'site_name'             => __('Site Name', 'publishpress-cart'),
        'customer_name'         => __('Customer Name', 'publishpress-cart'),
        'customer_phone'        => __('Customer Phone', 'publishpress-cart'),
        'customer_firstname'    => __('Customer First Name', 'publishpress-cart'),
        'customer_lastname'     => __('Customer Last Name', 'publishpress-cart'),
        'customer_email'        => __('Customer Email', 'publishpress-cart'),
        'customer_address'      => __('Customer Address', 'publishpress-cart'),
        'customer_address1'     => __('Customer Address Line 1', 'publishpress-cart'),
        'customer_address2'     => __('Customer Address Line 2', 'publishpress-cart'),
        'customer_city'         => __('Customer City', 'publishpress-cart'),
        'customer_state'        => __('Customer State', 'publishpress-cart'),
        'customer_zip'          => __('Customer Zip', 'publishpress-cart'),
        'customer_country'      => __('Customer Country', 'publishpress-cart'),
        'invoice_link'          => __('Invoice Link', 'publishpress-cart'),
        'login'                 => __('My Account/Login URL', 'publishpress-cart'),
        'password'              => __('Customer Password', 'publishpress-cart'),
        'username'              => __('Customer Username', 'publishpress-cart'),
        'product_name'          => __('Main Product Name', 'publishpress-cart'),
        'product_amount'        => __('Main Product Amount', 'publishpress-cart'),
        'plan_name'             => __('Plan Name', 'publishpress-cart'),
        'order_id'              => __('Order ID', 'publishpress-cart'),
        'order_date'            => __('Order Date', 'publishpress-cart'),
        'order_list'            => __('Order List', 'publishpress-cart'),
        'order_details'         => __('Order Details Table', 'publishpress-cart'),
        'order_inline_list'     => __('Inline Order List', 'publishpress-cart'),
        'order_amount'          => __('Order Amount', 'publishpress-cart'),
        'product_list'          => __('Product List', 'publishpress-cart'),
        'product_inline_list'   => __('Inline Product List', 'publishpress-cart'),
        'custom_fields'         => __('Custom Fields', 'publishpress-cart'),
        'refund_log'            => __('Refund Log', 'publishpress-cart'),
        'last_refund_id'        => __('Last Refund ID', 'publishpress-cart'),
        'last_refund_amount'    => __('Last Refund Amount', 'publishpress-cart'),
        'last_refund_date'      => __('Last Refund Date', 'publishpress-cart'),
        'next_bill_date'        => __('Next Bill Date', 'publishpress-cart'),
    ];
}

function ppcart_merge_tag_select()
{
    ?>
    <select class="ppcart-insert-merge-tag">
        <option value=''><?php esc_html_e('Insert Personalization Tag', 'publishpress-cart'); ?></option>
        <?php foreach (ppcart_merge_tag_list() as $tag => $description) : ?>
        <option value="<?php echo esc_attr($tag); ?>"><?php echo esc_html($description); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Replace `{merge_tag}` tokens in a string with order/customer values.
 *
 * @param string         $str                   Template string.
 * @param array          $order_info            Order data.
 * @param callable|false $filter                Optional callback applied to replacement values.
 * @param bool           $include_order_details Whether to expand `{order_details}`.
 * @param bool           $escape_text           When true and `$filter` is unused, HTML-escape text tags. Markup tags stay pre-escaped HTML.
 * @return string|void
 */
function ppcart_personalize($str, $order_info, $filter = false, $include_order_details = true, $escape_text = false)
{
    if (!$str) {
        return;
    }

    $keys = ['firstname', 'lastname', 'email', 'phone'];
    foreach ($keys as $key) {
        if (!isset($order_info[$key]) || !$order_info[$key]) {
            $order_info[$key] = ' ';
        }
    }

    $replacements = [
        'fname' => $order_info['firstname'], // deprecated
        'lname' => $order_info['lastname'], // deprecated
        'name' => $order_info['firstname'] . ' ' . $order_info['lastname'], // deprecated
        'email' => $order_info['email'], // deprecated
        'phone' => $order_info['phone'], // deprecated

        'coupon_code' => $order_info['coupon_id'] ?? '',
        'invoice_link' => $order_info['invoice_link_html'] ?? '',
        'customer_name' => $order_info['firstname'] . ' ' . $order_info['lastname'],
        'site_name' => get_bloginfo('name'),

        'publishpress_cart' => '<a href="' . esc_url('https://publishpress.com/publishpress-cart/') . '" target="_blank" rel="noreferrer noopener">' . esc_html('PublishPress Cart') . '</a>',
    ];

    $customer_fields = ['customer_phone','customer_firstname','customer_lastname','customer_email','customer_address1','customer_address2','customer_city','customer_state','customer_zip','customer_country'];
    foreach ($customer_fields as $customer_field) {
        $field = str_replace('customer_', '', $customer_field);
        $replacements[$customer_field] = $order_info[$field] ?? "";
    }

    if ($login = get_option('_ppcart_myaccount_page_id')) {
        $replacements['login'] = get_permalink($login);
    }

    if (isset($order_info['password'])) {
        $replacements['password'] = $order_info['password'];
    }

    if (isset($order_info['custom_fields'])) {
        $cf_data = '';
        foreach ($order_info['custom_fields'] as $k => $v) {
            if (is_array($v['value'])) {
                $value = [];
                for ($i = 0; $i < count($v['value']); $i++) {
                    $value[] = (isset($v['value_label'][$i])) ? $v['value_label'][$i] : $v['value'][$i];
                }
                $value = implode(', ', $value);
            } else {
                $value = (isset($v['value_label'])) ? $v['value_label'] : $v['value'];
            }

            $value = esc_html((string) $value);
            $replacements['custom_' . $k] = $value;
            $cf_data .= sprintf('%s: %s<br><br>', esc_html((string) $v['label']), $value);
        }
        $replacements['custom_fields'] = $cf_data;
    }

    if (isset($order_info['username'])) {
        $replacements['username'] = $order_info['username'];
    }

    if (isset($order_info['ID'])) {
        $replacements['product_name'] = $order_info['product_name'] ?? ppcart_get_public_product_name($order_info['product_id']);
        $replacements['plan_name'] = $order_info['item_name'] ?? ppcart_get_post_meta($order_info['ID'], 'item_name', true);

        $product_name = ($replacements['plan_name'] != '')
        ? sprintf(
            /* translators: 1: product name, 2: plan name. */
            _x('%1$s - %2$s', 'product and plan', 'publishpress-cart'),
            $replacements['product_name'],
            $replacements['plan_name']
        )
        : $replacements['product_name'];

        // with pay plan name
        $replacements['order_list'] = esc_html($product_name);
        $replacements['order_inline_list'] = esc_html($product_name);

        // without pay plan name
        $replacements['product_list'] = esc_html($replacements['product_name']);
        $replacements['product_inline_list'] = esc_html($replacements['product_name']);

        $replacements['order_id'] = $order_info['ID'];
        $replacements['order_date'] = $order_info['date'] ?? '';
        $replacements['product_amount'] = ppcart_format_price($order_info['amount']);
        $replacements['order_amount'] = ppcart_format_price($order_info['amount']);

        $replacements['quantity'] = $order_info['quantity'];

        $replacements['customer_address'] = ppcart_order_address($order_info['ID']);

        if (isset($order_info['sub_next_bill_date'])) {
            if (!is_numeric($order_info['sub_next_bill_date'])) {
                $order_info['sub_next_bill_date'] = strtotime($order_info['sub_next_bill_date']);
            }
            $replacements['next_bill_date'] = date_i18n(get_option('date_format'), $order_info['sub_next_bill_date']);
        }

        $replacements['last_refund_id'] = $replacements['last_refund_amount'] = $replacements['last_refund_date'] = $replacements['refund_log'] = '';
        if (isset($order_info['refund_log']) && is_countable($order_info['refund_log'])) {
            $i = 1;
            foreach ($order_info['refund_log'] as $log) {
                /* translators: 1: refund amount, 2: refund date. */
                $replacements['refund_log'] .= sprintf(__('%1$s refunded on %2$s', 'publishpress-cart'), ppcart_format_price($log['amount']), esc_html(ppcart_maybe_format_date($log['date'])));
                if ($i < count($order_info['refund_log'])) {
                    $replacements['refund_log'] .= '<br>';
                } else {
                    $replacements['last_refund_id'] = $log['refundID'];
                    $replacements['last_refund_amount'] = ppcart_format_price($log['amount']);
                    $replacements['last_refund_date'] = ppcart_maybe_format_date($log['date']);
                }
                $i++;
            }
        }

        if (!isset($replacements['username']) && $user_id = ppcart_get_post_meta($order_info['ID'], 'user_account', true)) {
            $user = get_user_by('id', $user_id);
            $replacements['username'] = $user->user_login;
        }

        if ($order_info['bump_id'] = ppcart_get_post_meta($order_info['ID'], 'bump_id', true)) {
            $replacements['order_list'] .= '<br>' . esc_html(ppcart_get_public_product_name($order_info['bump_id']));
            $replacements['product_list'] .= '<br>' . esc_html(ppcart_get_public_product_name($order_info['bump_id']));
            /* translators: 1: primary product name, 2: bump product name. */
            $replacements['order_inline_list'] = esc_html(sprintf(__('%1$s, %2$s', 'publishpress-cart'), $product_name, ppcart_get_public_product_name($order_info['bump_id'])));
            /* translators: 1: primary product name, 2: bump product name. */
            $replacements['product_inline_list'] = esc_html(sprintf(__('%1$s, %2$s', 'publishpress-cart'), $product_name, ppcart_get_public_product_name($order_info['bump_id'])));
        }

        if ($order_info['order_bumps'] = ppcart_get_post_meta($order_info['ID'], 'order_bumps', true)) {
            $products = [$product_name];
            $total_bump_amt = 0;

            foreach ($order_info['order_bumps'] as $bump) {
                $replacements['order_list'] .= '<br>' . esc_html($bump['name']);
                $replacements['product_list'] .= '<br>' . esc_html($bump['name']);
                $products[] = $bump['name'];
                $total_bump_amt += floatval($bump['amount']);
            }
            $replacements['order_inline_list'] = esc_html(implode(', ', $products));
            $replacements['product_inline_list'] = esc_html(implode(', ', $products));
            $replacements['bump_amount'] = ppcart_format_price($total_bump_amt);
            if (!isset($order_info['order_type']) || $order_info['order_type'] != 'bump') {
                $replacements['product_amount'] = ppcart_format_price($order_info['amount'] - floatval($total_bump_amt));
            }
        }

        if (isset($order_info['bump_amt']) && is_countable($order_info['bump_amt'])) {
            $total_bump_amt = 0;
            $all_bump_amt = ppcart_get_post_meta($order_info['ID'], 'bump_amt', true);
            if (is_countable($all_bump_amt)) {
                for ($j = 0; $j < count($all_bump_amt); $j++) {
                    $total_bump_amt = floatval($total_bump_amt) + floatval($all_bump_amt[$j]);
                }
            }
            $replacements['bump_amount'] = ppcart_format_price(floatval($total_bump_amt));
            if ($order_info['order_type'] != 'bump') {
                $replacements['product_amount'] = ppcart_format_price($order_info['amount'] - floatval($total_bump_amt));
            }
        }
    }

    if ($include_order_details && false !== strpos((string) $str, '{order_details}') && function_exists('ppcart_get_order_details_html')) {
        $order_details = ppcart_get_order_details_html($order_info);
        if ($order_details) {
            // Resolve any individual tags used by the rendered table without recursively expanding {order_details}.
            $replacements['order_details'] = ppcart_personalize($order_details, $order_info, false, false, $escape_text);
        }
    }

    $replacements = apply_filters('ppcart_personalize_replacements', $replacements, $order_info);

    $html_keys = [
        'invoice_link' => true,
        'publishpress_cart' => true,
        'custom_fields' => true,
        'order_list' => true,
        'product_list' => true,
        'order_inline_list' => true,
        'product_inline_list' => true,
        'order_details' => true,
        'refund_log' => true,
        'customer_address' => true,
        'product_amount' => true,
        'order_amount' => true,
        'bump_amount' => true,
        'last_refund_amount' => true,
    ];

    $search = $replace = [];
    foreach ($replacements as $k => $v) {
        if ($v) {
            $search[] = '{' . $k . '}';
            if ($filter) {
                $replace[] = $filter($v);
            } elseif ($escape_text && ! isset($html_keys[ $k ]) && 0 !== strpos($k, 'custom_')) {
                $replace[] = esc_html((string) $v);
            } else {
                $replace[] = $v;
            }
        }
    }
    return str_replace($search, $replace, $str);
}

function ppcart_localize_dt($date = 'now')
{
    return new DateTime($date, wp_timezone());
}

function ppcart_is_cart_closed($prod_id = false)
{

    if ($prod_id) {
        $ppcart_product = ppcart_setup_product($prod_id);
    } else {
        // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.VariableRedeclaration -- Reads shared product context from global scope when no product ID is passed.
        global $ppcart_product;
    }

    $cart_closed = false;

    // Check if cart is opened or closed

    $now = ppcart_localize_dt();

    if (isset($ppcart_product->checkout_starts) && $now < ppcart_localize_dt($ppcart_product->checkout_starts)) {
        return true;
    }

    if (isset($ppcart_product->checkout_ends) && ppcart_localize_dt($ppcart_product->checkout_ends) < $now) {
        return true;
    }

    // Check if managing stock levels
    if (isset($ppcart_product->manage_stock) && ($ppcart_product->limit < 1)) {
        return true;
    }

    return false;
}


function ppcart_is_prod_on_sale($prod_id = false)
{

    // editing/creating order manually
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin order editor reads posted item context in authorized admin action.
    if (isset($_POST['_ppcart_item_name']) && isset($_POST['on-sale']) && is_admin() && ppcart_user_can('manager_option')) {
        return true;
    }

    if ($prod_id) {
        $ppcart_product = ppcart_setup_product($prod_id);
    } else {
        // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.VariableRedeclaration -- Reads shared product context from global scope when no product ID is passed.
        global $ppcart_product;
    }

    $now = ppcart_localize_dt();

    if (isset($ppcart_product->on_sale)) {
        return true;
    } elseif (isset($ppcart_product->schedule_sale) && (isset($ppcart_product->sale_start) || isset($ppcart_product->sale_end))) {
        if ($ppcart_product->sale_start && ppcart_localize_dt($ppcart_product->sale_start) > $now) {
            return false;
        }
        if ($ppcart_product->sale_end && ppcart_localize_dt($ppcart_product->sale_end) < $now) {
            return false;
        }
        return true;
    }
    return false;
}


// add
function ppcart_get_user_subscriptions($user_id, $status = 'any', $type = null, $plan_id = 0)
{

    $postnum = (current_user_can('administrator')) ? 15 : -1;
    $plan_id = intval($plan_id);
    $user_info = get_userdata($user_id);

    $args = [
        'posts_per_page'      => $postnum,
        'post_type'        => ppcart_query_post_types('subscription'),
        'post_status'      => $status,
        'fields'        => 'ids',
    ];

    $args['meta_query'][] = [
        'relation' => 'OR',
        [
            'key' => ppcart_meta_key('user_account'),
            'value' => $user_id,
        ],
        [
            'key' => ppcart_meta_key('email'),
            'value' => $user_info->user_email,
        ],
    ];

    if ($type == 'installment') {
        $comparetype = '!=';
    } else {
        $comparetype = '=';
    }

    $args['meta_query'][] = [
        'key' => ppcart_meta_key('sub_installments'),
        'value' => '-1',
        'compare' => $comparetype,
    ];

    if ($plan_id > 0) {
        $args['post__in'] = (array) $plan_id;
    }

    $posts = get_posts($args);
    $subs = [];
    if (!empty($posts)) {
        foreach ($posts as $post) {
            $sub = new PPCart_Subscription($post);
            $subs[] = (object) $sub->get_data();
        }
        return $subs;
    }

    return false;
}


function ppcart_get_user_orders($user_id, $status = ['paid', 'completed', 'refunded'], $order_id = 0, $renewals = false, $hide_free = false)
{

    $postnum = (current_user_can('administrator')) ? 15 : -1;
    $user_info = get_userdata($user_id);

    $status_query = ['key' => ppcart_meta_key('status'),'value' => $status,'compare' => 'IN'];
    if ($status == 'any') {
        $status_query = ['key' => ppcart_meta_key('status'),'compare' => 'EXISTS'];
    }

    $args = [
        'posts_per_page'   => $postnum,
        'post_type'        => ppcart_query_post_types('order'),
        'post_status'      => 'any',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Order filtering relies on post meta in legacy storage.
        'meta_query' => [
            'relation' => 'AND',
            [
                'relation' => 'OR',
                $status_query,
                [
                    'relation' => 'AND',
                    [
                        'key' => ppcart_meta_key('status'),
                        'value' => 'pending-payment',
                    ],
                    [
                        'key' => ppcart_meta_key('pay_method'),
                        'value' => 'cod',
                    ],
                ],
            ],
        ],
    ];

    $args['meta_query'][] = [
        'relation' => 'OR',
        [
            'key' => ppcart_meta_key('user_account'),
            'value' => $user_id,
        ],
        [
            'key' => ppcart_meta_key('email'),
            'value' => $user_info->user_email,
        ],
    ];

    if ($hide_free) {
        $args['meta_query'][] = [
            [
                'key' => ppcart_meta_key('amount'),
                'value' => 0,
                'type'    => 'numeric',
                'compare' => '>',
            ],
        ];
    }

    if (!$renewals) {
        $args['meta_query'][] = [
            'key' => ppcart_meta_key('renewal_order'),
            'compare' => 'NOT EXISTS',
        ];
    }

    $order_id = intval($order_id);
    if ($order_id > 0) {
        $args['post__in'] = (array) $order_id;
    }

    $posts = get_posts($args);
    $orders = [];

    if (!empty($posts)) {
        foreach ($posts as $post) {
            $order = new PPCart_Order($post->ID);
            $orders[] = $order->get_data();
        }
        return $orders;
    }

    return false;
}


function ppcart_get_orders($args)
{

    $defaults = [
        'numberposts'      => 5,
        'post_type'        => ppcart_query_post_types('order'),
        'post_status'      => 'paid',
    ];
    $parsed_args = wp_parse_args($args, $defaults);
    $posts = get_posts($parsed_args);
    $orders = [];

    if (!empty($posts)) {
        foreach ($posts as $post) {
            if (ppcart_is_subscription_post_type($parsed_args['post_type'])) {
                $order_info = new PPCart_Subscription($post->ID);
            } else {
                $order_info = new PPCart_Order($post->ID);
            }
            $order_info = $order_info->get_data();
            $orders[] = ppcart_webhook_order_body($order_info, $args['post_status']);
        }
        return $orders;
    }

    return $posts;
}


function ppcart_get_order($id)
{
    if (ppcart_is_order_post_type(get_post_type($id))) {
        $order_info = new PPCart_Order($id);
        $order_info = $order_info->get_data();
        return ppcart_webhook_order_body($order_info);
    }
    return null;
}


//translate string for js
function ppcart_translate_js($js_script = '')
{
    $return_data = [];
    if ($js_script == "ppcart-admin.js") {
        //admin/js/ppcart-admin.js
        $return_data = [
            'invalid_charge_id' => __("Invalid Charge ID", "publishpress-cart"),
            'process_refund'    => __("Are you sure you wish to process this refund? This action cannot be undone.", "publishpress-cart"),
            'wait'              => __("Please wait...", "publishpress-cart"),
            'refund_success'    => __("Refund Successful", "publishpress-cart"),
            'try_again'         => __("Error: Please try again.", "publishpress-cart"),
            'invalid_sub_id'     => __("Invalid Subscriber ID", "publishpress-cart"),
            'sub_cancel'        => __("This subscription has been canceled.", "publishpress-cart"),
            'sub_started'       => __("Subscription resumed.", "publishpress-cart"),
            'sub_paused'        => __("This subscription has been paused.", "publishpress-cart"),
            'confirm_cancel_sub' => __("Are you sure you wish to cancel this subscription? This action cannot be undone.", "publishpress-cart"),
            'cancel_subscription_button' => __("Cancel subscription", "publishpress-cart"),
            'confirm_pause_sub' => __("Are you sure you wish to pause this subscription?", "publishpress-cart"),
            'confirm_activate_sub' => __("Are you sure you wish to resume this subscription?", "publishpress-cart"),
            'sub_cancel_scheduled' => __("This subscription has been scheduled to cancel at the end of the current billing period.", "publishpress-cart"),
            'sub_cancel_refund_failed' => __("This subscription has been canceled, but the refund could not be created.", "publishpress-cart"),
            'something_went_wrong' => __("Something went wrong. Please try again.", "publishpress-cart"),
            'list_renewed'   => __("All lists successfully renewed", "publishpress-cart"),
            'missing_required'   => __("Required fields missing", "publishpress-cart"),
            ];
        foreach ($return_data as $k => $v) {
            $return_data[$k] = apply_filters('ppcart_backend_message_' . $k, $v);
        }
    } elseif ($js_script == "ppcart-public.js") {
        //public/js/ppcart-public.js
        $return_data = [
            'empty_username'    => __("You need to enter your email address or username to continue.", "publishpress-cart"),
            'invalid_email'     => __("There are no users registered with this username or email address.", "publishpress-cart"),
            'invalidcombo'      => __("There are no users registered with this username or email address.", "publishpress-cart"),
            'invalid_email_or_password' => __('Invalid email or password.', 'publishpress-cart'),
            'retrieve_password_email_failure' => __('Failed to send reset email. Please try again later or contact support.', 'publishpress-cart'),
            'lost_password_invalid_nonce' => __('Your session has expired. Please try again.', 'publishpress-cart'),
            'expiredkey'        => __('The password reset link you used is not valid anymore.', 'publishpress-cart'),
            'invalidkey'        => __('The password reset link you used is not valid anymore.', 'publishpress-cart'),
            'password_reset_mismatch' =>  __("The two passwords you entered don't match.", 'publishpress-cart'),
            'password_reset_empty' => __("Please enter in a password.", 'publishpress-cart'),
            'empty_password' => __('Please enter a password to login.', 'publishpress-cart'),
            'invalid_username' => __("We don't have any users with that username or email address. Maybe you used a different one when signing up?", 'publishpress-cart'),
            /* translators: %s: lost password URL. */
            'incorrect_password' => sprintf(__("The password you entered wasn't quite right. <a href='%s'>Did you forget your password?</a>", 'publishpress-cart'), get_option('_ppcart_myaccount_page_id') ? get_permalink(get_option('_ppcart_myaccount_page_id')) . '?action=lostpassword' : wp_lostpassword_url()),
            'weak_password'     => __("Your password is too weak. It must be at least 12 characters long and include one uppercase letter, one lowercase letter, one number, and one special character.", "publishpress-cart"),
            'failed'            => __("Invalid username or password.", "publishpress-cart"),
            'sub_cancel'        => __("This subscription has been canceled.", "publishpress-cart"),
            'sub_started'       => __("Subscription resumed.", "publishpress-cart"),
            'sub_paused'        => __("This subscription has been paused.", "publishpress-cart"),
            'confirm_cancel_sub' => __("Are you sure you wish to cancel this subscription? This action cannot be undone.", "publishpress-cart"),
            'confirm_pause_sub' => __("Are you sure you wish to pause this subscription?", "publishpress-cart"),
            'confirm_activate_sub' => __("Are you sure you wish to resume this subscription?", "publishpress-cart"),
            'invalid_email' => __('Enter a valid email', "publishpress-cart"),
            'invalid_phone' => __('Enter a valid phone number', "publishpress-cart"),
            'invalid_pass' => __('Use 8 or more characters with a mix of letters, numbers, and symbols', "publishpress-cart"),
            'includes' => __('includes', "publishpress-cart"),
            'included_in_price' => __('Included In Price', "publishpress-cart"),
            'field_required' => __('This field is required', "publishpress-cart"),
            'username_exists' => __("This username already exists", "publishpress-cart"),
            'with_a' => apply_filters('ppcart_plan_text_with_a', __('with a', 'publishpress-cart')),
            'day_free_trial' => apply_filters('ppcart_plan_text_day_free_trial', __('-day free trial', 'publishpress-cart')),
            'and' => apply_filters('ppcart_plan_text_and_a', __('and a', 'publishpress-cart')),
            'sign_up_fee' => apply_filters('ppcart_plan_text_sign_up_fee', __('sign-up fee', 'publishpress-cart')),
            'processing' => __('Processing', 'publishpress-cart'),
            'loading_processing' => apply_filters('ppcart_plan_text_loading_processing', __('Order Now', 'publishpress-cart')), /* Used for loading state when click Order Now */
            'you_got' => __('You got', 'publishpress-cart'),
            'off' => __('off!', 'publishpress-cart'),
            'coupon' => __('Coupon:', 'publishpress-cart'),
            'discount_off' => __('off', 'publishpress-cart'),
            'forever'   => __('forever', 'publishpress-cart'),
            'expires'   => __('expires', 'publishpress-cart'),
            'day'       => __('day', 'publishpress-cart'),
            'days'      => __('days', 'publishpress-cart'),
            'week'      => __('week', 'publishpress-cart'),
            'weeks'     => __('weeks', 'publishpress-cart'),
            'month'     => __('month', 'publishpress-cart'),
            'months'    => __('months', 'publishpress-cart'),
            'missing_required'   => __("Required fields missing", "publishpress-cart"),
            'year'      => __('year', 'publishpress-cart'),
            'years'     => __('years', 'publishpress-cart'),
        ];
        foreach ($return_data as $k => $v) {
            $return_data[$k] = apply_filters('ppcart_frontend_message_' . $k, $v);
        }
    }
    return $return_data;
}
