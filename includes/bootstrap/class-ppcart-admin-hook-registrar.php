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
        include __DIR__ . '/templates/admin-hook-registrar-register.php';
    }
}
