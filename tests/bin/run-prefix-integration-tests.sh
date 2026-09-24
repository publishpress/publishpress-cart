#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

bash "$ROOT/tests/bin/fix-linux-test-volume-perms.sh"

shopt -s nullglob
files=(tests/codeception/Integration/Prefix/*Test.php)

if [ ${#files[@]} -eq 0 ]; then
    echo "No Prefix integration tests found." >&2
    exit 1
fi

for file in "${files[@]}"; do
    echo "==> ${file#tests/codeception/Integration/Prefix/}"
    vendor/bin/codecept run Integration "$file"
done
