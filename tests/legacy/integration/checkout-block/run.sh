#!/usr/bin/env bash
set -euo pipefail

PLUGIN_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../.." && pwd)"
TEST_FILE="$PLUGIN_ROOT_DIR/tests/legacy/integration/checkout-block/gutenberg-checkout-block.php"

if command -v php >/dev/null 2>&1; then
    php "$TEST_FILE"
elif command -v frankenphp >/dev/null 2>&1; then
    frankenphp php-cli "$TEST_FILE"
elif [ -x /usr/local/bin/frankenphp ]; then
    /usr/local/bin/frankenphp php-cli "$TEST_FILE"
else
    echo "Unable to find php or frankenphp to run integration tests." >&2
    exit 1
fi
