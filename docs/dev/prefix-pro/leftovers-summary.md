# Remaining Free leftovers Pro must match

One summary row per landed Free slice. Detail lives in the per-slice files. Part of [Pro prefix compliance](../prefix-pro.md).

---

## Remaining Free leftovers Pro must match (70–73)

Families **58–69** are done. After each Free slice **70–73**, Pro must match
the new emit/read. Do not copy leftover or mangled names into Pro.
Leftover → canonical: [Remaining leftovers (70–73)](../prefix-standardization.md#remaining-leftovers-70-73).

| Slice | Canonical (Pro must match) | Leftover |
|-------|-----------------------------|----------|
| 70 Mangled hooks | `ppcart_activate` / `ppcart_upgrade` / `ppcart_product` / `ppcart_product_price` | Do not copy mangled `ppcart_ctivate` / `ppcart_pgrade` / `ppcart_roduct`. Leftover `ncs_activate` / `sc_product` (filter) via Free Compat (Free done) |
| 71 Revoke GET | `ppcart-revoke` / `ppcart-revoked` (Free done) | leftover GET `sc-revoke` / `sc-revoked` Compat on |
| 72 HTML/JS | `row-ppcart-` / `ppcart-nav-tabs` | `row-sc-` / `ncs-nav-tabs` (hard cutover; rebuild Gutenberg) |
| 73 Upload dir | `ppcart-uploads` (Free done) | leftover `sc-uploads` Compat extra root; no silent file move |

## Remaining Free leftovers Pro must match (74–76)

Families **70–73** are done. Slices **74–76** are done. Pro must match
the canonical emit/read. Do not copy leftover names into Pro.
Leftover → canonical: [Remaining leftovers (74–76)](../prefix-standardization.md#remaining-leftovers-74-76).

| Slice | Canonical (Pro must match) | Leftover |
|-------|-----------------------------|----------|
| 74 Debug-log `Scrt*` | `PPCart_Order` / `PPCart_Subscription` only in log-id regex (Free done) | `ScrtOrder` / `ScrtSubscription` (hard cutover; no Compat) |
| 75 Merchant copy / URLs | `ppcart_field_id`; w.org `plugin/publishpress-cart`; `PPCART_DOCS_URL` default for Stripe subscription docs (Free done) | `studiocart_field_id` / `plugin/studiocart` / `studiocart.co` (hard cutover; no Compat) |
| 76 Comments | canonical names in comments/docblocks (Free done) | leftover `sc_*` / `_sc_*` / StudioCart product comments (docs-only; hard cutover) |

## Remaining Free leftovers Pro must match (77)

Families **74–77** are done. Slice **78** is done. Pro must match
the canonical emit/read. Do not copy leftover glued names into Pro.
Leftover → canonical: [Remaining leftovers (78)](../prefix-standardization.md#remaining-leftovers-78).

| Slice | Canonical (Pro must match) | Leftover |
|-------|-----------------------------|----------|
| 77 Glued HTML/JS | `data-ppcart-qty-price`; `originalPpcartSettings` (Free done) | `data-scq-price` / `originalNcs` (hard cutover; no Compat) |
| 78 Glued `$sc*` / `$scrt_*` locals | `$ppcart_order` / `$ppcart_subscription` (Free done) | `$scrt_order` / `$scorder` / `$scsub` (hard cutover; no Compat) |
| 79 jQuery `scPE` | `elementor/popup/show.ppcart-pe-` (Free done) | `.scPE-` (hard cutover; no Compat) |

## Remaining Free leftovers Pro must match (80–81)

Families **58–79** are done. After each Free slice **80–81**, Pro must match
the new emit/read. Do not copy leftover glued names or seeder keys into Pro.
Leftover → canonical: [Remaining leftovers (79–81)](../prefix-standardization.md#remaining-leftovers-79-81).

| Slice | Canonical (Pro must match) | Leftover |
|-------|-----------------------------|----------|
| 79 jQuery `scPE` | `elementor/popup/show.ppcart-pe-` (Free done) | `.scPE-` (hard cutover; no Compat) |
| 80 Sample tax CSV | `sample_ppcart_tax_rates.csv`; phpmd comments `PPCart_Public` / `PPCart_` (Free done) | `sample_sc_tax_rates.csv`; phpmd `NCS_Cart_Public` / `NCS_` / `Scrt` |
| 81 Test seeder keys | suffixes / `_ppcart_*` (Free tests) | `_sc_*` seeder keys. Never strip `_sc_` in Cart meta helpers |

## Remaining Free leftovers Pro must match (58–69)

These Free slices landed. After each, Pro must match the new emit/read. Do not
copy leftover names into Pro.
Leftover → canonical: [Remaining leftovers (58–69)](../prefix-standardization.md#remaining-leftovers-58-69).

| Slice | Canonical (Pro must match) | Leftover |
|-------|-----------------------------|----------|
| 58 HTML/JS storefront | `ppcart-current` / `ppcart-checkout-1` / `ppcart-shortcode` / `#ppcart-preloader` | `sc-current` / `sc-checkout-1` / `scshortcode` / `#sc-preloader` (hard cutover) |
| 59 HTML/JS admin | `ppcart-selectize` / `data-ppcart-editor-*` / `ppcart*Tab` / `.data('ppcart-editor-*')` / `'ppcart-' + optionId` | hard cutover (done) |
| 60 Gutenberg block | `publishpress-cart/checkout-form` | leftover `sc-products-shortcode/product-shortcode` Compat dual-register when on |
| 61 AJAX / JS globals | `ppcart_ajax_nonce` / `ppcart_tax_settings` | `ncs_ajax_*` / `var ncs` / `sc_tax_settings` Compat if needed |
| 62 Request fields | emit/read `ppcart-*` / `ppcart-slm-order` | leftover GET `sc-slm-order` Compat on (Free done) |
| 63 Recognition | canonical-only; no silent migrate | leftover read-through Compat only |
| 64 Compat prefix | `ppcartcomp_*` | `ppcart_compat_*` (Compat-only; Pro none) |
| 65 Admin TinyMCE JS | `'ppcart-' + optionId` / `.data('ppcart-editor-*')` | `sc-editor-*` / `'sc-' + optionId` (hard cutover) |
| 66 CPT/taxonomy filters | `ppcart_cpt_options` / `ppcart_taxonomy_options` | `sc-cart-cpt-options` / `sc-cart-taxonomy-options` Compat bridge only |
| 67 Tax CSV import | `ppcart_tax_rate_csv` | `ncs-cart_tax_rate_csv` (hard cutover) |
| 68 Cosmetic locals | `$ppcart_*` table/bootstrap locals | `$ncs_tax` / `$ncs_meta` / `$ncs_stripe` (hard cutover) |
| 69 Docblocks | canonical `@package` / comment names | `@package NCS_Cart` / comment `_sc_*` (docs-only; done UT-304) |

Webhook URLs are already canonical (`ppcart-webhook` / `ppcart-api` /
`ppcart-invoice`). Pro HTML/JS, Gutenberg, AJAX, and request fields must
follow Free after 58–62. No leftover recognition in Pro either.
