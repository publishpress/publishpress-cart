<?php

declare(strict_types=1);

namespace Tests\Integration\CheckoutBlock;

use Tests\Support\Integration\CheckoutBlockTestCase;

class RestRoutesTest extends CheckoutBlockTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureRestApiInit();
    }

    public function test_IT_144_checkout_block_products_rest_route_is_registered(): void
    {
        $routes = rest_get_server()->get_routes();

        $this->assertArrayHasKey('/publishpress-cart/v1/checkout-block/products', $routes);
    }

    public function test_IT_145_checkout_block_preview_rest_route_is_registered(): void
    {
        $routes = rest_get_server()->get_routes();

        $this->assertArrayHasKey('/publishpress-cart/v1/checkout-block/preview', $routes);
    }

    public function test_IT_146_account_block_detail_rest_route_is_registered(): void
    {
        $routes = rest_get_server()->get_routes();

        $this->assertArrayHasKey('/publishpress-cart/v1/account-block/detail', $routes);
    }

    public function test_IT_147_products_rest_route_denies_anonymous_visitors(): void
    {
        $routes = rest_get_server()->get_routes();
        $productsRoute = $routes['/publishpress-cart/v1/checkout-block/products'][0] ?? null;
        $permission = $productsRoute && isset($productsRoute['permission_callback'])
            ? call_user_func($productsRoute['permission_callback'])
            : null;

        $this->assertFalse((bool) $permission);
    }

    public function test_IT_148_account_detail_rest_route_requires_a_logged_in_customer(): void
    {
        $request = new \WP_REST_Request('GET', '/publishpress-cart/v1/account-block/detail');
        $request->set_param('type', 'order');
        $request->set_param('id', 1);
        $response = rest_do_request($request);

        $this->assertContains($response->get_status(), array(401, 403));
    }

    public function test_IT_149_products_rest_route_rejects_users_lacking_product_editing_capability(): void
    {
        $userId = wp_insert_user(
            array(
                'user_login' => 'ppcart_block_limited_' . wp_generate_uuid4(),
                'user_pass' => wp_generate_password(),
                'user_email' => 'ppcart-block-limited-' . wp_generate_uuid4() . '@example.invalid',
                'role' => 'author',
            )
        );

        if (is_wp_error($userId)) {
            $this->markTestSkipped('Limited capability REST permission check requires creating a temporary author user.');
        }

        $this->temporaryUserId = (int) $userId;
        wp_set_current_user($this->temporaryUserId);

        $routes = rest_get_server()->get_routes();
        $productsRoute = $routes['/publishpress-cart/v1/checkout-block/products'][0] ?? null;
        $permission = $productsRoute && isset($productsRoute['permission_callback'])
            ? call_user_func($productsRoute['permission_callback'])
            : null;

        $this->assertFalse((bool) $permission);
    }

    public function test_IT_150_preview_rest_route_rejects_users_without_product_access(): void
    {
        $productId = $this->createStandardProduct();
        if (! $productId) {
            $this->markTestSkipped('Preview permission denial check requires at least one product.');
        }

        $userId = wp_insert_user(
            array(
                'user_login' => 'ppcart_block_limited_' . wp_generate_uuid4(),
                'user_pass' => wp_generate_password(),
                'user_email' => 'ppcart-block-limited-' . wp_generate_uuid4() . '@example.invalid',
                'role' => 'author',
            )
        );

        if (is_wp_error($userId)) {
            $this->markTestSkipped('Preview permission denial check requires creating a temporary author user.');
        }

        $this->temporaryUserId = (int) $userId;
        wp_set_current_user($this->temporaryUserId);

        $request = new \WP_REST_Request('GET', '/publishpress-cart/v1/checkout-block/preview');
        $request->set_param('pid', (string) $productId);
        $response = rest_do_request($request);

        $this->assertTrue($response->is_error());
    }

    public function test_IT_151_preview_rest_route_is_available_to_an_authenticated_editor(): void
    {
        $productId = $this->createStandardProduct();
        if (! $productId) {
            $this->markTestSkipped('Rendered REST preview requires at least one product.');
        }

        $capableUserId = $this->findCapableProductEditorUserId();
        if (! $capableUserId) {
            $this->markTestSkipped('Rendered REST preview requires one editor user.');
        }

        wp_set_current_user($capableUserId);

        $request = new \WP_REST_Request('GET', '/publishpress-cart/v1/checkout-block/preview');
        $request->set_param('pid', (string) $productId);
        $request->set_param('template', 'normal');
        $request->set_param('text_settings', wp_json_encode(array('contactInfoHeading' => 'REST Buyer Details')));
        $response = rest_do_request($request);

        $this->assertFalse($response->is_error());
    }

    public function test_IT_152_preview_rest_route_returns_rendered_checkout_html(): void
    {
        $productId = $this->createStandardProduct();
        $capableUserId = $this->findCapableProductEditorUserId();

        if (! $productId || ! $capableUserId) {
            $this->markTestSkipped('Rendered REST preview requires a product and editor user.');
        }

        wp_set_current_user($capableUserId);

        $request = new \WP_REST_Request('GET', '/publishpress-cart/v1/checkout-block/preview');
        $request->set_param('pid', (string) $productId);
        $request->set_param('template', 'normal');
        $response = rest_do_request($request);
        $data = $response->get_data();

        $this->assertNotEmpty($data['html']);
        $this->assertStringContainsString('ppcart-form-container', $data['html']);
    }

    public function test_IT_153_preview_rest_route_applies_checkout_block_text_settings(): void
    {
        $productId = $this->createStandardProduct();
        $capableUserId = $this->findCapableProductEditorUserId();

        if (! $productId || ! $capableUserId) {
            $this->markTestSkipped('Rendered REST preview requires a product and editor user.');
        }

        wp_set_current_user($capableUserId);

        $request = new \WP_REST_Request('GET', '/publishpress-cart/v1/checkout-block/preview');
        $request->set_param('pid', (string) $productId);
        $request->set_param('template', 'normal');
        $request->set_param('text_settings', wp_json_encode(array('contactInfoHeading' => 'REST Buyer Details')));
        $response = rest_do_request($request);
        $data = $response->get_data();

        $this->assertStringContainsString('REST Buyer Details', $data['html']);
    }

    public function test_IT_154_products_rest_route_honors_filtered_product_post_types(): void
    {
        $this->createFilteredProduct();
        $capableUserId = $this->findCapableProductEditorUserId();

        if (! $this->temporaryFilteredProductId || ! $capableUserId) {
            $this->markTestSkipped('Filtered product REST check requires a filtered product and editor user.');
        }

        wp_set_current_user($capableUserId);
        add_filter('ppcart_product_post_type', 'ppcart_integration_include_filtered_product_type');
        add_filter('ppcart_setup_product_post_type', 'ppcart_integration_include_filtered_product_type');

        $request = new \WP_REST_Request('GET', '/publishpress-cart/v1/checkout-block/products');
        $response = rest_do_request($request);
        $products = $response->get_data();
        $productIds = is_array($products) ? wp_list_pluck($products, 'id') : array();

        remove_filter('ppcart_product_post_type', 'ppcart_integration_include_filtered_product_type');
        remove_filter('ppcart_setup_product_post_type', 'ppcart_integration_include_filtered_product_type');

        $this->assertContains(
            absint($this->temporaryFilteredProductId),
            array_map('absint', $productIds)
        );
    }

    public function test_IT_155_stripe_service_builds_a_client_when_keys_are_usable_but_webhook_id_is_missing(): void
    {
        if (! class_exists('PPCart_Stripe') || ! class_exists('PublishPress\\Stripe\\StripeClient')) {
            $this->markTestSkipped('Stripe service checks require the Stripe integration classes.');
        }

        $previous = $this->backupStripeOptions();
        update_option('_ppcart_stripe_api', 'test');
        update_option('_ppcart_stripe_test_sk', 'sk_test_ncscartblockcredential000000');
        update_option('_ppcart_stripe_test_pk', 'pk_test_ncscartblockcredential000000');
        delete_option('_ppcart_stripe_test_webhook_id');

        try {
            $client = \PPCart_Stripe::instance()->stripe();
            $this->assertInstanceOf(\PublishPress\Stripe\StripeClient::class, $client);
        } catch (\Throwable $exception) {
            $this->fail($exception->getMessage());
        } finally {
            $this->restoreStripeOptions($previous);
        }
    }

    public function test_IT_156_stripe_service_returns_false_when_account_credentials_are_unusable(): void
    {
        if (! class_exists('PPCart_Stripe') || ! class_exists('PublishPress\\Stripe\\StripeClient')) {
            $this->markTestSkipped('Stripe service checks require the Stripe integration classes.');
        }

        $previous = $this->backupStripeOptions();
        update_option('_ppcart_stripe_api', 'test');
        update_option('_ppcart_stripe_test_sk', '');

        try {
            $client = \PPCart_Stripe::instance()->stripe();
            $this->assertFalse($client);
        } catch (\Throwable $exception) {
            $this->fail($exception->getMessage());
        } finally {
            $this->restoreStripeOptions($previous);
        }
    }

    /**
     * @return int
     */
    private function findCapableProductEditorUserId(): int
    {
        foreach (get_users(array('number' => 20, 'fields' => array('ID'))) as $user) {
            wp_set_current_user($user->ID);
            if (ppcart_user_can('edit_ppcart_products')) {
                return (int) $user->ID;
            }
        }

        return 0;
    }

    /**
     * @return array<string, array{exists: bool, value: mixed}>
     */
    private function backupStripeOptions(): array
    {
        $keys = array(
            '_sc_stripe_api',
            '_sc_stripe_test_sk',
            '_sc_stripe_test_pk',
            '_sc_stripe_test_webhook_id',
        );
        $previous = array();

        foreach ($keys as $key) {
            $previous[$key] = array(
                'exists' => false !== get_option($key, false),
                'value' => get_option($key),
            );
        }

        return $previous;
    }

    /**
     * @param array<string, array{exists: bool, value: mixed}> $previous
     * @return void
     */
    private function restoreStripeOptions(array $previous): void
    {
        foreach ($previous as $key => $option) {
            if ($option['exists']) {
                update_option($key, $option['value']);
            } else {
                delete_option($key);
            }
        }
    }
}
