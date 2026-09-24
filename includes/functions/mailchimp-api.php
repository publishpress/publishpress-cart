<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Returns validated Mailchimp API connection details.
 *
 * @return array|false
 */
function ppcart_get_mailchimp_api_config()
{
    $api_key = (string) ppcart_get_sensitive_option('_ppcart_mailchimp_api');
    $parts = explode('-', $api_key, 2);

    if (2 !== count($parts) || '' === $parts[0] || ! preg_match('/^[a-z0-9]+$/i', $parts[1])) {
        return false;
    }

    return [
        'api_key' => $api_key,
        'base_url' => 'https://' . strtolower($parts[1]) . '.api.mailchimp.com/3.0/',
    ];
}

/**
 * Whether Mailchimp API credentials are configured.
 *
 * @return bool
 */
function ppcart_has_mailchimp_api()
{
    return false !== ppcart_get_mailchimp_api_config();
}

/**
 * Sends an authenticated request to the Mailchimp Marketing API.
 *
 * @param string $endpoint Relative API endpoint.
 * @param string $method   HTTP method.
 * @param array  $body     JSON request body.
 * @param array  $query    Query string values.
 *
 * @return object|WP_Error
 */
function ppcart_mailchimp_api_request($endpoint, $method = 'GET', $body = [], $query = [])
{
    $config = ppcart_get_mailchimp_api_config();
    if (false === $config) {
        return new WP_Error('ppcart_mailchimp_invalid_api_key', __('The Mailchimp API key is invalid.', 'publishpress-cart'));
    }

    $url = $config['base_url'] . ltrim($endpoint, '/');
    if (! empty($query)) {
        $url = add_query_arg($query, $url);
    }

    $args = [
        'method' => strtoupper($method),
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode('publishpress:' . $config['api_key']),
            'Content-Type' => 'application/json',
        ],
        // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- External API requests can require more than 3 seconds.
        'timeout' => 30,
    ];

    if (! empty($body)) {
        $args['body'] = wp_json_encode($body);
    }

    $response = wp_safe_remote_request($url, $args);
    if (is_wp_error($response)) {
        return $response;
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $response_body = (string) wp_remote_retrieve_body($response);
    if ($status < 200 || $status >= 300) {
        $decoded_error = json_decode($response_body);
        $message = isset($decoded_error->detail) ? (string) $decoded_error->detail : __('Mailchimp rejected the request.', 'publishpress-cart');

        return new WP_Error('ppcart_mailchimp_api_error', $message, ['status' => $status]);
    }

    if ('' === $response_body) {
        return (object) [];
    }

    $decoded = json_decode($response_body);
    if (JSON_ERROR_NONE !== json_last_error() || ! is_object($decoded)) {
        return new WP_Error('ppcart_mailchimp_invalid_response', __('Mailchimp returned an invalid response.', 'publishpress-cart'));
    }

    return $decoded;
}

/**
 * Encodes one Mailchimp URL path segment.
 *
 * @param mixed $value Path segment.
 *
 * @return string
 */
function ppcart_mailchimp_path_segment($value)
{
    return rawurlencode((string) $value);
}
