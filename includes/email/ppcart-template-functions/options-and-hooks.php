<?php

if (! defined('ABSPATH')) {
    exit;
}

function ppcart_prepare_email_template_options($options)
{
    foreach ($options as $section => &$fields) {
        if (! is_array($fields)) {
            continue;
        }

        foreach ($fields as &$field) {
            if (empty($field['settings']['id'])) {
                continue;
            }

            $email_field = ppcart_email_template_text_field_from_option($field['settings']['id']);
            if (! $email_field) {
                continue;
            }

            $template_key = $email_field['template'];
            $field_key    = $email_field['field'];

            $field['settings']['email_template_key']     = $template_key;
            $field['settings']['email_template_field']   = $field_key;
            $field['settings']['email_template_default'] = ppcart_get_email_template_default($template_key, $field_key);
            $field['settings']['value']                  = ppcart_get_email_template_value($template_key, $field_key);
        }
    }
    unset($fields, $field);

    return $options;
}

function ppcart_get_email_from_name()
{
    $from_name = trim((string) get_option('_ppcart_email_from_name', ''));

    return '' !== $from_name ? $from_name : get_bloginfo('name');
}

function ppcart_get_email_from_email()
{
    $from_email = trim((string) get_option('_ppcart_email_from_email', ''));

    return '' !== $from_email ? $from_email : get_option('admin_email');
}

function ppcart_get_email_reply_to()
{
    $reply_to = trim((string) get_option('_ppcart_email_reply_to', ''));

    return '' !== $reply_to ? $reply_to : '';
}

function ppcart_get_admin_notification_recipients()
{
    $admin_email = trim((string) get_option('ppcart_admin_email', ''));

    return '' !== $admin_email ? $admin_email : get_option('admin_email');
}

function ppcart_get_email_headers($reply_to = '')
{
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . ppcart_get_email_from_name() . ' <' . ppcart_get_email_from_email() . '>',
    ];

    $reply_to = trim((string) $reply_to);
    if ('' === $reply_to) {
        $reply_to = ppcart_get_email_reply_to();
    }

    if ('' !== $reply_to) {
        $headers[] = 'Reply-To: ' . $reply_to;
    }

    return $headers;
}

function ppcart_get_latest_email_preview_order_id()
{
    $orders = get_posts(
        [
            'post_type'      => ppcart_query_post_types('order'),
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ]
    );

    return ! empty($orders[0]) ? absint($orders[0]) : 0;
}

function ppcart_get_mock_email_preview_order_data()
{
    $order_info = new PPCart_Order();
    $order_info->id                = 1001;
    $order_info->status            = 'paid';
    $order_info->product_name      = __('Sample Product', 'publishpress-cart');
    $order_info->item_name         = __('Professional Plan', 'publishpress-cart');
    $order_info->plan              = '';
    $order_info->plan_id           = 'sample-plan';
    $order_info->option_id         = 'sample-option';
    $order_info->amount            = '49.00';
    $order_info->main_offer_amt    = '49.00';
    $order_info->pre_tax_amount    = '49.00';
    $order_info->tax_amount        = '0.00';
    $order_info->subscription_id   = 0;
    $order_info->quantity          = 1;
    $order_info->firstname         = __('Alex', 'publishpress-cart');
    $order_info->lastname          = __('Morgan', 'publishpress-cart');
    $order_info->first_name        = $order_info->firstname;
    $order_info->last_name         = $order_info->lastname;
    $order_info->customer_name     = $order_info->firstname . ' ' . $order_info->lastname;
    $order_info->email             = 'alex@example.com';
    $order_info->phone             = '+1 (555) 123-4567';
    $order_info->country           = __('United States', 'publishpress-cart');
    $order_info->address1          = __('123 Market Street', 'publishpress-cart');
    $order_info->address2          = __('Suite 400', 'publishpress-cart');
    $order_info->city              = __('Austin', 'publishpress-cart');
    $order_info->state             = 'TX';
    $order_info->zip               = '78701';
    $order_info->tax_desc          = __('Tax', 'publishpress-cart');
    $order_info->tax_rate          = 0;
    $order_info->tax_data          = (object) [ 'type' => 'inclusive' ];
    $order_info->refund_log        = [
        [
            'refundID' => 're_sample_123',
            'date'     => gmdate('Y-m-d'),
            'amount'   => '49.00',
        ],
    ];

    $order_info                     = $order_info->get_data();
    $order_info['date']             = date_i18n(get_option('date_format'), current_time('timestamp'));
    $order_info['customer_address'] = sprintf(
        '%1$s<br>%2$s<br>%3$s, %4$s %5$s<br>%6$s',
        $order_info['address1'],
        $order_info['address2'],
        $order_info['city'],
        $order_info['state'],
        $order_info['zip'],
        $order_info['country']
    );
    $order_info['invoice_link']      = site_url('/my-account/?ppcart-order=' . absint($order_info['ID']));
    $order_info['invoice_link_html'] = '<a href="' . esc_url($order_info['invoice_link']) . '">' . esc_html__('View invoice', 'publishpress-cart') . '</a>';

    return $order_info;
}

