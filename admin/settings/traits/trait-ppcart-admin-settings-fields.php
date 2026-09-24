<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require_once __DIR__ . '/trait-ppcart-admin-settings-text-fields.php';

require_once __DIR__ . '/trait-ppcart-admin-settings-input-fields.php';

trait PPCart_Admin_Settings_Fields_Trait
{
    use PPCart_Admin_Settings_Text_Fields_Trait;
    use PPCart_Admin_Settings_Input_Fields_Trait;

    /**
     * Registers settings fields with WordPress
     */

    public function register_fields()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/settings-fields-register-fields.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
