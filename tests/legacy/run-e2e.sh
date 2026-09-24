#!/usr/bin/env bash
set -euo pipefail

PLUGIN_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SUITES=("$PLUGIN_ROOT_DIR"/tests/legacy/e2e/*/run.sh)

if [ ! -e "${SUITES[0]}" ]; then
    echo "No E2E test suites found." >&2
    exit 1
fi

for suite in "${SUITES[@]}"; do
    suite_name="$(basename "$(dirname "$suite")")"
    echo "Running E2E suite: $suite_name"
    bash "$suite" "$@"
done
