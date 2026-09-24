<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Per-field request sanitization.
 *
 * Request payloads are built from a declared schema: only expected keys are read, and
 * every value is sanitized with the function appropriate to its type. Undeclared keys
 * are dropped instead of being forwarded, so handlers, gateway calls, and extension
 * points never receive arbitrary request keys.
 *
 * This follows the shape of ppcart_parse_report_filters() in functions/report-filters.php:
 * a named parse function per payload, one explicit key at a time, and a fixed-shape return.
 */

/**
 * Read a single request value, ignoring arrays and objects.
 *
 * Mirrors ppcart_report_filter_scalar(). Casting an array to string warns, and absint()
 * turns a non-empty array into 1, so a hostile `field[]=` submission would otherwise
 * produce a valid-looking ID.
 *
 * @param mixed $value Raw request value.
 * @return string|null Value as a string, or null when it is not scalar.
 */
function ppcart_request_scalar($value)
{
    if (! is_scalar($value)) {
        return null;
    }

    return (string) $value;
}

/**
 * Sanitize a price submitted through a request field.
 *
 * Reuses PPCart_Sanitize's `price` type so thousand/decimal separators are normalized
 * the same way the refund controller already normalizes them.
 *
 * @param mixed $value Raw request value.
 * @return string|null Normalized price, or null when the value is not scalar.
 */
function ppcart_sanitize_request_price($value)
{
    $value = ppcart_request_scalar($value);
    if (null === $value) {
        return null;
    }

    $value = sanitize_text_field($value);

    if (! class_exists('PPCart_Sanitize')) {
        return $value;
    }

    $sanitizer = new PPCart_Sanitize();
    $sanitizer->set_data($value);
    $sanitizer->set_type('price');

    return $sanitizer->clean();
}

/**
 * Sanitize one request value with the function appropriate to its type.
 *
 * @param mixed  $value Request value, already unslashed.
 * @param string $type  Schema type.
 * @return mixed|null Sanitized value, or null when the value must be dropped.
 */
function ppcart_sanitize_request_value($value, $type)
{
    if ('price' === $type) {
        return ppcart_sanitize_request_price($value);
    }

    $value = ppcart_request_scalar($value);
    if (null === $value) {
        return null;
    }

    switch ($type) {
        case 'int':
            return absint($value);

        case 'key':
            return sanitize_key($value);

        case 'email':
            return sanitize_email($value);

        case 'url_raw':
            // esc_url_raw() keeps ?, & and = intact. esc_url() entity-encodes them, which
            // would corrupt redirect targets handed back to the browser.
            return esc_url_raw($value);

        case 'textarea':
            return sanitize_textarea_field($value);

        case 'html':
            return wp_kses_post($value);

        case 'text':
        default:
            return sanitize_text_field($value);
    }
}

/**
 * Sanitize a nested request array whose keys are data rather than field names.
 *
 * Used for groups such as `pwyw_amount[<option_id>]`, `ppcart_custom_fields[<field_id>]`
 * and `ppcart-orderbump[<key>]`, where the key set comes from product configuration and
 * cannot be enumerated ahead of time. Keys are sanitized but not lowercased, because
 * custom-field ids are matched case-sensitively against the product's configuration.
 *
 * @param mixed  $value      Raw nested array, already unslashed.
 * @param string $value_type Type applied to each member value.
 * @param string $key_type   Type applied to each member key: 'int' or 'text'.
 * @return array
 */
function ppcart_sanitize_request_group($value, $value_type = 'text', $key_type = 'text')
{
    if (! is_array($value)) {
        return [];
    }

    $group = [];

    foreach ($value as $key => $member) {
        $clean_key = ('int' === $key_type) ? absint($key) : sanitize_text_field((string) $key);
        if ('' === (string) $clean_key) {
            continue;
        }

        if (is_array($member)) {
            // Multi-value inputs (checkbox groups, multi-selects) submit a flat array.
            $members = [];
            foreach ($member as $entry) {
                $clean = ppcart_sanitize_request_value($entry, $value_type);
                if (null !== $clean) {
                    $members[] = $clean;
                }
            }

            $group[ $clean_key ] = $members;
            continue;
        }

        $clean = ppcart_sanitize_request_value($member, $value_type);
        if (null !== $clean) {
            $group[ $clean_key ] = $clean;
        }
    }

    return $group;
}

