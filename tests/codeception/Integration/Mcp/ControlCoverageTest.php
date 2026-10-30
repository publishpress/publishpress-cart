<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class ControlCoverageTest extends McpTestCase
{
    private function registry()
    {
        global $wp_filter;
        $this->execute('list-features', []);
        foreach ($wp_filter['wp_abilities_api_init']->callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && $callback['function'][0] instanceof \PPCart_Mcp_Abilities) {
                    return $callback['function'][0];
                }
            }
        }
        $this->fail('Cart feature discovery has no active native registry.');
    }

    private function value($object, $name)
    {
        $property = new \ReflectionProperty($object, $name);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    public function test_each_native_store_control_has_an_exposure_or_native_setup_boundary(): void
    {
        $registry = $this->registry();
        $native = $this->value($this->value($registry, 'settings'), 'settings');
        $read = $this->execute('get-settings', []);
        $covered = (array) $read['settings'];
        $native_only = [
            '_ppcart_api_key', '_ppcart_paypal_ssl_verify', '_ppcart_paypal_email', '_ppcart_paypal_client_id',
            '_ppcart_paypal_sandbox_email', '_ppcart_paypal_sandbox_client_id',
            '_ppcart_squarepay_sandbox_location_id', '_ppcart_squarepay_production_location_id',
            '_ppcart_squarepay_production_access_token', '_ppcart_squarepay_production_webhook_signature_key',
            '_ppcart_activecampaign_url', '_ppcart_membervault_name', '_ppcart_googlerecaptchav2_site_key', '_ppcart_googlerecaptchav3_site_key',
            '_ppcart_converkit_api', '_ppcart_drip_id', '_ppcart_drip_api_key', '_ppcart_encharge_write_key',
            '_ppcart_gurucan_app_domain', '_ppcart_gurucan_api_key', '_ppcart_heartbeat_api_key',
            '_ppcart_mailerlite_api_key', '_ppcart_teachable_email', '_ppcart_teachable_url', '_ppcart_upcoach_api_token', '_ppcart_pro_license_key',
        ];
        $dedicated = ['_ppcart_stripe_checkout_experience', '_ppcart_stripe_hosted_checkout_enable', '_ppcart_stripe_payment_element_enable'];
        foreach ($native->get_options_list(true) as $fields) {
            foreach ($fields as $field) {
                $options = $field['settings'] ?? [];
                $id = $options['id'] ?? '';
                if (! $id || in_array($field['type'], ['html', 'maintenance_secrets'], true)) {
                    continue;
                }
                if (! empty($options['secret']) || 'password' === $field['type'] || in_array($id, $native_only, true)) {
                    $this->assertArrayNotHasKey($id, $covered, $id);
                    continue;
                }
                if (! empty($options['readonly']) || ! empty($options['disabled']) || isset($options['force_value']) || ! empty($options['skip_register'])) {
                    $this->assertArrayNotHasKey($id, $covered, $id);
                    continue;
                }
                if (in_array($id, $dedicated, true)) {
                    $this->assertNotWPError($this->execute('get-checkout-experience', []));
                    continue;
                }
                $this->assertArrayHasKey($id, $covered, 'Unclassified native Cart setting: ' . $id);
            }
        }
    }

    public function test_native_product_controls_have_scalar_or_feature_actions(): void
    {
        $registry = $this->registry();
        $native = $this->value($registry, 'native_products');
        $covered = (new \PPCart_Mcp_Products($native))->definitions();
        $dedicated = ['_ppcart_pay_options' => 'get-payment-plans', '_ppcart_custom_fields' => 'get-custom-fields',
            '_ppcart_coupons' => 'get-coupons', '_ppcart_order_bump_options' => 'get-order-bumps',
            '_ppcart_confirmations' => 'get-confirmations', '_ppcart_notifications' => 'get-product-notifications',
            '_ppcart_default_fields' => 'get-checkout-fields', '_ppcart_address_fields' => 'get-checkout-fields',
            '_ppcart_coupon_files' => 'import-pro-csv'];
        foreach ($native->get_local_field_definitions(0) as $id => $definition) {
            $field = $definition['field'];
            if (in_array($field['type'], ['html', 'hidden'], true) || ! empty($field['readonly']) || ! empty($field['disabled']) || isset($field['force_value'])
                || '_ppcart_coupon_fields_present' === $id) {
                continue;
            }
            if (isset($covered[$id])) {
                $this->assertNotEmpty($covered[$id]['schema'], $id);
                continue;
            }
            $this->assertArrayHasKey($id, $dedicated, 'Unclassified native Cart product control: ' . $id);
            $this->assertNotNull(wp_get_ability('publishpress-cart/' . $dedicated[$id]), $id);
        }
    }
}
