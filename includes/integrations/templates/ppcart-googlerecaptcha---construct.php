<?php

if (! defined('ABSPATH')) {
    exit;
}


$this->service_name = 'googlerecaptcha';
$this->service_label = 'Google reCAPTCHA';


$this->iscaptchakey = false;
$this->grecaptcha_type = get_option("_ppcart_googlerecaptchav2_captcha_type");
$grecaptchav2 = get_option("_ppcart_googlerecaptchav2_site_key");
$grecaptchav3 = get_option("_ppcart_googlerecaptchav3_site_key");

if (!empty($grecaptchav3)) {
    $this->iscaptchakey = true;
    $this->grecaptcha = $grecaptchav3;
    $this->version_type = 'v3';
    $this->site_secret = ppcart_get_sensitive_option('_ppcart_googlerecaptchav3_site_secret');

    if ($score = get_option('_ppcart_' . $this->service_name . '_v3rating')) {
        $this->score = $score;
    } else {
        $this->score = '0.5';
    }
} elseif (!empty($grecaptchav2)) {
    $this->iscaptchakey = true;
    $this->grecaptcha = $grecaptchav2;
    $this->version_type = 'v2';
    $this->site_secret = ppcart_get_sensitive_option('_ppcart_googlerecaptchav2_site_secret');
}

add_filter('_ppcart_integrations_tab_section', [$this, 'settings_section'], 10, 1);
add_filter('_ppcart_integrations_option_list', [$this, 'service_settings']);

if ($this->iscaptchakey !== false) {
    add_action('plugins_loaded', [$this, 'init']);
}
