#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/lib/wp-env.sh"

regression_load_dotenv

PLUGIN_ROOT="$(regression_plugin_root)"
ENV_FILE="$PLUGIN_ROOT/.env"
SETUP_PHP="$SCRIPT_DIR/lib/setup-fixtures.php"
SETUP_SCOPE="${1:-all}"

case "$SETUP_SCOPE" in
	admin|regression|all)
		;;
	*)
		echo "Usage: $0 [admin|regression|all]" >&2
		exit 2
		;;
esac

if [ ! -f "$ENV_FILE" ]; then
	echo "==> Creating .env from .env.example"
	cp "$PLUGIN_ROOT/.env.example" "$ENV_FILE"
fi

# Host WP-CLI is required for Local WP / bare-metal installs. Docker runtimes
# (official wordpress image sidecar, or containers that ship `wp` such as DDEV)
# supply WP-CLI later in regression_require_wp_cli.
if ! command -v "${WP_CLI:-wp}" >/dev/null 2>&1; then
	if [ -z "${REGRESSION_WP_CONTAINER:-}" ] && ! command -v docker >/dev/null 2>&1; then
		echo "Unable to find WP-CLI. Set WP_CLI to the wp executable path," >&2
		echo "or configure REGRESSION_WP_CONTAINER for a Docker/DDEV WordPress site." >&2
		exit 1
	fi
fi

# Resolve the WordPress install this script configures - an explicit
# REGRESSION_WP_PATH/WP_PATH, or Local by Flywheel auto-detection, same as
# always. If neither finds one (a fresh CI checkout, never true for local
# dev), default to a CI-managed path and provision a throwaway install
# there from scratch.
if ! REGRESSION_WP_PATH="$(regression_resolve_wp_path)"; then
	REGRESSION_WP_PATH="$PLUGIN_ROOT/.ci-wordpress"
fi
export REGRESSION_WP_PATH

regression_ensure_wordpress_provisioned "$REGRESSION_WP_PATH"

# Reload .env so variables in this shell reflect the provisioned site
# (URL, admin credentials and sanitized gateway credentials).
unset PUBLISHPRESS_CART_HOST WP_TESTS_URL WP_TESTS_ADMIN_USER WP_TESTS_ADMIN_PASSWORD \
	REGRESSION_WP_PATH REGRESSION_WP_CONTAINER REGRESSION_PLUGIN_CONTAINER_PATH \
	REGRESSION_WP_RUNTIME REGRESSION_WP_PATH_RESOLVED
regression_load_dotenv

regression_require_wp_cli

echo "==> WordPress target"
regression_print_target
echo ""

echo "==> Linking fixtures plugin"
regression_link_fixtures_plugin

echo "==> Linking PublishPress Cart plugin"
regression_link_cart_plugin

echo "==> Ensuring required plugins are active"
regression_ensure_plugin_active publishpress-cart
regression_ensure_plugin_active ppcart-fixtures

echo "==> Ensuring Cart order-item tables exist"
regression_wp eval 'if ( class_exists( "PPCart_Order_Items" ) ) { ( new PPCart_Order_Items() )->setup_items_table(); } echo "ok";'

if [ "$SETUP_SCOPE" = "admin" ] || [ "$SETUP_SCOPE" = "all" ]; then
	echo "==> Installing Classic Editor for the admin product workflow tests"
	regression_wp plugin install classic-editor --activate
fi

