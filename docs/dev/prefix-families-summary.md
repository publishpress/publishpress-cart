# StudioCart prefix families (summary)

Issue **#629**. This is the team briefing. Decision record:
[ADR-0004](../adr/0004-canonical-ppcart-prefix.md). The leftover tracker,
leftover→canonical maps, and Pro playbook live in
[prefix-standardization.md](prefix-standardization.md).

StudioCart did not have one prefix. It mixed **at least a dozen naming families** for the same plugin. Cart now uses one: `PPCart_` / `ppcart_` / `PPCART_` / `ppcart-`.

## Prefix families we renamed

| Legacy family | Used for | Examples |
|---------------|----------|----------|
| `NCS_Cart_` | PHP classes | `NCS_Cart_Order`, `NCS_Cart_Admin` |
| `NCS_CART_` | Constants | `NCS_CART_VERSION`, `NCS_CART_LOADED_BY_PRO` |
| `ncs_cart_` / `*_ncs_cart` | Bootstrap + locals | `activate_ncs_cart`, `$ncs_cart_*` |
| `ncs_` | Functions, AJAX, tables, locals | `ncs_activate`, `{prefix}ncs_tax_rate`, `$ncs_stripe` |
| `ncs-` / `ncs-cart-` | Files, hooks, import slug, CSS | `ncs-cart.php`, `ncs-cart-currencies`, `ncs-cart_tax_rate_csv`, `ncs-nav-tabs` |
| `nsc_` | Typo of `ncs_` | hook map treated `nsc_*` as leftover |
| `sc_` | Functions, hooks, AJAX, POST, CPT | `sc_format_price`, `sc_product`, `sc_check_username` |
| `_sc_` | Meta, options, HTML ids | `_sc_product_id`, `id="_sc_bump_bg_color"` |
| `sc-` | CSS, query vars, GET, uploads | `.sc-row`, `sc-webhook`, `sc-revoke`, `sc-uploads` |
| `SC_` | Classes / constants | `SC_Rest_*`, `SC_Bump_*`, `SC_STRIPE_CONNECT_*` |
| `Scrt` | Model class aliases | `ScrtOrder`, `ScrtSubscription`, `ScrtOrderItem` |
| `studiocart` / `studiocart_` | Product name as prefix | `studiocart_checkout_complete`, `{studiocart}`, `$studiocart`, `yourtheme/studiocart/` |
| `scshortcode` | CSS class with **no** separator | `.scshortcode` |
| `$scp` / `$scFiles` | One-off globals | `$scp`, `$scFiles` |

Same plugin, three class styles (`NCS_Cart_Order`, `ScrtOrder`, `SC_Rest_*`), two company codes (`NCS` and `sc`), plus the full product name `studiocart`, plus a typo `nsc_`.

## Same surface, different prefixes

That is the consistency problem. One kind of identifier did not get one prefix.

**Hooks**

- `sc_*`
- `_sc_*`
- `ncs_*` / `nsc_*`
- `studiocart_*`
- `ncs-cart-*` (kebab inside a PHP hook)
- `sc-cart-cpt-options` (kebab filter)
- `before_sc_order_refund` (prefix in the **middle**)
- `admin_action_sc_duplicate_product`

**Classes**

- `NCS_Cart_Order`
- `ScrtOrder`
- `Studiocart_Dashboard_Widget`
- `SC_Bump_*`

**JS globals**

- `studiocart`
- `sc_reg_vars` / `sc_popup` / `window.sc_coupon`
- `ncsCart*` / `ncs_settings`
- `var ncs`
- `sc_tax_settings`

**HTML / CSS**

- `id="sc_form"` and `class="sc-row"` (underscore vs hyphen)
- `#_sc_*` / `rid_sc_*` / `repeater_sc_*`
- `ncs-nav-tabs`
- `scshortcode`

**Admin / HTTP**

- AJAX: both `sc_*` and `ncs_*`
- GET: `sc-revoke` vs PHP that already expected `ppcart-revoke`
- Import slug: `ncs-cart_tax_rate_csv` (kebab **and** snake in one string)
- REST: `sc/v1`
- Gutenberg block: `sc-products-shortcode/product-shortcode`

**Storage**

- Options/meta: `_sc_*` and `sc_*`
- Tables: `{prefix}ncs_*`
- Uploads dir: `sc-uploads`
- Theme overrides: `studiocart/`
- Plugin files: `studiocart.php` **and** `ncs-cart.php`

## What Cart uses now

| Kind | One prefix |
|------|------------|
| Classes | `PPCart_` |
| Functions, hooks, options, globals | `ppcart_` / `_ppcart_` |
| Constants | `PPCART_` |
| Files, CSS, query vars, asset handles | `ppcart-` |
| Shortcodes | `ppcart_` |

Leftover StudioCart names stay in Compatibility Mode only. They are not the live Cart API.

The takeaway for the team: legacy was not “one old prefix.” It was **NCS + sc + Scrt + SC + studiocart + nsc**, mixed with `_`, `-`, no separator, and mid-name prefixes. That is why the rename took dozens of families instead of a single search-replace.
