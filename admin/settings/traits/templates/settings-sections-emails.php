<?php

if (!defined('ABSPATH')) {
    exit;
}

$emails = [
    'email_settings' => __('Settings', 'publishpress-cart'),
    'email_reports' => esc_html__('Reports & Notifications', 'publishpress-cart'),
    'emailtemplate_pending' => esc_html__('Order Received Confirmation', 'publishpress-cart'),
    'emailtemplate_purchase' => esc_html__('Purchase Confirmation', 'publishpress-cart'),
    'emailtemplate_registration' => esc_html__('New User Welcome', 'publishpress-cart'),
    'emailtemplate_refunded' => esc_html__('Order Refunded', 'publishpress-cart'),
    'emailtemplate_renewal' => esc_html__('Subscription Renewal Confirmation', 'publishpress-cart'),
    'emailtemplate_failed' => esc_html__('Subscription Renewal Failed', 'publishpress-cart'),
    'emailtemplate_canceled' => esc_html__('Subscription Canceled Confirmation', 'publishpress-cart'),
];
$emails = apply_filters('_ppcart_emails_tab_section', $emails);

foreach ($emails as $email_key => $email) :
    add_settings_section(
        $this->plugin_name . '-' . $email_key,
        apply_filters($this->plugin_name . 'section-title-' . $email_key, esc_html($email)),
        [$this, 'section_settings'],
        $this->plugin_name . '-email'
    );
endforeach;