if [ "$SETUP_SCOPE" = "regression" ] || [ "$SETUP_SCOPE" = "all" ]; then
	MOCKED_GATEWAYS=0
	case "${REGRESSION_MOCKED_GATEWAYS:-0}" in
		1|true|TRUE|yes|YES|on|ON) MOCKED_GATEWAYS=1 ;;
	esac

	if [ "$MOCKED_GATEWAYS" = "1" ]; then
		echo "==> Mocked gateways mode (REGRESSION_MOCKED_GATEWAYS=1)"
		regression_set_env_var "$ENV_FILE" "REGRESSION_MOCKED_GATEWAYS" "1"
		regression_set_env_var "$ENV_FILE" "REGRESSION_REAL_PAYPAL" "0"
		export REGRESSION_MOCKED_GATEWAYS=1
		export REGRESSION_REAL_PAYPAL=0
	else
		regression_set_env_var "$ENV_FILE" "REGRESSION_MOCKED_GATEWAYS" "0"
	fi

	echo "==> Configuring payment gateways for regression tests"
	GATEWAYS_PHP="$SCRIPT_DIR/lib/configure-gateways.php"

	GATEWAY_RESULT="$(regression_wp eval-file "$GATEWAYS_PHP")"
	echo "$GATEWAY_RESULT"

	if [ "$MOCKED_GATEWAYS" != "1" ]; then
		if regression_stripe_env_credentials_usable && ! echo "$GATEWAY_RESULT" | php -r '$d=json_decode(stream_get_contents(STDIN), true); exit(!empty($d["stripe_enabled"]) && !empty($d["secret_key_set"]) ? 0 : 1);'; then
			echo "WARNING: Stripe credentials from .env were not applied to WordPress." >&2
		fi

		if [ -n "${PAYPAL_SANDBOX_EMAIL:-}" ] && ! echo "$GATEWAY_RESULT" | php -r '$d=json_decode(stream_get_contents(STDIN), true); exit(!empty($d["paypal_enabled"]) ? 0 : 1);'; then
			echo "WARNING: PayPal sandbox email from .env was not applied to WordPress." >&2
		fi

		if regression_stripe_env_credentials_usable \
			&& [ "${STRIPE_TEST_KEY_SOURCE:-direct}" = "oauth_access_token" ] \
			&& ! regression_stripe_connect_configured; then
			echo "WARNING: STRIPE_TEST_CONNECT_ACCOUNT_ID is missing. Stripe checkout may fail on this Cart version." >&2
			echo "Copy the connected account id from Cart settings into .env when using Stripe Connect." >&2
		fi

		if ! regression_stripe_env_credentials_usable; then
			echo "WARNING: Stripe test credentials were not found in .env." >&2
			echo "Set STRIPE_TEST_PUBLISHABLE_KEY and STRIPE_TEST_SECRET_KEY before running Stripe regression tests." >&2
		fi

		if [ -z "${PAYPAL_SANDBOX_EMAIL:-}" ]; then
			echo "WARNING: PAYPAL_SANDBOX_EMAIL was not found in .env." >&2
			echo "Set PAYPAL_SANDBOX_EMAIL to your PayPal sandbox business email before running PayPal regression tests." >&2
		fi
	fi

	echo "==> Creating regression products and checkout pages"
	RESULT="$(regression_wp eval-file "$SETUP_PHP")"
	echo "$RESULT"

	echo "==> Updating URL_* variables in .env"
	regression_sync_url_env_vars "$ENV_FILE" "$RESULT"
fi

if [ "$SETUP_SCOPE" = "admin" ] || [ "$SETUP_SCOPE" = "all" ]; then
	echo "==> Creating admin Playwright fixture bundle"
	ADMIN_FIXTURES_PHP="$SCRIPT_DIR/lib/setup-admin-fixtures.php"
	ADMIN_RESULT="$(regression_wp eval-file "$ADMIN_FIXTURES_PHP")"
	echo "$ADMIN_RESULT"

	echo "==> Updating ADMIN_* variables in .env"
	regression_sync_admin_env_vars "$ENV_FILE" "$ADMIN_RESULT"
fi

echo "==> Flushing rewrite rules"
regression_wp rewrite flush >/dev/null

echo ""
case "$SETUP_SCOPE" in
	admin)
		echo "Admin fixture setup complete."
		echo "Run: composer test:admin"
		;;
	regression)
		echo "Regression fixture setup complete."
		if [ "${REGRESSION_MOCKED_GATEWAYS:-0}" = "1" ]; then
			echo "Mocked gateways enabled. Run: composer test:regression:mocked"
		else
			if ! regression_stripe_env_credentials_usable; then
				echo "Stripe credentials must be set in .env (see .env.example)."
			fi
			echo "Run: composer test:regression"
		fi
		;;
	all)
		echo "Combined regression/admin fixture setup complete."
		echo "Run: composer test:regression or composer test:admin"
		;;
esac
