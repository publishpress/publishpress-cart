<?php

if (! defined('ABSPATH')) {
    exit;
}


$options = ['' => '-- select tag --'];
$tags = get_option('ppcart_converkit_tags');
if (!empty($tags)) {
    foreach ($tags as $key => $value) {
        $options[$key] = $value;
    }
} else {
    $options = ['' => __('-- none found --', 'publishpress-cart')];
}
return $options;
