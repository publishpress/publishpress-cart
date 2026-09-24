<?php

if (! defined('ABSPATH')) {
    exit;
}


if (!$renew && $lists = get_option('ppcart_sendfox_lists')) {
    return $lists;
} else {
    $lists = $this->get_sendfox_list();

    if (
        (isset($lists['status']) && $lists['status'] === 'error') ||
        empty($lists['result']) ||
        empty($lists['result']['data'])
    ) {
        // no results
        delete_option('ppcart_sendfox_lists');
        return $lists;
    } else {
        $sf_lists = [];
        foreach ($lists['result']['data'] as $l) {
            $sf_lists[$l['id']] = $l['name'];
        }

        update_option('ppcart_sendfox_lists', $sf_lists);
        return $sf_lists;
    }
}
