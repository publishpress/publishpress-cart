#!/usr/bin/env bash
set -euo pipefail

PLUGIN_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"

if [ -f "$PLUGIN_ROOT_DIR/.env" ]; then
    set -a
    # shellcheck disable=SC1091
    source "$PLUGIN_ROOT_DIR/.env"
    set +a
fi

GUTENBERG_DIR="$PLUGIN_ROOT_DIR/includes/integrations/gutenberg"
WP_CLI="${WP_CLI:-wp}"
WP_ARGS=()

if [ -n "${WP_PATH:-}" ]; then
    WP_ARGS+=(--path="$WP_PATH")
fi

if [ -n "${WP_CLI_ARGS:-}" ]; then
    # shellcheck disable=SC2206
    WP_ARGS+=($WP_CLI_ARGS)
fi

WP_ARGS+=(--allow-root)

created_user_id=""
created_product_id=""
created_page_id=""

cleanup() {
    local exit_code=$?

    if [ -n "$created_page_id" ]; then
        "$WP_CLI" post delete "$created_page_id" --force "${WP_ARGS[@]}" >/dev/null 2>&1 || true
    fi

    if [ -n "$created_product_id" ]; then
        "$WP_CLI" post delete "$created_product_id" --force "${WP_ARGS[@]}" >/dev/null 2>&1 || true
    fi

    if [ -n "$created_user_id" ]; then
        "$WP_CLI" user delete "$created_user_id" --reassign=1 --yes "${WP_ARGS[@]}" >/dev/null 2>&1 || true
    fi

    exit "$exit_code"
}
trap cleanup EXIT

if ! command -v "$WP_CLI" >/dev/null 2>&1; then
    echo "Unable to find WP-CLI. Set WP_CLI to the wp executable path." >&2
    exit 1
fi

if ! command -v npm >/dev/null 2>&1; then
    echo "Unable to find npm. Install Node.js dependencies before running E2E tests." >&2
    exit 1
fi

if [ ! -d "$PLUGIN_ROOT_DIR/node_modules/@playwright/test" ]; then
    echo "Missing Playwright dependency. Run: npm install" >&2
    exit 1
fi

if ! npm --prefix "$PLUGIN_ROOT_DIR" exec -- playwright --version >/dev/null 2>&1; then
    echo "Unable to run Playwright. Run: npm install" >&2
    exit 1
fi

if ! ( cd "$GUTENBERG_DIR" && node -e "const { chromium } = require('@playwright/test'); chromium.launch().then((browser) => browser.close()).catch((error) => { console.error(error.message); process.exit(1); });" ) >/dev/null 2>&1; then
    echo "Unable to launch Playwright Chromium. Run: npm exec -- playwright install chromium" >&2
    exit 1
fi

WP_BASE_URL="${WP_BASE_URL:-$("$WP_CLI" option get siteurl "${WP_ARGS[@]}")}"
WP_BASE_URL="${WP_BASE_URL%/}"

if [ -z "${WP_ADMIN_USER:-}" ] || [ -z "${WP_ADMIN_PASSWORD:-}" ]; then
    wp_user_login="ppcart_e2e_admin_$(date +%s)"
    wp_user_email="${wp_user_login}@example.invalid"
    wp_user_password="ppcart-e2e-$(date +%s)-$RANDOM"
    created_user_id="$("$WP_CLI" user create "$wp_user_login" "$wp_user_email" --role=administrator --user_pass="$wp_user_password" --porcelain "${WP_ARGS[@]}")"
    export WP_ADMIN_USER="$wp_user_login"
    export WP_ADMIN_PASSWORD="$wp_user_password"
fi

if [ -z "${PPCART_E2E_PRODUCT_ID:-}" ]; then
    product_title="PublishPress Cart E2E Product $(date +%s)"
    created_product_id="$("$WP_CLI" post create --post_type=sc_product --post_status=publish --post_title="$product_title" --porcelain "${WP_ARGS[@]}")"

    PPCART_FIXTURE_PRODUCT_ID="$created_product_id" "$WP_CLI" eval '
        $product_id = (int) getenv( "PPCART_FIXTURE_PRODUCT_ID" );
        update_post_meta(
            $product_id,
            "_ppcart_pay_options",
            array(
                array(
                    "option_id"                 => "e2e_plan",
                    "option_name"               => "E2E Plan",
                    "price"                     => "100",
                    "frequency"                 => "1",
                    "sale_frequency"            => "1",
                    "interval"                  => "day",
                    "sale_interval"             => "day",
                    "installments"              => "-1",
                    "sale_installments"         => "-1",
                    "stripe_plan_id"            => "e2e_plan",
                ),
                array(
                    "option_id"                 => "e2e_plan_200",
                    "option_name"               => "E2E Plan 200",
                    "price"                     => "200",
                    "frequency"                 => "1",
                    "sale_frequency"            => "1",
                    "interval"                  => "day",
                    "sale_interval"             => "day",
                    "installments"              => "-1",
                    "sale_installments"         => "-1",
                    "stripe_plan_id"            => "e2e_plan_200",
                ),
            )
        );
        update_post_meta( $product_id, "_ppcart_plan_heading", "Payment Plan" );
        update_post_meta( $product_id, "_ppcart_button_color", "#000000" );
        update_post_meta( $product_id, "_ppcart_button_text", "Order Now" );
        update_post_meta( $product_id, "_ppcart_step1_button_label", "Continue" );
        update_post_meta( $product_id, "_ppcart_step1_button_icon_pos", "left" );
        update_post_meta( $product_id, "_ppcart_enabled_gateways", "" );
        update_post_meta( $product_id, "_ppcart_show_coupon_field", "1" );
        update_post_meta( $product_id, "_ppcart_checkout_ended_action", "message" );
        update_post_meta( $product_id, "_ppcart_checkout_ended_message", "Sorry, this product is no longer for sale." );
    ' "${WP_ARGS[@]}"

    export PPCART_E2E_PRODUCT_ID="$created_product_id"
else
    export PPCART_E2E_PRODUCT_ID
fi

if [ -z "${PPCART_E2E_PAGE_ID:-}" ]; then
    page_title="PublishPress Cart E2E Checkout $(date +%s)"
    created_page_id="$("$WP_CLI" post create --post_type=page --post_status=publish --post_title="$page_title" --porcelain "${WP_ARGS[@]}")"
    export PPCART_E2E_PAGE_ID="$created_page_id"
else
    export PPCART_E2E_PAGE_ID
fi

export WP_BASE_URL
export PPCART_E2E_PAGE_URL="${PPCART_E2E_PAGE_URL:-$WP_BASE_URL/?page_id=$PPCART_E2E_PAGE_ID}"
export PPCART_E2E_SCREENSHOT_DIR="${PPCART_E2E_SCREENSHOT_DIR:-$PLUGIN_ROOT_DIR/tests/e2e-results/screenshots}"

cd "$PLUGIN_ROOT_DIR"
npm run test:e2e:gutenberg -- "$@"
