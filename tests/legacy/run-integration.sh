#!/usr/bin/env bash
set -euo pipefail

PLUGIN_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SUITES=("$PLUGIN_ROOT_DIR"/tests/legacy/integration/*/run.sh)

if [ ! -e "${SUITES[0]}" ]; then
    echo "No integration test suites found." >&2
    exit 1
fi

for suite in "${SUITES[@]}"; do
    suite_name="$(basename "$(dirname "$suite")")"
    echo "Running integration suite: $suite_name"
    bash "$suite"
done
