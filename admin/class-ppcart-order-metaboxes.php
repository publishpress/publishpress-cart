<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once __DIR__ . '/order-metaboxes/traits/trait-ppcart-order-metabox-field-groups.php';

require_once __DIR__ . '/order-metaboxes/traits/trait-ppcart-order-metabox-save.php';

require_once __DIR__ . '/order-metaboxes/traits/trait-ppcart-order-metabox-edit-fields.php';

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
class PPCart_Order_Metaboxes
{
    use PPCart_Order_Metabox_Field_Groups_Trait;
    use PPCart_Order_Metabox_Save_Trait;
    use PPCart_Order_Metabox_Edit_Fields_Trait;

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
    private $sub_fields;

    public function __construct($plugin_name, $version, $prefix)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->prefix = $prefix;

        $this->set_meta();
    }

    /**
     * Registers metaboxes with WordPress
     *
     * @since 1.0.0
     * @access public
     */
    public function add_metaboxes()
    {
        $this->set_field_groups();

        add_meta_box(
            'ppcart-edit-order-details',
            apply_filters($this->plugin_name . '-metabox-title-order-details', esc_html__('Customer & Billing Details', 'publishpress-cart')),
            [ $this, 'order_detail_fields' ],
            ppcart_query_post_types('order'),
            'normal',
            'high'
        );

        add_meta_box(
            'ppcart-edit-order-details',
            apply_filters($this->plugin_name . '-metabox-title-order-details', esc_html__('Customer & Billing Details', 'publishpress-cart')),
            [ $this, 'sub_detail_fields' ],
            ppcart_query_post_types('subscription'),
            'normal',
            'high'
        );
    }
}
