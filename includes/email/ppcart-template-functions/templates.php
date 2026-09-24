<?php

if (! defined('ABSPATH')) {
    exit;
}

function ppcart_get_email_allowed_html()
{
    $allowed_html = wp_kses_allowed_html('post');

    // Transactional email clients commonly need inline CSS and style blocks.
    $allowed_html['style'] = [
        'media' => true,
        'type'  => true,
    ];

    $svg_attributes = [
        'aria-hidden'          => true,
        'class'                => true,
        'clip-path'            => true,
        'clip-rule'            => true,
        'cx'                   => true,
        'cy'                   => true,
        'd'                    => true,
        'fill'                 => true,
        'fill-rule'            => true,
        'focusable'            => true,
        'height'               => true,
        'href'                 => true,
        'id'                   => true,
        'mask'                 => true,
        'offset'               => true,
        'opacity'              => true,
        'points'               => true,
        'preserveAspectRatio'  => true,
        'preserveaspectratio'  => true,
        'r'                    => true,
        'role'                 => true,
        'rx'                   => true,
        'ry'                   => true,
        'stop-color'           => true,
        'stop-opacity'         => true,
        'stroke'               => true,
        'stroke-dasharray'     => true,
        'stroke-linecap'       => true,
        'stroke-linejoin'      => true,
        'stroke-miterlimit'    => true,
        'stroke-width'         => true,
        'style'                => true,
        'transform'            => true,
        'version'              => true,
        'viewBox'              => true,
        'viewbox'              => true,
        'width'                => true,
        'x'                    => true,
        'x1'                   => true,
        'x2'                   => true,
        'xlink:href'           => true,
        'xmlns'                => true,
        'xmlns:xlink'          => true,
        'y'                    => true,
        'y1'                   => true,
        'y2'                   => true,
    ];

    foreach ([ 'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'defs', 'clipPath', 'clippath', 'linearGradient', 'lineargradient', 'radialGradient', 'radialgradient', 'stop', 'mask', 'use' ] as $tag) {
        $allowed_html[ $tag ] = $svg_attributes;
    }

    return $allowed_html;
}

function ppcart_kses_email_html($html)
{
    if ('' === $html || null === $html) {
        return '';
    }

    return wp_kses((string) $html, ppcart_get_email_allowed_html());
}

function ppcart_get_order_details_email_markup()
{
    $body = '<h3>' . esc_html__('Order Details', 'publishpress-cart') . '</h3>';
    $body .= '<p>' . esc_html__('Order Number:', 'publishpress-cart') . ' {order_id}<br>';
    $body .= esc_html__('Purchase Date:', 'publishpress-cart') . ' {order_date}</p>';
    $body .= '<table style="width: 100%; border-collapse: collapse;" border="0" cellpadding="0" cellspacing="0">';
    $body .= '<tbody>';
    $body .= '<tr>';
    $body .= '<td style="padding: 10px 0; border-top: 1px dotted #cccccc;"><strong>{order_list}</strong></td>';
    $body .= '<td style="padding: 10px 0; border-top: 1px dotted #cccccc; text-align: right;">{product_amount}</td>';
    $body .= '</tr>';
    $body .= '<tr>';
    $body .= '<td style="padding: 10px 0; border-top: 1px dotted #cccccc;">' . esc_html__('Subtotal', 'publishpress-cart') . '</td>';
    $body .= '<td style="padding: 10px 0; border-top: 1px dotted #cccccc; text-align: right;">{product_amount}</td>';
    $body .= '</tr>';
    $body .= '<tr>';
    $body .= '<td style="padding: 10px 0; border-top: 1px dotted #cccccc;"><strong>' . esc_html__('Order Total', 'publishpress-cart') . '</strong></td>';
    $body .= '<td style="padding: 10px 0; border-top: 1px dotted #cccccc; text-align: right;"><strong>{order_amount}</strong></td>';
    $body .= '</tr>';
    $body .= '</tbody>';
    $body .= '</table>';
    $body .= "\n\n";
    $body .= '<h3>' . esc_html__('Customer Information', 'publishpress-cart') . '</h3>';
    $body .= '<p>{customer_name}<br>{customer_email}<br>{customer_phone}<br>{customer_address}</p>';

    return $body;
}

function ppcart_get_editable_order_details_email_body($intro = '', $extra = '')
{
    $body = trim((string) $intro);

    if ('' !== $body) {
        $body .= "\n\n";
    }

    $body .= '{order_details}';

    $extra = trim((string) $extra);
    if ('' !== $extra) {
        $body .= "\n\n" . $extra;
    }

    return $body;
}

function ppcart_email_type_uses_editable_order_details($type)
{
    return in_array($type, [ 'pending', 'confirmation', 'completed', 'renewal' ], true);
}

/**
 * Email template catalog for admin settings (subjects, bodies, enable flags).
 *
 * @return array
 */
function ppcart_email_templates()
{
    $templates = [
        'pending'      => [
            'section'     => 'emailtemplate_pending',
            'title'       => __('Order Received Confirmation', 'publishpress-cart'),
            'description' => __('Sent after an order is received (pending payment). Useful for COD orders.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
            'enable'      => '_ppcart_email_pending_enable',
            'admin'       => '_ppcart_email_pending_admin',
            'subject'     => [
                'option'  => '_ppcart_email_pending_subject',
                'default' => __('Your order received confirmation from {site_name}', 'publishpress-cart'),
            ],
            'headline'    => [
                'option'  => '_ppcart_email_pending_headline',
                'default' => __('Your order has been received!', 'publishpress-cart'),
            ],
            'body'        => [
                'option'  => '_ppcart_email_pending_body',
                'default' => ppcart_get_editable_order_details_email_body(__('Hi {customer_firstname},<br><br>We have received your order and will send another update when it is complete.', 'publishpress-cart')),
            ],
        ],
        'confirmation' => [
            'section'     => 'emailtemplate_purchase',
            'title'       => __('Purchase Confirmation', 'publishpress-cart'),
            'description' => __('Sent to customers after purchase (paid).', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
            'enable'      => '_ppcart_email_confirmation_enable',
            'admin'       => '_ppcart_email_confirmation_admin',
            'subject'     => [
                'option'  => '_ppcart_email_confirmation_subject',
                'default' => __('Your order confirmation from {site_name}', 'publishpress-cart'),
            ],
            'headline'    => [
                'option'  => '_ppcart_email_confirmation_headline',
                'default' => __('Thank you for your order!', 'publishpress-cart'),
            ],
            'body'        => [
                'option'  => '_ppcart_email_confirmation_body',
                'default' => ppcart_get_editable_order_details_email_body(__('Hi {customer_firstname},<br><br>Thank you for your order.', 'publishpress-cart')),
            ],
        ],
        'completed'    => [
            'section'     => 'emailtemplate_completed',
            'title'       => __('Order Complete Notification', 'publishpress-cart'),
            'description' => __('Sent when an order is marked complete.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
            'enable'      => '_ppcart_email_completed_enable',
            'admin'       => '_ppcart_email_completed_admin',
            'subject'     => [
                'option'  => '_ppcart_email_completed_subject',
                'default' => __('Your order from {site_name} is complete!', 'publishpress-cart'),
            ],
            'headline'    => [
                'option'  => '_ppcart_email_completed_headline',
                'default' => __('Thank you for your order!', 'publishpress-cart'),
            ],
            'body'        => [
                'option'  => '_ppcart_email_completed_body',
                'default' => ppcart_get_editable_order_details_email_body(__('Hi {customer_firstname},<br><br>Your order is now complete.', 'publishpress-cart')),
            ],
        ],
        'registration' => [
            'section'     => 'emailtemplate_registration',
            'title'       => __('New User Welcome', 'publishpress-cart'),
            'description' => __('Sent when a new user account is created.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
            'enable'      => '_ppcart_email_registration_enable',
            'admin'       => '_ppcart_registration_email_admin',
            'subject'     => [
                'option'  => '_ppcart_registration_subject',
                'default' => __('Welcome to {site_name}', 'publishpress-cart'),
            ],
            'body'        => [
                'option'  => '_ppcart_registration_email_body',
                'default' => __('Hi {customer_firstname},<br><br>Your account has been created for {site_name}.<br><br>Username: {username}<br>Password: {password}<br><br>You can log in here: {login}', 'publishpress-cart'),
            ],
        ],
        'refunded'     => [
            'section'     => 'emailtemplate_refunded',
            'title'       => __('Order Refunded', 'publishpress-cart'),
            'description' => __('Sent when an order is refunded.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
            'enable'      => '_ppcart_email_refunded_enable',
            'admin'       => '_ppcart_email_refunded_admin',
            'subject'     => [
                'option'  => '_ppcart_email_refunded_subject',
                'default' => __('Refund from {site_name}', 'publishpress-cart'),
            ],
            'body'        => [
                'option'  => '_ppcart_email_refunded_body',
                'default' => __("Order ID: {order_id}\nOrder Date: {order_date}\n\nYou're receiving this email because we have processed your refund of {last_refund_amount}. It can take up to 10 days to appear on your statement, if it takes longer please contact your bank for assistance.", 'publishpress-cart'),
            ],
        ],
        'trial_ending' => [
            'section'     => 'emailtemplate_trial_ending',
            'title'       => __('Trial Ending Reminder', 'publishpress-cart'),
            'description' => __('Sent before a subscription trial ends.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
            'enable'      => '_ppcart_email_trial_ending_enable',
            'admin'       => '_ppcart_email_trial_ending_admin',
            'subject'     => [
                'option'  => '_ppcart_email_trial_ending_subject',
                'default' => __('Your {product_name} trial is about to end', 'publishpress-cart'),
            ],
            'body'        => [
                'option'  => '_ppcart_email_trial_ending_body',
                'default' => __('Hi {customer_firstname},

Your trial is ending on <strong>{next_bill_date}</strong>. Once your trial is complete, we will automatically charge the payment method we have on file for <strong>{order_amount}</strong>.

To cancel your trial, please log in to <a href="{login}">your account</a>.

Thank you,
{site_name}', 'publishpress-cart'),
            ],
        ],
        'reminder'     => [
            'section'     => 'emailtemplate_reminder',
            'title'       => __('Upcoming Renewal Reminder', 'publishpress-cart'),
            'description' => __('Sent before an upcoming subscription renewal.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
            'enable'      => '_ppcart_email_reminder_enable',
            'admin'       => '_ppcart_email_reminder_admin',
            'subject'     => [
                'option'  => '_ppcart_email_reminder_subject',
                'default' => __('Upcoming payment reminder for {product_name}', 'publishpress-cart'),
            ],
            'body'        => [
                'option'  => '_ppcart_email_reminder_body',
                'default' => __("Hi {customer_firstname},\n\nJust a friendly reminder that we will be automatically charging the payment method we have on file for <strong>{order_amount}</strong> on <strong>{next_bill_date}</strong>.\n\nIf you have any questions regarding your upcoming payment, please don't hesitate to get in touch.\n\nThank you,\n{site_name}", 'publishpress-cart'),
            ],
        ],
        'renewal'      => [
            'section'     => 'emailtemplate_renewal',
            'title'       => __('Subscription Renewal Confirmation', 'publishpress-cart'),
            'description' => __('Sent after a subscription renews.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
            'enable'      => '_ppcart_email_renewal_enable',
            'admin'       => '_ppcart_email_renewal_admin',
            'subject'     => [
                'option'  => '_ppcart_email_renewal_subject',
                'default' => __('Your subscription renewed for {product_name}', 'publishpress-cart'),
            ],
            'body'        => [
                'option'  => '_ppcart_email_renewal_body',
                'default' => ppcart_get_editable_order_details_email_body(
                    __('Hi {customer_firstname},<br><br>Your subscription for {product_name} has renewed successfully.', 'publishpress-cart'),
                    __('Next billing date: {next_bill_date}<br><br>Thank you,<br>{site_name}', 'publishpress-cart')
                ),
            ],
        ],
        'failed'       => [
            'section'     => 'emailtemplate_failed',
            'title'       => __('Subscription Renewal Failed', 'publishpress-cart'),
            'description' => __('Sent when a subscription renewal payment fails.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
            'enable'      => '_ppcart_email_failed_enable',
            'admin'       => '_ppcart_email_failed_admin',
            'subject'     => [
                'option'  => '_ppcart_email_failed_subject',
                'default' => __('Payment failed for {product_name}', 'publishpress-cart'),
            ],
            'body'        => [
                'option'  => '_ppcart_email_failed_body',
                'default' => __('Hi {customer_firstname},

We were not able to process the renewal payment for {product_name}. Please log in to <a href="{login}">your account</a> to update your payment method.

Thank you,
{site_name}', 'publishpress-cart'),
            ],
        ],
        'canceled'     => [
            'section'     => 'emailtemplate_canceled',
            'title'       => __('Subscription Canceled Confirmation', 'publishpress-cart'),
            'description' => __('Sent when a subscription is canceled.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
            'enable'      => '_ppcart_email_canceled_enable',
            'admin'       => '_ppcart_email_canceled_admin',
            'subject'     => [
                'option'  => '_ppcart_email_canceled_subject',
                'default' => __('Your subscription has been canceled', 'publishpress-cart'),
            ],
            'body'        => [
                'option'  => '_ppcart_email_canceled_body',
                'default' => __('Hi {customer_firstname},

Your subscription for {product_name} has been canceled.

Thank you,
{site_name}', 'publishpress-cart'),
            ],
        ],
        'paused'       => [
            'section'     => 'emailtemplate_paused',
            'title'       => __('Subscription Paused Confirmation', 'publishpress-cart'),
            'description' => __('Sent when a subscription is paused.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
            'enable'      => '_ppcart_email_paused_enable',
            'admin'       => '_ppcart_email_paused_admin',
            'subject'     => [
                'option'  => '_ppcart_email_paused_subject',
                'default' => __('Your subscription has been paused', 'publishpress-cart'),
            ],
            'body'        => [
                'option'  => '_ppcart_email_paused_body',
                'default' => __('Hi {customer_firstname},

Your subscription for {product_name} has been paused.

Thank you,
{site_name}', 'publishpress-cart'),
            ],
        ],
    ];

    return apply_filters('ppcart_email_templates', $templates);
}

function ppcart_email_template_key_from_section($section)
{
    $section = (string) $section;
    foreach (ppcart_email_templates() as $template_key => $template) {
        if (isset($template['section']) && $section === $template['section']) {
            return $template_key;
        }
    }

    return '';
}

function ppcart_email_template_text_field_from_option($option_id)
{
    $option_id = (string) $option_id;
    foreach (ppcart_email_templates() as $template_key => $template) {
        foreach ([ 'subject', 'headline', 'body' ] as $field) {
            if (isset($template[ $field ]['option']) && $option_id === $template[ $field ]['option']) {
                return [
                    'template' => $template_key,
                    'field'    => $field,
                ];
            }
        }
    }

    return false;
}

function ppcart_email_template_field_from_option($option_id)
{
    return ppcart_email_template_text_field_from_option($option_id);
}

function ppcart_get_email_template_default($template_key, $field)
{
    $templates = ppcart_email_templates();

    if (! isset($templates[ $template_key ][ $field ]['default'])) {
        return '';
    }

    return (string) $templates[ $template_key ][ $field ]['default'];
}

function ppcart_get_email_template_option_id($template_key, $field)
{
    $templates = ppcart_email_templates();

    if (! isset($templates[ $template_key ][ $field ]['option'])) {
        return '';
    }

    return (string) $templates[ $template_key ][ $field ]['option'];
}

function ppcart_get_email_template_value($template_key, $field)
{
    $option_id = ppcart_get_email_template_option_id($template_key, $field);

    if ('' === $option_id) {
        return '';
    }

    $default = ppcart_get_email_template_default($template_key, $field);
    $value   = get_option($option_id, null);

    if (null === $value || false === $value || '' === (string) $value) {
        return $default;
    }

    return (string) $value;
}

function ppcart_is_email_template_customized($template_key, $field = '')
{
    $templates = ppcart_email_templates();
    $fields    = '' === $field ? [ 'subject', 'headline', 'body' ] : [ $field ];

    foreach ($fields as $field_key) {
        if (! isset($templates[ $template_key ][ $field_key ]['option'])) {
            continue;
        }

        $option_id = $templates[ $template_key ][ $field_key ]['option'];
        $value     = get_option($option_id, null);

        if (false !== $value && '' !== (string) $value && (string) $value !== ppcart_get_email_template_default($template_key, $field_key)) {
            return true;
        }
    }

    return false;
}

function ppcart_reset_email_template($template_key)
{
    $templates = ppcart_email_templates();

    if (! isset($templates[ $template_key ])) {
        return false;
    }

    foreach ([ 'subject', 'headline', 'body' ] as $field) {
        $option_id = ppcart_get_email_template_option_id($template_key, $field);
        if ('' !== $option_id) {
            delete_option($option_id);
        }
    }

    return true;
}

function ppcart_get_email_template_values($template_key)
{
    $values = [];

    foreach ([ 'subject', 'headline', 'body' ] as $field) {
        $option_id = ppcart_get_email_template_option_id($template_key, $field);
        if ('' === $option_id) {
            continue;
        }

        $values[ $option_id ] = [
            'field'   => $field,
            'value'   => ppcart_get_email_template_value($template_key, $field),
            'default' => ppcart_get_email_template_default($template_key, $field),
        ];
    }

    return $values;
}
