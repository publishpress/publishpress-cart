<?php

namespace unit\StripePrefixedSdk;

use Codeception\Test\Unit;
use PublishPress\Stripe\Collection;
use PublishPress\Stripe\SearchResult;
use PublishPress\Stripe\StripeObject;
use UnitTester;

$autoload = PPCART_PLUGIN_ROOT . 'lib/vendor/publishpress/stripe-php/lib/autoload.php';
if (! class_exists(\PublishPress\Stripe\StripeClient::class, false) && is_readable($autoload)) {
    require_once $autoload;
}

if (! class_exists(\PPCart_Stripe_Client::class, false)) {
    require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-stripe-client.php';
}

class PPCartStripeClientHarness extends \PPCart_Stripe_Client
{
    public $payload;

    public function request($method, $path, $params, $opts)
    {
        return $this->payload;
    }
}

class StripeListHelperTest extends Unit
{
    /** @var UnitTester */
    protected $tester;

    protected function _before(): void
    {
        $autoload = PPCART_PLUGIN_ROOT . 'lib/vendor/publishpress/stripe-php/lib/autoload.php';
        if (! class_exists(\PublishPress\Stripe\StripeClient::class, false) && is_readable($autoload)) {
            require_once $autoload;
        }

        if (! class_exists(\PPCart_Stripe_Client::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-stripe-client.php';
        }
    }

    /**
     * @test-id UT-346
     */
    public function test_UT_346_all_promotes_stripeobject_list_to_collection(): void
    {
        $client = new PPCartStripeClientHarness('sk_test_123');
        $client->payload = StripeObject::constructFrom(
            [
                'object'   => 'list',
                'url'      => '/v1/webhook_endpoints',
                'has_more' => false,
                'data'     => [
                    [
                        'id'     => 'we_123',
                        'object' => 'webhook_endpoint',
                        'url'    => 'https://example.com/ppcart-webhook/stripe',
                    ],
                ],
            ]
        );

        $result = $client->webhookEndpoints->all(['limit' => 100]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result->data);
        $this->assertSame('we_123', $result->data[0]->id);
        $this->assertSame('https://example.com/ppcart-webhook/stripe', $result->data[0]->url);
    }

    /**
     * @test-id UT-346
     */
    public function test_UT_346_search_promotes_stripeobject_to_search_result(): void
    {
        $client = new PPCartStripeClientHarness('sk_test_123');
        $client->payload = StripeObject::constructFrom(
            [
                'object'   => 'search_result',
                'url'      => '/v1/invoices/search',
                'has_more' => false,
                'data'     => [
                    [
                        'id'     => 'in_123',
                        'object' => 'invoice',
                    ],
                ],
            ]
        );

        $result = $client->invoices->search(
            [
                'query' => 'subscription:"sub_123"',
            ]
        );

        $this->assertInstanceOf(SearchResult::class, $result);
        $this->assertCount(1, $result->data);
        $this->assertSame('in_123', $result->data[0]->id);
    }
}
