<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Binds Stripe customers and payment methods to the checkout that created them.
 *
 * The checkout AJAX endpoints are public. A posted email, customer ID, or
 * payment method ID does not prove who the caller is. So:
 *
 * - A Stripe customer is reused only when it is stored on the logged-in user's
 *   own account. Guests always get a new customer.
 * - The browser gets an opaque checkout reference, not a customer ID. The
 *   reference maps to the customer on the server.
 * - Off-session upsell charges use only the payment method that paid the
 *   parent order.
 */
class PPCart_Stripe_Checkout_Customer
{
    const REF_TRANSIENT_PREFIX = 'ppcart_checkout_ref_';

    const REF_TTL = 86400;

    const USER_META_PREFIX = '_ppcart_stripe_customer_';

    const ORDER_PAYMENT_METHOD_META = 'stripe_payment_method_id';

    /**
     * @param mixed $value
     * @return bool
     */
    public static function is_customer_id($value)
    {
        return is_string($value) && 1 === preg_match('/^cus_[A-Za-z0-9]+$/', $value);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function is_payment_method_id($value)
    {
        return is_string($value) && 1 === preg_match('/^(pm|card|src)_[A-Za-z0-9]+$/', $value);
    }

    /**
     * @param string $mode Stripe gateway mode.
     * @return string
     */
    public static function user_meta_key($mode)
    {
        $mode = sanitize_key((string) $mode);

        return self::USER_META_PREFIX . ('' !== $mode ? $mode : 'default');
    }

    /**
     * The Stripe customer stored on the logged-in user's account, or ''.
     *
     * @param string $mode Stripe gateway mode.
     * @return string
     */
    public static function get_current_user_customer_id($mode)
    {
        $user_id = (int) get_current_user_id();
        if ($user_id <= 0) {
            return '';
        }

        $customer_id = get_user_meta($user_id, self::user_meta_key($mode), true);

        return self::is_customer_id($customer_id) ? $customer_id : '';
    }

    /**
     * The logged-in user's stored customer, only when Stripe still has it.
     * When it is gone, the caller creates a new one and it replaces the old ID.
     *
     * @param object $stripe Stripe client.
     * @param string $mode   Stripe gateway mode.
     * @return string
     */
    public static function get_live_current_user_customer_id($stripe, $mode)
    {
        $customer_id = self::get_current_user_customer_id($mode);
        if ('' === $customer_id) {
            return '';
        }

        try {
            $customer = $stripe->customers->retrieve($customer_id);
        } catch (Exception $e) {
            return '';
        }

        return ($customer && empty($customer->deleted)) ? $customer_id : '';
    }

    /**
     * @param string $customer_id Stripe customer ID.
     * @param string $mode        Stripe gateway mode.
     * @return void
     */
    public static function remember_current_user_customer_id($customer_id, $mode)
    {
        $user_id = (int) get_current_user_id();
        if ($user_id <= 0 || ! self::is_customer_id($customer_id)) {
            return;
        }

        update_user_meta($user_id, self::user_meta_key($mode), $customer_id);
    }

    /**
     * Create a new Stripe customer for this checkout. Never looks customers up by email.
     *
     * @param object $stripe        Stripe client.
     * @param array  $customer_args Customer create params.
     * @param string $mode          Stripe gateway mode.
     * @return object Stripe customer.
     */
    public static function create_customer($stripe, array $customer_args, $mode)
    {
        foreach (['email', 'phone'] as $key) {
            if (isset($customer_args[ $key ]) && '' === trim((string) $customer_args[ $key ])) {
                unset($customer_args[ $key ]);
            }
        }

        $customer = $stripe->customers->create($customer_args);

        if ($customer && isset($customer->id)) {
            self::remember_current_user_customer_id($customer->id, $mode);
        }

        return $customer;
    }

    /**
     * Store a server-side binding for this checkout and return its opaque reference.
     *
     * @param string $customer_id Stripe customer ID.
     * @param string $intent_id   PaymentIntent or SetupIntent ID, or '' when none exists yet.
     * @param string $mode        Stripe gateway mode.
     * @param bool   $is_new      True when the customer was created for this checkout.
     * @return string Reference, or '' when the customer ID is not valid.
     */
    public static function create_ref($customer_id, $intent_id, $mode, $is_new)
    {
        if (! self::is_customer_id($customer_id)) {
            return '';
        }

        try {
            $ref = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $ref = strtolower(wp_generate_password(64, false, false));
        }

        set_transient(
            self::ref_transient_key($ref),
            [
                'customer_id' => $customer_id,
                'intent_id'   => is_string($intent_id) ? $intent_id : '',
                'mode'        => (string) $mode,
                'user_id'     => (int) get_current_user_id(),
                'is_new'      => (bool) $is_new,
            ],
            self::REF_TTL
        );

        return $ref;
    }

    /**
     * Resolve a checkout reference. Returns null when it is unknown, expired,
     * for another gateway mode, or was created by a different WordPress user.
     *
     * @param mixed  $ref  Reference from the browser.
     * @param string $mode Stripe gateway mode.
     * @return array|null
     */
    public static function read_ref($ref, $mode)
    {
        if (! is_string($ref) || 1 !== preg_match('/^[A-Za-z0-9]{32,128}$/', $ref)) {
            return null;
        }

        $data = get_transient(self::ref_transient_key($ref));
        if (! is_array($data) || ! isset($data['customer_id']) || ! self::is_customer_id($data['customer_id'])) {
            return null;
        }

        if ((string) ($data['mode'] ?? '') !== (string) $mode) {
            return null;
        }

        if ((int) ($data['user_id'] ?? 0) !== (int) get_current_user_id()) {
            return null;
        }

        return [
            'customer_id' => $data['customer_id'],
            'intent_id'   => isset($data['intent_id']) && is_string($data['intent_id']) ? $data['intent_id'] : '',
            'is_new'      => ! empty($data['is_new']),
        ];
    }

    /**
     * Customer ID for an order saved from the browser. The posted customer ID is
     * never used; only the checkout reference counts.
     *
     * @param mixed  $ref       Reference from the browser.
     * @param string $intent_id Posted PaymentIntent ID.
     * @param string $mode      Stripe gateway mode.
     * @return string Customer ID, or '' when the reference does not match this intent.
     */
    public static function customer_id_for_order($ref, $intent_id, $mode)
    {
        $binding = self::read_ref($ref, $mode);
        if (! $binding) {
            return '';
        }

        if ('' !== $binding['intent_id'] && $binding['intent_id'] !== (string) $intent_id) {
            return '';
        }

        return $binding['customer_id'];
    }

    /**
     * Find the payment method for a subscription checkout and make sure it
     * belongs to the bound customer.
     *
     * With a SetupIntent binding the method comes from the SetupIntent, read
     * with the secret key. Without one, the posted method must be unattached
     * or already attached to the bound customer; it is then attached.
     *
     * @param object $stripe       Stripe client.
     * @param array  $binding      Result of read_ref().
     * @param string $posted_pm_id Payment method ID from the browser.
     * @param string $site_url     This site's URL (SetupIntent metadata origin).
     * @return string|WP_Error Payment method ID.
     */
    public static function resolve_subscription_payment_method($stripe, array $binding, $posted_pm_id, $site_url)
    {
        $customer_id = $binding['customer_id'];
        $intent_id   = $binding['intent_id'];

        if (0 === strpos($intent_id, 'seti_')) {
            $setup_intent = $stripe->setupIntents->retrieve($intent_id);

            $origin = isset($setup_intent->metadata->origin) ? (string) $setup_intent->metadata->origin : '';
            if (
                'succeeded' !== self::value($setup_intent, 'status')
                || self::resource_id(self::value($setup_intent, 'customer')) !== $customer_id
                || $origin !== (string) $site_url
            ) {
                return self::error();
            }

            $pm_id = self::resource_id(self::value($setup_intent, 'payment_method'));
            if (! self::is_payment_method_id($pm_id)) {
                return self::error();
            }

            return $pm_id;
        }

        if (! is_string($posted_pm_id) || 1 !== preg_match('/^pm_[A-Za-z0-9]+$/', $posted_pm_id)) {
            return self::error();
        }

        $payment_method = $stripe->paymentMethods->retrieve($posted_pm_id);
        $attached_to    = self::resource_id(self::value($payment_method, 'customer'));

        if ('' !== $attached_to && $attached_to !== $customer_id) {
            return self::error();
        }

        if ('' === $attached_to) {
            $payment_method->attach(['customer' => $customer_id]);
        }

        return $posted_pm_id;
    }

    /**
     * @param mixed  $invoice     Stripe invoice.
     * @param string $customer_id Bound Stripe customer ID.
     * @return bool
     */
    public static function invoice_belongs_to_customer($invoice, $customer_id)
    {
        return self::is_customer_id($customer_id)
            && self::resource_id(self::value($invoice, 'customer')) === $customer_id;
    }

    /**
     * @param int    $order_id          Order post ID.
     * @param string $payment_method_id Payment method that paid the order.
     * @return void
     */
    public static function remember_order_payment_method($order_id, $payment_method_id)
    {
        if ((int) $order_id > 0 && self::is_payment_method_id($payment_method_id)) {
            ppcart_update_post_meta((int) $order_id, self::ORDER_PAYMENT_METHOD_META, $payment_method_id);
        }
    }

    /**
     * The payment method that paid a parent order, for off-session upsell and
     * downsell charges. Never the customer's default or another saved card.
     *
     * @param object $stripe      Stripe client.
     * @param int    $order_id    Parent order post ID.
     * @param string $customer_id Stripe customer on the parent order.
     * @return string Payment method ID, or '' when none can be proven.
     */
    public static function get_order_payment_method_id($stripe, $order_id, $customer_id)
    {
        $order_id = (int) $order_id;
        if ($order_id <= 0 || ! self::is_customer_id($customer_id)) {
            return '';
        }

        $stored = ppcart_get_post_meta($order_id, self::ORDER_PAYMENT_METHOD_META, true);
        if (self::is_payment_method_id($stored)) {
            return $stored;
        }

        $transaction_id = ppcart_get_post_meta($order_id, 'transaction_id', true);
        $transaction_id = is_string($transaction_id) ? $transaction_id : '';

        try {
            if (0 === strpos($transaction_id, 'pi_')) {
                $payment = $stripe->paymentIntents->retrieve($transaction_id);
            } elseif (0 === strpos($transaction_id, 'ch_') || 0 === strpos($transaction_id, 'py_')) {
                $payment = $stripe->charges->retrieve($transaction_id);
            } else {
                return '';
            }
        } catch (Exception $e) {
            return '';
        }

        if (
            ! in_array(self::value($payment, 'status'), ['succeeded', 'requires_capture'], true)
            || self::resource_id(self::value($payment, 'customer')) !== $customer_id
        ) {
            return '';
        }

        $pm_id = self::resource_id(self::value($payment, 'payment_method'));
        if (! self::is_payment_method_id($pm_id)) {
            return '';
        }

        self::remember_order_payment_method($order_id, $pm_id);

        return $pm_id;
    }

    /**
     * @param string $ref Reference.
     * @return string
     */
    private static function ref_transient_key($ref)
    {
        return self::REF_TRANSIENT_PREFIX . hash('sha256', $ref);
    }

    /**
     * @param mixed  $resource Stripe object, array, or null.
     * @param string $key      Field.
     * @return mixed
     */
    private static function value($resource, $key)
    {
        if (is_array($resource)) {
            return $resource[ $key ] ?? null;
        }

        if (is_object($resource) && isset($resource->$key)) {
            return $resource->$key;
        }

        return null;
    }

    /**
     * @param mixed $resource Stripe ID string or expanded object.
     * @return string
     */
    private static function resource_id($resource)
    {
        if (is_string($resource)) {
            return $resource;
        }

        $id = self::value($resource, 'id');

        return is_string($id) ? $id : '';
    }

    /**
     * @return WP_Error
     */
    private static function error()
    {
        return new WP_Error(
            'ppcart_stripe_checkout_mismatch',
            __('This payment could not be verified. Please reload the page and try again.', 'publishpress-cart')
        );
    }
}
