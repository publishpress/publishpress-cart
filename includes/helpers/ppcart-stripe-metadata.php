<?php

/**
 * Stripe object metadata reader and Connect fee config.
 *
 * First-party code reads canonical `ppcart_*` keys. Compatibility Mode may
 * register a leftover-name lookup; the helper never branches on the toggle.
 *
 * @package PublishPress_Cart
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('ppcart_stripe_metadata_compatibility_lookup')) {
    /**
     * Get or set the Compatibility Mode leftover Stripe-metadata lookup.
     *
     * @param callable|null $lookup Lookup that returns leftover keys for a canonical key, or null to clear.
     * @param bool          $set    Whether to replace the current lookup.
     * @return callable|null
     */
    function ppcart_stripe_metadata_compatibility_lookup($lookup = null, $set = false)
    {
        static $registered_lookup = null;

        if ($set) {
            $registered_lookup = is_callable($lookup) ? $lookup : null;
        }

        return $registered_lookup;
    }
}

if (! function_exists('ppcart_stripe_metadata_is_empty')) {
    /**
     * Whether a Stripe metadata id is missing for matching.
     *
     * Local post ids never use 0.
     *
     * @param mixed $value Raw metadata value.
     * @return bool
     */
    function ppcart_stripe_metadata_is_empty($value)
    {
        return null === $value || '' === $value || false === $value || 0 === $value || '0' === $value;
    }
}

if (! function_exists('ppcart_stripe_metadata_property')) {
    /**
     * Read a key from an array or object.
     *
     * @param mixed  $source  Array, object, or null.
     * @param string $key     Property/key name.
     * @param mixed  $default Default when missing.
     * @return mixed
     */
    function ppcart_stripe_metadata_property($source, $key, $default = null)
    {
        if (is_array($source) && array_key_exists($key, $source)) {
            return $source[ $key ];
        }

        if (is_object($source) && isset($source->{$key})) {
            return $source->{$key};
        }

        return $default;
    }
}

if (! function_exists('ppcart_stripe_metadata_raw')) {
    /**
     * Read one metadata key with no leftover fallback.
     *
     * @param object|array|null $object Stripe resource that has a metadata bag.
     * @param string            $key    Metadata key.
     * @param mixed             $default Default when missing.
     * @return mixed
     */
    function ppcart_stripe_metadata_raw($object, $key, $default = null)
    {
        if (! is_string($key) || '' === $key) {
            return $default;
        }

        $metadata = ppcart_stripe_metadata_property($object, 'metadata');

        return ppcart_stripe_metadata_property($metadata, $key, $default);
    }
}

if (! function_exists('ppcart_stripe_metadata')) {
    /**
     * Read a canonical Stripe metadata key, with leftover fallback when a lookup is registered.
     *
     * Canonical non-empty wins even if leftover differs.
     *
     * @param object|array|null $object         Stripe resource that has a metadata bag.
     * @param string            $canonical_key Canonical metadata key.
     * @param mixed             $default        Default when canonical and leftover are empty.
     * @return mixed
     */
    function ppcart_stripe_metadata($object, $canonical_key, $default = null)
    {
        $canonical = ppcart_stripe_metadata_raw($object, $canonical_key, null);

        if (! ppcart_stripe_metadata_is_empty($canonical)) {
            return $canonical;
        }

        $lookup = ppcart_stripe_metadata_compatibility_lookup();

        if (is_callable($lookup)) {
            foreach ((array) call_user_func($lookup, $canonical_key) as $leftover) {
                if (! is_string($leftover) || '' === $leftover) {
                    continue;
                }

                $value = ppcart_stripe_metadata_raw($object, $leftover, null);

                if (! ppcart_stripe_metadata_is_empty($value)) {
                    return $value;
                }
            }
        }

        return $default;
    }
}

/**
 * Extra Stripe Connect application-fee percent charged on Free.
 * This method is not protected by the function_exists check
 * to avoid a simple function definition to bypass the verification.
 *
 * @return float
 */
function ppcart_stripe_connect_extra_percent()
{
    return ppcart_is_pro() ? 0.0 : (float) PPCART_STRIPE_CONNECT_EXTRA_PERCENT;
}

/**
 * Stripe Connect destination and fee configuration.
 *
 * Always sets free_extra_percent and derives total_fee_percent from it.
 * This function is not wrapped in function_exists so a stub cannot drop
 * the Free service fee.
 *
 * @return array{enabled: bool, destination: string, is_oauth_access_token_key: bool, platform_fee_percent: float, free_extra_percent: float, total_fee_percent: float}
 */
function ppcart_get_stripe_connect_config()
{
    global $ppcart_stripe;

    $mode = isset($ppcart_stripe['mode']) ? sanitize_text_field((string) $ppcart_stripe['mode']) : 'test';
    $is_enabled = (bool) get_option('_ppcart_stripe_enable', false);

    $destination_option_key = 'live' === $mode ? '_ppcart_stripe_connect_account_id_live' : '_ppcart_stripe_connect_account_id_test';
    $destination_account_id = sanitize_text_field((string) get_option($destination_option_key, ''));
    $key_source = sanitize_text_field((string) get_option('live' === $mode ? '_ppcart_stripe_live_key_source' : '_ppcart_stripe_test_key_source', ''));
    $is_oauth_access_token_key = ('oauth_access_token' === $key_source);

    // Stripe destination must be a connected account id (acct_...).
    if ('' !== $destination_account_id && 0 !== strpos($destination_account_id, 'acct_')) {
        $destination_account_id = '';
    }

    // No base platform fee for any user tier.
    $platform_fee_percent = 0.0;

    // Stripe payments work in Free. This service fee is applied to Free Stripe Connect payments.
    $free_user_extra_percent = ppcart_stripe_connect_extra_percent();
    $total_fee_percent = (float) $platform_fee_percent + (float) $free_user_extra_percent;

    return [
        'enabled' => $is_enabled,
        'destination' => $destination_account_id,
        'is_oauth_access_token_key' => $is_oauth_access_token_key,
        'platform_fee_percent' => $platform_fee_percent,
        'free_extra_percent' => $free_user_extra_percent,
        'total_fee_percent' => $total_fee_percent,
    ];
}