/**
 * Build a request payload from a declared schema.
 *
 * @param mixed  $source  Raw request array.
 * @param array  $schema  Map of expected field name => type. A nested array value
 *                        `[ 'group' => <value type>, 'keys' => <key type> ]` declares a
 *                        group whose keys come from configuration rather than a fixed list.
 * @param string $context Payload label passed to the schema filter.
 * @param bool   $unslash Whether to unslash $source. Pass false for values already read
 *                        through filter_input(), which are not slashed by WordPress.
 * @return array Declared keys that were present, each value sanitized by type.
 */
function ppcart_sanitize_request_fields($source, $schema, $context = '', $unslash = true)
{
    if (! is_array($source)) {
        return [];
    }

    /**
     * Declare additional request fields expected on a payload.
     *
     * Undeclared keys are dropped, so an extension that renders its own checkout or
     * admin inputs must register them here together with the type each value should be
     * sanitized as. Recognised types: text, textarea, html, email, url_raw, int, key,
     * price, or [ 'group' => <type>, 'keys' => 'int'|'text' ] for a nested array.
     *
     * @param array  $schema  Map of field name => sanitization type.
     * @param string $context Payload being parsed, for example 'checkout' or 'paypal_ipn'.
     * @param array  $source  Raw request array.
     */
    $schema = apply_filters('ppcart_expected_request_fields', $schema, $context, $source);

    if (! is_array($schema)) {
        return [];
    }

    if ($unslash) {
        $source = wp_unslash($source);
    }

    $clean = [];

    foreach ($schema as $field => $type) {
        if (! array_key_exists($field, $source)) {
            continue;
        }

        if (is_array($type)) {
            $clean[ $field ] = ppcart_sanitize_request_group(
                $source[ $field ],
                isset($type['group']) ? $type['group'] : 'text',
                isset($type['keys']) ? $type['keys'] : 'text'
            );
            continue;
        }

        $value = ppcart_sanitize_request_value($source[ $field ], $type);
        if (null !== $value) {
            $clean[ $field ] = $value;
        }
    }

    return $clean;
}

/**
 * Buyer and address field names accepted on the checkout form.
 *
 * The names are read from the same filtered lists the checkout form renders and the
 * validator checks, so a field added by an extension is accepted without having to be
 * declared twice.
 *
 * @param mixed $product Product object the form belongs to, when known.
 * @return array List of field names.
 */
function ppcart_checkout_buyer_field_names($product = null)
{
    $default_fields = [
        'firstname' => [ 'name' => 'first_name', 'required' => true ],
        'lastname'  => [ 'name' => 'last_name', 'required' => true ],
        'email'     => [ 'name' => 'email', 'required' => true ],
        'phone'     => [ 'name' => 'phone', 'required' => false ],
        'company'   => [ 'name' => 'company', 'required' => false ],
    ];

    $address_fields = [
        'country'  => [ 'name' => 'country', 'required' => true ],
        'address1' => [ 'name' => 'address1', 'required' => true ],
        'city'     => [ 'name' => 'city', 'required' => true ],
        'state'    => [ 'name' => 'state', 'required' => true ],
        'zip'      => [ 'name' => 'zip', 'required' => true ],
    ];

    $default_fields = apply_filters('ppcart_order_form_fields', $default_fields, $product);
    $address_fields = apply_filters('ppcart_order_form_address_fields', $address_fields, $product);

    // Rendered on the address form but never present in the required list.
    $names = [ 'address2' ];

    foreach ([ $default_fields, $address_fields ] as $group) {
        if (! is_array($group)) {
            continue;
        }

        foreach ($group as $field) {
            if (is_array($field) && isset($field['name']) && is_string($field['name']) && '' !== $field['name']) {
                $names[] = $field['name'];
            }
        }
    }

    return array_values(array_unique($names));
}

/**
 * Schema for the public checkout / payment form payload.
 *
 * @param mixed $product Product object the form belongs to, when known.
 * @return array
 */
