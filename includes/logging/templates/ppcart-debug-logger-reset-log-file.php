<?php

if (! defined('ABSPATH')) {
    exit;
}


if (empty($file_name)) {
    $file_name = $this->default_log_file;
}
$content = $this->get_debug_timestamp() . $this->log_reset_marker;

$this->overwrite = true;
$this->append_to_file($content, $file_name);
$this->clear_rotated_files($file_name);
