<?php

/**
 * Modern admin view for the PublishPress Cart settings page.
 *
 * Renders a redesigned shell (top bar + sidebar + cards) around the existing
 * WordPress Settings API output and existing canonical settings filters.
 *
 * @link https://publishpress.com/publishpress-cart/
 * @since 1.0.0
 *
 * @package PublishPress_Cart
 * @subpackage PublishPress_Cart/admin/partials
 */

if (! defined('ABSPATH')) {
    exit;
}

$plugin_name       = $this->plugin_name;
$plugin_title_raw  = apply_filters('ppcart_plugin_title', $this->plugin_title);
$brand_logo_url    = plugin_dir_url(dirname(__FILE__)) . 'assets/publishpress-logo.png';
$admin_assets_url  = plugin_dir_url(dirname(__FILE__)) . 'assets/';
$admin_assets_path = plugin_dir_path(dirname(__FILE__)) . 'assets/';
$brand_title       = 'PublishPress Cart' === trim(wp_strip_all_tags($plugin_title_raw))
    ? __('Cart', 'publishpress-cart')
    : $plugin_title_raw;
$settings_page_title = '';

global $plugin_page;
if (function_exists('get_admin_page_title') && ! empty($plugin_page)) {
    $settings_page_title = get_admin_page_title();
}

if ('' === trim(wp_strip_all_tags($settings_page_title))) {
    $settings_plugin_title = trim(wp_strip_all_tags($plugin_title_raw));

    if ('' === $settings_plugin_title) {
        $settings_plugin_title = __('PublishPress Cart', 'publishpress-cart');
    }

    $settings_page_title = sprintf(
        /* translators: %s: plugin title. */
        __('%s Settings', 'publishpress-cart'),
        $settings_plugin_title
    );
}

$get_admin_asset_url = static function ($asset_file) use ($admin_assets_url, $admin_assets_path) {
    if (empty($asset_file)) {
        return '';
    }

    $asset_file = ltrim($asset_file, '/');
    $asset_url  = $admin_assets_url . $asset_file;
    $asset_path = $admin_assets_path . $asset_file;

    if (file_exists($asset_path)) {
        $asset_url = add_query_arg('ver', filemtime($asset_path), $asset_url);
    }

    return $asset_url;
};

ob_start();

if (! ppcart_enabled_processors()) {
    ?>
    <div class="notice notice-error ppcart-settings__notice">
        <p><strong><?php esc_html_e('No payment methods found!', 'publishpress-cart'); ?></strong></p>
        <p>
            <?php esc_html_e('Please enable at least one payment method in the', 'publishpress-cart'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS . '#payment_methods')); ?>" rel="noreferrer noopener" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-settings-payment-methods-link')); ?>"><?php esc_html_e('PublishPress Cart settings', 'publishpress-cart'); ?></a>.
        </p>
    </div>
    <?php
}

// Surface any pending Stripe / payment notices the legacy template was rendering.
foreach ([ 'ppcart_stripe_settings_error', 'ppcart_express_payment_settings_error', 'ppcart_customer_portal_settings_error' ] as $transient) {
    $notice = get_transient($transient);
    if ($notice) {
        $notice_type    = is_array($notice) ? ($notice['type'] ?? 'error') : 'error';
        $notice_message = is_array($notice) ? ($notice['message'] ?? '') : (string) $notice;
        ?>
        <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible ppcart-settings__notice">
            <p><?php echo esc_html($notice_message); ?></p>
        </div>
        <?php
        delete_transient($transient);
    }
}

// Surface "Settings saved." after redirect from options.php and any other
// notices registered via add_settings_error().
settings_errors();

// Render captured global admin notices and plugin notices inside the settings shell.
do_action('ppcart_settings_admin_notices');

$settings_notice_output = trim(ob_get_clean());

// Setting tabs definition. Keep the same filter for back-compat.
$default_setting_tabs = [
    'general'         => __('General', 'publishpress-cart'),
    'pages'           => __('Pages', 'publishpress-cart'),
    'branding'        => __('Branding', 'publishpress-cart'),
    'payment_methods' => __('Payment Methods', 'publishpress-cart'),
    'invoice'         => __('Invoices', 'publishpress-cart'),
    'downloads'       => __('Downloads', 'publishpress-cart'),
    'emails'          => __('Email', 'publishpress-cart'),
    'integrations'    => __('Integrations', 'publishpress-cart'),
    'reports'         => __('Reports', 'publishpress-cart'),
    'debug'           => __('Debug', 'publishpress-cart'),
    'maintenance'     => __('Maintenance', 'publishpress-cart'),
    'advanced'        => __('Advanced', 'publishpress-cart'),
];

$setting_tabs = apply_filters(
    'ppcart_setting_tabs',
    $default_setting_tabs
);
if (! is_array($setting_tabs) || [] === $setting_tabs) {
    $setting_tabs = $default_setting_tabs;
}

// Fully Pro-only tabs, mapped to a render mode ('blur' | 'fields'). The White
// Label tab only exists in Pro, so surface it here too.
$pro_locked_tabs = function_exists('ppcart_pro_locked_tabs') ? ppcart_pro_locked_tabs() : [];
if (isset($pro_locked_tabs['white_label']) && ! isset($setting_tabs['white_label'])) {
    $setting_tabs['white_label'] = __('White Label', 'publishpress-cart');
}

$default_tab_page_map = [
    'general'         => '',
    'pages'           => '',
    'branding'        => '',
    'payment_methods' => 'payment',
    'tax'             => 'tax',
    'invoice'         => 'invoice',
    'downloads'       => '',
    'emails'          => 'email',
    'integrations'    => 'integrations',
    'reports'         => 'email',
    'debug'           => '',
    'maintenance'     => 'maintenance',
    'advanced'        => '',
];

$custom_tab_page_map = [];
foreach (array_keys((array) $setting_tabs) as $tab_slug) {
    if (! isset($default_tab_page_map[ $tab_slug ])) {
        $custom_tab_page_map[ $tab_slug ] = $tab_slug;
    }
}

// Tab → settings-page slug used by `do_settings_sections`.
$tab_page_map = apply_filters(
    'ppcart_setting_tab_pages',
    array_merge($default_tab_page_map, $custom_tab_page_map),
    $setting_tabs
);

$general_split_sections = [
    'general'    => [ $plugin_name . '-currency' ],
    'pages'      => [ $plugin_name . '-pages' ],
    'branding'   => [ $plugin_name . '-company' ],
    'downloads'  => [ $plugin_name . '-downloads' ],
    'debug'      => [ $plugin_name . '-debug' ],
];

$general_known_section_ids = [
    $plugin_name . '-currency',
    $plugin_name . '-pages',
    $plugin_name . '-company',
    $plugin_name . '-downloads',
    $plugin_name . '-debug',
];

$general_plain_section_ids = array_merge(
    $general_known_section_ids,
    [ $plugin_name . '-settings' ]
);

// Per-tab metadata: icon, description and grouping in the sidebar.

require __DIR__ . '/settings-page/metadata.php';
require __DIR__ . '/settings-page/render-helpers.php';
require __DIR__ . '/settings-page/view.php';