function ppcart_email_preview_value_is_empty($value)
{
    if (is_array($value)) {
        return empty($value);
    }

    if (is_object($value)) {
        return empty(get_object_vars($value));
    }

    return '' === trim((string) $value);
}

function ppcart_fill_email_preview_order_data($order_info)
{
    $mock_order_info = ppcart_get_mock_email_preview_order_data();
    $order_info      = is_array($order_info) ? $order_info : [];

    foreach ($mock_order_info as $key => $value) {
        if (! array_key_exists($key, $order_info) || ppcart_email_preview_value_is_empty($order_info[ $key ])) {
            $order_info[ $key ] = $value;
        }
    }

    if (empty($order_info['firstname']) && ! empty($order_info['first_name'])) {
        $order_info['firstname'] = $order_info['first_name'];
    }

    if (empty($order_info['lastname']) && ! empty($order_info['last_name'])) {
        $order_info['lastname'] = $order_info['last_name'];
    }

    if (empty($order_info['first_name']) && ! empty($order_info['firstname'])) {
        $order_info['first_name'] = $order_info['firstname'];
    }

    if (empty($order_info['last_name']) && ! empty($order_info['lastname'])) {
        $order_info['last_name'] = $order_info['lastname'];
    }

    if (empty($order_info['customer_name'])) {
        $order_info['customer_name'] = trim((string) $order_info['firstname'] . ' ' . (string) $order_info['lastname']);
    }

    return $order_info;
}

function ppcart_get_email_preview_order_data()
{
    $order_id = ppcart_get_latest_email_preview_order_id();

    if ($order_id) {
        $order_info = ppcart_setup_order($order_id, true);
        if (! empty($order_info)) {
            return ppcart_fill_email_preview_order_data($order_info);
        }
    }

    return ppcart_get_mock_email_preview_order_data();
}

function ppcart_email_template_option_defaults()
{
    $defaults = [
        '_ppcart_email_from_name'  => get_bloginfo('name'),
        '_ppcart_email_from_email' => get_option('admin_email'),
    ];

    foreach (ppcart_email_templates() as $template) {
        foreach ([ 'subject', 'headline', 'body' ] as $field) {
            if (isset($template[ $field ]['option'], $template[ $field ]['default'])) {
                $defaults[ $template[ $field ]['option'] ] = $template[ $field ]['default'];
            }
        }
    }

    return $defaults;
}

function ppcart_email_template_alias_options()
{
    return [
        '_ppcart_email_past_due_enable'   => '_ppcart_email_failed_enable',
        '_ppcart_email_past_due_admin'    => '_ppcart_email_failed_admin',
        '_ppcart_email_past_due_subject'  => '_ppcart_email_failed_subject',
        '_ppcart_email_past_due_headline' => '_ppcart_email_failed_headline',
        '_ppcart_email_past_due_body'     => '_ppcart_email_failed_body',
    ];
}

