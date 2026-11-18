<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registers the on-page PayPal webhook in the merchant's PayPal app and
 * reports its state on the settings page.
 *
 * Registration runs at shutdown of the request that saved a PayPal setting,
 * once every option of that save is stored. A refusal (PayPal rejects
 * non-public or non-HTTPS URLs) does not block on-page mode; the hourly
 * reconcile job covers it.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Admin_Paypal_Webhook
{
    public const EVENTS = [
        'BILLING.SUBSCRIPTION.ACTIVATED',
        'BILLING.SUBSCRIPTION.UPDATED',
        'BILLING.SUBSCRIPTION.SUSPENDED',
        'BILLING.SUBSCRIPTION.CANCELLED',
        'BILLING.SUBSCRIPTION.EXPIRED',
        'BILLING.SUBSCRIPTION.PAYMENT.FAILED',
        'PAYMENT.SALE.COMPLETED',
        'PAYMENT.SALE.DENIED',
        'PAYMENT.SALE.REVERSED',
        'PAYMENT.SALE.REFUNDED',
        'CHECKOUT.ORDER.APPROVED',
        'PAYMENT.CAPTURE.COMPLETED',
        'PAYMENT.CAPTURE.DENIED',
        'PAYMENT.CAPTURE.PENDING',
        'PAYMENT.CAPTURE.REVERSED',
        'PAYMENT.CAPTURE.REFUNDED',
    ];

    private const WATCHED_OPTIONS = [
        '_ppcart_paypal_checkout_experience',
        '_ppcart_paypal_enable',
        '_ppcart_paypal_enable_sandbox',
        '_ppcart_paypal_client_id',
        '_ppcart_paypal_secret',
        '_ppcart_paypal_sandbox_client_id',
        '_ppcart_paypal_sandbox_secret',
    ];

    /**
     * @var bool
     */
    private $pending = false;

    public function __construct()
    {
        foreach (self::WATCHED_OPTIONS as $option) {
            add_action('add_option_' . $option, [ $this, 'mark_pending' ]);
            add_action('update_option_' . $option, [ $this, 'mark_pending' ]);
        }
        add_action('shutdown', [ $this, 'maybe_register' ]);
    }

    /**
     * @return void
     */
    public function mark_pending()
    {
        $this->pending = true;
    }

    /**
     * @return void
     */
    public function maybe_register()
    {
        if (! $this->pending || ! is_admin() || ! class_exists('PPCart_Paypal_Onpage') || ! PPCart_Paypal_Onpage::is_active()) {
            return;
        }
        $this->pending = false;
        $this->register();
    }

    /**
     * @return string
     */
    public static function webhook_url()
    {
        return home_url('/ppcart-webhook/paypal-rest');
    }

    /**
     * Ensure the merchant's PayPal app has a webhook for this site's URL.
     *
     * @return bool True when a webhook with this URL exists after the call.
     */
    public function register()
    {
        $client = PPCart_Paypal_Rest_Client::from_settings();
        $mode   = $client->mode();
        $url    = self::webhook_url();

        $res = $client->request('GET', '/v1/notifications/webhooks');
        if (! PPCart_Paypal_Sync::ok($res)) {
            $this->store($mode, '', self::error_text($res));
            return false;
        }

        foreach ((array) ($res['body']['webhooks'] ?? []) as $webhook) {
            if (is_array($webhook) && ($webhook['url'] ?? '') === $url && ! empty($webhook['id'])) {
                $this->store($mode, sanitize_text_field((string) $webhook['id']), '');
                return true;
            }
        }

        $event_types = [];
        foreach (self::EVENTS as $event) {
            $event_types[] = [ 'name' => $event ];
        }

        $res = $client->request('POST', '/v1/notifications/webhooks', [
            'url'         => $url,
            'event_types' => $event_types,
        ]);
        if (PPCart_Paypal_Sync::ok($res) && ! empty($res['body']['id'])) {
            $this->store($mode, sanitize_text_field((string) $res['body']['id']), '');
            return true;
        }

        $this->store($mode, '', self::error_text($res));

        return false;
    }

    /**
     * One line for the Checkout experience field, from stored state only.
     *
     * @return string HTML allowed by wp_kses_post.
     */
    public static function status_note()
    {
        if (! class_exists('PPCart_Paypal_Onpage') || 'onpage' !== get_option(PPCart_Paypal_Onpage::OPTION_EXPERIENCE, 'redirect')) {
            return '';
        }

        $mode       = PPCart_Paypal_Rest_Client::current_mode();
        $mode_label = 'live' === $mode ? __('live', 'publishpress-cart') : __('sandbox', 'publishpress-cart');
        $url        = '<code>' . esc_html(self::webhook_url()) . '</code>';
        $webhook_id = sanitize_text_field((string) get_option('_ppcart_paypal_' . $mode . '_webhook_id', ''));
        $error      = sanitize_text_field((string) get_option('_ppcart_paypal_' . $mode . '_webhook_error', ''));

        if ('' !== $webhook_id) {
            /* translators: 1: live or sandbox, 2: webhook URL. */
            return sprintf(esc_html__('PayPal webhook registered for %1$s mode at %2$s.', 'publishpress-cart'), esc_html($mode_label), $url);
        }

        if ('' !== $error) {
            return sprintf(
                /* translators: 1: live or sandbox, 2: PayPal error, 3: webhook URL, 4: event names. */
                esc_html__('PayPal refused the webhook for %1$s mode: %2$s. Add it yourself in your PayPal app (Developer Dashboard, your app, Webhooks) with the URL %3$s and these events: %4$s. Until then, the hourly check completes payments.', 'publishpress-cart'),
                esc_html($mode_label),
                esc_html($error),
                $url,
                '<code>' . esc_html(implode(', ', self::EVENTS)) . '</code>'
            );
        }

        /* translators: %s: webhook URL. */
        return sprintf(esc_html__('The PayPal webhook is registered when you save with Client ID and Secret set. URL: %s', 'publishpress-cart'), $url);
    }

    private function store($mode, $webhook_id, $error)
    {
        update_option('_ppcart_paypal_' . $mode . '_webhook_id', $webhook_id, false);
        update_option('_ppcart_paypal_' . $mode . '_webhook_error', $error, false);
    }

    private static function error_text($res)
    {
        $body    = is_array($res['body']) ? $res['body'] : [];
        $message = isset($body['message']) && is_string($body['message']) ? $body['message'] : '';
        $issues  = [];
        foreach ((array) ($body['details'] ?? []) as $detail) {
            if (is_array($detail) && ! empty($detail['issue'])) {
                $issues[] = (string) $detail['issue'];
            }
        }
        if ('' === $message && empty($issues)) {
            $message = 0 === (int) $res['code']
                ? __('PayPal could not be reached.', 'publishpress-cart')
                /* translators: %d: HTTP status code. */
                : sprintf(__('HTTP %d', 'publishpress-cart'), (int) $res['code']);
        }

        return sanitize_text_field(trim($message . ' ' . implode(', ', $issues)));
    }
}
