<?php

namespace {
    if (! function_exists('add_query_arg')) {
        /**
         * @param array  $args Query args.
         * @param string $url  Base URL.
         * @return string
         */
        function add_query_arg($args, $url)
        {
            $separator = (false === strpos($url, '?')) ? '?' : '&';

            return $url . $separator . http_build_query($args);
        }
    }

    if (! function_exists('wp_safe_remote_request')) {
        /**
         * @param string $url  Request URL.
         * @param array  $args Request args.
         * @return array|WP_Error
         */
        function wp_safe_remote_request($url, $args = [])
        {
            return \Tests\Support\WordPressStubContext::invoke('wp_safe_remote_request', func_get_args());
        }
    }

    if (! function_exists('wp_remote_retrieve_response_code')) {
        /**
         * @param array|WP_Error $response HTTP response.
         * @return int|string
         */
        function wp_remote_retrieve_response_code($response)
        {
            return is_array($response) && isset($response['response']['code']) ? $response['response']['code'] : '';
        }
    }

    if (! function_exists('wp_remote_retrieve_body')) {
        /**
         * @param array|WP_Error $response HTTP response.
         * @return string
         */
        function wp_remote_retrieve_body($response)
        {
            return is_array($response) && isset($response['body']) ? $response['body'] : '';
        }
    }
}

namespace unit\Mailchimp {

use Codeception\Test\Unit;
use PPCart_Secrets;
use Tests\Support\WordPressStubContext;
use UnitTester;
use WP_Error;

class MailchimpApiRequestTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var string
     */
    private $mailchimpKey = '';

    /**
     * @var array<int, array{url: string, args: array}>
     */
    private $requests = [];

    /**
     * @var array|WP_Error
     */
    private $nextResponse = [];

    protected function _before(): void
    {
        WordPressStubContext::clear();
        $this->mailchimpKey = 'abc123-us1';
        $this->requests = [];
        $this->nextResponse = $this->response(200, '{}');

        WordPressStubContext::set('apply_filters', static function ($hook, $value) {
            return $value;
        });
        WordPressStubContext::set('add_query_arg', static function ($args, $url) {
            $separator = (false === strpos($url, '?')) ? '?' : '&';

            return $url . $separator . http_build_query($args);
        });
        WordPressStubContext::set(
            'get_option',
            function ($option_name, $default = false) {
                return '_ppcart_mailchimp_api' === $option_name ? $this->mailchimpKey : $default;
            }
        );
        WordPressStubContext::set(
            'wp_safe_remote_request',
            function ($url, $args = []) {
                $this->requests[] = [
                    'url' => (string) $url,
                    'args' => (array) $args,
                ];

                return $this->nextResponse;
            }
        );

        $this->ensureSecretsHelpersLoaded();

        if (! function_exists('ppcart_mailchimp_api_request')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions/mailchimp-api.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-359
     */
    public function test_UT_359_list_fetch_sends_authenticated_get_to_datacenter(): void
    {
        $this->nextResponse = $this->response(200, '{"lists":[{"id":"l1","name":"Buyers"}]}');

        $result = ppcart_mailchimp_api_request('lists', 'GET', [], ['count' => 100]);

        $this->assertFalse(is_wp_error($result));
        $this->assertSame('l1', $result->lists[0]->id);
        $this->assertCount(1, $this->requests);

        $request = $this->requests[0];
        $this->assertSame('https://us1.api.mailchimp.com/3.0/lists?count=100', $request['url']);
        $this->assertSame('GET', $request['args']['method']);
        $this->assertSame(
            'Basic ' . base64_encode('publishpress:abc123-us1'),
            $request['args']['headers']['Authorization']
        );
        $this->assertSame('application/json', $request['args']['headers']['Content-Type']);
        $this->assertArrayNotHasKey('body', $request['args']);
    }

    /**
     * @test-id UT-359
     */
    public function test_UT_359_error_status_returns_wp_error_with_mailchimp_detail(): void
    {
        $this->nextResponse = $this->response(
            400,
            '{"title":"Member Exists","status":400,"detail":"buyer@example.com is already a list member."}'
        );

        $result = ppcart_mailchimp_api_request('lists/l1/members', 'POST', ['email_address' => 'buyer@example.com']);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('ppcart_mailchimp_api_error', $result->get_error_code());
        $this->assertSame('buyer@example.com is already a list member.', $result->get_error_message());
        $this->assertSame(['status' => 400], $result->get_error_data());
        $this->assertSame('POST', $this->requests[0]['args']['method']);
        $this->assertSame(
            ['email_address' => 'buyer@example.com'],
            json_decode((string) $this->requests[0]['args']['body'], true)
        );
    }

    /**
     * @test-id UT-359
     */
    public function test_UT_359_error_status_without_detail_uses_generic_message(): void
    {
        $this->nextResponse = $this->response(500, '<html>Server error</html>');

        $result = ppcart_mailchimp_api_request('lists');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('ppcart_mailchimp_api_error', $result->get_error_code());
        $this->assertSame('Mailchimp rejected the request.', $result->get_error_message());
        $this->assertSame(['status' => 500], $result->get_error_data());
    }

    /**
     * @test-id UT-359
     */
    public function test_UT_359_non_json_success_body_returns_invalid_response_error(): void
    {
        $this->nextResponse = $this->response(200, 'not json');

        $result = ppcart_mailchimp_api_request('lists');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('ppcart_mailchimp_invalid_response', $result->get_error_code());
    }

    /**
     * @test-id UT-359
     */
    public function test_UT_359_transport_error_is_returned_unchanged(): void
    {
        $transportError = new WP_Error('http_request_failed', 'cURL error 28: timed out');
        $this->nextResponse = $transportError;

        $result = ppcart_mailchimp_api_request('lists');

        $this->assertSame($transportError, $result);
    }

    /**
     * @test-id UT-359
     */
    public function test_UT_359_invalid_datacenter_suffix_sends_no_request(): void
    {
        $this->mailchimpKey = 'abc123-us1.evil.example/';

        $result = ppcart_mailchimp_api_request('lists');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('ppcart_mailchimp_invalid_api_key', $result->get_error_code());
        $this->assertSame([], $this->requests);
    }

    /**
     * @param int    $code HTTP status.
     * @param string $body Response body.
     * @return array
     */
    private function response(int $code, string $body): array
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

    private function ensureSecretsHelpersLoaded(): void
    {
        if (function_exists('ppcart_get_sensitive_option')) {
            return;
        }

        if (! defined('AUTH_KEY')) {
            define('AUTH_KEY', 'unit-test-auth-key-for-mailchimp-api');
        }

        require_once PPCART_PLUGIN_ROOT . 'includes/logging/class-ppcart-debug-log-viewer.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-secrets.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-secrets-functions.php';

        $reflection = new \ReflectionClass(PPCart_Secrets::class);
        if ($reflection->hasProperty('alloptions_decrypt_done')) {
            $prop = $reflection->getProperty('alloptions_decrypt_done');
            $prop->setAccessible(true);
            $prop->setValue(null, false);
        }
        if ($reflection->hasProperty('sensitive_option_names_cache')) {
            $cache = $reflection->getProperty('sensitive_option_names_cache');
            $cache->setAccessible(true);
            $cache->setValue(null, null);
        }
    }
}
}
