#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/lib/wp-env.sh"

regression_load_dotenv

if [ -z "${WP_TESTS_ADMIN_USER:-}" ] || [ -z "${WP_TESTS_ADMIN_PASSWORD:-}" ]; then
	echo "Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env before running admin tests." >&2
	exit 1
fi

regression_require_wp_cli

ADMIN_WORKFLOW_RUN_ID="${ADMIN_WORKFLOW_RUN_ID:-$(date -u +%Y%m%d%H%M%S)-$$}"
export ADMIN_WORKFLOW_RUN_ID
export ADMIN_WORKFLOW_PRODUCT_TITLE="${ADMIN_WORKFLOW_PRODUCT_TITLE:-PP Cart Playwright Product ${ADMIN_WORKFLOW_RUN_ID}}"
export ADMIN_WORKFLOW_META_PRODUCT_TITLE="${ADMIN_WORKFLOW_META_PRODUCT_TITLE:-PP Cart Playwright Meta Product ${ADMIN_WORKFLOW_RUN_ID}}"
export ADMIN_WORKFLOW_PUBLIC_PRODUCT_NAME="${ADMIN_WORKFLOW_PUBLIC_PRODUCT_NAME:-Playwright Public Name ${ADMIN_WORKFLOW_RUN_ID}}"
export ADMIN_WORKFLOW_CATEGORY_SLUG="${ADMIN_WORKFLOW_CATEGORY_SLUG:-ppcart-pw-${ADMIN_WORKFLOW_RUN_ID}-category}"
export ADMIN_WORKFLOW_TAG_SLUG="${ADMIN_WORKFLOW_TAG_SLUG:-ppcart-pw-${ADMIN_WORKFLOW_RUN_ID}-tag}"
# Prefer ADMIN_PLAYWRIGHT_WORKERS from .env, then shared PW_WORKERS, else 1.
# Admin defaults to serial because specs share one WordPress site.
ADMIN_PLAYWRIGHT_WORKERS="${ADMIN_PLAYWRIGHT_WORKERS:-${PW_WORKERS:-1}}"

cleanup_admin_workflow() {
	set +e
	echo ""
	echo "==> Cleaning admin workflow state"
	regression_wp eval-file "$SCRIPT_DIR/lib/cleanup-admin-workflow.php"
}

trap cleanup_admin_workflow EXIT

echo "==> Preparing admin workflow state (${ADMIN_WORKFLOW_RUN_ID})"
regression_wp eval-file "$SCRIPT_DIR/lib/prepare-admin-workflow.php"

echo "==> Running admin Playwright tests (workers=${ADMIN_PLAYWRIGHT_WORKERS})"
npm run test:e2e:admin:raw -- --workers="$ADMIN_PLAYWRIGHT_WORKERS" "$@"
