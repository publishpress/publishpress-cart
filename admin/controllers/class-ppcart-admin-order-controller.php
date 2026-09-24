<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Renders order edit-screen details, line items, and product relationship metaboxes.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */

require_once __DIR__ . '/order/traits/trait-ppcart-admin-order-metabox.php';
require_once __DIR__ . '/order/traits/trait-ppcart-admin-order-ajax.php';
require_once __DIR__ . '/order/traits/trait-ppcart-admin-order-product-form.php';
require_once __DIR__ . '/order/traits/trait-ppcart-admin-order-query.php';
class PPCart_Admin_Order_Controller
{
    use PPCart_Admin_Order_Metabox_Trait;
    use PPCart_Admin_Order_Ajax_Trait;
    use PPCart_Admin_Order_Product_Form_Trait;
    use PPCart_Admin_Order_Query_Trait;

    /** @var string */
    private $plugin_name;

    /** @var string */
    private $plugin_title;

    /** @var string */
    private $version;

    /** @var \PublishPress\Stripe\StripeClient|null */
    private $stripe;

    public function __construct($plugin_name, $plugin_title, $version)
    {
        global $ppcart_stripe;

        $this->stripe = empty($ppcart_stripe['sk']) ? null : ppcart_stripe_client($ppcart_stripe['sk']);
        $this->plugin_name = $plugin_name;
        $this->plugin_title = $plugin_title;
        $this->version = $version;

        add_action('save_post', [$this, 'save_access_info']);
    }
}
