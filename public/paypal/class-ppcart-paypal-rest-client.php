<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * HTTP client for the PayPal REST API used by the on-page checkout flow.
 *
 * Every PayPal call of the on-page flow goes through request(). A transport
 * failure is reported as HTTP code 0 so callers treat it like a 5xx.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */
class PPCart_Paypal_Rest_Client
{
    public const TIMEOUT = 20;

    /**
     * @var string sandbox|live
     */
    private $mode;

    /**
     * @var string
     */
    private $client_id;

    /**
     * @var string
     */
    private $secret;

    public function __construct($mode, $client_id, $secret)
    {
        $this->mode      = 'live' === $mode ? 'live' : 'sandbox';
        $this->client_id = trim((string) $client_id);
        $this->secret    = trim((string) $secret);
    }

    /**
     * Client for the mode the merchant selected in the PayPal settings.
     *
     * @return self
     */
    public static function from_settings()
    {
        $mode        = self::current_mode();
        $credentials = self::credentials($mode);

        return new self($mode, $credentials['client_id'], $credentials['secret']);
    }

    /**
     * Client for the mode a record was created in, so a mode switch in the
     * settings does not orphan older records.
     *
     * @param string $gateway_mode test|live, or '' for the current setting.
     * @return self
     */
    public static function for_gateway_mode($gateway_mode)
    {
        if ('live' === $gateway_mode) {
            $mode = 'live';
        } elseif ('test' === $gateway_mode) {
            $mode = 'sandbox';
        } else {
            $mode = self::current_mode();
        }
        $credentials = self::credentials($mode);

        return new self($mode, $credentials['client_id'], $credentials['secret']);
    }

    /**
     * @return string sandbox|live
     */
    public static function current_mode()
    {
        return 'disable' === get_option('_ppcart_paypal_enable_sandbox') ? 'live' : 'sandbox';
    }

    /**
     * @param string $mode sandbox|live
     * @return array{client_id: string, secret: string}
     */
    public static function credentials($mode)
    {
        if ('live' === $mode) {
            return [
                'client_id' => (string) get_option('_ppcart_paypal_client_id', ''),
                'secret'    => (string) ppcart_get_sensitive_option('_ppcart_paypal_secret', ''),
            ];
        }

        return [
            'client_id' => (string) get_option('_ppcart_paypal_sandbox_client_id', ''),
            'secret'    => (string) ppcart_get_sensitive_option('_ppcart_paypal_sandbox_secret', ''),
        ];
    }

    public function mode()
    {
        return $this->mode;
    }

    public function client_id()
    {
        return $this->client_id;
    }

    public function has_credentials()
    {
        return '' !== $this->client_id && '' !== $this->secret;
    }

    public function base_url()
    {
        return 'live' === $this->mode ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Send one request to PayPal.
     *
     * @param string     $method  GET or POST.
     * @param string     $path    Path with a leading slash, query string allowed.
     * @param array|null $body    JSON body for POST. Null sends an empty object.
     * @param array      $headers Extra headers, for example PayPal-Request-Id.
     * @return array{code: int, body: array}
     */
    public function request($method, $path, $body = null, $headers = [])
    {
        $method = strtoupper((string) $method);
        if (! in_array($method, [ 'GET', 'POST' ], true)) {
            return $this->failure($method, $path, [ 'code' => 0, 'body' => [] ], 'unsupported method');
        }

        if (! $this->has_credentials()) {
            return $this->failure($method, $path, [ 'code' => 0, 'body' => [] ], 'missing credentials');
        }

        $result = $this->send($method, $path, $body, $headers, false);
        if (401 === $result['code']) {
            $result = $this->send($method, $path, $body, $headers, true);
        }

        if (0 === $result['code'] || $result['code'] >= 400) {
            return $this->failure($method, $path, $result);
        }

        return $result;
    }

    /**
     * Drop the cached OAuth token for this mode and client ID.
     *
     * @return void
     */
    public function forget_token()
    {
        delete_transient($this->token_key());
    }

    private function send($method, $path, $body, $headers, $fresh_token)
    {
        $token = $this->access_token($fresh_token);
        if ('' === $token) {
            return [ 'code' => 0, 'body' => [] ];
        }

        $args = [
            'timeout' => self::TIMEOUT,
            'headers' => array_merge(
                [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                $headers
            ),
        ];

        $url = $this->base_url() . $path;

        if ('GET' === $method) {
            $response = ppcart_safe_remote_get($url, $args);
        } else {
            $args['body'] = empty($body) ? '{}' : wp_json_encode($body);
            $response     = ppcart_safe_remote_post($url, $args);
        }

        return $this->normalize($response);
    }

    private function normalize($response)
    {
        if (is_wp_error($response)) {
            return [ 'code' => 0, 'body' => [ 'message' => $response->get_error_message() ] ];
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);

        return [
            'code' => (int) wp_remote_retrieve_response_code($response),
            'body' => is_array($decoded) ? $decoded : [],
        ];
    }

    private function access_token($fresh)
    {
        $key = $this->token_key();

        if (! $fresh) {
            $cached = get_transient($key);
            if (is_string($cached) && '' !== $cached) {
                return $cached;
            }
        }

        $response = ppcart_safe_remote_post(
            $this->base_url() . '/v1/oauth2/token',
            [
                'timeout' => self::TIMEOUT,
                'headers' => [
                    'Accept'          => 'application/json',
                    'Accept-Language' => 'en_US',
                    'Content-Type'    => 'application/x-www-form-urlencoded',
                    'Authorization'   => PPCart_Base64::basic_auth_header($this->client_id, $this->secret),
                ],
                'body'    => 'grant_type=client_credentials',
            ]
        );

        $result = $this->normalize($response);
        $token  = isset($result['body']['access_token']) ? (string) $result['body']['access_token'] : '';

        if (200 !== $result['code'] || '' === $token) {
            $this->failure('POST', '/v1/oauth2/token', $result);
            return '';
        }

        $expires_in = isset($result['body']['expires_in']) ? (int) $result['body']['expires_in'] : 0;
        set_transient($key, $token, max(60, $expires_in - 60));

        return $token;
    }

    private function token_key()
    {
        return 'ppcart_pp_token_' . $this->mode . '_' . md5($this->client_id);
    }

    /**
     * Log a failed call without the token, the secret, or payer data.
     *
     * @return array{code: int, body: array}
     */
    private function failure($method, $path, $result, $reason = '')
    {
        global $ppcart_debug_logger;

        $body    = is_array($result['body']) ? $result['body'] : [];
        $details = [];
        if (isset($body['details']) && is_array($body['details'])) {
            foreach ($body['details'] as $detail) {
                if (isset($detail['issue'])) {
                    $details[] = (string) $detail['issue'];
                }
            }
        }

        if (is_object($ppcart_debug_logger) && method_exists($ppcart_debug_logger, 'log_debug')) {
            $ppcart_debug_logger->log_debug(
                'PayPal REST ' . $method . ' ' . strtok((string) $path, '?') . ' failed: ' . wp_json_encode([
                    'code'     => (int) $result['code'],
                    'reason'   => $reason,
                    'name'     => $body['name'] ?? ($body['error'] ?? ''),
                    'message'  => $body['message'] ?? ($body['error_description'] ?? ''),
                    'debug_id' => $body['debug_id'] ?? '',
                    'issues'   => $details,
                ])
            );
        }

        return [ 'code' => (int) $result['code'], 'body' => $body ];
    }
}