/**
 * Allowed Connect application-fee percent after filters.
 *
 * Pro is 0. Free must be PPCART_STRIPE_CONNECT_EXTRA_PERCENT. Any other
 * value is invalid. Not wrapped in function_exists.
 *
 * @return float
 */
function ppcart_stripe_connect_allowed_fee_percent()
{
    return ppcart_is_pro() ? 0.0 : (float) PPCART_STRIPE_CONNECT_EXTRA_PERCENT;
}

/**
 * Whether Stripe request args still carry a valid Connect application fee.
 *
 * Does not modify $args. Pro expects 0 (or omitted). Free must match
 * PPCART_STRIPE_CONNECT_EXTRA_PERCENT. Covers Checkout Session, PaymentIntent,
 * and Subscription create/update shapes. Not wrapped in function_exists.
 *
 * @param mixed $args               Request args, possibly returned by a filter.
 * @param int   $amount_for_stripe  Payment amount in Stripe units. Unused for subscriptions.
 * @return bool
 */
function ppcart_stripe_connect_fee_is_valid($args, $amount_for_stripe = 0)
{
    if (! is_array($args)) {
        return false;
    }

    $connect = ppcart_get_stripe_connect_config();
    if (empty($connect['enabled'])) {
        return true;
    }

    if (empty($connect['is_oauth_access_token_key']) && empty($connect['destination'])) {
        return true;
    }

    $expected_percent = ppcart_stripe_connect_allowed_fee_percent();
    $mode = isset($args['mode']) ? (string) $args['mode'] : '';
    $is_session_subscription = ('subscription' === $mode) || isset($args['subscription_data']);
    $is_session_payment = ('payment' === $mode) || isset($args['payment_intent_data']);
    $is_percent_fee = $is_session_subscription
        || (! $is_session_payment && (isset($args['items']) || array_key_exists('application_fee_percent', $args)));

    if ($is_percent_fee) {
        $actual = null;
        if ($is_session_subscription) {
            if (isset($args['subscription_data']) && is_array($args['subscription_data']) && array_key_exists('application_fee_percent', $args['subscription_data'])) {
                $actual = round((float) $args['subscription_data']['application_fee_percent'], 2);
            }
        } elseif (array_key_exists('application_fee_percent', $args)) {
            $actual = round((float) $args['application_fee_percent'], 2);
        }

        if ($expected_percent > 0) {
            return $actual === round($expected_percent, 2);
        }

        return null === $actual || 0.0 === $actual;
    }

    if (isset($args['line_items'][0]['price_data']['unit_amount'])) {
        $amount_for_stripe = (int) $args['line_items'][0]['price_data']['unit_amount'];
    } elseif (isset($args['amount'])) {
        $amount_for_stripe = (int) $args['amount'];
    }

    $actual = null;
    if (isset($args['payment_intent_data']) && is_array($args['payment_intent_data']) && array_key_exists('application_fee_amount', $args['payment_intent_data'])) {
        $actual = (int) $args['payment_intent_data']['application_fee_amount'];
    } elseif (array_key_exists('application_fee_amount', $args)) {
        $actual = (int) $args['application_fee_amount'];
    }

    if ($expected_percent > 0) {
        $expected_amount = (int) round(((float) $amount_for_stripe) * ($expected_percent / 100));
        if ($expected_amount < 1) {
            $expected_amount = 1;
        }

        return $actual === $expected_amount;
    }

    return null === $actual || 0 === $actual;
}

/**
 * Keep the first subscription invoice on-session for 3D Secure.
 *
 * Basil removed Invoice.payment_intent. Checkout JS confirms with
 * latest_invoice.confirmation_secret.client_secret, which must be expanded.
 * Re-apply after ppcart_checkout_stripe_subscription_args so a filter cannot
 * drop the secret, expand the removed payment_intent field, or mark the first
 * invoice complete off-session. Not wrapped in function_exists.
 *
 * @param mixed $args Subscription create args.
 * @return array
 */
function ppcart_stripe_subscription_on_session_args($args)
{
    if (! is_array($args)) {
        $args = [];
    }

    $args['payment_behavior'] = 'default_incomplete';

    $payment_settings = [];
    if (isset($args['payment_settings']) && is_array($args['payment_settings'])) {
        $payment_settings = $args['payment_settings'];
    }
    $payment_settings['save_default_payment_method'] = 'on_subscription';
    $args['payment_settings'] = $payment_settings;

    $expand = [];
    if (isset($args['expand']) && is_array($args['expand'])) {
        foreach ($args['expand'] as $entry) {
            if (! is_string($entry)) {
                continue;
            }
            if ('latest_invoice.payment_intent' === $entry || 0 === strpos($entry, 'latest_invoice.payment_intent.')) {
                continue;
            }
            $expand[] = $entry;
        }
    }
    if (! in_array('latest_invoice.confirmation_secret', $expand, true)) {
        $expand[] = 'latest_invoice.confirmation_secret';
    }
    $args['expand'] = $expand;

    return $args;
}
