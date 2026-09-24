<?php

/*
 * Integration suite bootstrap file.
 *
 * This file is loaded AFTER the suite modules are initialized, WordPress, plugins and themes are loaded.
 *
 * If you need to load plugins or themes, add them to the Integration suite configuration file, in the
 * "modules.config.WPLoader.plugins" and "modules.config.WPLoader.theme" settings.
 *
 * If you need to load one or more database dump file(s) to set up the test database, add the path to the dump file to
 * the "modules.config.WPLoader.dump" setting.
 */

if (! class_exists('Tests\NoTransactionWPTestCase')) {
    require_once __DIR__ . '/NoTransactionWPTestCase.php';
}

if (! class_exists('Tests\Support\Integration\StripeSyncTestCase')) {
    require_once dirname(__DIR__) . '/Support/Integration/StripeSyncTestCase.php';
}

if (! class_exists('Tests\Support\Integration\PPCartBlockTestConfirmationPublic')) {
    require_once dirname(__DIR__) . '/Support/Integration/PPCartBlockTestConfirmationPublic.php';
}

if (! class_exists('Tests\Support\Integration\CheckoutBlockTestCase')) {
    require_once dirname(__DIR__) . '/Support/Integration/CheckoutBlockTestCase.php';
}

if (! defined('PPCART_PLUGIN_ROOT')) {
    define('PPCART_PLUGIN_ROOT', dirname(__DIR__, 3) . '/');
}

if (! function_exists('ppcart_integration_include_filtered_product_type')) {
    /**
     * @param array<int, string>|mixed $postTypes
     * @return array<int, string>
     */
    function ppcart_integration_include_filtered_product_type($postTypes)
    {
        $postTypes   = (array) $postTypes;
        $postTypes[] = 'ppcart_filter_prod';

        return array_values(array_unique($postTypes));
    }
}
