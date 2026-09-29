<?php

declare(strict_types=1);

namespace Tests\Integration\Mailchimp;

use lucatume\WPBrowser\TestCase\WPTestCase;
use WP_Error;

class MailchimpApiRequestTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var mixed
     */
    private $previousApiKey;

    /**
     * @var array<int, array{url: string, args: array}>
     */
    private $capturedRequests = [];

    /**
     * @var callable|null
     */
    private $httpInterceptor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousApiKey = get_option('_ppcart_mailchimp_api', false);
        $this->capturedRequests = [];
        $this->httpInterceptor = null;
        delete_option('_ppcart_mailchimp_api');
    }

    protected function tearDown(): void
    {
        if (null !== $this->httpInterceptor) {
            remove_filter('pre_http_request', $this->httpInterceptor);
            $this->httpInterceptor = null;
        }

        if (false === $this->previousApiKey) {
            delete_option('_ppcart_mailchimp_api');
        } else {
            update_option('_ppcart_mailchimp_api', $this->previousApiKey);
        }

        parent::tearDown();
    }

    /**
     * @test-id IT-373
     */
    public function test_IT_373_invalid_api_key_returns_wp_error_without_http(): void
    {
        $this->interceptHttp(
            static function () {
                return [
                    'headers' => [],
                    'body' => '{"id":"should-not-run"}',
                    'response' => [
                        'code' => 200,
                        'message' => 'OK',
                    ],
                    'cookies' => [],
                    'filename' => null,
                ];
            }
        );

        $result = ppcart_mailchimp_api_request('lists');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('ppcart_mailchimp_invalid_api_key', $result->get_error_code());
        $this->assertSame([], $this->capturedRequests);
    }

    /**
     * @test-id IT-373
     */
    public function test_IT_373_put_member_sends_json_body_to_datacenter_url(): void
    {
        ppcart_set_sensitive_option('_ppcart_mailchimp_api', 'abc123-us1');

        $listId = 'list123';
        $email = 'buyer@example.com';
        $memberHash = md5(strtolower($email));
        $endpoint = 'lists/' . ppcart_mailchimp_path_segment($listId) . '/members/' . $memberHash;
        $body = [
            'email_address' => $email,
            'merge_fields' => [
                'FNAME' => 'Ada',
            ],
            'status' => 'subscribed',
            'status_if_new' => 'subscribed',
        ];

        $this->interceptHttp(
            static function () use ($email) {
                return [
                    'headers' => [],
                    'body' => wp_json_encode(
                        [
                            'id' => 'mem_1',
                            'email_address' => $email,
                            'status' => 'subscribed',
                        ]
                    ),
                    'response' => [
                        'code' => 200,
                        'message' => 'OK',
                    ],
                    'cookies' => [],
                    'filename' => null,
                ];
            }
        );

        $result = ppcart_mailchimp_api_request($endpoint, 'PUT', $body);

        $this->assertFalse(is_wp_error($result));
        $this->assertIsObject($result);
        $this->assertSame('mem_1', $result->id);
        $this->assertCount(1, $this->capturedRequests);

        $captured = $this->capturedRequests[0];
        $this->assertSame(
            'https://us1.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberHash,
            $captured['url']
        );
        $this->assertSame('PUT', $captured['args']['method']);

        $sent = json_decode((string) $captured['args']['body'], true);
        $this->assertIsArray($sent);
        $this->assertSame($email, $sent['email_address']);
        $this->assertSame('subscribed', $sent['status']);
        $this->assertSame('Ada', $sent['merge_fields']['FNAME']);
    }

    /**
     * @test-id IT-373
     */
    public function test_IT_373_delete_204_empty_body_returns_success_object(): void
    {
        ppcart_set_sensitive_option('_ppcart_mailchimp_api', 'abc123-us1');

        $this->interceptHttp(
            static function () {
                return [
                    'headers' => [],
                    'body' => '',
                    'response' => [
                        'code' => 204,
                        'message' => 'No Content',
                    ],
                    'cookies' => [],
                    'filename' => null,
                ];
            }
        );

        $result = ppcart_mailchimp_api_request('lists/list123/members/deadbeef', 'DELETE');

        $this->assertFalse(is_wp_error($result));
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame([], get_object_vars($result));
        $this->assertCount(1, $this->capturedRequests);
        $this->assertSame('DELETE', $this->capturedRequests[0]['args']['method']);
    }

    /**
     * @test-id IT-373
     */
    public function test_IT_373_list_fetch_error_returns_wp_error_with_status(): void
    {
        ppcart_set_sensitive_option('_ppcart_mailchimp_api', 'abc123-us1');

        $this->interceptHttp(
            function () {
                return $this->mockResponse(401, '{"title":"API Key Invalid","status":401,"detail":"Your API key may be invalid."}');
            }
        );

        $result = ppcart_mailchimp_api_request('lists', 'GET', [], ['count' => 100]);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('ppcart_mailchimp_api_error', $result->get_error_code());
        $this->assertSame('Your API key may be invalid.', $result->get_error_message());
        $this->assertSame(['status' => 401], $result->get_error_data());
        $this->assertCount(1, $this->capturedRequests);
        $this->assertSame('https://us1.api.mailchimp.com/3.0/lists?count=100', $this->capturedRequests[0]['url']);
        $this->assertSame(
            'Basic ' . base64_encode('publishpress:abc123-us1'),
            $this->capturedRequests[0]['args']['headers']['Authorization']
        );
    }

    /**
     * @test-id IT-373
     */
    public function test_IT_373_subscribe_upserts_member_then_sets_groups_and_tags(): void
    {
        ppcart_set_sensitive_option('_ppcart_mailchimp_api', 'abc123-us1');

        $orderId = $this->factory()->post->create(['post_type' => 'post']);
        $email = 'Buyer@Example.com';
        $memberPath = 'https://us1.api.mailchimp.com/3.0/lists/list123/members/' . md5(strtolower($email));

        $mergeFilter = static function ($merge, $filteredOrderId) use ($orderId) {
            if ((int) $filteredOrderId === (int) $orderId) {
                $merge['SOURCE'] = 'cart';
            }

            return $merge;
        };
        add_filter('ppcart_mailchimp_merge_data', $mergeFilter, 10, 2);

        $this->interceptHttp(
            function () {
                return $this->mockResponse(200, '{"id":"ok"}');
            }
        );

        ppcart_add_remove_mailchimp_subscriber(
            $orderId,
            'mailchimp',
            'subscribed',
            'list123',
            'tag-55',
            'grp1,grp2',
            $email,
            '555-0100',
            'Ada',
            'Lovelace',
            ['mc_phone_tag' => 'PHONE'],
            null
        );

        remove_filter('ppcart_mailchimp_merge_data', $mergeFilter, 10);

        $this->assertCount(3, $this->capturedRequests);

        [$upsert, $groups, $tags] = $this->capturedRequests;

        $this->assertSame($memberPath, $upsert['url']);
        $this->assertSame('PUT', $upsert['args']['method']);
        $upsertBody = json_decode((string) $upsert['args']['body'], true);
        $this->assertSame($email, $upsertBody['email_address']);
        $this->assertSame('subscribed', $upsertBody['status_if_new']);
        $this->assertSame(
            ['FNAME' => 'Ada', 'LNAME' => 'Lovelace', 'PHONE' => '555-0100', 'SOURCE' => 'cart'],
            $upsertBody['merge_fields']
        );

        $this->assertSame($memberPath, $groups['url']);
        $this->assertSame('PATCH', $groups['args']['method']);
        $groupsBody = json_decode((string) $groups['args']['body'], true);
        $this->assertSame(['grp1' => true, 'grp2' => true], $groupsBody['interests']);

        $this->assertSame('https://us1.api.mailchimp.com/3.0/lists/list123/segments/55', $tags['url']);
        $this->assertSame('PATCH', $tags['args']['method']);
        $tagsBody = json_decode((string) $tags['args']['body'], true);
        $this->assertSame(['members_to_add' => [$email]], $tagsBody);
    }

    /**
     * @test-id IT-373
     */
    public function test_IT_373_subscribe_stops_after_rejected_upsert(): void
    {
        ppcart_set_sensitive_option('_ppcart_mailchimp_api', 'abc123-us1');

        $orderId = $this->factory()->post->create(['post_type' => 'post']);

        $this->interceptHttp(
            function () {
                return $this->mockResponse(400, '{"detail":"Invalid Resource"}');
            }
        );

        ppcart_add_remove_mailchimp_subscriber(
            $orderId,
            'mailchimp',
            'subscribed',
            'list123',
            'tag-55',
            'grp1',
            'buyer@example.com',
            '',
            'Ada',
            'Lovelace',
            [],
            null
        );

        $this->assertCount(1, $this->capturedRequests);
        $this->assertSame('PUT', $this->capturedRequests[0]['args']['method']);
        $this->assertEmpty(ppcart_get_post_meta($orderId, 'order_log', true));
    }

    /**
     * @test-id IT-373
     */
    public function test_IT_373_unsubscribe_deletes_member(): void
    {
        ppcart_set_sensitive_option('_ppcart_mailchimp_api', 'abc123-us1');

        $this->interceptHttp(
            function () {
                return $this->mockResponse(204, '');
            }
        );

        ppcart_add_remove_mailchimp_subscriber(
            0,
            'mailchimp',
            'unsubscribed',
            'list123',
            '',
            '',
            'buyer@example.com',
            '',
            '',
            '',
            [],
            null
        );

        $this->assertCount(1, $this->capturedRequests);
        $this->assertSame('DELETE', $this->capturedRequests[0]['args']['method']);
        $this->assertSame(
            'https://us1.api.mailchimp.com/3.0/lists/list123/members/' . md5('buyer@example.com'),
            $this->capturedRequests[0]['url']
        );
    }

    /**
     * @param int    $code HTTP status.
     * @param string $body Response body.
     * @return array
     */
    private function mockResponse(int $code, string $body): array
    {
        return [
            'headers' => [],
            'body' => $body,
            'response' => [
                'code' => $code,
                'message' => '',
            ],
            'cookies' => [],
            'filename' => null,
        ];
    }

    /**
     * @param callable $responder
     */
    private function interceptHttp(callable $responder): void
    {
        $this->httpInterceptor = function ($preempt, $args, $url) use ($responder) {
            $this->capturedRequests[] = [
                'url' => (string) $url,
                'args' => (array) $args,
            ];

            return $responder($preempt, $args, $url);
        };

        add_filter('pre_http_request', $this->httpInterceptor, 10, 3);
    }
}
