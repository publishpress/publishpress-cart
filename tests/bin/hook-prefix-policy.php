<?php

/**
 * Focused assertions for hook prefix standardization compatibility.
 */

$root = dirname(__DIR__, 2);
$manifestPath = $root . '/docs/hooks-manifest.json';

if (! is_file($manifestPath)) {
    fail('Missing hook manifest. Run php tests/bin/hook-manifest.php --update');
}

$manifest = json_decode((string) file_get_contents($manifestPath), true);
if (! is_array($manifest)) {
    fail('Hook manifest is not valid JSON.');
}

$hooks = [];
foreach ($manifest['hooks'] as $hook) {
    $hooks[$hook['name']][] = $hook;
}

assert_hook($hooks, 'ppcart_after_order_paid', 'canonical');
assert_hook($hooks, '_ppcart_option_list', 'canonical');
assert_hook($hooks, 'ppcart_product_setting_tab_{$tab_id}_fields', 'canonical');
assert_hook($hooks, 'ppcart_product_field_scripts', 'canonical');

$settingsTabTrait = (string) file_get_contents($root . '/admin/metaboxes/traits/trait-ppcart-product-metaboxes-render.php');
if (strpos($settingsTabTrait, 'ppcart_product_setting_tab_{$tab_id}_fields') === false) {
    fail('Free product metabox must fire ppcart_product_setting_tab_{$tab_id}_fields.');
}

$metaboxFieldsFile = (string) file_get_contents($root . '/admin/metaboxes/traits/templates/product-metabox-render-product-settings-fields.php');
if (strpos($metaboxFieldsFile, "apply_filters('ppcart_product_field_scripts'") === false) {
    fail('Free product metabox must fire ppcart_product_field_scripts.');
}

$functionsFile = (string) file_get_contents($root . '/includes/functions/admin-ajax-and-notices.php');
if (strpos($functionsFile, "wp_schedule_single_event(time(), 'ppcart_run_price_formatting'") === false) {
    fail('New price formatting schedules must use ppcart_run_price_formatting.');
}
if (strpos($functionsFile, "add_action('ppcart_run_price_formatting', 'ppcart_run_price_formatting')") === false) {
    fail('Canonical price formatting cron hook is not registered.');
}
if (strpos($functionsFile, "add_action('nsc_run_price_formatting'") !== false) {
    fail('Legacy price formatting cron listeners must live in Compatibility Mode only.');
}

$checkoutPaymentFile = (string) file_get_contents($root . '/public/controllers/checkout/traits/templates/checkout-payment-ppcart-process-payment.php');
if (strpos($checkoutPaymentFile, 'studiocart_checkout_complete') !== false) {
    fail('First-party checkout must not fire studiocart_checkout_complete directly.');
}

assert_no_legacy_hook_calls_outside_compat($root);

if (($manifest['policy']['default_new_hook_prefix'] ?? null) !== 'ppcart_') {
    fail('Manifest default hook prefix must be ppcart_.');
}

foreach ($manifest['policy']['disallowed_new_prefixes_or_patterns'] as $pattern) {
    if (! in_array($pattern, ['ncs-cart-*', 'nsc_*', 'pp_cart_*', 'sc_', 'studiocart_', '_sc_'], true)) {
        fail('Unexpected disallowed-prefix policy entry: ' . $pattern);
    }
}

echo "Hook prefix compatibility assertions passed.\n";

function assert_hook(array $hooks, $name, $status)
{
    if (! isset($hooks[$name])) {
        fail('Missing hook in manifest: ' . $name);
    }

    foreach ($hooks[$name] as $hook) {
        if ($hook['status'] === $status) {
            return;
        }
    }

    fail('Hook has unexpected status: ' . $name . ' expected ' . $status);
}

function assert_no_legacy_hook_calls_outside_compat($root)
{
    $pattern = '/\b(?:do_action|apply_filters|add_action|add_filter|remove_action|remove_filter|has_action|has_filter|wp_schedule_event|wp_schedule_single_event|wp_next_scheduled|wp_clear_scheduled_hook)\s*\(\s*([\'"])([^\'"]+)\1/';
    $legacy_pattern = '/^(?:sc_|_sc_|studiocart_|ncs_|nsc_)/';
    $exclude = [
        'includes/compat/',
        'tests/',
        'vendor/',
        'lib/vendor/',
        'node_modules/',
        '.web/',
        'dev-workspace-cache/',
        'dist/',
        'graphify-out/',
    ];
    $frozen = [
        'save_post_sc_',
        'manage_sc_',
        'admin_action_sc_',
        'admin_post_sc_',
        'add_option_',
        'update_option_',
        'delete_option_',
        'option_',
        'default_option_',
    ];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY,
        RecursiveIteratorIterator::CATCH_GET_CHILD
    );
    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $path = $file->getPathname();
        if (! is_readable($path)) {
            continue;
        }
        $relative = str_replace($root . '/', '', $path);
        foreach ($exclude as $prefix) {
            if (strpos($relative, $prefix) === 0) {
                continue 2;
            }
        }

        $source = (string) file_get_contents($path);
        if (! preg_match_all($pattern, $source, $matches)) {
            continue;
        }

        foreach ($matches[2] as $hookName) {
            if (! preg_match($legacy_pattern, $hookName)) {
                continue;
            }

            $is_frozen = false;
            foreach ($frozen as $frozen_prefix) {
                if (0 === strpos($hookName, $frozen_prefix)) {
                    $is_frozen = true;
                    break;
                }
            }

            if ($is_frozen) {
                continue;
            }

            fail('Legacy hook call outside Compatibility Mode: ' . $relative . ' (' . $hookName . ')');
        }
    }
}

function fail($message)
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}
