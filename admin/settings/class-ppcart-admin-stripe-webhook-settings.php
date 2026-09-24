<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Stripe webhook setup and status helpers for settings.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Admin_Stripe_Webhook_Settings
{
    /**
     * Stripe Connect settings controller.
     *
     * @var PPCart_Admin_Stripe_Connect_Settings
     */
    private $connect_settings;

    /**
     * Resolved webhook state per mode, so Stripe is queried once per request.
     *
     * @var array
     */
    private $webhook_state = [];

    /**
     * Returns the resolved webhook state for a mode.
     *
     * @param string $mode Stripe mode.
     * @return array state, events, note and fix_url.
     */
    public function get_webhook_state($mode)
    {
        if (isset($this->webhook_state[ $mode ])) {
            return $this->webhook_state[ $mode ];
        }

        $this->webhook_state[ $mode ] = $this->resolve_webhook_state($mode);

        return $this->webhook_state[ $mode ];
    }

    /**
     * Queries Stripe and the stored options to work out the webhook state.
     *
     * @param string $mode Stripe mode.
     * @return array
     */
    private function resolve_webhook_state($mode)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-settings-get-webhook-state.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Adds the webhook setup controls to a wp_kses allowlist.
     *
     * The Stripe panel markup passes through several kses filters on its way
     * to the page. Each one must allow these tags, or the fields are stripped
     * and the button loses the form attribute that submits it.
     *
     * @param array $allowed Allowed HTML map.
     * @return array
     */
    public static function augment_allowed_html($allowed)
    {
        if (! is_array($allowed)) {
            $allowed = [];
        }

        $allowed['input'] = [
            'type'         => true,
            'id'           => true,
            'name'         => true,
            'form'         => true,
            'class'        => true,
            'value'        => true,
            'placeholder'  => true,
            'autocomplete' => true,
            'spellcheck'   => true,
            'data-testid'  => true,
        ];

        $allowed['button'] = [
            'type'        => true,
            'form'        => true,
            'class'       => true,
            'data-testid' => true,
        ];

        $allowed['label'] = [
            'for'   => true,
            'class' => true,
        ];

        $allowed['a'] = [
            'href'        => true,
            'target'      => true,
            'rel'         => true,
            'title'       => true,
            'data-testid' => true,
        ];

        foreach ([ 'ol', 'li', 'span', 'strong', 'code', 'p', 'div', 'a' ] as $tag) {
            if (! isset($allowed[ $tag ]) || ! is_array($allowed[ $tag ])) {
                $allowed[ $tag ] = [];
            }

            $allowed[ $tag ]['class'] = true;
            $allowed[ $tag ]['id'] = true;
            $allowed[ $tag ]['hidden'] = true;
        }

        $allowed['a']['data-ppcart-toggle'] = true;
        $allowed['div']['data-ppcart-toggle'] = true;

        return $allowed;
    }

    public function __construct(PPCart_Admin_Stripe_Connect_Settings $connect_settings)
    {
        $this->connect_settings = $connect_settings;
        add_action('admin_init', [ $this, 'maybe_handle_stripe_webhook_setup' ]);
        add_action('admin_post_ppcart_stripe_webhook_manual_setup', [ $this, 'handle_manual_webhook_setup' ]);
        add_action('admin_footer', [ $this, 'print_manual_webhook_forms' ]);
    }

    /**
     * Checks whether a value looks like an account-owned Stripe key for a mode.
     *
     * Restricted keys (rk_) are preferred, but a plain secret key also works.
     *
     * @param string $key  Stripe key.
     * @param string $mode Stripe mode.
     * @return bool
     */
    private function is_stripe_account_key($key, $mode)
    {
        $mode = in_array($mode, [ 'test', 'live' ], true) ? $mode : 'test';

        return 1 === preg_match('/^(rk|sk)_' . $mode . '_[A-Za-z0-9_]+$/', (string) $key);
    }

    /**
     * Handles the one-time key submission that installs the webhook.
     *
     * @return void
     */
    public function handle_manual_webhook_setup()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-settings-handle-manual-webhook-setup.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Prints the detached forms that the webhook fields submit to.
     *
     * The settings page already wraps everything in its own form, and forms
     * cannot nest. The fields therefore live in the panel and point here with
     * the HTML form attribute.
     *
     * @return void
     */
    public function print_manual_webhook_forms()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-settings-print-manual-webhook-forms.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Returns the webhook setup fields shown inside the Stripe panel.
     *
     * @param string $mode   Stripe mode.
     * @param string $reason Why the fields are shown: 'blocked' or 'missing'.
     * @return string
     */
    public function get_stripe_webhook_manual_setup_html($mode, $reason = 'missing')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-settings-get-manual-setup-html.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
    private function get_expected_stripe_webhook_events()
    {
        return [
            'charge.succeeded',
            'charge.refunded',
            // Finalizes hosted-checkout orders; charge.succeeded cannot match them
            // because the pending order has no transaction id yet.
            'checkout.session.completed',
            'invoice.payment_failed',
            'invoice.payment_succeeded',
            'invoice.marked_uncollectible',
            'refund.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'customer.subscription.paused',
            'customer.subscription.resumed',
        ];
    }



    private function is_stripe_webhook_permission_error($e)
    {
        $message = strtolower((string) $e->getMessage());

        return false !== strpos($message, 'required permissions for this endpoint')
            || false !== strpos($message, 'permission');
    }



    private function get_stripe_webhook_dashboard_url($mode)
    {
        if ('live' === $mode) {
            return 'https://dashboard.stripe.com/webhooks';
        }

        return 'https://dashboard.stripe.com/test/webhooks';
    }



    private function is_local_development_host($host)
    {
        if (! is_string($host) || '' === $host) {
            return false;
        }

        $host = strtolower($host);

        if (in_array($host, [ 'localhost', '127.0.0.1', '::1' ], true)) {
            return true;
        }

        return 1 === preg_match('/\.(test|local|localhost|invalid)$/i', $host);
    }



    private function is_local_webhook_url($webhook_url)
    {
        $host = wp_parse_url($webhook_url, PHP_URL_HOST);

        return $this->is_local_development_host((string) $host);
    }



    private function get_stripe_webhook_url()
    {
        if (function_exists('ppcart_get_webhook_url')) {
            $webhook_url = (string) ppcart_get_webhook_url('stripe');
        } else {
            $webhook_url = (string) get_site_url() . '/ppcart-webhook/stripe';
        }

        if (! $this->is_local_webhook_url($webhook_url)) {
            return $webhook_url;
        }

        $public_base_url = $this->get_current_public_base_url();
        if ('' === $public_base_url) {
            return $webhook_url;
        }

        return trailingslashit($public_base_url) . 'ppcart-webhook/stripe';
    }



    private function get_current_public_base_url()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field((string) wp_unslash($_SERVER['HTTP_HOST'])) : '';
        if ($this->is_local_development_host($host)) {
            return '';
        }

        $scheme = is_ssl() ? 'https' : 'http';
        if ('https' !== $scheme || '' === $host) {
            return '';
        }

        return esc_url_raw($scheme . '://' . $host);
    }

    /**
     * Creates or updates the Stripe webhook endpoint for a mode.
     *
     * Stripe returns a signing secret only from create(). Updating an
     * existing endpoint does not.
     *
     * @param string $mode       Stripe mode.
     * @param string $secret_key Stripe secret or restricted key.
     * @return array|WP_Error {
     *     @type bool $secret_saved Whether a new secret was stored this request.
     *     @type bool $has_secret   Whether a signing secret is stored now.
     * }
     */
    public function sync_stripe_webhook_for_mode($mode, $secret_key)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-settings-sync-stripe-webhook-for-mode.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }



    public function maybe_handle_stripe_webhook_setup()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-settings-maybe-handle-stripe-webhook-setup.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
