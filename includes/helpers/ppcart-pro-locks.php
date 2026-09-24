<?php

/**
 * In-context Pro upsell helpers.
 *
 * Small, reusable helpers used to render "locked" previews of premium features
 * in the free plugin: a "PRO" nav badge, a "Pro feature" lock button (for cards
 * and rows), and a blur/overlay for whole locked tabs. Shared visual primitives
 * live in admin/css/ppcart-pro-locks.css (loaded on every plugin screen).
 *
 * @link https://publishpress.com/publishpress-cart/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/includes/helpers
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/pro-locks/settings-locks.php';
require_once __DIR__ . '/pro-locks/field-and-email-locks.php';
require_once __DIR__ . '/pro-locks/product-locks.php';
