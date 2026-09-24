<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in PPCart_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The PPCart_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

global $ppcart_is_admin_screen;

if (! PPCart_Admin_Screens::is_plugin_screen($hook_suffix)) {
    return;
}

$ppcart_is_admin_screen = true;

wp_enqueue_script('ppcart-selectize', PPCART_BASE_URL . 'admin/js/selectize.js', [], $this->version, false);
wp_enqueue_script('ppcart-flatpickr', PPCART_BASE_URL . 'admin/assets/libs/flatpickr.min.js', [ 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider' ], $this->version, false);
wp_enqueue_script('ppcart-font-awesome-all', PPCART_BASE_URL . 'includes/assets/font-awesome-all.min.js', [], $this->version, true);
wp_enqueue_script('jquery-ui-dialog');
wp_enqueue_script('ppcart', PPCART_BASE_URL . 'admin/js/ppcart-admin.js', [ 'jquery', 'wp-color-picker', 'jquery-ui-dialog' ], $this->version, false);
wp_enqueue_script('moment');
wp_enqueue_script('ppcart-daterangepicker', PPCART_BASE_URL . 'admin/js/ppcart-daterangepicker.min.js', [ 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider', 'moment' ], $this->version, false);
wp_enqueue_script('ppcart-datatables', PPCART_BASE_URL . 'admin/js/jquery.dataTables.min.js', [ 'jquery' ], $this->version, true);

wp_localize_script(
    'ppcart',
    'ppcart_reg_vars',
    [
    'upload_url' => admin_url('async-upload.php'),
    'ajax_url'   => admin_url('admin-ajax.php'),
    'nonce'         => wp_create_nonce('ppcart_ajax_nonce'),
    'resend_purchase_confirmation_email_nonce' => wp_create_nonce('ppcart_resend_purchase_confirmation_email'),
    'search_user_nonce' => wp_create_nonce('ppcart_admin_search_user_nonce'),
    'media_nonce'      => wp_create_nonce('media-form'),
    ]
);

wp_localize_script('ppcart', 'ppcart_translate_backend', ppcart_translate_js('ppcart-admin.js'));
wp_localize_script('ppcart', 'ppcart_admin_i18n', [
    'payment_label_cashondelivery' => __('Cash on Delivery', 'publishpress-cart'),
    'payment_label_stripe' => __('Stripe', 'publishpress-cart'),
    'payment_label_paypal' => __('PayPal', 'publishpress-cart'),
    'sync_config_error' => __('Configuration error: Missing AJAX settings. Please refresh the page.', 'publishpress-cart'),
    'sync_missing_subscription_id' => __('Subscription ID is missing.', 'publishpress-cart'),
    'sync_confirm' => __('Sync subscription with Stripe?', 'publishpress-cart'),
    'sync_wait' => __('Wait...', 'publishpress-cart'),
    'sync_success' => __('Subscription synced successfully.', 'publishpress-cart'),
    'sync_try_again' => __('Error: Please try again.', 'publishpress-cart'),
    'encrypt_migration_title' => __('Migration required', 'publishpress-cart'),
    'encrypt_migration_confirm_singular' => __('You have 1 stored credential in plaintext. After enabling encryption, run migration from Settings → Maintenance to encrypt it.', 'publishpress-cart'),
    /* translators: %d: number of stored credentials. */
    'encrypt_migration_confirm_plural' => __('You have %d stored credentials in plaintext. After enabling encryption, run migration from Settings → Maintenance to encrypt them.', 'publishpress-cart'),
    'encrypt_updated_title' => __('Encryption updated', 'publishpress-cart'),
    'continue_button' => __('Continue', 'publishpress-cart'),
    'cancel' => __('Cancel', 'publishpress-cart'),
    'ok' => __('OK', 'publishpress-cart'),
    'encrypt_update_failed' => __('Unable to update security encryption setting.', 'publishpress-cart'),
]);
if (get_option('ppcart_mailchimp_tags')) {
    wp_localize_script('ppcart', 'ppcart_mc_tags', get_option('ppcart_mailchimp_tags'));
}
if (get_option('ppcart_mailchimp_groups')) {
    wp_localize_script('ppcart', 'ppcart_mc_groups', get_option('ppcart_mailchimp_groups'));
}

$this->enqueue_product_editor_metabox_panel_script($hook_suffix);

if (PPCart_Admin_Screens::is_reports_screen($hook_suffix)) {
    wp_enqueue_script(
        'ppcart-reports',
        PPCART_BASE_URL . 'admin/js/ppcart-reports.js',
        [ 'jquery', 'ppcart-flatpickr' ],
        $this->version,
        true
    );
    wp_localize_script(
        'ppcart-reports',
        'ppcartReports',
        [
            'ajaxUrl'              => admin_url('admin-ajax.php'),
            'searchCustomersNonce' => wp_create_nonce('ppcart_search_report_customers'),
            'searchProductsNonce'  => wp_create_nonce('ppcart_search_report_products'),
            'noCustomers'          => __('No matching customers', 'publishpress-cart'),
            'noProducts'           => __('No matching products', 'publishpress-cart'),
            'allProducts'          => __('All products', 'publishpress-cart'),
            'searching'            => __('Searching…', 'publishpress-cart'),
        ]
    );
}

if (PPCart_Admin_Screens::is_settings_screen($hook_suffix)) {
    // Modern settings UI behaviour (sidebar, search, sticky save bar).
    wp_enqueue_script(
        'ppcart-settings',
        PPCART_BASE_URL . 'admin/js/ppcart-settings.js',
        [ 'jquery', 'ppcart', 'wp-util' ],
        $this->version,
        true
    );

    wp_localize_script('ppcart-settings', 'ppcartSettingsI18n', [
        'noResults'           => __('No matching settings', 'publishpress-cart'),
        'noResultsHint'       => __('Try a different keyword or clear the search to see all settings.', 'publishpress-cart'),
        'discardConfirm'      => __('Discard your unsaved changes?', 'publishpress-cart'),
        'resetEmailConfirm'   => __('Reset this email template to the built-in default?', 'publishpress-cart'),
        'previewTitle'        => __('Email preview', 'publishpress-cart'),
        'previewLoading'      => __('Generating preview...', 'publishpress-cart'),
        'previewEmailFailed'  => __('Email preview could not be generated. Please refresh the page and try again.', 'publishpress-cart'),
        'previewButton'       => __('Preview', 'publishpress-cart'),
        'closePreviewButton'  => __('Close Preview', 'publishpress-cart'),
        'enabled'             => __('Enabled', 'publishpress-cart'),
        'disabled'            => __('Disabled', 'publishpress-cart'),
        'migrateSecretsTitle' => __('Encrypt stored credentials', 'publishpress-cart'),
        /* translators: %d: number of plaintext credentials. */
        'migrateSecretsConfirm' => __('Encrypt %d plaintext credential(s) in the database? Take a backup first and run this during a maintenance window.', 'publishpress-cart'),
        'migrateSecretsConfirmEmpty' => __('Scan stored credentials and encrypt any plaintext values? Take a backup first and run this during a maintenance window.', 'publishpress-cart'),
    ]);

    $this->enqueue_tax_rate_templates();

    do_action('ppcart_enqueue_admin_tax_settings', $this->plugin_name, $this->version);
}
wp_enqueue_media();
wp_enqueue_editor();
wp_enqueue_script('ppcart-uploader', PPCART_BASE_URL . 'admin/js/ppcart-file-uploader.js', [ 'jquery' ], $this->version, true);
wp_enqueue_script('ppcart-repeater', PPCART_BASE_URL . 'admin/js/ppcart-repeater.js', [ 'jquery', 'editor', 'quicktags', 'jquery-ui-sortable' ], $this->version, true);

wp_localize_script('ppcart-repeater', 'ppcartNotificationI18n', [
    'previewTitle'   => __('Email preview', 'publishpress-cart'),
    'previewLoading' => __('Generating preview…', 'publishpress-cart'),
    'previewFailed'  => __('Email preview could not be generated. Please refresh the page and try again.', 'publishpress-cart'),
    'testSending'    => __('Sending test email…', 'publishpress-cart'),
    'testSent'       => __('Test email sent.', 'publishpress-cart'),
    'testFailed'     => __('Test email failed to send. Please refresh the page and try again.', 'publishpress-cart'),
    'testNeedEmail'  => __('Enter an email address to send the test to.', 'publishpress-cart'),
]);