function ppcart_checkout_request_schema($product = null)
{
    $schema = [
        // Product and cart identity.
        'ppcart_product_id'            => 'int',
        'ppcart_product_option'        => 'text',
        'ppcart_product_name'          => 'text',
        'ppcart_qty'                   => 'int',
        'ppcart_amount'                => 'price',
        'ppcart_page_id'               => 'int',
        'ppcart_page_url'              => 'url_raw',
        'ppcart_currency_code'         => 'text',
        'ppcart_currency_country_code' => 'text',
        'ppcart_amount_due_label'      => 'text',
        'ppcart_due_today_label'       => 'text',
        'ppcart_process_payment'       => 'text',
        'on-sale'                      => 'text',
        'name'                         => 'text',
        'sale_name'                    => 'text',
        'price'                        => 'price',
        'sale_price'                   => 'price',

        // Payment routing.
        'pay-method'               => 'text',
        'ppcart_payment_method'    => 'text',
        'ppcart_payment_method_id' => 'text',
        'ppcart_payment_intent'    => 'text',
        'ppcart_preload_intent'    => 'int',
        'ppcart_pe_mount'          => 'int',
        'ppcart_hosted'            => 'text',
        'ppcart_session'           => 'text',
        'customerId'               => 'text',

        // Order continuation.
        'ppcart_order_id'         => 'int',
        'ppcart_temp_order_id'    => 'int',
        'ppcart_temp_order_token' => 'text',
        'ppcart-access'           => 'text',
        'coupon_id'               => 'text',

        // Consent.
        'ppcart_accept_terms'   => 'text',
        'ppcart_accept_privacy' => 'text',
        'ppcart_consent'        => 'text',

        // Tax.
        'vat-number'           => 'text',
        'vat-number-available' => 'text',

        // Nonces.
        'ppcart-nonce'          => 'text',
        'ppcart_upsell_nonce'   => 'text',
        'ppcart_downsell_nonce' => 'text',

        // Groups whose keys come from product configuration.
        'ppcart_custom_fields' => [ 'group' => 'text', 'keys' => 'text' ],
        'pwyw_amount'          => [ 'group' => 'price', 'keys' => 'text' ],
        'ppcart-orderbump'     => [ 'group' => 'text', 'keys' => 'text' ],
    ];

    foreach (ppcart_checkout_buyer_field_names($product) as $name) {
        if (! isset($schema[ $name ])) {
            $schema[ $name ] = ('email' === $name) ? 'email' : 'text';
        }
    }

    return $schema;
}

/**
 * Read the checkout / payment form payload by field.
 *
 * Callers verify the checkout nonce before consuming the result.
 *
 * @param array|null $source  Request values. Defaults to POST.
 * @param mixed      $product Product object the form belongs to, when known.
 * @return array
 */
function ppcart_parse_checkout_request($source = null, $product = null)
{
    if (! is_array($source)) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Callers verify the checkout nonce; every value is sanitized by field below.
        $source = isset($_POST) && is_array($_POST) ? $_POST : [];
    }

    if (null === $product && isset($GLOBALS['ppcart_product'])) {
        $product = $GLOBALS['ppcart_product'];
    }

    return ppcart_sanitize_request_fields($source, ppcart_checkout_request_schema($product), 'checkout');
}

/**
 * Read the "name your own price" amounts submitted for a product's plans.
 *
 * Keys are plan option ids and values are prices compared against the plan price.
 * A plan id is the merchant-entered "Plan ID" field, so it is arbitrary text such as
 * `pro-annual`, not a number.
 *
 * @param mixed $value   Raw `pwyw_amount` array.
 * @param bool  $unslash Whether to unslash. Pass false for filter_input() output.
 * @return array
 */
function ppcart_parse_pwyw_amounts($value, $unslash = true)
{
    if (! is_array($value)) {
        return [];
    }

    if ($unslash) {
        $value = wp_unslash($value);
    }

    return ppcart_sanitize_request_group($value, 'price', 'text');
}

/**
 * Read the PayPal IPN fields this plugin consumes.
 *
 * Only the consumed copy of the payload is narrowed. The raw payload must be kept
 * intact for ppcart_verifyTransaction(), which echoes every submitted key back to
 * PayPal as the `cmd=_notify-validate` body.
 *
 * @param mixed $source Raw IPN request array.
 * @return array
 */
