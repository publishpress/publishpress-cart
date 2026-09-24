<?php

if (!defined('ABSPATH')) {
    exit;
}

$url = esc_url_raw((string) $url);
$scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
$host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
$port = wp_parse_url($url, PHP_URL_PORT);
$url_path = (string) wp_parse_url($url, PHP_URL_PATH);

if ('' === $scheme || '' === $host) {
    return '';
}

$normalized = $scheme . '://' . $host;
if (null !== $port) {
    $normalized .= ':' . (string) $port;
}

$normalized .= '/' . ltrim(untrailingslashit($url_path), '/');

return untrailingslashit($normalized);
