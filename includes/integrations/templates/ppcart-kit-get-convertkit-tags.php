<?php

if (! defined('ABSPATH')) {
    exit;
}


$tags = [];
$apikey = ppcart_get_sensitive_option('_ppcart_converkit_api');
$secretKey = ppcart_get_sensitive_option('_ppcart_converkit_secret_key');
if ($apikey && $secretKey) {
    try {
        $url = "https://api.convertkit.com/v3/tags?api_key={$apikey}";
        $response = ppcart_safe_remote_get($url);
        $responseBody = wp_remote_retrieve_body($response);
        $result = json_decode($responseBody, true);
        if (is_array($result) && !is_wp_error($result)) {
            foreach ($result['tags'] as $convertkit_tag) {
                $tag_id = $convertkit_tag['id'];
                $name = $convertkit_tag['name'];
                $tags[$tag_id] = $name;
            }
        }
    } catch (\Exception $e) {
        echo esc_html($e->getMessage()); //add custom message
        return;
    }
}
update_option('ppcart_converkit_tags', $tags);
return $tags;
