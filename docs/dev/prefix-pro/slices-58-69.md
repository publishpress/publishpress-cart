# Per-slice Pro contract — families 58–69

Storefront and admin HTML, Gutenberg block, AJAX keys, request fields, recognition, TinyMCE, CPT filters, tax CSV, cosmetic locals. Part of [Pro prefix compliance](../prefix-pro.md).

---

## Storefront HTML classes (Free slice 58 — hard cutover)

Pro checkout templates, storefront JS, and CSS must emit and select canonical
`ppcart-*` storefront classes only. No leftover `sc-current`, `sc-checkout-1`,
`scshortcode`, `#sc-preloader`, or related storefront selectors in Pro
first-party code. No Compatibility Mode class aliases.

| Leftover (no-op in Pro) | Canonical (Pro must match Free) |
|-------------------------|----------------------------------|
| `sc-current` | `ppcart-current` |
| `sc-checkout-1` | `ppcart-checkout-1` |
| `sc-splitin-form` | `ppcart-splitin-form` |
| `scshortcode` | `ppcart-shortcode` |
| `#sc-preloader` | `#ppcart-preloader` |
| `sc-payment-element` | `ppcart-payment-element` |
| `sc-selected` | `ppcart-selected` |
| `sc-show-coupon` | `ppcart-show-coupon` |
| `sc-password` | `ppcart-password` |
| `sc-address-1` / `sc-address-2` | `ppcart-address-1` / `ppcart-address-2` |

Gutenberg inline `.ppcart-shortcode` selectors track the emitted wrapper class,
not the leftover block name (slice 60).

## Admin HTML classes (Free slice 59 — hard cutover)

Pro admin templates, settings JS, and metabox field markup must emit and
select canonical `ppcart-*` admin classes, `data-ppcart-editor-*`,
`data-ppcart-*` secrets UI hooks, and `ppcartSettingsMainTab` /
`ppcartPaymentMethodsSubtab` localStorage keys only. No leftover
`sc-selectize`, `data-sc-editor-*`, `data-ncs-*`, or `sc*Tab` in Pro
first-party code. No Compatibility Mode class aliases.

| Leftover (no-op in Pro) | Canonical (Pro must match Free) |
|-------------------------|----------------------------------|
| `sc-selectize` | `ppcart-selectize` |
| `sc-user-search-custom` | `ppcart-user-search-custom` |
| `sc-payment-subtab-link` | `ppcart-payment-subtab-link` |
| `data-sc-editor-*` | `data-ppcart-editor-*` |
| `data-ncs-*` (secrets UI) | `data-ppcart-*` |
| localStorage `scSettingsMainTab` | `ppcartSettingsMainTab` |
| localStorage `scPaymentMethodsSubtab` | `ppcartPaymentMethodsSubtab` |

## Gutenberg leftover checkout block (Free slice 60 — Compat dual-register)

Pro must register and emit only `publishpress-cart/checkout-form`. Do not
dual-register leftover `sc-products-shortcode/product-shortcode` in Pro
first-party, localize `legacyBlockName`, or keep leftover editor CSS. Persisted
leftover blocks render only while Free Compatibility Mode is on.

| Leftover (Pro must not register) | Canonical (Pro must match Free) |
|----------------------------------|----------------------------------|
| `sc-products-shortcode/product-shortcode` | `publishpress-cart/checkout-form` |
| `.wp-block-sc-products-shortcode-product-shortcode` | `.wp-block-publishpress-cart-checkout-form` |
| `legacyBlockName` / `LEGACY_BLOCK_NAME` | omit; Compat editor JS hardcodes leftover |

## Admin AJAX keys and JS globals (Free slice 61 — Compat bridge)

Pro must post/read canonical admin AJAX keys and tax settings globals. Do not
emit `ncs_ajax_nonce` / `ncs_action` or `var ncs` in Pro first-party JS.

| Leftover (Compat while on) | Canonical (Pro must match Free) |
|----------------------------|----------------------------------|
| POST `ncs_ajax_nonce` / `ncs_action` | `ppcart_ajax_nonce` / `ppcart_action` |
| `var ncs` (Stripe JS namespace) | `ppcartStripe` |
| `sc_tax_settings` localize | `ppcart_tax_settings` |

## Request `name=` / GET leftovers (Free slice 62 — Compat GET copy)

Pro must emit hidden fields via `$atts['name']` (`_ppcart_*` / repeater
array names) and read GET `ppcart-slm-order`. Do not emit leftover `sc-`
hidden names or read leftover GET `sc-slm-order` in Pro first-party.

