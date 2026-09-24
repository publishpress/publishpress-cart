# [ADR-0004] Canonical PublishPress Cart prefix

- **Status:** Accepted
- **Date:** 2026-09-09
- **Issue:** [#629](https://github.com/publishpress/publishpress-cart/issues/629)

## Context

StudioCart mixed at least a dozen naming families for one plugin (`NCS_Cart_`,
`ncs_`, `sc_`, `_sc_`, `sc-`, `SC_`, `Scrt`, `studiocart`, `nsc_`, glued
forms). WordPress.org and Pro both need one live prefix. Dual-read and silent
row migration in Free would keep leftover names as a first-party API.

The leftover→canonical maps, slice tracker, and Pro playbook live in
[prefix-standardization.md](../dev/prefix-standardization.md). Why it was
dozens of families, not one search-replace:
[prefix-families-summary.md](../dev/prefix-families-summary.md).

Related bridges: [ADR-0001](0001-request-field-compat-bridge.md) (request
fields), [ADR-0003](0003-stripe-metadata-compat-bridge.md) (Stripe metadata).

## Decision

**Compatibility Mode** (PHP shims for leftover names) and merchant **Data
migration** (leftover **rows** → canonical keys) live in the
[PublishPress Cart Compat](https://github.com/publishpress/publishpress-cart-compat)
plugin, not in Cart.

First-party Cart uses one prefix family:

| Kind | Prefix |
|------|--------|
| Classes | `PPCart_` |
| Functions, hooks, options, globals | `ppcart_` / `_ppcart_` |
| Constants | `PPCART_` |
| Files, CSS, query vars, asset handles | `ppcart-` |
| Shortcodes | `ppcart_` |

Leftover StudioCart / NCS / `sc` names are not the live Cart API. Cart
first-party does not emit, read, write, or *recognize* leftover names. Silent
data migration in Free is denied.

Do not reintroduce `PP_Cart_` / `pp_cart_` / `pp-cart-` (underscore after `PP`).

## Consequences

WordPress.org ships a complete clean plugin. Leftover names and leftover rows
are unavailable unless PublishPress Cart Compat is installed.

**Pro** – Fire and listen on canonical names only.
[prefix-standardization.md](../dev/prefix-standardization.md) is the reuse
contract. Do not “clean up” unused-looking hooks Pro still depends on.

Third-party code, by default, still writes and listens on leftover names, so it
needs Compat. Those integrations should be updated to canonical names so they
do not require the Compat plugin.
