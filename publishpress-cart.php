<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 * @package PPCart
 *
 * @wordpress-plugin
 * Plugin Name:       PublishPress Cart
 * Plugin URI:        https://publishpress.com/publishpress-cart/
 * Description:       Create order pages and simplified sales flow creation that helps you sell digital products, programs, and services.
 * Version:           1.0.0
 * Author:            PublishPress
 * Author URI:        https://publishpress.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       publishpress-cart
 * Domain Path:       /languages
 */

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

// If the plugin is already loaded, terminate the plugin execution.
if (defined('PPCART_FREE_LOADED')) {
    return;
}

define('PPCART_FREE_LOADED', true);

const PPCART_MINIMUM_PHP_VERSION = '7.4';
const PPCART_MINIMUM_WP_VERSION = '6.7';

global $wp_version;

// Exit if PHP or WordPress version requirements are not met.
if (version_compare(PHP_VERSION, PPCART_MINIMUM_PHP_VERSION, '<')) {
    return;
}

if (version_compare($wp_version, PPCART_MINIMUM_WP_VERSION, '<')) {
    return;
}

if (! defined('PPCART_BASE_FILE')) {
    define('PPCART_BASE_FILE', __FILE__);
}

/**
 * Cart and leftover Studiocart cannot run at the same time: overlapping CPTs,
 * checkout, and PHP names. Detect Studiocart by plugin basename before any Cart
 * bootstrap so the load can abort without a wp_die wall.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-studiocart-conflict.php';

if (! function_exists('ppcart_refuse_studiocart_activation')) {
    /**
     * Soft-stop when activated beside Studiocart.
     *
     * Stay listed as Active and defer PPCart_Activator until Studiocart is gone.
     * Do not wp_die or deactivate — that would hide the admin notice.
     *
     * @return void
     */
    function ppcart_refuse_studiocart_activation()
    {
        PPCart_Studiocart_Conflict::on_activate(PPCART_BASE_FILE);
    }
}

register_activation_hook(PPCART_BASE_FILE, 'ppcart_refuse_studiocart_activation');

/**
 * Stay in active_plugins so an admin-only notice can persist. Return before
 * CPTs, checkout, companion packages, or ppcart_run(). Studiocart keeps serving
 * the storefront until it is deactivated.
 */
if (PPCart_Studiocart_Conflict::is_active()) {
    PPCart_Studiocart_Conflict::register_notice();

    return;
}

require_once plugin_dir_path(__FILE__) . 'includes/helpers/ppcart-stripe-metadata.php';

/**
 * StudioCart leftover packages live in the sibling plugin publishpress-cart-compat,
 * not in this Free tree (IT-161). Load that plugin's package entry files from
 * WP_PLUGIN_DIR when it is active — before the canonical-only live stubs below —
 * so leftover-aware helpers exist first. Constant bridges run twice: inbound
 * leftover names before Cart defines PPCART_* defaults, then outbound aliases
 * after those defaults (second call is later in this file).
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-companion-loader.php';

$ppcart_loaded_companion_packages = ppcart_load_companion_compat_packages();

require_once plugin_dir_path(__FILE__) . 'includes/helpers/ppcart-live.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers/ppcart-live-tables.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers/ppcart-meta.php';

/**
 * Inbound constant bridges: leftover NCS_CART_* / SC_* already defined in wp-config
 * (or by a third party) should feed Cart's PPCART_* defaults. Must run before
 * this file defines those canonical constants.
 */
ppcart_maybe_register_studiocart_constants($ppcart_loaded_companion_packages);

if (! defined('PPCART_VERSION')) {
    define('PPCART_VERSION', '1.0.0');
}
if (! defined('PPCART_BASE_DIR')) {
    define('PPCART_BASE_DIR', plugin_dir_path(__FILE__));
}
if (! defined('PPCART_BASE_URL')) {
    define('PPCART_BASE_URL', plugin_dir_url(__FILE__));
}
if (! defined('PPCART_BASE_FILE')) {
    define('PPCART_BASE_FILE', __FILE__);
}
if (! defined('PPCART_STYLESHEET_PATH')) {
    define('PPCART_STYLESHEET_PATH', get_stylesheet_directory());
}

// Stripe Connect extra percent.
if (defined('PPCART_STRIPE_CONNECT_EXTRA_PERCENT')) {
    throw new RuntimeException('PPCART_STRIPE_CONNECT_EXTRA_PERCENT cannot be overridden.');
}

define('PPCART_STRIPE_CONNECT_EXTRA_PERCENT', 2.0);

// Stripe connect server URL.
if (! defined('PPCART_STRIPE_CONNECT_SERVER_URL')) {
    define('PPCART_STRIPE_CONNECT_SERVER_URL', 'https://publishpress.com');
}

