<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Public page, template, and shortcode behavior.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */

/**
 * Registers public shortcodes, templates, redirects, assets, and tracking output.
 */
require_once __DIR__ . '/page/traits/trait-ppcart-public-page-shortcodes.php';
require_once __DIR__ . '/page/traits/trait-ppcart-public-page-customer.php';
require_once __DIR__ . '/page/traits/trait-ppcart-public-page-template.php';
require_once __DIR__ . '/page/traits/trait-ppcart-public-page-routing.php';
require_once __DIR__ . '/page/traits/trait-ppcart-public-page-downloads.php';

class PPCart_Public_Page_Controller
{
    use PPCart_Public_Page_Shortcodes_Trait;
    use PPCart_Public_Page_Customer_Trait;
    use PPCart_Public_Page_Template_Trait;
    use PPCart_Public_Page_Routing_Trait;
    use PPCart_Public_Page_Downloads_Trait;


    /**
     * Public script handle.
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin asset version.
     *
     * @var string
     */
    private $version;

    /**
     * Plugin meta prefix.
     *
     * @var string
     */
    public $prefix;

    public function __construct($plugin_name = '', $version = '', $prefix = '')
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->prefix = $prefix;

        add_shortcode('ppcart_form', [$this, 'product_shortcode']);
        add_shortcode('ppcart_receipt', [$this, 'receipt_shortcode']);
        add_shortcode('ppcart_store', [$this, 'store_shortcode']);
        add_shortcode('ppcart_customer_bought_product', [$this, 'customer_bought_product']);
        add_shortcode('ppcart_customer_has_subscription', [$this, 'customer_has_subscription']);
    }
}
