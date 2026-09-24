<?php

if (!defined('ABSPATH')) {
    exit;
}

$intigrations = [
    'activecampaign' => __('ActiveCampaign', 'publishpress-cart'),
    'mailchimp' => __('Mailchimp', 'publishpress-cart'),
    'mailpoet' => __('MailPoet', 'publishpress-cart'),
    'membervault' => __('MemberVault', 'publishpress-cart'),
    'sendfox' => __('SendFox', 'publishpress-cart'),
    'fbads' => __('Enable Facebook Ad Events', 'publishpress-cart'),
    'ga' => __('Google Analytics Purchase Event Tracking', 'publishpress-cart'),
];
$intigrations = apply_filters('_ppcart_integrations_tab_section', $intigrations);
foreach ($intigrations as $intigration_key => $intigration) :
    add_settings_section(
        $this->plugin_name . '-' . $intigration_key,
        apply_filters($this->plugin_name . 'section-title-' . $intigration_key, esc_html($intigration)),
        [$this, 'section_settings'],
        $this->plugin_name . '-integrations'
    );
endforeach;
do_action('_ppcart_register_sections', $this, $this->plugin_name);
