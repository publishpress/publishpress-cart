<?php

if (! defined('ABSPATH')) {
    exit;
}


foreach ($allowed_callbacks as $allowed_callback) {
    if (isset($allowed_callback['function']) && is_string($function) && $allowed_callback['function'] === $function) {
        return true;
    }

    if (! is_array($function) || ! isset($allowed_callback['class'], $allowed_callback['method'], $function[0], $function[1])) {
        continue;
    }

    if ($allowed_callback['method'] !== $function[1]) {
        continue;
    }

    if (is_object($function[0]) && is_a($function[0], $allowed_callback['class'])) {
        return true;
    }

    if (is_string($function[0]) && is_a($function[0], $allowed_callback['class'], true)) {
        return true;
    }
}

return false;
