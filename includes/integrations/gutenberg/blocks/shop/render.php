<?php

if (! defined('ABSPATH')) {
    exit;
}

echo ppcart_render_shop($attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered templates escape their output.
