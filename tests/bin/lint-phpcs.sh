#!/usr/bin/env bash
# PHPCS for the plugin tree listed in the rulesets. Do not pass "." — that
# scans the whole checkout (vendor, nested templates) and can OOM.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

if [ -z "${PHPCS_WORKERS:-}" ] && [ -f "$ROOT/.env" ]; then
    line="$(grep -E '^[[:space:]]*PHPCS_WORKERS=' "$ROOT/.env" | tail -n 1 || true)"
    if [ -n "$line" ]; then
        PHPCS_WORKERS="${line#*=}"
        PHPCS_WORKERS="${PHPCS_WORKERS%\"}"
        PHPCS_WORKERS="${PHPCS_WORKERS#\"}"
        PHPCS_WORKERS="${PHPCS_WORKERS%\'}"
        PHPCS_WORKERS="${PHPCS_WORKERS#\'}"
    fi
fi

PARALLEL="${PHPCS_WORKERS:-2}"

phpcs() {
    php -d memory_limit=1G vendor/bin/phpcs -d memory_limit=1G -p \
        --warning-severity=0 \
        --parallel="$PARALLEL" \
        "$@"
}

run_phpcs() {
    local label="$1"
    local standard="$2"
    local out="$3"
    local rc=0

    set +e
    {
        echo "▶ PHPCS: $label"
        phpcs --standard="$standard"
    } >"$out" 2>&1
    rc=$?
    set -e
    echo "$rc" >"${out}.rc"
}

out1="$(mktemp)"
out2="$(mktemp)"
trap 'rm -f "$out1" "$out2" "$out1.rc" "$out2.rc"' EXIT

echo "▶ PHPCS: ${PARALLEL} workers, project standards + plugin review in parallel"

run_phpcs "project standards" ".phpcs.xml" "$out1" &
run_phpcs "WordPress.org plugin review" ".phpcs-plugin-review.xml" "$out2" &
wait || true

cat "$out1"
echo
cat "$out2"

rc1="$(cat "$out1.rc")"
rc2="$(cat "$out2.rc")"
if [ "$rc1" -ne 0 ]; then
    exit "$rc1"
fi
exit "$rc2"
