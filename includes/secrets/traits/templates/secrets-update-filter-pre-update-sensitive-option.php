<?php

if (! defined('ABSPATH')) {
    exit;
}


if (self::should_block_sensitive_update()) {
    return $old_value;
}

if (is_string($value) && '' === $value && is_string($old_value) && '' !== $old_value) {
    return $old_value;
}

if (! self::encryption_enabled() || ! is_string($value) || '' === $value) {
    return $value;
}

if (self::is_encrypted_value($value)) {
    return $value;
}

$encrypted = self::encrypt_value($value);
return false !== $encrypted ? $encrypted : $value;
