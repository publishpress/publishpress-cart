<?php
/**
 * Plugin Name: PublishPress Cart - Silence vendor deprecations
 * Description: Drops E_DEPRECATED so thecodingmachine/safe v1.x on PHP 8.4 stays quiet. Linked into WordPress mu-plugins by tests/bin/lib/wp-env.sh (isolated test installs and this plugin's DDEV .web site).
 * Version: 1.1.0
 *
 * @package PublishPressCart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Must run as an mu-plugin: after wp_debug_mode() (which sets E_ALL) and before
// regular plugins autoload Safe. set_error_handler cannot swallow these
// signature deprecations; only error_reporting can.
error_reporting( E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED );
