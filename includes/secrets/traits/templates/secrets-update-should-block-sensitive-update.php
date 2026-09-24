<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Secrets_Update_Trait::should_block_sensitive_update().

if (self::$allow_internal_secret_migration) {
    return false;
}

if (! function_exists('current_user_can')) {
    return false;
}

if (wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
    return false;
}

$user_id = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
if ($user_id > 0) {
    return ! current_user_can('manage_options');
}

if (is_admin() || wp_doing_ajax()) {
    return ! current_user_can('manage_options');
}

return false;
