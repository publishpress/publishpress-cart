#!/usr/bin/env bash
set -euo pipefail

PLUGIN_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SOURCE_DIR="$PLUGIN_ROOT_DIR/tests/ppcart-fixtures"
DIST_DIR="$PLUGIN_ROOT_DIR/tests/dist"
ZIP_PATH="$DIST_DIR/ppcart-fixtures.zip"

if [ ! -f "$SOURCE_DIR/ppcart-fixtures.php" ]; then
	echo "Fixtures plugin not found at: $SOURCE_DIR" >&2
	exit 1
fi

if ! command -v zip >/dev/null 2>&1; then
	echo "zip is required to build the fixtures plugin." >&2
	exit 1
fi

mkdir -p "$DIST_DIR"
rm -f "$ZIP_PATH"

(
	cd "$(dirname "$SOURCE_DIR")"
	zip -r -q "$ZIP_PATH" ppcart-fixtures \
		-x "*.DS_Store" \
		-x "*~"
)

echo "Built $ZIP_PATH"
