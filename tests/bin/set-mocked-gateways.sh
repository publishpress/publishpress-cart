#!/usr/bin/env bash
# Flip WordPress + .env gateway mock flags for regression Playwright runs.
# Usage: set-mocked-gateways.sh <1|0>
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/lib/wp-env.sh"

regression_load_dotenv

MODE="${1:-}"
case "$MODE" in
	1|0)
		;;
	*)
		echo "Usage: $0 <1|0>" >&2
		echo "  1 = enable mocked Stripe/PayPal for test:regression:mocked" >&2
		echo "  0 = restore real gateway mode for test:regression" >&2
		exit 2
		;;
esac

PLUGIN_ROOT="$(regression_plugin_root)"
ENV_FILE="$PLUGIN_ROOT/.env"

if [ ! -f "$ENV_FILE" ]; then
	echo "Missing $ENV_FILE. Run composer test:regression:setup first." >&2
	exit 1
fi

if ! REGRESSION_WP_PATH="$(regression_resolve_wp_path)"; then
	REGRESSION_WP_PATH="$PLUGIN_ROOT/.ci-wordpress"
fi
export REGRESSION_WP_PATH

regression_require_wp_cli

if [ "$MODE" = "1" ]; then
	echo "==> Enabling mocked gateways on WordPress"
	regression_set_env_var "$ENV_FILE" "REGRESSION_MOCKED_GATEWAYS" "1"
	regression_set_env_var "$ENV_FILE" "REGRESSION_REAL_PAYPAL" "0"
	export REGRESSION_MOCKED_GATEWAYS=1
	export REGRESSION_REAL_PAYPAL=0

	regression_wp eval '
		update_option( "ppcart_regression_mocked_gateways", "1" );
		update_option( "ppcart_regression_real_paypal", "0" );
		if ( class_exists( "PPCart_Regression_PayPal_Mock" ) ) {
			PPCart_Regression_PayPal_Mock::ensure_page();
		}
		$encrypt_flag = get_option( "_ppcart_encrypt_secrets", false );
		if ( class_exists( "PPCart_Secrets" ) && is_string( $encrypt_flag ) && PPCart_Secrets::is_encrypted_value( $encrypt_flag ) && false === PPCart_Secrets::decrypt_value( $encrypt_flag ) ) {
			update_option( "_ppcart_encrypt_secrets", "0" );
		}
		$stripe_usable = function_exists( "ppcart_get_stripe_platform_credentials_status" )
			? ! empty( ppcart_get_stripe_platform_credentials_status()["is_usable"] )
			: false;
		if ( "1" !== (string) get_option( "_ppcart_stripe_enable" ) || ! $stripe_usable ) {
			update_option( "_ppcart_stripe_enable", "1" );
			update_option( "_ppcart_stripe_api", "test" );
			update_option( "_ppcart_stripe_test_pk", "pk_test_mock_regression_publishable_key_00000000000000000000000000" );
			update_option( "_ppcart_stripe_test_sk", "sk_test_mock_regression_secret_key_000000000000000000000000000000" );
			update_option( "_ppcart_stripe_test_key_source", "direct" );
			update_option( "_ppcart_stripe_payment_element_enable", "" );
			delete_option( "_ppcart_stripe_connect_account_id_test" );
		}
		if ( "1" !== (string) get_option( "_ppcart_paypal_enable" ) ) {
			update_option( "_ppcart_paypal_enable", "1" );
			update_option( "_ppcart_paypal_enable_sandbox", "enable" );
			if ( ! get_option( "_ppcart_paypal_sandbox_email" ) ) {
				update_option( "_ppcart_paypal_sandbox_email", "regression-mock-merchant@example.com" );
			}
		}
		echo wp_json_encode( array(
			"mocked_gateways" => get_option( "ppcart_regression_mocked_gateways" ),
			"real_paypal"     => get_option( "ppcart_regression_real_paypal" ),
			"stripe_enable"   => get_option( "_ppcart_stripe_enable" ),
			"paypal_enable"   => get_option( "_ppcart_paypal_enable" ),
			"stripe_usable"   => function_exists( "ppcart_get_stripe_platform_credentials_status" )
				? ! empty( ppcart_get_stripe_platform_credentials_status()["is_usable"] )
				: null,
		), JSON_UNESCAPED_SLASHES );
	'
	echo ""
	echo "Mocked gateways enabled."
else
	echo "==> Restoring real gateway mode on WordPress"
	regression_set_env_var "$ENV_FILE" "REGRESSION_MOCKED_GATEWAYS" "0"
	regression_set_env_var "$ENV_FILE" "REGRESSION_REAL_PAYPAL" "1"
	export REGRESSION_MOCKED_GATEWAYS=0
	export REGRESSION_REAL_PAYPAL=1

	regression_wp eval '
		update_option( "ppcart_regression_mocked_gateways", "0" );
		update_option( "ppcart_regression_real_paypal", "1" );
		echo wp_json_encode( array(
			"mocked_gateways" => get_option( "ppcart_regression_mocked_gateways" ),
			"real_paypal"     => get_option( "ppcart_regression_real_paypal" ),
		), JSON_UNESCAPED_SLASHES );
	'
	echo ""
	echo "Real gateway mode restored."

	HAS_MOCK_PRICES="$(regression_wp eval 'echo ( class_exists( "PPCart_Regression_Fixtures" ) && PPCart_Regression_Fixtures::has_mock_stripe_price_ids() ) ? "1" : "0";' 2>/dev/null || true)"
	if [ "${HAS_MOCK_PRICES}" = "1" ]; then
		echo "==> Regression products still have mock Stripe price IDs; re-syncing against real Stripe"
		REGRESSION_MOCKED_GATEWAYS=0 REGRESSION_REAL_PAYPAL=1 bash "$SCRIPT_DIR/setup-site.sh" regression
	fi
fi
