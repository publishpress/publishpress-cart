<?php

if (! defined('ABSPATH')) {
    exit;
}


$lists = [];
$body = $this->activecampaign_cached_get('/api/3/lists?limit=100', $renew);
if (! empty($body['lists'])) {
    foreach ($body['lists'] as $value) {
        $list_id = $value['id'];
        $name = $value['name'];
        if (! empty($list_id)) {
            $lists['list-' . $list_id] = $name;
        }
    }
}
update_option('ppcart_activecampaign_lists', $lists);
return $lists;
