# Per-slice Pro contract — families 80–82

Sample tax CSV filename, test seeder keys, and the doubled `ppcart_cart_` prefix. Part of [Pro prefix compliance](../prefix-pro.md).

---

## Sample tax CSV filename (Free slice 80 — done)

Do not ship `sample_sc_tax_rates.csv`. Canonical sample is
`sample_ppcart_tax_rates.csv`. Mirror phpmd comment names (`PPCart_Public` /
`PPCart_`, not `NCS_Cart_Public` / `NCS_` / `Scrt`). Hard cutover; no Compat
shim. Free landed in `3368e2c5`.

## Test seeder `_sc_*` keys (Free slice 81 — done)

Free-test only. Do not copy leftover `_sc_*` seeder keys into Pro tests.
Never strip `_sc_` in Cart meta helpers. Playwright/legacy seeders pass suffix
keys to `ppcart_fixtures_update_post_meta()` and `_ppcart_*` to raw
`update_post_meta` / `update_option`. Hard cutover; no Compat shim. Free
landed in `dd08ca94`.

## Doubled `ppcart_cart_` prefix (Free slice 82)

Pro must call `ppcart_supports` / `ppcart_is_pro` / `ppcart_loaded` /
`ppcart_pro_*` and roles `ppcart_manager` / `ppcart_administrator`. Product
checkout-window meta suffixes and `$ppcart_product` properties are
`checkout_starts` / `checkout_ends` / `checkout_ended_action` /
`checkout_ended_redirect` / `checkout_ended_message`. Do not read
`cart_open` / `_ppcart_cart_*`. Thank-you URL stays `_ppcart_redirect`.
Leftover `_sc_cart_open` (and siblings) only via Free Compat. Free landed in
`186e0f69`.
