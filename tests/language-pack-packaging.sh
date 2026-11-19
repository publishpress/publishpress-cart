#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
temporary="$(mktemp -d)"
trap 'rm -rf -- "$temporary"' EXIT

source="$temporary/source"
output="$temporary/output"
mkdir -p "$source/languages" "$source/includes/helpers" \
    "$source/lib/vendor/another-package/data"
for extension in po mo json l10n.php; do
    printf 'catalog\n' > "$source/languages/publishpress-cart-pt_BR.$extension"
done
printf 'template\n' > "$source/languages/publishpress-cart.pot"
printf 'runtime\n' > "$source/publishpress-cart.php"
printf 'runtime\n' > "$source/includes/helpers/ppcart-language-pack-notice.php"
printf 'dependency\n' > "$source/lib/vendor/another-package/data/runtime.json"

rsync -r -f "merge $root/.rsync-filters-post-build" "$source/" "$output"

for extension in po mo json l10n.php; do
    if [[ -e "$output/languages/publishpress-cart-pt_BR.$extension" ]]; then
        printf 'FAIL: catalog shipped: %s\n' "publishpress-cart-pt_BR.$extension" >&2
        exit 1
    fi
    test -f "$source/languages/publishpress-cart-pt_BR.$extension"
done
for path in publishpress-cart.php languages/publishpress-cart.pot \
    includes/helpers/ppcart-language-pack-notice.php \
    lib/vendor/another-package/data/runtime.json; do
    cmp "$source/$path" "$output/$path"
done

printf 'PASS: Free catalogs excluded; source catalogs and runtime files preserved\n'
