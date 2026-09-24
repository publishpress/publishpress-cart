<?php

/**
 * Enables Stripe and PayPal for regression browser tests on the current WordPress install.
 */

if ( ! defined( 'PPCART_FIXTURES_ENABLED' ) ) {
	define( 'PPCART_FIXTURES_ENABLED', true );
}

/**
 * @return array<string, string>
 */
function regression_read_env_file( string $file ): array {
	$values = array();

	if ( ! is_readable( $file ) ) {
		return $values;
	}

	$lines = file( $file, FILE_IGNORE_NEW_LINES );

	if ( false === $lines ) {
		return $values;
	}

	foreach ( $lines as $line ) {
		$line = trim( $line );

		if ( '' === $line || str_starts_with( $line, '#' ) || ! str_contains( $line, '=' ) ) {
			continue;
		}

		[ $key, $value ] = array_map( 'trim', explode( '=', $line, 2 ) );
		$values[ $key ]  = trim( $value, " \t\"'" );
	}

	return $values;
}

/**
 * @param array<string, string> $env
 */
function regression_env_value( array $env, string $key ): string {
	$value = getenv( $key );

	if ( false !== $value && '' !== $value ) {
		return (string) $value;
	}

	if ( isset( $env[ $key ] ) && '' !== $env[ $key ] ) {
		return $env[ $key ];
	}

	return '';
}

function regression_is_usable_stripe_publishable_key( string $key ): bool {
	return 1 === preg_match( '/^pk_test_/', $key );
}

function regression_is_usable_stripe_secret_key( string $key ): bool {
	return 1 === preg_match( '/^(sk|rk)_test_/', $key );
}

function regression_env_flag_enabled( array $env, string $key ): bool {
	$value = strtolower( regression_env_value( $env, $key ) );

	return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}

$plugin_root           = dirname( __DIR__, 3 );
$env                   = regression_read_env_file( $plugin_root . '/.env' );
$publishable_key       = regression_env_value( $env, 'STRIPE_TEST_PUBLISHABLE_KEY' );
$secret_key            = regression_env_value( $env, 'STRIPE_TEST_SECRET_KEY' );
$key_source            = regression_env_value( $env, 'STRIPE_TEST_KEY_SOURCE' );
$connect_account       = regression_env_value( $env, 'STRIPE_TEST_CONNECT_ACCOUNT_ID' );
$paypal_sandbox_email  = regression_env_value( $env, 'PAYPAL_SANDBOX_EMAIL' );
$paypal_sandbox_client = regression_env_value( $env, 'PAYPAL_SANDBOX_CLIENT_ID' );
$paypal_sandbox_secret = regression_env_value( $env, 'PAYPAL_SANDBOX_SECRET' );
$regression_currency   = strtoupper( regression_env_value( $env, 'REGRESSION_CURRENCY' ) );
$mocked_gateways       = regression_env_flag_enabled( $env, 'REGRESSION_MOCKED_GATEWAYS' );

$result = array(
	'stripe_enabled'      => false,
	'paypal_enabled'      => false,
	'cash_on_delivery'    => (bool) get_option( '_ppcart_cashondelivery_enable' ),
	'publishable_key_set' => false,
	'secret_key_set'      => false,
	'paypal_email_set'    => false,
	'currency_set'        => false,
	'currency'            => (string) get_option( '_ppcart_currency', '' ),
	'credentials_source'  => 'none',
	'real_paypal_enabled' => false,
	'mocked_gateways'     => $mocked_gateways,
);

$has_usable_keys = regression_is_usable_stripe_publishable_key( $publishable_key )
	&& regression_is_usable_stripe_secret_key( $secret_key );
$used_mock_keys  = false;

if ( $mocked_gateways && ! $has_usable_keys ) {
	$publishable_key = 'pk_test_mock_regression_publishable_key_00000000000000000000000000';
	$secret_key      = 'sk_test_mock_regression_secret_key_000000000000000000000000000000';
	$key_source      = 'direct';
	$has_usable_keys = true;
	$used_mock_keys  = true;
}

if ( $has_usable_keys ) {
	$key_source = '' !== $key_source ? $key_source : 'direct';

	if ( $mocked_gateways ) {
		$key_source = 'direct';
	}

	update_option( '_ppcart_stripe_enable', '1' );
	update_option( '_ppcart_stripe_api', 'test' );
	update_option( '_ppcart_stripe_test_pk', $publishable_key );
	update_option( '_ppcart_stripe_test_sk', $secret_key );
	update_option( '_ppcart_stripe_test_key_source', $key_source );
	// Card Element matches Ghost Inspector iframe selectors; Payment Element needs a mount-time intent.
	update_option( '_ppcart_stripe_payment_element_enable', '' );

	// Regression checkout tests do not exercise Connect destination transfers.
	delete_option( '_ppcart_stripe_connect_account_id_test' );

	update_option( '_ppcart_cashondelivery_enable', '' );

	$result['stripe_enabled']      = true;
	$result['cash_on_delivery']    = false;
	$result['publishable_key_set'] = true;
	$result['secret_key_set']      = true;
	$result['connect_account_set'] = false;
	$result['credentials_source']  = $used_mock_keys ? 'mocked' : 'env';
}

if ( '' === $paypal_sandbox_email ) {
	$paypal_sandbox_email = (string) get_option( '_ppcart_paypal_sandbox_email', '' );
}

if ( $mocked_gateways && '' === $paypal_sandbox_email ) {
	$paypal_sandbox_email = 'regression-mock-merchant@example.com';
}

if ( '' !== $paypal_sandbox_email ) {
	update_option( '_ppcart_paypal_enable', '1' );
	update_option( '_ppcart_paypal_enable_sandbox', 'enable' );
	update_option( '_ppcart_paypal_sandbox_email', $paypal_sandbox_email );

	if ( '' !== $paypal_sandbox_client ) {
		update_option( '_ppcart_paypal_sandbox_client_id', $paypal_sandbox_client );
	}

	if ( '' !== $paypal_sandbox_secret ) {
		update_option( '_ppcart_paypal_sandbox_secret', $paypal_sandbox_secret );
	}

	$result['paypal_enabled']   = true;
	$result['paypal_email_set'] = true;
}

if ( '' !== $regression_currency && 1 === preg_match( '/^[A-Z]{3}$/', $regression_currency ) ) {
	update_option( '_ppcart_currency', $regression_currency );
	$result['currency_set'] = true;
	$result['currency']     = $regression_currency;
}

$real_paypal_flag = strtolower( regression_env_value( $env, 'REGRESSION_REAL_PAYPAL' ) );
$real_paypal_enabled = '' === $real_paypal_flag
	|| in_array( $real_paypal_flag, array( '1', 'true', 'yes', 'on' ), true );

if ( $mocked_gateways ) {
	$real_paypal_enabled = false;
}

update_option( 'ppcart_regression_real_paypal', $real_paypal_enabled ? '1' : '0' );
update_option( 'ppcart_regression_mocked_gateways', $mocked_gateways ? '1' : '0' );
$result['real_paypal_enabled'] = $real_paypal_enabled;
$result['mocked_gateways']     = $mocked_gateways;

echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
