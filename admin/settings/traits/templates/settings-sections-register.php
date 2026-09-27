<?php

if (!defined('ABSPATH')) {
    exit;
}

// New, focused General-tab subsections. Fields can opt-in by setting
// a 'subsection' attribute pointing to one of these slugs. Extensions
// that still add fields to the legacy 'settings' bucket continue to
// render under the catch-all section below for backwards compatibility.
add_settings_section(
    $this->plugin_name . '-currency',
    apply_filters($this->plugin_name . 'section-title-currency', esc_html__('Currency & Pricing', 'publishpress-cart')),
    [ $this, 'section_settings' ],
    $this->plugin_name
);
add_settings_section(
    $this->plugin_name . '-pages',
    apply_filters($this->plugin_name . 'section-title-pages', esc_html__('Pages & Customer Account', 'publishpress-cart')),
    [ $this, 'section_settings' ],
    $this->plugin_name
);
add_settings_section(
    $this->plugin_name . '-company',
    apply_filters($this->plugin_name . 'section-title-company', esc_html__('Company & Branding', 'publishpress-cart')),
    [ $this, 'section_settings' ],
    $this->plugin_name
);
add_settings_section(
    $this->plugin_name . '-downloads',
    apply_filters($this->plugin_name . 'section-title-downloads', esc_html__('Downloads', 'publishpress-cart')),
    [ $this, 'section_settings' ],
    $this->plugin_name
);
add_settings_section(
    $this->plugin_name . '-debug',
    apply_filters($this->plugin_name . 'section-title-debug', esc_html__('Debug', 'publishpress-cart')),
    [ $this, 'section_settings' ],
    $this->plugin_name
);
add_settings_section(
    $this->plugin_name . '-settings',
    apply_filters($this->plugin_name . 'section-title-settings', esc_html__('Other Settings', 'publishpress-cart')),
    [ $this, 'section_settings' ],
    $this->plugin_name
);
$this->register_integration_tab_section();
$this->register_payment_gateway_tab_section();
$this->register_email_tab_section();
$this->register_tax_tab_section();
$this->register_invoice_tab_section();
$this->register_maintenance_tab_section();
$this->register_maintenance_db_schema_section();

/* register custom tab field */
$ppcart_tabs = apply_filters('ppcart_setting_tabs', []);

$this->register_tab_section($ppcart_tabs);

do_action('ppcart_register_sections');
