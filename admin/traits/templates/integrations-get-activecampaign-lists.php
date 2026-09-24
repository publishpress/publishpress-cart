<?php

if (! defined('ABSPATH')) {
    exit;
}


$lists = [];
$activecampaign = $this->activecampaign_authentication();
if ($activecampaign) {
    $response = wp_safe_remote_get($activecampaign['url'] . '/api/3/lists?limit=100', [
        'headers' => [
            'Api-Token' => $activecampaign['key'],
        ],
        'timeout' => 3,
    ]);
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['lists']) && !empty($body['lists'])) {
            foreach ($body['lists'] as $value) {
                $list_id = $value['id'];
                $name = $value['name'];
                if (!empty($list_id)) {
                    $lists['list-' . $list_id] = $name;
                }
            }
        }
    }
}
update_option('ppcart_activecampaign_lists', $lists);
return $lists;