function ppcart_parse_paypal_ipn_fields($source)
{
    return ppcart_sanitize_request_fields(
        $source,
        [
            'payer_email'    => 'email',
            'receiver_email' => 'email',
            'txn_id'         => 'text',
            'parent_txn_id'  => 'text',
            'txn_type'       => 'text',
            'subscr_id'      => 'text',
            'item_name'      => 'text',
            'item_number'    => 'text',
            'payment_status' => 'text',
            // PayPal always sends amounts with a dot decimal separator, so these stay
            // text rather than going through locale-aware price normalization.
            'mc_gross'       => 'text',
            'mc_currency'    => 'text',
            'amount1'        => 'text',
            'payment_gross'  => 'text',
            'ipn_track_id'   => 'text',
            'custom'         => 'text',
        ],
        'paypal_ipn'
    );
}

/**
 * Read the Stripe order-status response the browser posts back.
 *
 * The array is echoed to the browser again, which navigates to `formAction`, so URL
 * values are sanitized with esc_url_raw() to keep their query separators.
 *
 * @param mixed $source  Raw `response` array.
 * @param bool  $unslash Whether to unslash. Pass false for values already unslashed at the read site.
 * @return array
 */
function ppcart_parse_stripe_status_response($source, $unslash = true)
{
    return ppcart_sanitize_request_fields(
        $source,
        [
            'formAction'              => 'url_raw',
            'redirect'                => 'url_raw',
            'order_id'                => 'int',
            'ppcart_order_id'         => 'int',
            'ppcart_temp_order_id'    => 'int',
            'prod_id'                 => 'int',
            'ppcart_temp_order_token' => 'text',
            'intent_id'               => 'text',
            'customer_id'             => 'text',
            'clientSecret'            => 'text',
            'paymentMethodId'         => 'text',
            'preloadedIntent'         => 'text',
            'directConfirmation'      => 'text',
            'amount'                  => 'text',
            'error'                   => 'text',
            'vat_error'               => 'text',
            'is_vat'                  => 'text',
            // 'fields' is deliberately absent: it is a nested validation-error list that
            // only appears on error responses, which are never posted back here.
        ],
        'stripe_order_status_response',
        $unslash
    );
}

/**
 * Read the Stripe payment intent summary posted alongside an order-status update.
 *
 * @param mixed $source  Raw `paymentIntent` array.
 * @param bool  $unslash Whether to unslash. Pass false for filter_input() output.
 * @return array
 */
function ppcart_parse_stripe_payment_intent($source, $unslash = true)
{
    return ppcart_sanitize_request_fields(
        $source,
        [
            'id'            => 'text',
            'status'        => 'text',
            'client_secret' => 'text',
        ],
        'stripe_payment_intent',
        $unslash
    );
}

/**
 * Read the admin order-refund request by field.
 *
 * @param mixed $source Raw request array.
 * @return array
 */
function ppcart_parse_order_refund_request($source)
{
    return ppcart_sanitize_request_fields(
        $source,
        [
            'id'            => 'int',
            'refund_amount' => 'price',
            'mode'          => 'text',
            'restock'       => 'text',
            'nonce'         => 'text',
        ],
        'order_refund'
    );
}

/**
 * Read the order fields the tracking scripts need.
 *
 * @param mixed $source  Raw `ppcart_order` array.
 * @param bool  $unslash Whether to unslash. Pass false for filter_input() output.
 * @return array
 */
function ppcart_parse_tracking_order_fields($source, $unslash = true)
{
    return ppcart_sanitize_request_fields(
        $source,
        [
            'ID'         => 'int',
            'pay_method' => 'text',
        ],
        'tracking_order',
        $unslash
    );
}

/**
 * Read the admin AJAX request by field.
 *
 * The free plugin defines no ppcart_<action> handlers, so only the dispatcher's own
 * fields are declared here. Handlers shipped by extensions declare the fields they
 * expect through ppcart_expected_request_fields, using the 'admin_ajax:<action>' context.
 *
 * @param mixed  $source Raw request array.
 * @param string $action Sanitized ppcart_action value.
 * @return array
 */
function ppcart_parse_admin_ajax_request($source, $action)
{
    return ppcart_sanitize_request_fields(
        $source,
        [
            'ppcart_action'     => 'key',
            'ppcart_ajax_nonce' => 'text',
        ],
        'admin_ajax:' . $action
    );
}
