<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The metabox-specific functionality of the plugin.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */

/**
 * The metabox-specific functionality of the plugin.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 * @author PublishPress <help@publishpress.com>
 */
require_once __DIR__ . '/metaboxes/traits/trait-ppcart-product-metaboxes-render.php';
require_once __DIR__ . '/metaboxes/traits/trait-ppcart-product-metaboxes-save.php';
require_once __DIR__ . '/metaboxes/traits/trait-ppcart-product-metaboxes-field-groups.php';
require_once __DIR__ . '/metaboxes/traits/trait-ppcart-product-metaboxes-general-fields.php';
require_once __DIR__ . '/metaboxes/traits/trait-ppcart-product-metaboxes-sales-fields.php';
require_once __DIR__ . '/metaboxes/traits/trait-ppcart-product-metaboxes-message-fields.php';
require_once __DIR__ . '/metaboxes/traits/trait-ppcart-product-metaboxes-integration-fields.php';
require_once __DIR__ . '/metaboxes/traits/trait-ppcart-product-metaboxes-pay-plan-fields.php';
require_once __DIR__ . '/metaboxes/traits/trait-ppcart-product-metaboxes-bump-fields.php';
require_once __DIR__ . '/metaboxes/traits/trait-ppcart-product-metaboxes-options.php';

class PPCart_Product_Metaboxes
{
    use PPCart_Product_Metaboxes_Render_Trait;
    use PPCart_Product_Metaboxes_Save_Trait;
    use PPCart_Product_Metaboxes_Field_Groups_Trait;
    use PPCart_Product_Metaboxes_General_Fields_Trait;
    use PPCart_Product_Metaboxes_Sales_Fields_Trait;
    use PPCart_Product_Metaboxes_Message_Fields_Trait;
    use PPCart_Product_Metaboxes_Integration_Fields_Trait;
    use PPCart_Product_Metaboxes_Pay_Plan_Fields_Trait;
    use PPCart_Product_Metaboxes_Bump_Fields_Trait;
    use PPCart_Product_Metaboxes_Options_Trait;


    /**
     * The post meta data
     *
     * @since 1.0.0
     * @access private
     * @var string          $meta               The post meta data.
     */
    private $meta;

    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string          $plugin_name        The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string          $version            The current version of this plugin.
     */
    private $version;

    /**
     * The prefix of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string          prefix          The prefix of this plugin.
     */
    private $prefix;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string            $plugin_name        The name of this plugin.
     * @param string            $version            The version of this plugin.
     */

    private $general;
    private $access;
    private $payments;
    private $pricing;
    private $fields;
    private $coupons;
    private $orderbump;
    private $upsellPath;
    private $confirmation;
    private $notifications;
    private $integrations;
    private $scripts;
    private $shipping;
    private $files;
    private $affiliate;

    /**
     * Select-list options used by product metabox fields.
     *
     * @var PPCart_Product_Metabox_Option_Sources
     */
    private $option_sources;


    public function __construct($plugin_name, $version, $prefix)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->prefix = $prefix;
        $this->option_sources = new PPCart_Product_Metabox_Option_Sources();
        $this->scripts = '';
        $this->set_meta();

        add_filter('ppcart_integration_fields', [$this, 'add_consent_field'], 10, 2);
    }
}
