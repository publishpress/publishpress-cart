<?php

if (! defined('ABSPATH')) {
    exit;
}

if ('tax' === $tab_slug) {
    echo '<div class="ppcart-settings__card ppcart-settings__card--tax">';
    include PPCART_BASE_DIR . 'admin/partials/ppcart-admin-page-settings-tax.php';
    echo '</div>';
}

// White Label only exists in Pro: render its fields as a locked preview.
if ('white_label' === $tab_slug && ! empty($is_pro_locked_tab) && function_exists('ppcart_pro_locked_field_rows_html')) {
    echo '<div class="ppcart-settings__card ppcart-settings__card--plain">';
    echo '<table class="form-table" role="presentation">';
    echo ppcart_pro_locked_field_rows_html('white_label'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in helper.
    echo '</table>';
    echo '</div>';
}
