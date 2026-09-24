<?php

if (! defined('ABSPATH')) {
    exit;
}

if ('integrations' === $tab_slug) {
    // Locked previews of premium integrations, inside the
    // searchable listbox alongside the available ones.
    $locked_integration_keys = function_exists('ppcart_pro_locked_integration_keys') ? ppcart_pro_locked_integration_keys() : [];
    foreach ($locked_integration_keys as $locked_key) {
        if (in_array($locked_key, $rendered_integration_keys, true)) {
            continue;
        }

        $locked_info = $integration_meta[ $locked_key ] ?? [];
        if (empty($locked_info['title'])) {
            continue;
        }

        $locked_logo_url = ! empty($locked_info['logo_file']) ? $get_admin_asset_url($locked_info['logo_file']) : '';

        $render_locked_pro_card([
            'type'        => 'integration',
            'key'         => $locked_key,
            'title'       => $locked_info['title'],
            'description' => $locked_info['description'] ?? '',
            'logo_url'    => $locked_logo_url,
            'logo_label'  => $locked_info['logo_label'] ?? mb_substr(wp_strip_all_tags($locked_info['title']), 0, 2),
            'category'    => $locked_info['category'] ?? 'automation',
            'context'     => 'integration-' . $locked_key,
        ]);
    }

    echo '</div>';
    echo '<p class="ppcart-settings__integrations-request"><span aria-hidden="true">?</span> ' . esc_html__('Can\'t find the integration you need?', 'publishpress-cart') . ' <a href="https://publishpress.com/contact/" target="_blank" rel="noreferrer noopener" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-integrations-request')) . '">' . esc_html__('Request an integration.', 'publishpress-cart') . '</a></p>';
    echo '</div>';
    echo '<div class="ppcart-settings__integrations-detail">';
    echo wp_kses($integration_panels_html, ppcart_admin_allowed_html());
    echo '</div>';
    echo '</div>';
}
