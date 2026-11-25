<?php

if (! defined('ABSPATH')) {
    exit;
}

echo ppcart_render_mini_cart($attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered templates escape their output.
