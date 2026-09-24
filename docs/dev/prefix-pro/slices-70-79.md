# Per-slice Pro contract — families 70–79

Mangled hooks, revoke GET keys, escaped HTML/JS, upload directory, debug log, merchant copy, comments, glued names, jQuery namespace. Part of [Pro prefix compliance](../prefix-pro.md).

---

## Mangled leftover hooks (Free slice 70 — done)

Pro must listen/fire canonical `ppcart_activate` / `ppcart_upgrade` /
`ppcart_product` / `ppcart_product_price`. Do not copy mangled
`ppcart_ctivate` / `ppcart_pgrade` / `ppcart_roduct` (never shipped). Leftover
`ncs_activate` / `sc_activate` / `ncs_upgrade` / `sc_upgrade` / `sc_product`
filter names only via Free Compat if those were shipped public APIs. Do not
collapse leftover filter `sc_product` with leftover shortcode tag `sc_product`.

| Leftover (never copy into Pro) | Canonical (Pro must match Free) |
|--------------------------------|----------------------------------|
| `ppcart_ctivate` | `ppcart_activate` |
| `ppcart_pgrade` | `ppcart_upgrade` |
| `ppcart_roduct` / `ppcart_roduct_price` | `ppcart_product` / `ppcart_product_price` |

## Revoke GET keys (Free slice 71 — done)

Pro must emit and read `ppcart-revoke` / `ppcart-revoked`. Leftover GET
`sc-revoke` / `sc-revoked` copy only via Free Compat while on (slice 26
pattern). Do not emit leftover query keys in Pro first-party.

| Leftover (Compat while on) | Canonical (Pro must match Free) |
|----------------------------|----------------------------------|
| GET `sc-revoke` | `ppcart-revoke` |
| GET `sc-revoked` | `ppcart-revoked` |

## Escaped HTML/JS leftovers (Free slice 72 — done)

Pro must match Free `row-ppcart-` ids and `ppcart-nav-tabs`. Rebuild any Pro
editor bundle that still ships `ncs-nav-tabs`. Hard cutover; no dual-class.

| Leftover (no-op in Pro) | Canonical (Pro must match Free) |
|-------------------------|----------------------------------|
| `id="row-sc-{field_id}"` | `id="row-ppcart-{field_id}"` |
| Gutenberg build `ncs-nav-tabs` | `ppcart-nav-tabs` |

## Download upload directory (Free slice 73 — done)

Pro must write and allow-list `wp-content/uploads/ppcart-uploads` only. Leftover
`sc-uploads` is an extra allowed root only while Free Compatibility Mode is on.
Do not silent-move files. Do not invent `ncs-cart/uploads`. Existing merchant
files under leftover dir work with Compat on until an explicit Data migration
card (out of this slice).

| Leftover (Compat extra root while on) | Canonical (Pro must match Free) |
|---------------------------------------|----------------------------------|
| `wp-content/uploads/sc-uploads` | `wp-content/uploads/ppcart-uploads` |

## Debug-log `Scrt*` recognition (Free slice 74 — done)

Pro must match Free canonical-only log-id regex (`PPCart_Order` /
`PPCart_Subscription`). Do not recognize leftover `ScrtOrder` /
`ScrtSubscription` in Pro first-party. Hard cutover; no Compat shim.

## Merchant leftover copy / URLs (Free slice 75 — done)

Pro must match Free `ppcart_field_id` help text, w.org
`plugin/publishpress-cart` reviews URL, and the Cart docs hub default
(`PPCART_DOCS_URL` = `https://docs.rambleventures.com/publishpress/publishpress-cart/`)
for Stripe subscription docs (`ppcart_stripe_subscriptions_documentation_url`).
Do not ship `studiocart_field_id`, `plugin/studiocart`, `studiocart.co`,
`knowledge-base/introduction-cart`, or `publishpress.com/docs-category` defaults.
Hard cutover; no Compat shim.

## Leftover names in comments (Free slice 76 — done)

No runtime contract. Mirror Free canonical comment/docblock names when
touching the same files.

## Glued HTML/JS leftovers (Free slice 77 — done)

Pro must emit `data-ppcart-qty-price` on quantity custom fields (not glued
`data-scq-price` / `data-ppcartq-price`). Settings JS local is
`originalPpcartSettings` (not `originalNcs`). Hard cutover; no Compat shim.

## Glued `$sc*` / `$scrt_*` locals (Free slice 78 — done)

Pro must match Free `$ppcart_order` / `$ppcart_subscription` in vendored copies
of CSV export, my-account order detail, order product-form, and subscription
column templates. Do not copy `$scrt_order` / `$scorder` / `$scsub`. Mirror
the debug-logger comment (`$ppcart_debug_logger`, not “SC debug logger”).
Hard cutover; no Compat shim. Free landed in `58966193`.

## jQuery `scPE` namespace (Free slice 79 — done)

Pro must match Free `elementor/popup/show.ppcart-pe-` if it vendors or copies
the Elementor popup handler. Do not copy `.scPE-`. Hard cutover; no Compat
shim. Free landed in `eaedf4d1`.
