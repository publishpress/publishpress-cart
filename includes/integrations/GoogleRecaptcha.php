<?php

namespace PublishPress\Cart;

if (!defined('ABSPATH')) {
    exit;
}

class GoogleRecaptcha
{
    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $plugin_name    The ID of this plugin.
     */

    private $service_name;
    private $service_label;
    private $version_type;
    private $site_secret;
    private $score;
    private $iscaptchakey;
    private $grecaptcha;
    private $grecaptcha_type;

    public function __construct()
    {
        include __DIR__ . '/templates/ppcart-googlerecaptcha---construct.php';
    }

    public function init()
    {
        add_action('ppcart_before_buy_button', [$this, 'gen_recaptcha_html'], 10, 1);
        add_action('ppcart_before_create_main_order', [$this, 'google_recaptcha_validation']);
    }

    public function google_recaptcha_validation()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-googlerecaptcha-google-recaptcha-validation.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function reCaptcha($recaptcha, $gsecretk)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-googlerecaptcha-recaptcha.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function settings_section($intigrations)
    {

        $intigrations[$this->service_name] = $this->service_label;
        $intigrations[$this->service_name . '-v2'] = $this->service_label . ' v2 Settings';
        $intigrations[$this->service_name . '-v3'] = $this->service_label . ' v3 Settings';
        return $intigrations;
    }

    public function service_settings($options)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-googlerecaptcha-service-settings.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function add_service($options)
    {
        $options[$this->service_name] = $this->service_label;
        return $options;
    }

    public function add_front_scripts()
    {
        if ($this->version_type == "v3") {
            $src = "https://www.google.com/recaptcha/api.js?render=" . rawurlencode($this->grecaptcha);
        } elseif ($this->version_type == "v2") {
            $src = "https://www.google.com/recaptcha/api.js";
        } else {
            return;
        }

        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google controls this remote reCAPTCHA script URL.
        wp_enqueue_script('ppcart-google-recaptcha', $src, [], null, [ 'strategy' => 'async' ]);
    }

    public function gen_recaptcha_html($ppcart_product)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-googlerecaptcha-gen-recaptcha-html.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
