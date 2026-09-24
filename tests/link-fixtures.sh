#!/usr/bin/env bash
set -euo pipefail

PLUGIN_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FIXTURES_DIR="$PLUGIN_ROOT_DIR/tests/ppcart-fixtures"

if [ -f "$PLUGIN_ROOT_DIR/.env" ]; then
	set -a
	# shellcheck disable=SC1091
	source "$PLUGIN_ROOT_DIR/.env"
	set +a
fi

if [ -z "${LOCAL_SYNC_TARGET_DIR:-}" ]; then
	echo "LOCAL_SYNC_TARGET_DIR is not set. Add it to .env (see .env.example)." >&2
	exit 1
fi

if [ ! -f "$FIXTURES_DIR/ppcart-fixtures.php" ]; then
	echo "Fixtures plugin not found at: $FIXTURES_DIR" >&2
	exit 1
fi

PLUGINS_DIR="$(cd "$(dirname "$LOCAL_SYNC_TARGET_DIR")" && pwd)"
LINK_PATH="$PLUGINS_DIR/ppcart-fixtures"
LEGACY_FILE="$PLUGINS_DIR/ppcart-fixtures.php"

if [ -e "$LEGACY_FILE" ] || [ -L "$LEGACY_FILE" ]; then
	rm -f "$LEGACY_FILE"
fi

ln -sfn "$FIXTURES_DIR" "$LINK_PATH"

echo "Linked $LINK_PATH -> $FIXTURES_DIR"
