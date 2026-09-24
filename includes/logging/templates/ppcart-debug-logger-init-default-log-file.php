<?php

if (! defined('ABSPATH')) {
    exit;
}


if (!$file = get_option('_ppcart_log_file')) {
    $this->default_log_file      = wp_generate_uuid4() . '-log.txt';
    $file = $this->default_log_file;
    update_option('_ppcart_log_file', $file);
    $this->reset_log_file();
} else {
    $this->default_log_file = $file;
}
