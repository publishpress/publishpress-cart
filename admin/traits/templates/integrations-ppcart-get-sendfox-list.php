<?php

if (! defined('ABSPATH')) {
    exit;
}


$temp_list = ppcart_sendfox_api_request('lists', ['page' => $page]);
if (
    !empty($temp_list['result']) &&
    $temp_list['result']['current_page'] < $temp_list['result']['last_page']
) {
    if (empty($lists)) {
        if (isset($temp_list['result']) && is_array($temp_list['result'])) {
            $lists['result']['data'] = $temp_list['result']['data'];
        }
    } else {
        foreach ($temp_list['result']['data'] as $data) {
            array_push($lists['result']['data'], $data);
        }
    }
    $page++;
    $temp_list = $this->get_sendfox_list($lists, $page);
}
if (empty($lists)) {
    if (isset($temp_list['result']) && is_array($temp_list['result'])) {
        $lists['result']['data'] = $temp_list['result']['data'];
    }
} else {
    foreach ($temp_list['result']['data'] as $data) {
        array_push($lists['result']['data'], $data);
    }
}

return $lists;
