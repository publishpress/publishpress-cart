<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! $this->debug_enabled) {
    return;
}
if (function_exists('ppcart_redact_secrets_from_text')) {
    $message = ppcart_redact_secrets_from_text((string) $message);
}
$content  = $this->get_debug_timestamp();//Timestamp
$content .= $this->get_debug_status($level);//Debug status
$content .= ' : ';
$content .= $message . "\n";
$content .= $this->get_section_break($section_break);
$this->append_to_file($content, $file_name);
