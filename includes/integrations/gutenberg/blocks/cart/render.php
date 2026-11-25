<?php

if (! defined('ABSPATH')) {
    exit;
}

$ppcart_output = ppcart_render_shopping_cart($attributes);
// The editor preview already sits inside the block's own wrapper.
if ('' === $ppcart_output || wp_is_serving_rest_request()) {
    echo $ppcart_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered templates escape their output.
    return;
}

printf('<div %1$s>%2$s</div>', get_block_wrapper_attributes(), $ppcart_output); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes the wrapper attributes; rendered templates escape their output.
