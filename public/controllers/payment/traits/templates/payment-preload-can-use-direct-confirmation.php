<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! is_object($ppcart_product)) {
    return false;
}

if (! empty($ppcart_product->upsell_path)) {
    return false;
}

if (isset($ppcart_product->confirmation) && 'redirect' === $ppcart_product->confirmation) {
    return false;
}

if (! empty($ppcart_product->redirect_url) || ! empty($ppcart_product->confirmations)) {
    return false;
}

return ! $this->has_checkout_complete_side_effects() && ! $this->has_upsell_side_effects();
