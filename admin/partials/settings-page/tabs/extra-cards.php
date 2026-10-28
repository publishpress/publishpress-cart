<?php

if (! defined('ABSPATH')) {
    exit;
}

if ('tax' === $tab_slug) {
    echo '<div class="ppcart-settings__card ppcart-settings__card--tax">';
    include PPCART_BASE_DIR . 'admin/partials/ppcart-admin-page-settings-tax.php';
    echo '</div>';
}

// Pro-only settings use the same disabled fields and upgrade links.
if (in_array($tab_slug, [ 'white_label', 'affiliate' ], true) && ! empty($is_pro_locked_tab) && function_exists('ppcart_pro_locked_field_rows_html')) {
    echo '<div class="ppcart-settings__card ppcart-settings__card--plain">';
    echo '<table class="form-table" role="presentation">';
    echo wp_kses(ppcart_pro_locked_field_rows_html($tab_slug), ppcart_admin_allowed_html());
    echo '</table>';
    echo '</div>';
}
