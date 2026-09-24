<?php

if (! defined('ABSPATH')) {
    exit;
}


add_filter('_ppcart_integrations_tab_section', [$this, 'settings_section'], 10, 1);
add_filter('_ppcart_integrations_option_list', [$this, 'service_settings']);
add_filter('ppcart_show_optin_checkbox_services', [$this, 'add_optin_service']);

add_action('add_option__ppcart_converkit_api', [$this, 'get_' . $this->service_name . '_forms'], 10);
add_action('add_option__ppcart_converkit_api', [$this, 'get_' . $this->service_name . '_tags'], 10);
add_action('update_option__ppcart_converkit_api', [$this, 'get_' . $this->service_name . '_forms'], 10);
add_action('update_option__ppcart_converkit_api', [$this, 'get_' . $this->service_name . '_tags'], 10);

//we store the option for converkit instead of convertkit
$this->api_key = ppcart_get_sensitive_option('_ppcart_converkit_api');
$this->secret_key = ppcart_get_sensitive_option('_ppcart_converkit_secret_key');

if ($this->api_key && $this->secret_key) {
    $integration = $this;
    add_action('ppcart_renew_integrations_lists', function () use ($integration) {
        $integration->get_convertkit_forms(true);
    });
    add_action('ppcart_renew_integrations_lists', function () use ($integration) {
        $integration->get_convertkit_tags(true);
    });
    add_filter('ppcart_integrations', [$this, 'add_service']);
    add_filter('ppcart_integration_fields', [$this, 'add_integration_fields'], 10, 2);
    add_action('ppcart_' . $this->service_name . '_integrations', [$this, 'add_remove_to_service'], 10, 3);
}
