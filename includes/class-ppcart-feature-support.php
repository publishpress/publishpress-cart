<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Shared feature support helpers.
 */

/**
 * Returns true when the free plugin is loaded by Cart Pro.
 *
 * @return bool
 */
function ppcart_is_pro()
{
    return defined('PPCART_LOADED_BY_PRO') && true === PPCART_LOADED_BY_PRO;
}

/**
 * Returns true when Cart supports a named feature.
 *
 * @param string $feature Feature key.
 * @return bool
 */
function ppcart_supports($feature)
{
    $feature = is_string($feature) ? sanitize_key($feature) : '';

    if ('' === $feature) {
        return false;
    }

    return (bool) apply_filters('ppcart_supports_feature', ppcart_is_pro(), $feature);
}
