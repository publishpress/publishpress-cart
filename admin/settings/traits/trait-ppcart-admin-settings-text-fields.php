<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Admin_Settings_Text_Fields_Trait
{
    /**
         * Creates a text field
         *
         * @param array         $args           The arguments for the field
         * @return  string                      The HTML field
         */
    public function field_text($args)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/settings-text-fields-field-text.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function field_email($args)
    {
        include __DIR__ . '/templates/settings-text-fields-field-email.php';
    }

    public function field_password($args)
    {
        include __DIR__ . '/templates/settings-text-fields-field-password.php';
    }

    /**
         * Creates a html field
         *
         * @param array         $args           The arguments for the field
         * @return  string                      The HTML field
         */
    public function field_html($args)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/settings-text-fields-field-html.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
         * Renders the Stripe checkout-experience radio group.
         */
    public function field_stripe_checkout_experience($args)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/settings-text-fields-field-stripe-checkout-experience.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function field_color($args)
    {
        include __DIR__ . '/templates/settings-text-fields-field-color.php';
    }

    /**
         * Creates a textarea field
         *
         * @param array         $args           The arguments for the field
         * @return  string                      The HTML field
         */
    public function field_textarea($args)
    {
        include __DIR__ . '/templates/settings-text-fields-field-textarea.php';
    }

    /**
         * The tax rate importer which extends WP_Importer.
         */
    public function field_upload($args)
    {
        include __DIR__ . '/templates/settings-text-fields-field-upload.php';
    }

    private function sanitizer($type, $data)
    {
        if (empty($type)) {
            return;
        }
        if (empty($data)) {
            return;
        }
        $return     = '';
        $sanitizer  = new PPCart_Sanitize();
        $sanitizer->set_data($data);
        $sanitizer->set_type($type);
        $return = $sanitizer->clean();
        unset($sanitizer);
        return $return;
    }
}
