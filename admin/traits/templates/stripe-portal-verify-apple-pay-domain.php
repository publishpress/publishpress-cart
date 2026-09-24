<?php

if (! defined('ABSPATH')) {
    exit;
}


try {
    if (! function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $dir = trailingslashit(get_home_path()) . '.well-known/';
    $file = $dir . 'apple-developer-merchantid-domain-association';

    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }

    $verification_response = ppcart_safe_remote_get(
        'https://stripe.com/files/apple-pay/apple-developer-merchantid-domain-association',
        [
            'headers' => [ 'Authorization' => 'Bearer ' . $stripe->getApiKey() ],
            'timeout' => 3,
        ]
    );

    if (is_wp_error($verification_response)) {
        set_transient('ppcart_express_payment_settings_error', [
            'message' => 'Express Payment Apple pay: Failed to fetch Apple Pay verification file content ' . $verification_response->get_error_message(),
            'type' => 'error',
        ], 30);
        return false;
    }

    $content = wp_remote_retrieve_body($verification_response);

    global $wp_filesystem;

    WP_Filesystem();

    if (! $wp_filesystem || ! $wp_filesystem->put_contents($file, $content, FS_CHMOD_FILE)) {
        set_transient('ppcart_express_payment_settings_error', [
            'message' => 'Express Payment Apple pay: Failed to write Apple Pay verification file.',
            'type' => 'error',
        ], 30);
        return false;
    }

    // Register and verify the domain with Stripe
    $stripe->request('POST', '/v1/apple_pay/domains', ['domain_name' => $domain_name], []);
    $response = $stripe->request('POST', "/v1/payment_method_domains/$domainId/validate", [], []);

    if (isset($response['apple_pay']['status']) && $response['apple_pay']['status'] === 'active') {
        return true;
    } else {
        set_transient('ppcart_express_payment_settings_error', [
           'message' => 'Express Payment Apple pay: Domain ownership verification failed. Response: ' . wp_json_encode($response),
           'type' => 'error',
        ], 30);
        return false;
    }
} catch (\Exception $e) {
    set_transient('ppcart_express_payment_settings_error', [
        'message' => 'Express Payment Apple pay: Exception during Apple Pay domain verification:: ' . $e->getMessage(),
        'type' => 'error',
    ], 30);
}
