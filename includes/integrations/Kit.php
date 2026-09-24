<?php

namespace PublishPress\Cart;

if (!defined('ABSPATH')) {
    exit;
}

class Kit
{
    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $plugin_name    The ID of this plugin.
     */
    private $service_name;
    private $service_label;
    private $api_key;
    private $secret_key;

    public function __construct()
    {
        $this->service_name = 'convertkit';
        $this->service_label = 'Kit';
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-kit-init.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function add_optin_service($options)
    {
        $options[] = $this->service_name;
        return $options;
    }

    public function settings_section($intigrations)
    {
        $intigrations[$this->service_name] = $this->service_label;
        return $intigrations;
    }

    public function service_settings($options)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-kit-service-settings.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function add_service($options)
    {
        $options[$this->service_name] = $this->service_label;
        return $options;
    }

    public function add_integration_fields($fields, $save)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-kit-add-integration-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function add_remove_to_service($int, $ppcart_product_id, $order)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-kit-add-remove-to-service.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    //get_convertkit_forms
    public function get_convertkit_forms($renew = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-kit-get-convertkit-forms.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    //get_convertkit_tags
    public function get_convertkit_tags($renew = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-kit-get-convertkit-tags.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    //get_convertkit_form_options
    public function get_convertkit_form_options()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-kit-get-ppcart-convertkit-forms.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    //get_converkit_tag_options
    public function get_converkit_tag_options()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-kit-get-ppcart-converkit-tags.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function add_remove_convertkit_subscriber($order_id, $service_id, $action_name, $ppcart_mail_forms, $ppcart_mail_tags, $email, $phone, $fname, $lname, $fieldmap)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-kit-ppcart-add-remove-convertkit-subscriber.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
