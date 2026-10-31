<?php

/**
 * Register Cart's optional private Abilities API integration.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/class-ppcart-mcp-input.php';
require_once __DIR__ . '/class-ppcart-mcp-csv.php';
require_once __DIR__ . '/class-ppcart-mcp-options.php';
require_once __DIR__ . '/class-ppcart-mcp-settings.php';
require_once __DIR__ . '/class-ppcart-mcp-checkout-experience.php';
require_once __DIR__ . '/class-ppcart-mcp-products.php';
require_once __DIR__ . '/class-ppcart-mcp-plans.php';
require_once __DIR__ . '/class-ppcart-mcp-records.php';
require_once __DIR__ . '/class-ppcart-mcp-content.php';
require_once __DIR__ . '/class-ppcart-mcp-lifecycle.php';
require_once __DIR__ . '/class-ppcart-mcp-reports.php';
require_once __DIR__ . '/class-ppcart-mcp-export.php';
require_once __DIR__ . '/class-ppcart-mcp-terms.php';
require_once __DIR__ . '/class-ppcart-mcp-payments.php';
require_once __DIR__ . '/class-ppcart-mcp-order-admin.php';
require_once __DIR__ . '/class-ppcart-mcp-sync-guard.php';
require_once __DIR__ . '/class-ppcart-mcp-sync.php';
require_once __DIR__ . '/class-ppcart-mcp-email-test.php';
require_once __DIR__ . '/class-ppcart-mcp-payment-operations.php';
require_once __DIR__ . '/class-ppcart-mcp-features.php';
require_once __DIR__ . '/class-ppcart-mcp-notifications.php';
require_once __DIR__ . '/class-ppcart-mcp-files.php';
require_once __DIR__ . '/class-ppcart-mcp-upload.php';
require_once __DIR__ . '/class-ppcart-mcp-download-access.php';
require_once __DIR__ . '/class-ppcart-mcp-integration-mapping.php';
require_once __DIR__ . '/class-ppcart-mcp-choice-cache.php';
require_once __DIR__ . '/class-ppcart-mcp-integrations.php';
require_once __DIR__ . '/class-ppcart-mcp-abilities.php';

/**
 * Reuse the native controllers without requiring Hub or the MCP Adapter.
 *
 * @since 1.1.0
 * @param PPCart_Admin_Settings $settings Settings definitions and sanitizers.
 * @param PPCart_Product_Metaboxes $products Product definitions.
 * @return void
 */
function ppcart_mcp_boot($settings, $products)
{
    $abilities = new PPCart_Mcp_Abilities(new PPCart_Mcp_Settings($settings), new PPCart_Mcp_Products($products), new PPCart_Mcp_Plans($products), $products);
    add_action('wp_abilities_api_categories_init', [$abilities, 'register_category']);
    add_action('wp_abilities_api_init', [$abilities, 'register']);
}
