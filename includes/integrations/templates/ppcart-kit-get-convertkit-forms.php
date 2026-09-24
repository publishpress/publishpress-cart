<?php

if (! defined('ABSPATH')) {
    exit;
}


if (!$renew && $tags = get_option('ppcart_convertkit_forms')) {
    return $tags;
} else {
    $tags = [];
    $apikey = ppcart_get_sensitive_option('_ppcart_converkit_api');
    $secretKey = ppcart_get_sensitive_option('_ppcart_converkit_secret_key');

    if ($apikey && $secretKey) {
        try {
            $url = "https://api.convertkit.com/v3/forms?api_key={$apikey}";
            $response = ppcart_safe_remote_get($url);
            if (is_wp_error($response)) {
                return;
            }
            $responseBody = wp_remote_retrieve_body($response);
            $result = json_decode($responseBody, true);

            if (is_array($result) && !is_wp_error($result)) {
                if (isset($result['forms'])) {
                    foreach ($result['forms'] as $convertkit_form) {
                        $form_id = $convertkit_form['id'];
                        $name = $convertkit_form['name'];
                        $tags[$form_id] = $name;
                    }
                } else {
                }
            }
        } catch (\Exception $e) {
            echo esc_html($e->getMessage());
            return;
        }
    }
    update_option('ppcart_convertkit_forms', $tags);
    return $tags;
}
