<?php

if (!defined('ABSPATH')) {
    exit;
}


foreach ($ppcart_tabs as $ppcart_tab_key => $ppcart_tab_value) {
    $ppcart_tab_sections = [
        $ppcart_tab_key . '-setting' => $ppcart_tab_value . ' Options',
    ];
    $ppcart_tab_sections = apply_filters('_ppcart_' . $ppcart_tab_key . '_tab_section', $ppcart_tab_sections);

    foreach ($ppcart_tab_sections as $ppcart_section_key => $ppcart_section_value) :
        add_settings_section(
            $this->plugin_name . '-' . $ppcart_section_key,
            apply_filters($this->plugin_name . 'section-title-' . $ppcart_section_key, esc_html($ppcart_section_value)),
            [$this, 'section_settings'],
            $this->plugin_name . '-' . $ppcart_tab_key
        );
    endforeach;
}
