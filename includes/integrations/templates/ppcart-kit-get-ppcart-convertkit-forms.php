<?php

if (! defined('ABSPATH')) {
    exit;
}


$options = ['' => '-- select form --'];
$tags = get_option('ppcart_convertkit_forms');
if (!empty($tags)) {
    foreach ($tags as $key => $value) {
        $options[$key] = $value;
    }
} else {
    $options = ['' => __('-- none found --', 'publishpress-cart')];
}
return $options;
