<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Secrets_Read_Trait::migrate_plaintext_option().

$option_name = (string) $option_name;

if (
    '' === $option_name
    || ! self::is_sensitive_option($option_name)
    || ! self::encryption_enabled()
    || ! self::encryption_available()
    || isset(self::$migrating_plaintext_options[$option_name])
) {
    return false;
}

$raw = self::get_raw_option_value($option_name);
if ('' === $raw || self::is_encrypted_value($raw)) {
    return false;
}

$plaintext = maybe_unserialize($raw);
if (! is_string($plaintext)) {
    $plaintext = (string) $plaintext;
}

if ('' === $plaintext) {
    return false;
}

self::$migrating_plaintext_options[$option_name] = true;
self::$allow_internal_secret_migration              = true;

$updated = update_option($option_name, $plaintext);

self::$allow_internal_secret_migration = false;
unset(self::$migrating_plaintext_options[$option_name]);

if ($updated) {
    return true;
}

return self::is_encrypted_value(self::get_raw_option_value($option_name));
