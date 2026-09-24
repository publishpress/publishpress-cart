<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/account-renderer/trait-ppcart-account-renderer-context.php';
require_once __DIR__ . '/account-renderer/trait-ppcart-account-renderer-navigation.php';
require_once __DIR__ . '/account-renderer/trait-ppcart-account-renderer-tab-content.php';
require_once __DIR__ . '/account-renderer/trait-ppcart-account-renderer-metadata.php';

class PPCart_Account_Renderer
{
    use PPCart_Account_Renderer_Context;
    use PPCart_Account_Renderer_Navigation;
    use PPCart_Account_Renderer_Tab_Content;
    use PPCart_Account_Renderer_Metadata;

    public const BLOCK_NAME    = 'publishpress-cart/account-page-builder';
    public const SCRIPT_HANDLE = 'ppcart-account-page-editor';
    public const VIEW_SCRIPT_HANDLE = 'ppcart-account-page-view';
    public const STYLE_HANDLE  = 'ppcart-account-block-style';
    public const EDITOR_STYLE_HANDLE = 'ppcart-account-block-editor-style';

    /**
     * Block metadata loaded from block.json files, keyed by block name.
     *
     * @var array
     */
    private $metadata = [];

    /**
     * Account wrapper and style service.
     *
     * @var PPCart_Account_Styles
     */
    private $styles;

    /**
     * Account request and user context.
     *
     * @var PPCart_Account_Context
     */
    private $context;

    /**
     * Account block folders.
     *
     * @var array
     */
    private $block_slugs = [
        'account-page-builder',
        'account-navigation',
        'account-tab',
        'account-orders',
        'account-subscriptions',
        'account-payment-plans',
        'account-profile',
        'account-login',
        'account-downloads',
    ];

    /**
     * Tab block template map.
     *
     * @var array
     */
    private $tab_blocks = [
        'publishpress-cart/account-orders'        => [
            'tab_id'   => 'tab-orders',
            'template' => 'order-history',
        ],
        'publishpress-cart/account-subscriptions' => [
            'tab_id'   => 'tab-subscriptions',
            'template' => 'subscriptions',
        ],
        'publishpress-cart/account-payment-plans' => [
            'tab_id'   => 'tab-plans',
            'template' => 'plans',
        ],
        'publishpress-cart/account-profile'       => [
            'tab_id'   => 'tab-profile',
            'template' => 'user-profile',
        ],
    ];

    /**
     * Whether an account child block is being rendered inside the layout block.
     *
     * @var bool
     */
    private $is_rendering_layout = false;

    /**
     * Active tab selected by the current account layout block.
     *
     * @var string
     */
    private $layout_active_tab = 'tab-orders';
}