| Leftover (Compat while on) | Canonical (Pro must match Free) |
|----------------------------|----------------------------------|
| Hidden `str_replace('_ppcart_', 'sc-', …)` emit | `$atts['name']` / `_ppcart_*` |
| GET `sc-slm-order` | `ppcart-slm-order` |

## Leftover recognition (Free slice 63 — Compat read-through)

Pro must not silent-migrate `_my_account`, skip-copy duplicator keys by leftover
prefix, strip `sc_` in Stripe owned-field helpers, or register leftover FSE
templates. Use canonical options/meta only in Pro first-party.

| Leftover (Compat while on) | Canonical (Pro must match Free) |
|----------------------------|----------------------------------|
| Option `_my_account` read-through | `_ppcart_myaccount_page_id` only in Pro code |
| Duplicator `_sc_*` source meta | `_ppcart_*` allowlist + Compat rewrite filter |
| `_sc_*` Stripe owned-field names | Canonical suffix / `_ppcart_*` via Compat `ppcart_meta_key_suffix` |
| Leftover FSE `single-sc_product` template | Canonical `single-ppcart_product` only in Pro |

## Compat metadata bridge prefix (Free slice 64 — Compat-only)

Metadata API bridge callbacks in `publishpress-cart-compat` use `ppcartcomp_*`
(for example `ppcartcomp_get_post_metadata`). Pro has no action — Cart live
helpers stay `ppcart_*`.

## Admin TinyMCE JS (Free slice 65 — hard cutover)

Pro admin JS must use canonical TinyMCE editor ids and jQuery data keys. PHP
already emits `ppcart-{optionId}` via `ppcart-admin-field-editor.php`; Pro JS
must not look up `sc-{optionId}` or use `.data('sc-editor-*')`.

| Leftover (no-op in Pro) | Canonical (Pro must match Free) |
|-------------------------|----------------------------------|
| `.data('sc-editor-initialized')` | `.data('ppcart-editor-initialized')` |
| `.data('sc-editor-repairing')` | `.data('ppcart-editor-repairing')` |
| `'sc-' + optionId` TinyMCE lookup | `'ppcart-' + optionId` |

No Compatibility Mode JS aliases for leftover TinyMCE ids or jQuery data keys.

## CPT/taxonomy option filters (Free slice 66 — Compat bridge)

Pro must listen on canonical `ppcart_cpt_options` / `ppcart_taxonomy_options`
only. Do not `apply_filters` leftover `sc-cart-cpt-options` /
`sc-cart-taxonomy-options` in Pro first-party. Leftover filter names reach
canonical listeners only via Free Compat while Compatibility Mode is on.

| Leftover (Compat on only) | Canonical (Pro must match Free) |
|---------------------------|----------------------------------|
| `sc-cart-cpt-options` | `ppcart_cpt_options` |
| `sc-cart-taxonomy-options` | `ppcart_taxonomy_options` |

## Tax CSV import slug (Free slice 67 — hard cutover)

Pro owns the tax-rate CSV importer (`NCS_Cart_Tax_Rate_Importer` today). Free
links to `admin.php?import=ppcart_tax_rate_csv` and fires
`ppcart_register_importers` on `admin_init` when `WP_LOAD_IMPORTERS` is defined.
Pro must migrate off `sc_register_importers` and register slug
`ppcart_tax_rate_csv` only.

| Leftover (no-op in Pro) | Canonical (Pro must match Free) |
|-------------------------|----------------------------------|
| `sc_register_importers` hook | `ppcart_register_importers` |
| `ncs-cart_tax_rate_csv` importer slug | `ppcart_tax_rate_csv` |

No Compatibility Mode redirect for leftover `import=` slug.

## Cosmetic `$ncs_*` locals (Free slice 68 — hard cutover)

Slice **41** renamed `$sc_*` locals only. Slice **68** closes the gap for
leftover `$ncs_*` table/bootstrap locals in activator, upgrade, order items,
files storage, and Stripe card update template. These are not a public API; no
Compat shim.

| Leftover (no-op in Pro) | Canonical (Pro must match Free) |
|-------------------------|----------------------------------|
| `$ncs_tax` (tax_rate) | `$ppcart_tax_table` |
| `$ncs_tax` (order_items) | `$ppcart_order_items_table` |
| `$ncs_meta` | `$ppcart_order_itemmeta_table` |
| `$ncs_tax` (downloads) | `$ppcart_downloads_table` |
| `$ncs_stripe` | `$ppcart_stripe` |
