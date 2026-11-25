<?php

if (! defined('ABSPATH')) {
    exit;
}

class PPCart_Admin_Hook_Registrar
{
    private $loader;

    private $cart;

    public function __construct($loader, $cart)
    {
        $this->loader = $loader;
        $this->cart   = $cart;
    }

    public function register()
    {
        require_once dirname(__DIR__, 2) . '/admin/settings/class-ppcart-admin-shopping-cart-pages.php';
        $shopping_cart_pages = new PPCart_Admin_Shopping_Cart_Pages(new PPCart_Shopping_Cart_Settings());
        $this->loader->add_action('admin_post_ppcart_shopping_cart_create_pages', $shopping_cart_pages, 'create_missing_pages');
        $this->loader->add_action('ppcart_settings_admin_notices', $shopping_cart_pages, 'render_notice');
        $this->loader->add_action('admin_footer', $shopping_cart_pages, 'render_form');
        $this->loader->add_action('admin_init', $shopping_cart_pages, 'register_page_fill');
        include __DIR__ . '/templates/admin-hook-registrar-register.php';
    }
}
