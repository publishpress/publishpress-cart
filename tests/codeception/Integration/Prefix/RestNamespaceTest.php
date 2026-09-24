<?php

declare(strict_types=1);

namespace Tests\Integration\Prefix;

use lucatume\WPBrowser\TestCase\WPTestCase;

class RestNamespaceTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    private const COMPATIBILITY_MODE_OPTION = '_ppcart_compatibility_mode';

    protected function setUp(): void
    {
        parent::setUp();

        delete_option(self::COMPATIBILITY_MODE_OPTION);

        if (! did_action('init')) {
            do_action('init');
        }

        if (! did_action('rest_api_init')) {
            do_action('rest_api_init');
        }
    }

    protected function tearDown(): void
    {
        delete_option(self::COMPATIBILITY_MODE_OPTION);

        parent::tearDown();
    }

    /**
     * @test-id IT-329
     */
    public function test_IT_329_gutenberg_rest_routes_use_publishpress_cart_v1(): void
    {
        $routes = rest_get_server()->get_routes();

        $this->assertArrayHasKey('/publishpress-cart/v1/checkout-block/products', $routes);
        $this->assertArrayHasKey('/publishpress-cart/v1/checkout-block/preview', $routes);
        $this->assertArrayHasKey('/publishpress-cart/v1/account-block/detail', $routes);
    }

    /**
     * @test-id IT-329
     */
    public function test_IT_329_leftover_sc_v1_routes_stay_unregistered_with_compat_off(): void
    {
        $this->assertNoLeftoverRestNamespace(rest_get_server()->get_routes());
    }

    /**
     * @test-id IT-329
     */
    public function test_IT_329_leftover_sc_v1_routes_stay_unregistered_with_compat_on(): void
    {
        update_option(self::COMPATIBILITY_MODE_OPTION, '1');
        do_action('rest_api_init');

        $this->assertNoLeftoverRestNamespace(rest_get_server()->get_routes());
        $this->assertArrayHasKey(
            '/publishpress-cart/v1/checkout-block/products',
            rest_get_server()->get_routes()
        );
    }

    /**
     * @param array<string, mixed> $routes
     */
    private function assertNoLeftoverRestNamespace(array $routes): void
    {
        foreach (array_keys($routes) as $route) {
            $this->assertStringStartsNotWith(
                '/sc/v1',
                (string) $route,
                $route . ' is a leftover REST namespace'
            );
        }
    }
}
