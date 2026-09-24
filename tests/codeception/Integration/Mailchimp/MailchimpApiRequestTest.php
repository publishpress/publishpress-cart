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