function ppcart_email_template_option_names()
{
    $options = [
        '_ppcart_email_from_name',
        '_ppcart_email_from_email',
        '_ppcart_email_pending_subject',
        '_ppcart_email_pending_headline',
        '_ppcart_email_pending_body',
        '_ppcart_email_confirmation_subject',
        '_ppcart_email_confirmation_headline',
        '_ppcart_email_confirmation_body',
        '_ppcart_email_completed_subject',
        '_ppcart_email_completed_headline',
        '_ppcart_email_completed_body',
        '_ppcart_registration_subject',
        '_ppcart_registration_email_body',
        '_ppcart_email_refunded_subject',
        '_ppcart_email_refunded_body',
        '_ppcart_email_trial_ending_subject',
        '_ppcart_email_trial_ending_body',
        '_ppcart_email_reminder_subject',
        '_ppcart_email_reminder_body',
        '_ppcart_email_renewal_subject',
        '_ppcart_email_renewal_body',
        '_ppcart_email_failed_subject',
        '_ppcart_email_failed_body',
        '_ppcart_email_canceled_subject',
        '_ppcart_email_canceled_body',
        '_ppcart_email_paused_subject',
        '_ppcart_email_paused_body',
    ];

    $options = array_merge($options, array_keys(ppcart_email_template_alias_options()));
    $options = apply_filters('ppcart_email_template_option_names', $options);

    if (! is_array($options)) {
        return [];
    }

    return array_values(array_unique($options));
}

function ppcart_email_template_default_option($default, $option = '')
{
    $defaults = ppcart_email_template_option_defaults();

    if (isset($defaults[ $option ])) {
        return $defaults[ $option ];
    }

    $aliases = ppcart_email_template_alias_options();
    if (isset($aliases[ $option ])) {
        return get_option($aliases[ $option ], $default);
    }

    return $default;
}

function ppcart_email_template_option_value($value, $option = '')
{
    if (false !== $value && null !== $value && '' !== (string) $value) {
        return $value;
    }

    return ppcart_email_template_default_option($value, $option);
}

function ppcart_register_email_template_option_filters()
{
    static $registered = [];

    // Dedupes the file-load pass and the late init pass for addon option names.
    foreach (ppcart_email_template_option_names() as $option) {
        if (isset($registered[ $option ])) {
            continue;
        }

        add_filter('default_option_' . $option, 'ppcart_email_template_default_option', 10, 2);
        add_filter('option_' . $option, 'ppcart_email_template_option_value', 10, 2);

        $registered[ $option ] = true;
    }
}

function ppcart_email_personalize_replacements($replacements, $order_info)
{
    $replacements['PublishPress Cart'] = '<a href="https://publishpress.com/publishpress-cart/" target="_blank" rel="noreferrer noopener">PublishPress Cart</a>';

    if (isset($order_info['quantity'])) {
        $replacements['quantity'] = $order_info['quantity'];
    }

    if (! empty($order_info['customer_address'])) {
        $replacements['customer_address'] = $order_info['customer_address'];
    }

    return $replacements;
}

function ppcart_email_reply_to_header($args)
{
    $reply_to = ppcart_get_email_reply_to();

    if ('' === $reply_to || empty($args['headers'])) {
        return $args;
    }

    $headers      = $args['headers'];
    $header_lines = is_array($headers) ? $headers : preg_split('/\r\n|\r|\n/', (string) $headers);
    $is_html      = false;

    foreach ($header_lines as $header) {
        if (0 === stripos(trim((string) $header), 'Reply-To:')) {
            return $args;
        }

        if (false !== stripos((string) $header, 'Content-Type: text/html')) {
            $is_html = true;
        }
    }

    if (! $is_html) {
        return $args;
    }

    $header_lines[] = 'Reply-To: ' . $reply_to;
    $args['headers'] = is_array($headers) ? $header_lines : implode("\r\n", $header_lines);

    return $args;
}

ppcart_register_email_template_option_filters();
// Re-run after normal addon setup so addon-provided option names can opt in.
add_action('init', 'ppcart_register_email_template_option_filters', 99);
add_filter('ppcart_personalize_replacements', 'ppcart_email_personalize_replacements', 10, 2);
add_filter('wp_mail', 'ppcart_email_reply_to_header');