// Public documentation (docs/public → Scribe).
if (! defined('PPCART_DOCS_URL')) {
    define('PPCART_DOCS_URL', 'https://publishpress.com/knowledge-base/getting-started-with-publishpress-cart/');
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ppcart-activator.php
 */
if (! function_exists('ppcart_activate')) {
    function ppcart_activate()
    {
        require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-activator.php';
        PPCart_Activator::activate();
    }
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ppcart-deactivator.php
 */
if (! function_exists('ppcart_deactivate')) {
    function ppcart_deactivate()
    {
        require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-deactivator.php';
        PPCart_Deactivator::deactivate();
    }
}

/**
 * The code that runs during plugin upgrade.
 * This action is documented in includes/class-ppcart-upgrade.php
 *
 * @param object $upgrader_object The upgrader object.
 * @param array $options The options array.
 * @return void
 */
if (! function_exists('ppcart_upgrade')) {
    function ppcart_upgrade($upgrader_object, $options)
    {
        $current_plugin_dir_name = dirname(plugin_basename(PPCART_BASE_FILE));
        if (isset($upgrader_object->result) && isset($upgrader_object->result['destination_name']) && $upgrader_object->result['destination_name'] == $current_plugin_dir_name) {
            require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-upgrade.php';
            PPCart_Upgrade::upgrade();
        }
    }
}

register_activation_hook(PPCART_BASE_FILE, 'ppcart_activate');
register_deactivation_hook(PPCART_BASE_FILE, 'ppcart_deactivate');

/**
 * First-time Activate-while-Studiocart-is-on never reached ppcart_activate()
 * because that hook is registered after the conflict return above. Run it once
 * Studiocart is gone so tax table, caps, API key, and rewrite flush still happen.
 */
if (PPCart_Studiocart_Conflict::consume_pending_activation()) {
    ppcart_activate();
}

add_action(
    'upgrader_process_complete',
    'ppcart_upgrade',
    10,
    2
);

$ppcart_lib_vendor_path = __DIR__ . '/lib/vendor';
if (! defined('PPCART_LIB_VENDOR_PATH')) {
    define('PPCART_LIB_VENDOR_PATH', $ppcart_lib_vendor_path);
}
if (! defined('PPCART_VENDOR_ASSETS_URL')) {
    if (defined('PPCART_LOADED_BY_PRO') && PPCART_LOADED_BY_PRO && defined('PPCART_PRO_BASE_URL')) {
        define('PPCART_VENDOR_ASSETS_URL', PPCART_PRO_BASE_URL . 'lib/vendor/');
    } else {
        define('PPCART_VENDOR_ASSETS_URL', PPCART_BASE_URL . 'lib/vendor/');
    }
}

/**
 * Outbound constant aliases: Cart has now defined PPCART_* defaults. Companion
 * Compatibility Mode can expose leftover NCS_CART_* / SC_* names for third-party
 * PHP that still reads those constants.
 */
ppcart_maybe_register_studiocart_constants($ppcart_loaded_companion_packages);

$ppcart_bundled_translations_path = '/publishpress/bundled-translations/core/include.php';

if (
    is_file(PPCART_LIB_VENDOR_PATH . $ppcart_bundled_translations_path)
    && is_readable(PPCART_LIB_VENDOR_PATH . $ppcart_bundled_translations_path)
) {
    require_once PPCART_LIB_VENDOR_PATH . $ppcart_bundled_translations_path;
} elseif (
    defined('PPCART_PRO_LIB_VENDOR_DIR')
    && is_file(PPCART_PRO_LIB_VENDOR_DIR . $ppcart_bundled_translations_path)
    && is_readable(PPCART_PRO_LIB_VENDOR_DIR . $ppcart_bundled_translations_path)
) {
    require_once PPCART_PRO_LIB_VENDOR_DIR . $ppcart_bundled_translations_path;
}

add_action('plugins_loaded', function () {
    if (class_exists('PublishPress\BundledTranslations\BundledTranslations')) {
        $ppcart_bundled_translations = new PublishPress\BundledTranslations\BundledTranslations(
            'publishpress-cart',
            PPCART_BASE_DIR . 'languages',
            PPCART_BASE_FILE
        );

        $ppcart_bundled_translations->init();
    }
}, 10);

// Load shared feature support helpers used by both Free and Pro.
require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-feature-support.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers/ppcart-pro-locks.php';

if (! defined('PPCART_PRO_LIB_VENDOR_DIR')) {
    $ppcart_autoload_path = PPCART_LIB_VENDOR_PATH . '/autoload.php';
    if (
        ! class_exists('ComposerAutoloaderInitPublishPressCart')
        && is_file($ppcart_autoload_path)
        && is_readable($ppcart_autoload_path)
    ) {
        require_once $ppcart_autoload_path;
    }
}

require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-version-notices.php';
PPCart_Version_Notices::init();
require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-reviews.php';
PPCart_Reviews::init();

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-ppcart.php';
require plugin_dir_path(__FILE__) . 'includes/schedule-event.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-status-labels.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-post-status-sync.php';
require_once plugin_dir_path(__FILE__) . 'models/class-ppcart-order.php';
require_once plugin_dir_path(__FILE__) . 'models/class-ppcart-subscription.php';
require_once plugin_dir_path(__FILE__) . 'includes/logging/class-ppcart-debug-log-viewer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-secrets.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers/ppcart-secrets-functions.php';

PPCart_Secrets::init();
require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-order-refunds.php';
require_once plugin_dir_path(__FILE__) . 'includes/logging/class-ppcart-stripe-webhook-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-ppcart-stripe-sync.php';
require_once plugin_dir_path(__FILE__) . 'includes/email/ppcart-email-template-functions.php';
/**
 * Include helper functions
 */
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers/ppcart-scheduling.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
if (! function_exists('ppcart_run')) {
    function ppcart_run()
    {
        $plugin = new PPCart();
        $plugin->run();
    }
}

/**
 * Return Helper class Instance
 */
function ppcart_helper()
{
    return PPCart_Helper::instance();
}


if (! did_action('ppcart_loaded')) {
    ppcart_run();
    do_action('ppcart_loaded');
}
