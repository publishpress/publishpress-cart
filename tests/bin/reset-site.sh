#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/lib/wp-env.sh"

regression_load_dotenv
regression_require_wp_cli

PLUGIN_ROOT="$(regression_plugin_root)"
ENV_FILE="$PLUGIN_ROOT/.env"
RESET_PHP="$SCRIPT_DIR/lib/reset-fixtures.php"
RESET_SCOPE="${1:-all}"

case "$RESET_SCOPE" in
	admin|regression|all)
		;;
	*)
		echo "Usage: $0 [admin|regression|all]" >&2
		exit 2
		;;
esac

echo "==> Regression target"
regression_print_target
echo ""

if [ "$RESET_SCOPE" = "regression" ] || [ "$RESET_SCOPE" = "all" ]; then
	echo "==> Removing regression products and checkout pages"
	RESULT="$(regression_wp eval-file "$RESET_PHP")"
	echo "$RESULT"
fi

if [ "$RESET_SCOPE" = "admin" ] || [ "$RESET_SCOPE" = "all" ]; then
	echo "==> Removing admin test fixtures"
	regression_wp eval-file "$SCRIPT_DIR/lib/reset-admin-fixtures.php"
fi

echo "==> Flushing rewrite rules"
regression_wp rewrite flush >/dev/null

if [ -f "$ENV_FILE" ]; then
	if [ "$RESET_SCOPE" = "regression" ] || [ "$RESET_SCOPE" = "all" ]; then
		echo "==> Resetting URL_* and CHECKOUT_* variables in .env to \"/\""
		regression_reset_url_env_vars "$ENV_FILE"
	fi

	if [ "$RESET_SCOPE" = "admin" ] || [ "$RESET_SCOPE" = "all" ]; then
		echo "==> Clearing ADMIN_* fixture variables in .env"
		regression_reset_admin_env_vars "$ENV_FILE"
	fi
else
	echo "No .env file found; skipped env reset."
fi

echo ""
case "$RESET_SCOPE" in
	admin)
		echo "Admin site reset complete."
		;;
	regression)
		echo "Regression site reset complete."
		;;
	all)
		echo "Combined site reset complete."
		;;
esac
