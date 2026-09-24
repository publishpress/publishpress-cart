<?php

if (! defined('ABSPATH')) {
    exit;
}


if (empty($file_name)) {
    $file_name = $this->default_log_file;
}
$log_file = $this->get_log_file_path($file_name);
if (! file_exists($log_file) && ! file_exists($this->get_rotated_file_path($file_name, 1))) {
    echo 'Log file is empty';
    exit();
}
header('Content-Type: text/plain');

for ($i = self::MAX_ROTATED_FILES; $i >= 1; $i--) {
    $this->stream_log_file($this->get_rotated_file_path($file_name, $i));
}
$this->stream_log_file($log_file);
exit();
