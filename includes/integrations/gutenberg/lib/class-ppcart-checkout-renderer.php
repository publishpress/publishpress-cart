<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/checkout-renderer/trait-ppcart-checkout-renderer-request.php';
require_once __DIR__ . '/checkout-renderer/trait-ppcart-checkout-renderer-style-utilities.php';
require_once __DIR__ . '/checkout-renderer/trait-ppcart-checkout-renderer-styles.php';
require_once __DIR__ . '/checkout-renderer/trait-ppcart-checkout-renderer-metadata.php';

class PPCart_Checkout_Renderer
{
    use PPCart_Checkout_Renderer_Request;
    use PPCart_Checkout_Renderer_Style_Utilities;
    use PPCart_Checkout_Renderer_Styles;
    use PPCart_Checkout_Renderer_Metadata;

    public const BLOCK_NAME        = 'publishpress-cart/checkout-form';
    public const SCRIPT_HANDLE     = 'ppcart-checkout-form-editor';
    public const STYLE_HANDLE      = 'ppcart-checkout-form-style';
    public const EDITOR_STYLE_HANDLE = 'ppcart-checkout-form-editor-style';
    public const PUBLIC_STYLE_HANDLE = 'ppcart-checkout-form-public-style';
    public const SELECTIZE_STYLE_HANDLE = 'ppcart-selectize-default';

    /**
     * Block metadata loaded from block.json.
     *
     * @var array
     */
    private $metadata = [];
}
