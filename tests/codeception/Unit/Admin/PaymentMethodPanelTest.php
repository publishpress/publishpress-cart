<?php

namespace {
    if (! function_exists('esc_attr')) {
        function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
    }
    if (! function_exists('esc_attr__')) {
        function esc_attr__($value, $domain = '') { return esc_attr($value); }
    }
    if (! function_exists('esc_attr_e')) {
        function esc_attr_e($value, $domain = '') { echo esc_attr($value); }
    }
    if (! function_exists('wp_strip_all_tags')) {
        function wp_strip_all_tags($value) { return strip_tags((string) $value); }
    }
    if (! function_exists('sanitize_html_class')) {
        function sanitize_html_class($value, $fallback = '') { return preg_replace('/[^A-Za-z0-9_-]/', '', (string) $value); }
    }
}

namespace unit\Admin {

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;

class PaymentMethodPanelTest extends Unit
{
    protected function _before(): void
    {
        WordPressStubContext::clear();
        foreach (['add_action', 'add_filter', 'add_shortcode'] as $function) {
            WordPressStubContext::set($function, static function () { return true; });
        }
        WordPressStubContext::set('get_option', static function ($option, $default = false) { return $default; });
        require_once PPCART_PLUGIN_ROOT . 'includes/functions.php';
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    public function testStripePanelRendersWithoutPaypalLocals(): void
    {
        $panel = $this->renderPanel('stripe', [
            ['title' => 'Stripe mode', 'args' => ['id' => '_ppcart_stripe_api', 'name' => 'stripe_mode']],
            ['title' => 'Stripe key', 'args' => ['id' => 'stripe_key']],
        ]);
        $this->assertStringContainsString('name="stripe_mode"', $panel);
        $this->assertStringContainsString('data-pp-stripe-mode-option="test"', $panel);
        $this->assertStringContainsString('Stripe key', $panel);
    }

    public function testPaypalPanelGroupsLiveAndSandboxFields(): void
    {
        $panel = $this->renderPanel('paypal', [
            ['title' => 'PayPal mode', 'args' => ['id' => '_ppcart_paypal_enable_sandbox', 'name' => 'paypal_mode']],
            ['title' => 'Live email', 'args' => ['id' => '_ppcart_paypal_email']],
            ['title' => 'Sandbox email', 'args' => ['id' => '_ppcart_paypal_sandbox_email']],
        ]);
        $this->assertStringContainsString('name="paypal_mode"', $panel);
        $this->assertStringContainsString('data-pp-paypal-mode-field="live"', $panel);
        $this->assertStringContainsString('data-pp-paypal-mode-field="sandbox"', $panel);
        $this->assertStringContainsString('Live email', $panel);
        $this->assertStringContainsString('Sandbox email', $panel);
    }

    public function testOtherPaymentPanelDoesNotNeedPaypalLocals(): void
    {
        $panel = $this->renderPanel('other', [
            ['title' => 'Other key', 'args' => ['id' => 'other_key']],
        ]);
        $this->assertStringContainsString('data-pp-payment-detail="other"', $panel);
        $this->assertStringContainsString('Other key', $panel);
        $this->assertStringNotContainsString('data-pp-paypal-mode', $panel);
    }

    private function renderPanel($method, $section_fields): string
    {
        // Match the caller's inputs; PayPal field lists are local to the template.
        $plugin_name = 'ppcart';
        $section = ['id' => 'ppcart-' . $method, 'title' => $method];
        $rendered_payment_keys = [];
        $payment_method_meta = [];
        $payment_panels_html = '';
        $get_admin_asset_url = static function ($file) { return '/assets/' . $file; };
        $get_payment_method_status = static function () {
            return ['class' => 'is-disabled', 'label' => 'Disabled', 'enabled_label' => 'Enabled', 'disabled_label' => 'Disabled'];
        };
        $render_payment_toggle_control = static function () { echo 'toggle'; };
        $render_setting_field_row = static function ($field, $attributes = []) {
            echo '<tr';
            foreach ($attributes as $name => $value) {
                echo ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
            }
            echo '><td>' . esc_html($field['title']) . '</td></tr>';
        };

        $level = ob_get_level();
        set_error_handler(static function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        ob_start();
        try {
            include PPCART_PLUGIN_ROOT . 'admin/partials/settings-page/sections/payment-method.php';
        } finally {
            ob_end_clean();
            restore_error_handler();
        }
        $this->assertSame($level, ob_get_level());
        return $payment_panels_html;
    }
}
}
