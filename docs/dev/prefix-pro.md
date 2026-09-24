# Pro prefix compliance

Playbook for applying the issue **#629** prefix standard to **PublishPress
Cart Pro** (`publishpress-cart-pro`). Use it when a Pro PR starts, not as a
live leak tracker for the Free repo.

**Scope:** identifier prefixes only — the names Pro must call, emit, read,
listen to, and register so it talks to the Free API that already shipped.

This is **not** [pro-plugin-extraction.md](pro-plugin-extraction.md).
Extraction moves Pro *logic* out of Free; this file only covers *names*. The
two are independent: a file can be extracted without a rename, and renamed
without being extracted.

**Compat paths:** leftover shims and Data migration packages live in sibling
`publishpress-cart-compat`. Paths written as `includes/compat/…` in this
playbook are companion-repo-relative unless marked Pro (`cart-pro`
`includes/compat/legacy.php`). Free Cart has no `includes/compat/` directory.

**Related:**

| Doc | Role |
|-----|------|
| [prefix-standardization.md](prefix-standardization.md) | Free-side #629 tracker: leftover families, slice checkboxes, leftover→canonical by kind |
| [pro-plugin-extraction.md](pro-plugin-extraction.md) | Free/Pro architecture split — hooks, module moves, phases |
| [prefix-families-summary.md](prefix-families-summary.md) | Team briefing on the prefix families |
| [../adr/overview.md](../adr/overview.md) | ADR-0004 — Cart live API is `PPCart_` / `ppcart_` / `PPCART_` / `ppcart-` only |

**Status:** Free leftover families **43–82** are done; slice **18** stays
`[~]`, blocked on the Pro dual-define. No Pro slice has landed yet.

---

## Keep in sync with leftover prefix slices

Every leftover prefix slice must update this file when it changes identifiers
Pro still calls (constants Pro dual-defines, hooks/filters Pro listens to,
license filters, class names, JS handles, option/meta keys). Target: Pro
follows the same refactor as Free — canonical `PPCart_` / `ppcart_` /
`PPCART_` / `ppcart-`; leftover names only via Compat. Do not leave this file
describing leftover Free APIs as the destination after Free has moved.

Playbook: [prefix-standardization.md](prefix-standardization.md) **Keep the Pro
contract in sync**, leftover-slice Phase 5.

---

## Canonical prefix map (Pro)

| Kind | Prefix | Pro-owned example |
|------|--------|-------------------|
| Classes | `PPCart_` | `PPCart_Pro_Bootstrap`, `PPCart_Order` (Free class) |
| Functions, hooks, options, constants, globals | `ppcart_` / `PPCART_` | `ppcart_is_pro()`, `PPCART_LOADED_BY_PRO` |
| Pro-only functions / constants | `ppcart_pro_` / `PPCART_PRO_` | `PPCART_PRO_BASE_URL` |
| Files, asset handles, CSS/BEM | `ppcart-` | `class-ppcart-pro-bootstrap.php`, `ppcart-public-pro` |
| CSS custom properties | `--ppcart-` | `--ppcart-bg` (not `--pp-cs-bg`); same tokens as Free settings CSS |
| HTML `id` / `class` / `for` | `ppcart-` / `ppcart_` | Same rule as Free; update Pro CSS/JS in the same PR |

## Report cron recurrence slugs

Free `cron_schedules` keys are `ppcart_daily` / `ppcart_weekly` /
`ppcart_semi_monthly`. Stored `ppcart_report_schedule` sentinel is
`ppcart_none`. Do not register or persist leftover `mt_*` values.

## Order and subscription post statuses

Logical status strings on `$order->status`, `_ppcart_status`, and
`PPCart_Order::$paid_str` stay unprefixed (`paid`, `pending-payment`,
`active`). Pro must keep comparing those logical values.

`wp_posts.post_status` on canonical order/subscription CPTs is prefixed
(`ppcart_paid`, `ppcart_pending` for pending-payment). WP_Query `post_status`
`paid` still matches via Free's query expansion. Do not register bare
`paid` / `completed` / `active` post statuses from Pro.

## Settings CSS custom properties

Free settings UI tokens live on `.ppcart-settings-page` in
`admin/css/ppcart-settings.css`. Hard cutover from abbreviated `--pp-cs-*` to
`--ppcart-*`. No Compatibility Mode aliases. Pro settings CSS that overrides or
extends the Free settings screen must use the canonical names.

| Leftover (no-op in Pro) | Canonical (Pro must match Free) |
|-------------------------|----------------------------------|
| `--pp-cs-bg` | `--ppcart-bg` |
| `--pp-cs-surface` | `--ppcart-surface` |
| `--pp-cs-surface-alt` | `--ppcart-surface-alt` |
| `--pp-cs-border` | `--ppcart-border` |
| `--pp-cs-border-strong` | `--ppcart-border-strong` |
| `--pp-cs-text` | `--ppcart-text` |
| `--pp-cs-text-muted` | `--ppcart-text-muted` |
| `--pp-cs-text-soft` | `--ppcart-text-soft` |
| `--pp-cs-primary` | `--ppcart-primary` |
| `--pp-cs-primary-hover` | `--ppcart-primary-hover` |
| `--pp-cs-primary-soft` | `--ppcart-primary-soft` |
| `--pp-cs-section-header` | `--ppcart-section-header` |
| `--pp-cs-section-header-border` | `--ppcart-section-header-border` |
| `--pp-cs-sidebar-primary` | `--ppcart-sidebar-primary` |
| `--pp-cs-sidebar-bg` | `--ppcart-sidebar-bg` |
| `--pp-cs-sidebar-hover` | `--ppcart-sidebar-hover` |
| `--pp-cs-sidebar-border` | `--ppcart-sidebar-border` |
| `--pp-cs-accent` | `--ppcart-accent` |
| `--pp-cs-success` | `--ppcart-success` |
| `--pp-cs-success-soft` | `--ppcart-success-soft` |
| `--pp-cs-warning` | `--ppcart-warning` |
| `--pp-cs-warning-soft` | `--ppcart-warning-soft` |
| `--pp-cs-danger` | `--ppcart-danger` |
| `--pp-cs-radius-sm` | `--ppcart-radius-sm` |
| `--pp-cs-radius` | `--ppcart-radius` |
| `--pp-cs-radius-lg` | `--ppcart-radius-lg` |
| `--pp-cs-shadow-sm` | `--ppcart-shadow-sm` |
| `--pp-cs-shadow` | `--ppcart-shadow` |
| `--pp-cs-sidebar-width` | `--ppcart-sidebar-width` |
| `--pp-cs-header-height` | `--ppcart-header-height` |
| `--pp-cs-admin-bar-height` | `--ppcart-admin-bar-height` |
| `--pp-cs-savebar-height` | `--ppcart-savebar-height` |
| `--pp-cs-content-pad` | `--ppcart-content-pad` |

## Why Pro has to catch up

Free first-party PHP now uses only `PPCart_` / `ppcart_` / `PPCART_` outside
StudioCart Compatibility Mode.

**Today** (until Pro dual-defines contract constants):

- Pro still defines `NCS_CART_LOADED_BY_PRO` and related `NCS_CART_PRO_*`
  constants. Free reads `PPCART_*`. The inbound copy
  (`ncs_cart_resolve_canonical_constants`) runs **only when Compatibility Mode
  is on**. Do **not** ungate it. UT-194 is the contract: mode off,
  `NCS_CART_LOADED_BY_PRO` must not create `PPCART_LOADED_BY_PRO`.
- Free fires `ppcart_*` hooks. Pro listeners still on `sc_*` /
  `studiocart_*` run **only when Compatibility Mode is on**.
- wp-admin slugs already hard-cut over to `ppcart` / `ppcart-*`. Pro submenu
  pages that still parent on `studiocart` or register `sc-white-label` are
  orphaned with **no** Compatibility Mode bridge.

**After Pro dual-defines** `PPCART_*` before requiring Free (then aliases
`NCS_CART_*`): Pro **detection**, vendor assets, and autoload work with
Compatibility Mode **off**. Pro **modules** still need the later Pro hook
slice. The Free docs commit `docs(prefix): record Pro-contract constants
dual-define (#629)` records that contract; it is **not** the Pro PR and must
not be treated as slice 18 `[x]`.

Acceptance for a finished Pro prefix pass: **Free + Pro with Compatibility Mode
off**. Mode on is only a third-party check, not Pro’s own contract.

## Pro hard rules

| Rule | Detail |
|------|--------|
| Do not copy Compatibility Mode into Pro | Shims stay in sibling `publishpress-cart-compat` (`includes/compat/studiocart-compatibility-mode/`). |
| Do not put StudioCart names in Pro first-party code | Pro calls canonical Free APIs. Third parties keep using Free’s toggle. |
| Leave Pro `includes/compat/legacy.php` alone | Affiliate class aliases in cart-pro; unrelated to #629. |
| One identifier family per PR | Same as Free: do not mix a PHP rename with a persisted-data migration. |
| Dual-define, then drop | During a slice, Pro may define both names so mixed Free versions keep working. Remove the legacy name once the slice is the supported floor. |
| Frozen strings stay frozen until their slice | Pro-owned CPT **source** is now `ppcart_*` (Free slice 28); leftover `sc_collection` / `sc_us_path` / `sc_membership` / `sc_upgrade_path` **rows** have no Free Data migration — Pro must dual-register leftover+canonical until Pro migrates. REST namespace is `publishpress-cart/v1` (Free slice 29 hard cutover; leftover `sc/v1` unregistered in Free). Custom tables use live helpers + opt-in Data migration (slice 16). Shortcode tags are `ppcart_*` (Free slice 14). Free product/order/subscription CPT and cap slugs have an opt-in Data migration — Pro must call the live helpers, not hardcode either slug. Post/user-meta keys use `ppcart_meta_key()` / `ppcart_get_post_meta()`. Request field names are Free slice 26 — Pro must post/read canonical keys. |
| Do not dual-fire legacy hooks from Pro | Free Compatibility Mode already bridges `sc_*` ↔ `ppcart_*`. Pro call sites fire/listen on canonical names only. |
| Coordinate Free JS for Pro AJAX | Free checkout JS posts canonical `ppcart_check_username` / `ppcart_capture_lead` (slice 30). Leftover names bridge in Free Compat. Coupon / upsell leftovers stay Pro until a later Pro slice. |

## Current Free/Pro contract

These are the names Free already uses. Pro must match them. Live leftover maps in sibling `publishpress-cart-compat` (paths companion-relative; Free has no `includes/compat/`):

- Constants: `includes/compat/studiocart-compatibility-mode/maps/constants.php`
- Classes: `maps/bootstrap.php`, `maps/admin.php`, `maps/public.php`, `maps/models.php`, `maps/helpers.php`, `maps/gutenberg.php`, `maps/admin-controllers.php`, `maps/stripe-logging.php`, `maps/fixtures.php`
- AJAX (Free-owned): `maps/ajax.php`
- Nonces: `maps/nonces.php`
- Request fields: `maps/request-fields.php`. First-party posts and reads
  canonical `ppcart_*` / `ppcart-*` including POST `ppcart_qty` (slice 35),
  GET `ppcart_extension_notice` (slice 47), GET `ppcart-slm-order`
  (slice 62), and GET `ppcart-revoke` / `ppcart-revoked` (slice 71). Hidden
  fields emit `$atts['name']` (`_ppcart_*` / repeater names). Leftover
  `sc_qty`, leftover GET `sc_extension_notice`, leftover GET `sc-slm-order`,
  and leftover GET `sc-revoke` / `sc-revoked` copy only while Compatibility
  Mode is on.
- Stripe object metadata: `maps/stripe-metadata.php`. First-party writes and
  reads `metadata.ppcart_*` via `ppcart_stripe_metadata()`. Leftover `sc_*`
  keys match only while Compatibility Mode is on. Product save stamps canonical
  when the canonical key is missing.
- Hooks: `maps/hooks.php` plus the mechanical rule in `hooks.php`.
  First-party fires `ppcart_order_*` / `ppcart_subscription_*` /
  `ppcart_renewal_*` (slice 34) and `ppcart_before_order_refund` plus
  preload/checkout `ppcart_*` hooks (slice 44). Leftover `sc_order_complete`
  and `before_sc_order_refund` listeners run only while Compatibility Mode
  is on.
- Identity APIs: `ppcart_is_checkout_context()`, privacy exporter `ppcart_exporter`, order export group `ppcart-orders`, and offer CPT comparison `ppcart_offer`. Email personalization uses `{publishpress_cart}`; leftover `{studiocart}` is a Compat-only runtime bridge and explicit Data migration input.
- Divi module identity (slice 57): Free and Compat ship no Divi implementation. The historical Free sources were removed as Pro ghost files. A future Pro-owned module must use `PPCart_Divi_Order_Form` (final name to match its live source) and `namespace PPCart`; leftover class aliases belong only in Compat and only when a canonical target exists.
- Pro-owned VAT/tax classes: Free calls `PPCart_VAT` / `PPCart_Tax` only. Pro
  defines those canonical classes; there is no Free fallback or Compat alias.
- Admin slugs: `includes/class-ppcart-admin-screens.php`
- Options: `get_option('_ppcart_*')` / `ppcart_*` / `add_option__ppcart_*`.
  First-party PHP names leftover `_sc_*` option keys only in Compat. Leftover
  `_sc_*` / `sc_*` rows copy onto canonical keys then delete via Data
  migration. Storage bridges read leftover rows in place. Leftover `_sc_*`
  secret names are sensitive only while Compatibility Mode is on. Map:
  `includes/compat/studiocart-compatibility-mode/maps/options.php`. Do **not**
  `update_option('_sc_*')` from Pro — leftover writes are no longer the
  first-party storage contract.
- CPT / cap / role live names: `includes/compat/cpt-slug-migration/helpers.php`
  (`ppcart_live_post_type()`, `ppcart_query_post_types()`, `ppcart_live_cap()`,
  `ppcart_user_can()`, `ppcart_live_role()`). Do **not** hardcode
  `ppcart_product` / `ppcart_order` while a store can still be on leftover
  `sc_*` rows. Pro-owned CPT **source** is `ppcart_collection` /
  `ppcart_us_path` / `ppcart_membership` / `ppcart_upgrade_path`. Queries must
  include leftover from `ppcart_query_pro_post_types()` until Pro migrates.
  Leftover names live in `cpt-slug-migration` maps and Compatibility Mode.
- Post/user-meta: `ppcart_meta_key()` / `ppcart_get_post_meta()` /
  `ppcart_update_post_meta()` (and user-meta twins). First-party helper
  call sites pass the **suffix** (`'amount'`), not leftover `'_sc_amount'`
  literals. Do **not** hardcode leftover `_sc_*` helper arguments. Helpers
  store `_ppcart_*` rows. Leftover **names** are Compatibility Mode metadata
  bridges. Query leftover keys only while Compatibility Mode is on.
  Leftover **rows** move in the merchant Data migration. Map:
  `includes/compat/meta-key-migration/helpers.php`. `$this->prefix` /
  `get_prefix()` is `ppcart_`.
- HTML `id` / `class` / `for`: `ppcart_` / `ppcart-` (Free slices 19 + **25b**
  hard cutover). Field ids `_ppcart_*` / wrappers `rid_ppcart_*` /
  `repeater_ppcart_*`. Metabox ids `ppcart-product-settings` /
  `ppcart-edit-order-details` / `ppcart-order-notes` / `ppcart-product`.
  Settings CSS custom properties on `.ppcart-settings-page` are `--ppcart-*`
  (not `--pp-cs-*`). Token register:
  [Settings CSS custom properties](#settings-css-custom-properties).
  Product lock tab ids are `ppcart_pro_*` (slice **48** hard cutover). Pro must
  register matching tabs; leftover `sc_pro_*` no-ops.
  Form `name=` / POST / GET keys are canonical `ppcart_*` /
  `ppcart-*` (slice 26 + **35** `ppcart_qty` + **62** `ppcart-slm-order` +
  **71** `ppcart-revoke` / `ppcart-revoked`). Hidden fields emit
  `$atts['name']`; leftover GET `sc-slm-order` / `sc-revoke` / `sc-revoked`
  copy only via Free Compat. Leftover names only via Free Compatibility Mode. Order-bump CSS targets `[for^=ppcart-orderbump]`.
  Cancel-subscription repeater inner keys are `ppcart_sub_prod_id` /
  `ppcart_sub_plan_id` / `ppcart_sub_cancel` (slice **36**). Leftover
  `sc_sub_*` keys copy only while Compatibility Mode is on; stored rows stay.
  Integration `services` ids are `ppcart_subscription` / `ppcart_refund_order`
  / `ppcart_wpdomainchecker` (slice **49**). Leftover `sc_subscription` /
  `sc_refund_order` / `sc_wpdomainchecker` copy only while Compatibility Mode
  is on; stored rows stay.
  Tax script templates: `tmpl-ppcart-tax-table-row`
  (Pro `wp.template('ncs-tax-table-row')` must become
  `ppcart-tax-table-row`). Leftover CPT body classes stay until Data
  migration; first-party selectors use the live body class (slice 28). Inner
  classes on those screens are canonical.
- Stripe object metadata: write/read `metadata.ppcart_*` via
  `ppcart_stripe_metadata()`. Leftover `sc_*` keys match only while
  Compatibility Mode is on. Product save stamps canonical when missing.
  Do not write leftover Stripe keys.
- REST namespace: Gutenberg (Free) is `publishpress-cart/v1`. Leftover
  `sc/v1` is unregistered in Free (slice 29 hard cutover). Pro developer
  REST must register `publishpress-cart/v1` only; do not dual-register
  leftover `sc/v1`. `SC_Rest_*` class rename stays on Pro’s classes slice.
- PHP methods: first-party uses `save_post_order`, `register_importers`,
  `get_mailchimp_*` / `mailchimp_authentication` (bool; not ApiClient) /
  `get_activecampaign_*`, `get_convertkit_form_options` /
  `get_converkit_tag_options`, `get_products`, `get_service_type`,
  `register_tab_section`, `set_custom_edit_*_columns` / `custom_*_column`,
  `update_ppcart_*_amount`, `ppcartLogger`. No Free method aliases. Pro callers/subclasses
  must use these names.
- Admin JS localize / `window.*`: `ppcart_reg_vars` (inner `ajax_url`),
  `ppcart_translate_backend`, `ppcart_admin_i18n`, `ppcart_mc_*`,
  `ppcartReports`, `ppcartSettingsI18n`, `ppcartNotificationI18n`,
  `ppcart_settings`. Public checkout: `ppcart` (was `studiocart`),
  `ppcart_translate_frontend`, `ppcart_currency`, `ppcart_user`,
  `ppcart_popup`, `window.ppcart_coupon`. No leftover `sc_reg_vars` /
  `ncsCart*` / `ncs_settings` / `sc_translate_*` / `sc_popup` /
  `window.sc_coupon`. Checkout JS inner functions are
  `ppcart_parse_json_response` / `ppcart_validate` / `ppcart_do_lead_capture`.
  jQuery events are `ppcart/orderform/*` (slice 39 hard cutover). Tracking
  localize inner keys are `is_ppcart_checkout` / `ppcart_oto_get` /
  `ppcart_order_get` / `ppcart_order_post_id` / `ppcart_preview_get` /
  `ppcart_order_post` (slice 45). Admin user search uses `search_ppcart_user`.
  No Compatibility Mode JS aliases.

### Remaining Free leftovers Pro must match (70–73)

Families **58–69** are done. After each Free slice **70–73**, Pro must match
the new emit/read. Do not copy leftover or mangled names into Pro.
Leftover → canonical: [Remaining leftovers (70–73)](prefix-standardization.md#remaining-leftovers-70-73).

| Slice | Canonical (Pro must match) | Leftover |
|-------|-----------------------------|----------|
| 70 Mangled hooks | `ppcart_activate` / `ppcart_upgrade` / `ppcart_product` / `ppcart_product_price` | Do not copy mangled `ppcart_ctivate` / `ppcart_pgrade` / `ppcart_roduct`. Leftover `ncs_activate` / `sc_product` (filter) via Free Compat (Free done) |
| 71 Revoke GET | `ppcart-revoke` / `ppcart-revoked` (Free done) | leftover GET `sc-revoke` / `sc-revoked` Compat on |
| 72 HTML/JS | `row-ppcart-` / `ppcart-nav-tabs` | `row-sc-` / `ncs-nav-tabs` (hard cutover; rebuild Gutenberg) |
| 73 Upload dir | `ppcart-uploads` (Free done) | leftover `sc-uploads` Compat extra root; no silent file move |

### Remaining Free leftovers Pro must match (74–76)

Families **70–73** are done. Slices **74–76** are done. Pro must match
the canonical emit/read. Do not copy leftover names into Pro.
Leftover → canonical: [Remaining leftovers (74–76)](prefix-standardization.md#remaining-leftovers-74-76).

| Slice | Canonical (Pro must match) | Leftover |
|-------|-----------------------------|----------|
| 74 Debug-log `Scrt*` | `PPCart_Order` / `PPCart_Subscription` only in log-id regex (Free done) | `ScrtOrder` / `ScrtSubscription` (hard cutover; no Compat) |
| 75 Merchant copy / URLs | `ppcart_field_id`; w.org `plugin/publishpress-cart`; `PPCART_DOCS_URL` default for Stripe subscription docs (Free done) | `studiocart_field_id` / `plugin/studiocart` / `studiocart.co` (hard cutover; no Compat) |
| 76 Comments | canonical names in comments/docblocks (Free done) | leftover `sc_*` / `_sc_*` / StudioCart product comments (docs-only; hard cutover) |

### Remaining Free leftovers Pro must match (77)

Families **74–77** are done. Slice **78** is done. Pro must match
the canonical emit/read. Do not copy leftover glued names into Pro.
Leftover → canonical: [Remaining leftovers (78)](prefix-standardization.md#remaining-leftovers-78).

| Slice | Canonical (Pro must match) | Leftover |
|-------|-----------------------------|----------|
| 77 Glued HTML/JS | `data-ppcart-qty-price`; `originalPpcartSettings` (Free done) | `data-scq-price` / `originalNcs` (hard cutover; no Compat) |
| 78 Glued `$sc*` / `$scrt_*` locals | `$ppcart_order` / `$ppcart_subscription` (Free done) | `$scrt_order` / `$scorder` / `$scsub` (hard cutover; no Compat) |
| 79 jQuery `scPE` | `elementor/popup/show.ppcart-pe-` (Free done) | `.scPE-` (hard cutover; no Compat) |

### Remaining Free leftovers Pro must match (80–81)

Families **58–79** are done. After each Free slice **80–81**, Pro must match
the new emit/read. Do not copy leftover glued names or seeder keys into Pro.
Leftover → canonical: [Remaining leftovers (79–81)](prefix-standardization.md#remaining-leftovers-79-81).

| Slice | Canonical (Pro must match) | Leftover |
|-------|-----------------------------|----------|
| 79 jQuery `scPE` | `elementor/popup/show.ppcart-pe-` (Free done) | `.scPE-` (hard cutover; no Compat) |
| 80 Sample tax CSV | `sample_ppcart_tax_rates.csv`; phpmd comments `PPCart_Public` / `PPCart_` (Free done) | `sample_sc_tax_rates.csv`; phpmd `NCS_Cart_Public` / `NCS_` / `Scrt` |
| 81 Test seeder keys | suffixes / `_ppcart_*` (Free tests) | `_sc_*` seeder keys. Never strip `_sc_` in Cart meta helpers |

### Remaining Free leftovers Pro must match (58–69)

These Free slices landed. After each, Pro must match the new emit/read. Do not
copy leftover names into Pro.
Leftover → canonical: [Remaining leftovers (58–69)](prefix-standardization.md#remaining-leftovers-58-69).

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

### Constants Pro must define before requiring Free

Define the canonical names **before** `require` of `publishpress-cart.php`.
Inbound Compatibility Mode copy is a temporary crutch, not the contract.
Free does **not** ungate `ncs_cart_resolve_canonical_constants` in this
slice; completing slice 18 is **blocked on this Pro dual-define**. Keep the
Compatibility Mode aliases in
`includes/compat/studiocart-compatibility-mode/maps/constants.php`.

| Canonical (required) | Legacy Pro still ships | Free uses it for |
|----------------------|------------------------|------------------|
| `PPCART_LOADED_BY_PRO` | `NCS_CART_LOADED_BY_PRO` | `ppcart_is_pro()` |
| `PPCART_PRO_BASE_URL` | `NCS_CART_PRO_BASE_URL` | Vendor asset URL when Pro loads Free |
| `PPCART_PRO_LIB_VENDOR_DIR` | `NCS_CART_PRO_LIB_VENDOR_DIR` | Bundled translations / autoload fallback |
| `PPCART_PRO_PLUGIN_NAME` | `NCS_CART_PRO_PLUGIN_NAME` | Admin copy; Free defaults to “PublishPress Cart Pro” |
| `PPCART_BASE_FILE` override | `NCS_CART_BASE_FILE` | Free bootstrap skips its own `__FILE__` when already defined |

Pro-local (Free does not read these today; define canonical anyway):

| Canonical | Legacy |
|-----------|--------|
| `PPCART_PRO_BASE_FILE` | `NCS_CART_PRO_BASE_FILE` (if present) |
| `PPCART_PRO_BASE_DIR` | `NCS_CART_PRO_BASE_DIR` (if present) |

If Pro currently overrides Free’s base file via `NCS_CART_BASE_FILE`, switch
that override to `PPCART_BASE_FILE`. Do not keep a Compatibility Mode-only path.

**Transition snippet** (first Pro slice; delete the `NCS_*` lines later):

```php
define('PPCART_LOADED_BY_PRO', true);
define('PPCART_PRO_BASE_FILE', __FILE__);
define('PPCART_PRO_BASE_DIR', plugin_dir_path(__FILE__));
define('PPCART_PRO_BASE_URL', plugin_dir_url(__FILE__));
define('PPCART_PRO_PLUGIN_NAME', 'PublishPress Cart Pro');
define('PPCART_PRO_LIB_VENDOR_DIR', PPCART_PRO_BASE_DIR . 'lib/vendor');

if (! defined('NCS_CART_LOADED_BY_PRO')) {
    define('NCS_CART_LOADED_BY_PRO', PPCART_LOADED_BY_PRO);
}
if (! defined('NCS_CART_PRO_BASE_URL')) {
    define('NCS_CART_PRO_BASE_URL', PPCART_PRO_BASE_URL);
}
if (! defined('NCS_CART_PRO_LIB_VENDOR_DIR')) {
    define('NCS_CART_PRO_LIB_VENDOR_DIR', PPCART_PRO_LIB_VENDOR_DIR);
}
if (! defined('NCS_CART_PRO_PLUGIN_NAME')) {
    define('NCS_CART_PRO_PLUGIN_NAME', PPCART_PRO_PLUGIN_NAME);
}

require_once WP_PLUGIN_DIR . '/publishpress-cart/publishpress-cart.php';
```

### Bootstrap hooks Pro listens to

Free already fires these. Rename Pro `add_action` targets; do not add new
`sc_*` names.

| Canonical | Typical Pro job |
|-----------|-----------------|
| `ppcart_load_pro_modules` | Require Pro module files (`PPCart_Dependency_Loader`) |
| `ppcart_register_pro_hooks` | REST, admin, public Pro hooks (`PPCart::run`) |
| `ppcart_register_public_ajax_handlers` | Pro AJAX on the public class |
| `ppcart_before_load` | Early Pro setup, if Pro uses it |
| `ppcart_supports_feature` | Feature gate. Compatibility Mode hooks this at priority **20** so a Pro blanket `return true` cannot force that feature on. Keep Pro’s filter at a lower priority or skip the `ppcart-compatibility-mode` key. |

Detection helpers (call these; do not reimplement):

| Canonical | Compatibility Mode wrapper (do not call from Pro) |
|-----------|---------------------------------------------------|
| `ppcart_is_pro()` | `sc_cart_is_pro()` |
| `ppcart_supports( $feature )` | `sc_cart_supports( $feature )` |

### Classes Pro may extend or instantiate

Canonical first. `NCS_Cart_*` / `Scrt*` / `SC_*` exist only when Compatibility
Mode is on.

| Canonical | Legacy aliases (Free Compatibility Mode) |
|-----------|------------------------------------------|
| `PPCart` | `NCS_Cart` |
| `PPCart_Loader` | `NCS_Cart_Loader` |
| `PPCart_Public` | `NCS_Cart_Public` |
| `PPCart_Paypal` | `NCS_Cart_Paypal` |
| `PPCart_Admin` / `PPCart_Admin_Settings` / `PPCart_Product_Metaboxes` | `NCS_Cart_*` equivalents |
| `PPCart_Order` | `NCS_Cart_Order`, `ScrtOrder` |
| `PPCart_Subscription` | `NCS_Cart_Subscription`, `ScrtSubscription` |
| `PPCart_Collection` | `NCS_Cart_Collection`, `ScrtCollection` |
| `PPCart_Order_Item` | `NCS_Cart_Order_Item`, `ScrtOrderItem` |
| `PPCart_Helper` / `PPCart_Stripe` / `PPCart_Price_Format` | `NCS_Cart_*`, `NCS_Helper`, `NCS_Stripe`, `NCS_Price_Format` |
| `PPCart_Files` | `NCS_Cart_Files` — Pro subclass pattern was `PPCart_Files_Pro` / `NCS_Cart_Files_Pro` → keep `PPCart_Files_Pro` |

Pro-owned classes: `NCS_Cart_Pro_*` → `PPCart_Pro_*`, files
`class-ncs-cart-pro-*.php` → `class-ppcart-pro-*.php`.

### Admin screens Pro registers

Hard cutover already shipped in Free. No redirects, no dual slugs, no
Compatibility Mode bridge. Pro must use these exact values.

| Role | Canonical slug | Screen hook |
|------|----------------|-------------|
| Parent menu | `ppcart` | `toplevel_page_ppcart` |
| Settings | `ppcart-settings` | `ppcart_page_ppcart-settings` |
| White label | `ppcart-white-label` | `ppcart_page_ppcart-white-label` |
| Affiliates | `ppcart-affiliates` | `ppcart_page_ppcart-affiliates` |

Also rename CSS that targeted `.studiocart_page_*` or `.toplevel_page_studiocart`
to `.ppcart_page_*` / `.toplevel_page_ppcart`.

### Extension hooks Pro listens to or fires

### Checkout completion access (Free #676)

Pro funnel step URLs that hit `?ppcart-order=&step=` must append
`PPCart_Order::access_arg( $order_id )` so Free can authorize completion.
After the first `ppcart_checkout_complete` fire, listen for
`ppcart_checkout_step_viewed` instead of expecting completion to re-fire.

### Mailchimp API (no SDK)

Free no longer returns `\MailchimpMarketing\ApiClient` from
`PPCart_Admin::mailchimp_authentication()`. That method is a bool alias of
`ppcart_has_mailchimp_api()`. `$admin->mailchimp_authentication()->lists`
fatals. Do not instantiate the Mailchimp Marketing SDK from Pro.

| Need | Call |
|------|------|
| Credentials present? | `ppcart_has_mailchimp_api()` or `$admin->mailchimp_authentication()` (bool) |
| Marketing API HTTP | `ppcart_mailchimp_api_request( $endpoint, $method, $body, $query )` |
| Key / datacenter URL | `ppcart_get_mailchimp_api_config()` → `array{api_key,base_url}\|false` |


Use the [mechanical hook map](prefix-standardization.md#hook-prefix-map-mechanical) above. Hooks Pro is
known to use against Free (verify in the Pro tree):

| Canonical | Was |
|-----------|-----|
| `ppcart_product_setting_tabs` | `sc_product_setting_tabs` |
| `ppcart_product_setting_tab_{$tab}_fields` | `sc_product_{$tab}_fields` |
| `ppcart_confirmation_fields` | `sc_confirmation_fields` |
| `ppcart_setting_tabs` | `sc_setting_tabs` |
| `ppcart_register_sections` / `_ppcart_register_sections` | `sc_register_sections` |
| `ppcart_coupon_fields` / `ppcart_coupon_status` | `sc_coupon_fields` / `sc_coupon_status` |
| `ppcart_checkout_step_viewed` | funnel step after completion fired once |
| `PPCart_Order::access_arg()` | `ppcart-access` query arg for completion URLs |
| `ppcart_checkout_template_path` | checkout template filter |
| `ppcart_enqueue_scripts_upsell_downsell` / `ppcart_script_vars` | upsell script vars |
| `ppcart_buy_button_icon` | buy-button icon slot, `$product`, `left`/`right` |
| `ppcart_buy_button_subtext` | buy-button subtext slot, `$product` |
| `ppcart_step_1_button_icon` | step-1 button icon slot, `$product`, `left`/`right` |
| `ppcart_step_1_button_subtext` | step-1 button subtext slot, `$product` |
| `ppcart_show_version_notices` | Free upgrade nag; Pro returns false. See [Pro-gate filters](#pro-gate-filters) |
| `ppcart_show_reviews` | Free WP.org review notice; Pro returns false. See [Pro-gate filters](#pro-gate-filters) |
| `ppcart_register_file_handler` | load Free `PPCart_Files`; Pro returns false. See [Pro-gate filters](#pro-gate-filters) |
| `ppcart_checkout_hide_labels` | hide checkout field labels when product meta is set |
| `ppcart_integration_trigger_options` | Lead Captured trigger |
| `ppcart_integration_plan_targets` | bump/upsell/downsell plan targets |
| `ppcart_pay_plan_recurring_fields` | sign-up fee, trial, cancel-immediately; runs before is_hidden |
| `ppcart_cpt_options` | collection CPT show_in_rest |
| `_ppcart_option_list` | coupon URL param, disable product template, white-label link |
| `ppcart_integrations` | WishList / Tutor / RCP / webhook services |
| `ppcart_product_field_groups` | coupons, order bump, and upsell path groups |
| `ppcart_product_general_fields` | hide-page, tax status, purchase note, and other Pro general fields |
| `ppcart_product_page_redirect` | action on a product page after Free's own redirects; Pro redirects hidden product pages here |
| `ppcart_setup_product_from_meta` | hide_phone_field default and other Pro setup keys |
| `ppcart_product_paypal_enabled` | per-product PayPal disable |
| `ppcart_order_summary_items` | action: print the detailed order-summary block; Free prints the Order Total heading when nothing is hooked |
| `ppcart_subtotal_label` | Pro-owned now; Free no longer applies it |
| `ppcart_order_summary_bump_text` | Pro-owned now; Free no longer applies it, and the `ob_custom_description` callback moved out of Free |

After renaming Pro call sites, grep Pro for leftover `add_action( 'sc_` /
`apply_filters( 'sc_`. If Pro still needs a name Free never mapped, add it to
Free `maps/hooks.php` `override` / `extra` — do not dual-fire from Pro.

### Pro-gate filters

These two replace gates that used to read `ppcart_supports('pro')`. That call
was true at Free include time because Pro defines `PPCART_LOADED_BY_PRO`
before it requires Free. A filter has no such guarantee.

| Filter | When Free reads it |
|--------|--------------------|
| `ppcart_register_file_handler` | While Free boots (`admin-hook-registrar-register.php`). `plugins_loaded` is too late. |
| `ppcart_show_version_notices` | `PPCart_Version_Notices::register_notice_settings()` on `plugins_loaded` priority 20. |
| `ppcart_show_reviews` | `PPCart_Reviews::register_reviews()` on `admin_init`. `plugins_loaded` is early enough on a normal boot. |

Register Files and version notices **before** `require` of Free — same place as `PPCART_LOADED_BY_PRO`.
That is the only path that works on Pro activation, when WP includes the plugin
after `plugins_loaded` already ran:

```php
add_filter( 'ppcart_show_version_notices', '__return_false' );
add_filter( 'ppcart_show_reviews', '__return_false' );
add_filter( 'ppcart_register_file_handler', '__return_false' );
require $free;
```

A `plugins_loaded` priority 10 `add_filter` can still skip version notices on a
normal boot. It cannot skip Files, and it does nothing on the activation
request. Too late fails silently: Free keeps its file handler, or the upgrade
notice stays registered.

`PPCart_Files::__construct()` calls `initialize()` at once. Skip construction
to skip Free file handling. `initialize()` registers `ppcart_activate`,
`ppcart_upgrade`, `upload_dir`, the downloads shortcode, and `init` rewrites,
so the construction cannot wait until `plugins_loaded`.

### AJAX: Free-owned vs Pro-owned

Free-owned POST actions are canonical `ppcart_*`. Compatibility Mode
dual-registers the shipped `sc_*` / `ncs_*` values from `maps/ajax.php`.
Pro JS that talks to **Free** handlers should post `ppcart_*` (and can keep
posting the mapped legacy value only while supporting old Free).

Pro-owned actions that Free posts (and leftovers Compat bridges) are in Free’s
ajax map so leftover names work when Compatibility Mode is on. Known leftovers:

| Current POST `action` (legacy) | Canonical target | Notes |
|--------------------------------|------------------|--------|
| `sc_check_username` | `ppcart_check_username` | Free JS posts canonical (slice 30); Compat maps leftover |
| `sc_capture_lead` | `ppcart_capture_lead` | Free JS posts canonical (slice 30); Compat maps leftover |
| `sc_validate_coupon` | `ppcart_validate_coupon` | Confirm in Pro; may live in Pro JS |
| `sc_process_upsell` | `ppcart_process_upsell` | Confirm in Pro |
| PayPal upsell AJAX | `ppcart_*` equivalent | Confirm in Pro |

Pro registers only canonical `wp_ajax(_nopriv)_ppcart_check_username` /
`ppcart_capture_lead`. Free Compat dual-registers leftover `sc_*` when the
toggle is on. Coupon / upsell remain Pro dual-register until those Free/Pro
slices land.

Nonce **actions** Pro generates should be `ppcart_*`. Compatibility Mode
accepts mapped leftover nonce strings for Free’s map only. Request **field**
names follow Free slice **26** (canonical `ppcart_*` / `ppcart-*`; leftover
POST/GET via Free Compat). Hidden `sc-` emit and GET `sc-slm-order` are Free
slice **62** (Free done; leftover GET only via Free Compat).

### JS localize object

Free localizes checkout script handle `ppcart` as the global `ppcart`.
Pro JS must read `ppcart.ajax` (not leftover `studiocart.ajax`). Also match
`ppcart_translate_frontend`, `ppcart_currency`, `ppcart_user`, `ppcart_popup`,
and `window.ppcart_coupon`. Admin JS must read Free 21a names.

Asset handles in Pro: `ncs-cart-*` → `ppcart-*` (for example
`ncs-cart-public-pro` → `ppcart-public-pro`).

### Runtime globals

If Pro reads checkout globals, use `$ppcart_stripe`, `$ppcart_currency`,
`$ppcart_currency_symbol`, `$ppcart_debug_logger`, `$ppcart_product`,
`$ppcart_checkout_request_rendered`, `$ppcart_checkout_block_arrangement`,
`$ppcart_public`, `$ppcart_files`, `$ppcart_product_fields`, and
`$ppcart_is_admin_screen`.
`$sc_*` / `$scp` / leftover `$GLOBALS['sc_checkout_*']` / leftover
`$studiocart` / leftover `$scFiles` / leftover `$sc_product_fields` /
leftover `$sc_is_studiocart_admin_screen` exist only with Compatibility Mode
on. Do not add new Pro reads of those leftover names.

### Transients

Free first-party transients are `ppcart_*` (slice 24). Leftover `_transient_sc_*`
rows copy onto canonical keys at boot. Leftover `get_transient('sc_*')` names
work only with Compatibility Mode on and write canonical. Pro must not write
`sc_*` transients.

### Meta helper call sites

Free first-party `ppcart_*_meta()` call sites pass the suffix (`'amount'`), not
leftover `'_sc_amount'` literals (slice 25a). Pro must not pass leftover
`'_sc_*'` strings into the helpers.

### Leftover HTML ids

Free first-party HTML ids and matching JS/CSS selectors are `_ppcart_*` /
`rid_ppcart_*` / `repeater_ppcart_*` (slice 25b). Metabox ids are
`ppcart-product-settings` / `ppcart-edit-order-details` /
`ppcart-order-notes` / `ppcart-product`. No Compatibility Mode HTML id
aliases. Pro markup/JS/CSS must match.

### Dashboard widget id

Free registers `ppcart_dashboard_widget` (slice 23 hard cutover). There is no
Compatibility Mode leftover `studiocart_dashboard_widget`. Pro must not
`wp_add_dashboard_widget( 'studiocart_dashboard_widget', … )` or target that
leftover id in CSS/JS.

## Per-slice Pro contract (leftover families 58–82)

Detail behind the summary tables above. Each entry is a Free slice that
already landed; Pro must match the canonical column and must not copy the
leftover column into first-party code.

### Storefront HTML classes (Free slice 58 — hard cutover)

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

### Admin HTML classes (Free slice 59 — hard cutover)

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

### Gutenberg leftover checkout block (Free slice 60 — Compat dual-register)

Pro must register and emit only `publishpress-cart/checkout-form`. Do not
dual-register leftover `sc-products-shortcode/product-shortcode` in Pro
first-party, localize `legacyBlockName`, or keep leftover editor CSS. Persisted
leftover blocks render only while Free Compatibility Mode is on.

| Leftover (Pro must not register) | Canonical (Pro must match Free) |
|----------------------------------|----------------------------------|
| `sc-products-shortcode/product-shortcode` | `publishpress-cart/checkout-form` |
| `.wp-block-sc-products-shortcode-product-shortcode` | `.wp-block-publishpress-cart-checkout-form` |
| `legacyBlockName` / `LEGACY_BLOCK_NAME` | omit; Compat editor JS hardcodes leftover |

### Admin AJAX keys and JS globals (Free slice 61 — Compat bridge)

Pro must post/read canonical admin AJAX keys and tax settings globals. Do not
emit `ncs_ajax_nonce` / `ncs_action` or `var ncs` in Pro first-party JS.

| Leftover (Compat while on) | Canonical (Pro must match Free) |
|----------------------------|----------------------------------|
| POST `ncs_ajax_nonce` / `ncs_action` | `ppcart_ajax_nonce` / `ppcart_action` |
| `var ncs` (Stripe JS namespace) | `ppcartStripe` |
| `sc_tax_settings` localize | `ppcart_tax_settings` |

### Request `name=` / GET leftovers (Free slice 62 — Compat GET copy)

Pro must emit hidden fields via `$atts['name']` (`_ppcart_*` / repeater
array names) and read GET `ppcart-slm-order`. Do not emit leftover `sc-`
hidden names or read leftover GET `sc-slm-order` in Pro first-party.

| Leftover (Compat while on) | Canonical (Pro must match Free) |
|----------------------------|----------------------------------|
| Hidden `str_replace('_ppcart_', 'sc-', …)` emit | `$atts['name']` / `_ppcart_*` |
| GET `sc-slm-order` | `ppcart-slm-order` |

### Leftover recognition (Free slice 63 — Compat read-through)

Pro must not silent-migrate `_my_account`, skip-copy duplicator keys by leftover
prefix, strip `sc_` in Stripe owned-field helpers, or register leftover FSE
templates. Use canonical options/meta only in Pro first-party.

| Leftover (Compat while on) | Canonical (Pro must match Free) |
|----------------------------|----------------------------------|
| Option `_my_account` read-through | `_ppcart_myaccount_page_id` only in Pro code |
| Duplicator `_sc_*` source meta | `_ppcart_*` allowlist + Compat rewrite filter |
| `_sc_*` Stripe owned-field names | Canonical suffix / `_ppcart_*` via Compat `ppcart_meta_key_suffix` |
| Leftover FSE `single-sc_product` template | Canonical `single-ppcart_product` only in Pro |

### Compat metadata bridge prefix (Free slice 64 — Compat-only)

Metadata API bridge callbacks in `publishpress-cart-compat` use `ppcartcomp_*`
(for example `ppcartcomp_get_post_metadata`). Pro has no action — Cart live
helpers stay `ppcart_*`.

### Admin TinyMCE JS (Free slice 65 — hard cutover)

Pro admin JS must use canonical TinyMCE editor ids and jQuery data keys. PHP
already emits `ppcart-{optionId}` via `ppcart-admin-field-editor.php`; Pro JS
must not look up `sc-{optionId}` or use `.data('sc-editor-*')`.

| Leftover (no-op in Pro) | Canonical (Pro must match Free) |
|-------------------------|----------------------------------|
| `.data('sc-editor-initialized')` | `.data('ppcart-editor-initialized')` |
| `.data('sc-editor-repairing')` | `.data('ppcart-editor-repairing')` |
| `'sc-' + optionId` TinyMCE lookup | `'ppcart-' + optionId` |

No Compatibility Mode JS aliases for leftover TinyMCE ids or jQuery data keys.

### CPT/taxonomy option filters (Free slice 66 — Compat bridge)

Pro must listen on canonical `ppcart_cpt_options` / `ppcart_taxonomy_options`
only. Do not `apply_filters` leftover `sc-cart-cpt-options` /
`sc-cart-taxonomy-options` in Pro first-party. Leftover filter names reach
canonical listeners only via Free Compat while Compatibility Mode is on.

| Leftover (Compat on only) | Canonical (Pro must match Free) |
|---------------------------|----------------------------------|
| `sc-cart-cpt-options` | `ppcart_cpt_options` |
| `sc-cart-taxonomy-options` | `ppcart_taxonomy_options` |

### Tax CSV import slug (Free slice 67 — hard cutover)

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

### Cosmetic `$ncs_*` locals (Free slice 68 — hard cutover)

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

### Mangled leftover hooks (Free slice 70 — done)

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

### Revoke GET keys (Free slice 71 — done)

Pro must emit and read `ppcart-revoke` / `ppcart-revoked`. Leftover GET
`sc-revoke` / `sc-revoked` copy only via Free Compat while on (slice 26
pattern). Do not emit leftover query keys in Pro first-party.

| Leftover (Compat while on) | Canonical (Pro must match Free) |
|----------------------------|----------------------------------|
| GET `sc-revoke` | `ppcart-revoke` |
| GET `sc-revoked` | `ppcart-revoked` |

### Escaped HTML/JS leftovers (Free slice 72 — done)

Pro must match Free `row-ppcart-` ids and `ppcart-nav-tabs`. Rebuild any Pro
editor bundle that still ships `ncs-nav-tabs`. Hard cutover; no dual-class.

| Leftover (no-op in Pro) | Canonical (Pro must match Free) |
|-------------------------|----------------------------------|
| `id="row-sc-{field_id}"` | `id="row-ppcart-{field_id}"` |
| Gutenberg build `ncs-nav-tabs` | `ppcart-nav-tabs` |

### Download upload directory (Free slice 73 — done)

Pro must write and allow-list `wp-content/uploads/ppcart-uploads` only. Leftover
`sc-uploads` is an extra allowed root only while Free Compatibility Mode is on.
Do not silent-move files. Do not invent `ncs-cart/uploads`. Existing merchant
files under leftover dir work with Compat on until an explicit Data migration
card (out of this slice).

| Leftover (Compat extra root while on) | Canonical (Pro must match Free) |
|---------------------------------------|----------------------------------|
| `wp-content/uploads/sc-uploads` | `wp-content/uploads/ppcart-uploads` |

### Debug-log `Scrt*` recognition (Free slice 74 — done)

Pro must match Free canonical-only log-id regex (`PPCart_Order` /
`PPCart_Subscription`). Do not recognize leftover `ScrtOrder` /
`ScrtSubscription` in Pro first-party. Hard cutover; no Compat shim.

### Merchant leftover copy / URLs (Free slice 75 — done)

Pro must match Free `ppcart_field_id` help text, w.org
`plugin/publishpress-cart` reviews URL, and the Cart docs hub default
(`PPCART_DOCS_URL` = `https://docs.rambleventures.com/publishpress/publishpress-cart/`)
for Stripe subscription docs (`ppcart_stripe_subscriptions_documentation_url`).
Do not ship `studiocart_field_id`, `plugin/studiocart`, `studiocart.co`,
`knowledge-base/introduction-cart`, or `publishpress.com/docs-category` defaults.
Hard cutover; no Compat shim.

### Leftover names in comments (Free slice 76 — done)

No runtime contract. Mirror Free canonical comment/docblock names when
touching the same files.

### Glued HTML/JS leftovers (Free slice 77 — done)

Pro must emit `data-ppcart-qty-price` on quantity custom fields (not glued
`data-scq-price` / `data-ppcartq-price`). Settings JS local is
`originalPpcartSettings` (not `originalNcs`). Hard cutover; no Compat shim.

### Glued `$sc*` / `$scrt_*` locals (Free slice 78 — done)

Pro must match Free `$ppcart_order` / `$ppcart_subscription` in vendored copies
of CSV export, my-account order detail, order product-form, and subscription
column templates. Do not copy `$scrt_order` / `$scorder` / `$scsub`. Mirror
the debug-logger comment (`$ppcart_debug_logger`, not “SC debug logger”).
Hard cutover; no Compat shim. Free landed in `58966193`.

### jQuery `scPE` namespace (Free slice 79 — done)

Pro must match Free `elementor/popup/show.ppcart-pe-` if it vendors or copies
the Elementor popup handler. Do not copy `.scPE-`. Hard cutover; no Compat
shim. Free landed in `eaedf4d1`.

### Sample tax CSV filename (Free slice 80 — done)

Do not ship `sample_sc_tax_rates.csv`. Canonical sample is
`sample_ppcart_tax_rates.csv`. Mirror phpmd comment names (`PPCart_Public` /
`PPCart_`, not `NCS_Cart_Public` / `NCS_` / `Scrt`). Hard cutover; no Compat
shim. Free landed in `3368e2c5`.

### Test seeder `_sc_*` keys (Free slice 81 — done)

Free-test only. Do not copy leftover `_sc_*` seeder keys into Pro tests.
Never strip `_sc_` in Cart meta helpers. Playwright/legacy seeders pass suffix
keys to `ppcart_fixtures_update_post_meta()` and `_ppcart_*` to raw
`update_post_meta` / `update_option`. Hard cutover; no Compat shim. Free
landed in `dd08ca94`.

### Doubled `ppcart_cart_` prefix (Free slice 82)

Pro must call `ppcart_supports` / `ppcart_is_pro` / `ppcart_loaded` /
`ppcart_pro_*` and roles `ppcart_manager` / `ppcart_administrator`. Product
checkout-window meta suffixes and `$ppcart_product` properties are
`checkout_starts` / `checkout_ends` / `checkout_ended_action` /
`checkout_ended_redirect` / `checkout_ended_message`. Do not read
`cart_open` / `_ppcart_cart_*`. Thank-you URL stays `_ppcart_redirect`.
Leftover `_sc_cart_open` (and siblings) only via Free Compat. Free landed in
`186e0f69`.

## Suggested Pro slice order

Same families as the Free remaining slices, ordered for Pro’s runtime
dependency on Free. One slice per PR.

| # | Slice | Why this next | Free analog |
|---|--------|---------------|-------------|
| 1 | **Pro-contract constants** | Unblocks Pro **detection** with Compatibility Mode off. Dual-define `PPCART_*` before requiring Free, then alias `NCS_CART_*`. Free inbound stays gated (UT-194). Intended follow-up of `docs(prefix): record Pro-contract constants dual-define (#629)` — not already done. Modules still need the later Pro hook slice. | Slice 18 / constants |
| 2 | Bootstrap functions + main plugin file | `ppcart_pro_*` activate/deactivate if prefixed; `publishpress-cart-pro.php` locals `$ppcart_*` | Slices 1, 5 |
| 3 | Pro classes + PHP filenames | `PPCart_Pro_*` / `class-ppcart-pro-*.php`; callers of Free classes switch to `PPCart_*` | Classes / filenames |
| 4 | Functions Pro defines or calls | `ppcart_*` / `ppcart_pro_*`; stop calling `sc_*` wrappers | Functions |
| 5 | Hook listeners and fires | Canonical names from the table above | Hooks / plugin identity |
| 6 | Admin menu slugs + screen CSS | Parent `ppcart`; white label / affiliates canonical slugs | Slice 9 |
| 7 | Pro AJAX actions | Canonical `wp_ajax_ppcart_*` for username/lead (Free slice 30; leftover via Free Compat). Dual-register coupon/upsell leftovers until those Pro/Free slices land | Slices 11 + 30 |
| 8 | Pro nonce actions | Canonical `ppcart_*` nonce strings; request field names follow Free slice 26 | Slice 11 (nonce commit) |
| 9 | Assets, BEM, HTML ids/classes, icon/font leftovers, JS localize | Handles, CSS classes, CSS custom properties `--ppcart-*` (not `--pp-cs-*`), `id`/`class`/`for`, file names `ppcart-*`. Admin JS must read Free 21a names (`ppcart_reg_vars`, `ppcartSettings*`, `ppcartReports`). Public checkout JS must read Free 21b names (`ppcart`, `ppcart_translate_frontend`, `ppcart_popup`, `window.ppcart_coupon`). | Slices 8 + 19 + 21 |
| 10 | Test helpers / fixtures | No production aliases | Slice 7 |
| 11 | Free CPT/cap live helpers | Call `ppcart_live_post_type()` / `ppcart_query_post_types()` / `ppcart_live_cap()`; do not hardcode `ppcart_product` | Slices 12 + 15 |
| 12 | Option keys | `get_option('_ppcart_*')` / `update_option('ppcart_*')`; leftover option rows go away | Slice 13a |
| 14a | Shortcode tags | Leftover tags dual-register only in Free Compatibility Mode | After Free 14a |
| 14b | First-party shortcode content | Emit/detect canonical tags | After Free 14b |
| 14c | Shortcode content Data migration | Leftover tags in posts/emails rewrite to canonical | After Free 14c |
| 16 | Custom tables / metadata types | Call `ppcart_live_table()` / `ppcart_live_metadata_type()`; `PPCart_Tax` must use `ppcart_live_table( 'tax_rate' )`. No Compat table-name shim. | After Free 16 |
| 17 | On-disk debug log directory | Keep Free default `{uploads}/publishpress-cart/logs` (`wp_upload_dir()['basedir']`; override `PPCART_DEBUG_LOG_DIR`); do not invent `ncs-cart/logs` | After Free 17 |
| 22 | Unlisted PHP runtime globals | Use `$ppcart_checkout_request_rendered` / `$ppcart_checkout_block_arrangement`; leftover `sc_checkout_*` only via Free Compat | After Free 22 |
| 23 | Dashboard widget id | Use `ppcart_dashboard_widget`; no leftover `studiocart_dashboard_widget` | After Free 23 |
| 24 | Transient prefixes | Use `ppcart_*` transients; leftover `_transient_sc_*` rows copy at Free boot; leftover names only via Free Compat | After Free 24 |
| 25a | Meta helper leftover literals | Pass suffix into `ppcart_*_meta()`, not leftover `'_sc_*'` | After Free 25a |
| 25b | Leftover HTML ids / JS / first-party CSS | Match `#_ppcart_*` / `rid_ppcart_*` / `repeater_ppcart_*` and metabox `ppcart-*`; no leftover HTML id aliases | After Free 25b |
| 26 | Request field names | Post and read Free canonical `ppcart_*` / `ppcart-*` keys; leftover names only via Free Compat | After Free 26 |
| 27 | Stripe object metadata | Write/read `metadata.ppcart_*`; leftover `sc_*` only via Free Compat; do not write leftover Stripe keys | After Free 27 |
| 28 | Leftover CPT / taxonomy strings | Register Pro-owned CPTs as `ppcart_*`; dual-register leftover until Pro migrates; FSE `single-ppcart_product`; rewrite `ppcart_product_*`; use `ppcart_query_pro_post_types()` | After Free 28 |
| 29 | REST namespace | Register developer REST on `publishpress-cart/v1` only. Do not dual-register leftover `sc/v1`. `SC_Rest_*` class rename stays on Pro’s classes slice. | After Free 29 |
| 31 | Runtime globals `$studiocart` / `$scFiles` | Use Free canonical `$ppcart_public` / `$ppcart_files` / `$ppcart_product_fields` / `$ppcart_is_admin_screen`; leftover `$studiocart` / `$scFiles` / `$sc_product_fields` / `$sc_is_studiocart_admin_screen` only via Free Compat | After Free 31 |
| 32 | Admin bulk actions | Match Free `ppcart_make_*` / `ppcart_sync_stripe` / `bulk_ppcart_*`; hard cutover; do not post leftover `sc_make_*` | After Free 32 |
| 33 | Log viewer request fields | Match Free `ppcart_view_log` / canonical nonce fields | After Free 33 |
| 34 | Integration trigger tags | Listen/fire `ppcart_order_*` / `ppcart_subscription_*` / `ppcart_renewal_*`; leftover `sc_*` only via Free Compat | After Free 34 |
| 35 | Checkout leftovers | Register `ppcart_orderbumps`; look up `$wp_filter['ppcart_card_details_fields']`; post/read `ppcart_qty`; leftover POST `sc_qty` only via Free Compat | After Free 35 |
| 36 | Integration repeater ids | Match Free `ppcart_sub_prod_id` / `ppcart_sub_plan_id` / `ppcart_sub_cancel`; leftover inner keys only via Free Compat | After Free 36 (done) |
| 37 | Invoice format values | Match Free `ppcart_pns` / `ppcart_pn` / `ppcart_ns` / `ppcart_n`; leftover `sc_*` codes only via Free Compat; do not write leftover | After Free 37 (done) |
| 38 | Tax-rate inner keys | Match Free `_ppcart_tax_rate_title` / `_ppcart_tax_rate_slug` / `_ppcart_tax_rate`; leftover `_sc_*` copy only via Free Compat; do not write leftover | After Free 38 (done) |
| 39 | JS inner functions / jQuery events | Match Free `ppcart_*` functions and `ppcart/orderform/*` events; leftover `studiocart/orderform/*` no-ops | After Free 39 (done) |
| 40 | CSV export query var | Use `ppcart-csv-export`; call `ppcart_csv_escape_cell`; leftover `sc-csv-export` only via Free Compat | After Free 40 (done) |
| 41 | Cosmetic `$sc_*` locals | Match Free `$ppcart_*` / `$posted_ppcart_*` / `$request_ppcart_*` / `$query_ppcart_*`; no shim | After Free 41 (done) |
| 42 | Theme override directory | Match Free `yourtheme/publishpress-cart/`; leftover `yourtheme/studiocart/` only via Free Compat | After Free 42 (done) |
| 54 | Leftover shortcode detectors | Match Free canonical `has_shortcode` / email download tags; leftover tags only via Free Compat | After Free 54 (done) |
| 43 | Webhook / invoice query vars | Match Free `ppcart-webhook` / `ppcart-api` / `ppcart-invoice`; leftover `sc-*` only via Free Compat 302/rewrite/POST copy | After Free 43 (done) |
| 44 | Leftover hook fires / lookups | Listen/fire `ppcart_before_order_refund` and preload `ppcart_*`; leftover `before_sc_*` / `studiocart_*` only via Free Compat | After Free 44 (done) |
| 45 | JS leftovers | Match Free `is_ppcart_checkout` / `ppcart_oto_get` / `search_ppcart_user`; leftover `sc_payment_pay` / `is_studiocart_checkout` no-ops | After Free 45 (done) |
| 46 | Helper logger method | Match Free `ppcartLogger`; leftover `NCSLogger` no-ops | After Free 46 (done) |
| 47 | Admin leftover POST / notice keys | Match Free `ppcart_stripe_webhook_manual_setup` / GET `ppcart_extension_notice` / dismiss `ppcart_price_formatted`. Leftover GET `sc_extension_notice` only via Free Compat. Do not post leftover `admin_post_sc_*`. | After Free 47 (done) |
| 48 | Pro lock keys | Match Free `ppcart_pro_*` lock tab ids. Hard cutover; leftover `sc_pro_*` no-ops. Do not keep leftover tab ids in Pro first-party. | After Free 48 (done) |
| 49 | Integration service ids | Match Free `ppcart_subscription` / `ppcart_refund_order` / `ppcart_wpdomainchecker`; leftover `services` and leftover `ppcart_sc_*_integrations` only via Free Compat | After Free 49 (done) |
| 50 | Leftover option-name literals | Match Free `_ppcart_*` / `add_option__ppcart_*`; leftover `_sc_*` names only via Free Compat | After Free 50 (done) |
| 51 | Identity leftovers | Match Free `ppcart_is_checkout_context()` / `ppcart_exporter` / `{publishpress_cart}` / `ppcart_offer`; leftover `{studiocart}` only via Free Compat | After Free 51 (done) |
| 52 | VAT / Tax class names | Define `PPCart_VAT` / `PPCart_Tax`; Free callers switched in 52. Do not keep `NCS_Cart_VAT` / `NCS_Cart_Tax` in Pro first-party | With Free 52 (Free done) |
| 53 | Stripe portal cosmetic locals | Use `$is_ppcart_stripe_express_payment_enable` and `$is_ppcart_stripe_customer_portal_enable`; no leftover alias | After Free 53 (Free done) |
| 55 | Leftover meta names in comments | No runtime contract change; Pro comments should name `_ppcart_*` | After Free 55 (Free done) |
| 56 | Feature id / Compat option key | Match Free `ppcart-compatibility-mode` / `_ppcart_compatibility_mode`; leftover id/option Compat-only | After Free 56 (Free done) |
| 57 | Divi module identity / namespaces | Pro-owned: use `PPCart_Divi_Order_Form` (final name to match live source) and `namespace PPCart`; do not recreate removed sources in Free. Compat may add leftover aliases only after a canonical target exists. | Free audit closed — no live Free/Compat source |
| 58 | Storefront HTML/JS/CSS leftovers | Match Free `ppcart-*` storefront classes/ids; leftover `sc-current` / `scshortcode` / `#sc-preloader` no-ops after Free hard cutover | After Free 58 (Free done) |
| 59 | Admin HTML/JS leftovers | Match Free `ppcart-selectize` / `data-ppcart-editor-*` / `ppcart*Tab`; finish TinyMCE id prefix in **65** | After Free 59 / **65** |
| 60 | Gutenberg leftover block | Register only `publishpress-cart/checkout-form`. Do not dual-register leftover `sc-products-shortcode/product-shortcode` in Pro; leftover persisted blocks via Free Compat | After Free 60 (Free done) |
| 61 | Leftover admin AJAX + JS globals | Post/read `ppcart_ajax_nonce` / `ppcart_tax_settings`. Do not emit `ncs_ajax_*` / `var ncs` | After Free 61 (Free done) |
| 62 | Request `name=` / GET leftovers | Emit/read canonical `ppcart-*` / `ppcart-slm-order`; leftover GET only via Free Compat | After Free 62 (Free done) |
| 63 | Leftover recognition | No leftover recognition or silent migrate in Pro; leftover read-through is Free Compat | After Free 63 |
| 64 | Compat `ppcart_compat_*` | None (Compat-only `ppcartcomp_*`) | After Free 64 |
| 65 | Admin TinyMCE JS | Match `'ppcart-' + optionId` and `.data('ppcart-editor-*')`; leftover TinyMCE lookup no-ops | After Free **65** |
| 66 | CPT/taxonomy filters | Hook `ppcart_cpt_options` / `ppcart_taxonomy_options` only; leftover filter names via Free Compat | After Free 66 (Free done) |
| 67 | Tax CSV import slug | Use `ppcart_tax_rate_csv` importer slug; do not link/register `ncs-cart_tax_rate_csv` | After Free **67** |
| 68 | Cosmetic `$ncs_*` locals | Match Free `$ppcart_*` table/bootstrap locals | After Free **68** |
| 69 | Docblocks / comments | Mirror canonical `@package` and comment names when editing same files | After Free **69** (optional) |
| 70 | Mangled leftover hooks | Listen/fire `ppcart_activate` / `ppcart_upgrade` / `ppcart_product` / `ppcart_product_price`. Do not copy mangled `ppcart_ctivate`. Leftover `ncs_activate` / `sc_product` filter via Free Compat | After Free **70** (Free done) |
| 71 | Revoke GET keys | Emit/read `ppcart-revoke` / `ppcart-revoked`; leftover GET only via Free Compat | After Free **71** (Free done) |
| 72 | Escaped HTML/JS | Match `row-ppcart-` / `ppcart-nav-tabs`; rebuild leftover `ncs-nav-tabs` in editor JS | After Free **72** |
| 73 | Download upload directory | Write/allow `ppcart-uploads`; leftover `sc-uploads` only via Free Compat extra root; no silent file move | After Free **73** (Free done) |
| 74 | Debug-log `Scrt*` recognition | Match Free canonical-only `PPCart_Order` / `PPCart_Subscription` log-id regex. Do not recognize `ScrtOrder` / `ScrtSubscription` | After Free **74** (Free done) |
| 75 | Merchant leftover copy / URLs | Match Free `ppcart_field_id`, w.org `plugin/publishpress-cart`, PublishPress KB default. Do not ship `studiocart.co` / `plugin/studiocart` | After Free **75** (Free done) |
| 76 | Leftover names in comments | Mirror canonical comment/docblock names when editing same files | After Free **76** (optional) |
| 77 | Glued HTML/JS leftovers | Match Free `data-ppcart-qty-price` and `originalPpcartSettings`. Do not emit leftover `data-scq-price` / `originalNcs`. Hard cutover; no Compat. | After Free **77** (Free done) |
| 78 | Glued `$sc*` / `$scrt_*` locals | Match Free `$ppcart_order` / `$ppcart_subscription`. Do not copy `$scrt_order` / `$scorder` / `$scsub`. Hard cutover; no Compat. | After Free **78** (Free done) |
| 79 | jQuery `scPE` namespace | Match Free `elementor/popup/show.ppcart-pe-`. Do not copy `.scPE-`. Hard cutover; no Compat. | Free **79** done (`eaedf4d1`) |
| 80 | Sample tax CSV filename | Do not ship `sample_sc_tax_rates.csv`. Mirror phpmd canonical comments. Hard cutover; no Compat. | Free **80** done (`3368e2c5`) |
| 81 | Test seeder `_sc_*` keys | None (Free tests). Do not copy leftover `_sc_*` seeder keys. Never strip `_sc_` in Cart meta helpers. | Free **81** done (`dd08ca94`) |
| 82 | Doubled `ppcart_cart_` prefix | Match `ppcart_supports` / `ppcart_is_pro` / `ppcart_loaded` / `ppcart_pro_*` / `ppcart_manager`. Product object/meta suffixes are `checkout_starts` / `checkout_ends` / `checkout_ended_*` (not `cart_open` / `_ppcart_cart_*`). Leftover `_sc_cart_open` via Free Compat only. | Free **82** done (`186e0f69`) |

Skip Free-only work: Compatibility Mode class, integration template filenames
in Free, Free public query-var **registration**. Pro must **read** Free's
registered vars: `get_query_var('ppcart-coupon')` (not `coupon`) for URL
coupons; checkout pay-plan preselect is `ppcart-pay-plan`, distinct from
account `ppcart-plan`. Custom `_ppcart_coupon_url` GET still works. Merchant
URLs `?coupon=` / `?plan=` hard-cut over. Pro only **registers** extra query
vars if it adds public routes of its own.

## Frozen until a dedicated migration

Do not rename these in a prefix PR. They are stored or URL-visible in customer
sites. Free and Pro stay on the same strings until both migrate together.

- Pro-owned CPT **rows** stay leftover (`sc_collection`, `sc_us_path`, `sc_membership`, `sc_upgrade_path`) until a dedicated Pro migration. Free **source** is canonical `ppcart_*` (slice 28). Pro should dual-register leftover+canonical until Pro migrates.
- Free product/order/subscription CPT, taxonomy, role, and cap leftover strings migrate in Free via **Data migration**. Pro must call `ppcart_live_post_type()` / `ppcart_query_post_types()` / `ppcart_live_cap()` / `ppcart_user_can()` / `ppcart_live_role()` instead of hardcoding `sc_product` or `ppcart_product`.
- Post-meta **keys**: first-party helper call sites pass the suffix into
  `ppcart_meta_key()` / `ppcart_get_post_meta()` (slice 25a). Helpers store
  `_ppcart_*` rows. Leftover `_sc_*` names are Compatibility Mode; leftover
  rows move in Free Data migration. Option keys cut over in Free slice 13a — Pro must `get_option('_ppcart_*')` / `update_option('ppcart_*')`. Leftover option rows remain until explicit migration. Merchant Compatibility Mode toggle is canonical `_ppcart_compatibility_mode`; historical toggle rows are Compat-only read-through.
- Shortcode tags until Free 14a. Canonical tags are `ppcart_*` (see [Shortcodes (slice 14)](prefix-standardization.md#shortcodes-slice-14)). Compatibility Mode dual-registers leftover tags. Free first-party content until 14b. Leftover tags in stored posts and email HTML move via Data migration (14c).
- Roles / capabilities leftover strings migrate with Free CPT Data migration; Pro must use `ppcart_live_cap()` / `ppcart_live_role()`
- Custom tables: leftover `{prefix}ncs_*` until opt-in Data migration; Pro must call `ppcart_live_table()` / `ppcart_live_metadata_type()` (slice 16). No Compatibility Mode table-name shim.
- HTML `id` / `class` / `for` already follow Free slices 19 + 25b (`ppcart_` /
  `ppcart-` / `_ppcart_*` / `rid_ppcart_*` / `repeater_ppcart_*`). Form `name=` / request fields follow Free slice 26. Pro markup,
  JS, and PHP that copy Free templates must post and read canonical keys,
  including tax `wp.template('ppcart-tax-table-row')`
  (was `ncs-tax-table-row`). Leftover request names only via Free Compat.
- Stripe object metadata: write/read `metadata.ppcart_*` (Free slice 27). Leftover `sc_*` keys match only via Free Compat. Do not write leftover Stripe keys. Product save stamps canonical when missing.
- Text domain / public plugin slug: Free stays `publishpress-cart`; Pro keeps its own shipped text domain
- Pro `includes/compat/legacy.php` affiliate aliases
- On-disk debug logs: keep `{uploads}/publishpress-cart/logs` (slice 17; `wp_upload_dir()['basedir']`; override `PPCART_DEBUG_LOG_DIR`; not a rename; no filesystem shim). Pro must not invent `ncs-cart/logs`. UT-221.
- Download upload files: leftover merchant files under `uploads/sc-uploads` stay until an explicit Data migration card. Slice **73** changes first-party emit/allow-list only; do not silent-move files. Pro must not invent `ncs-cart/uploads`.

## Inventory greps (run in the Pro repo)

Start here. Ignore `vendor/`, `lib/vendor/`, `node_modules/`, and Pro’s
affiliate `includes/compat/legacy.php` when hunting first-party leftovers.

```bash
# Constants still on the old Pro contract
rg 'NCS_CART_LOADED_BY_PRO|NCS_CART_PRO_|NCS_CART_BASE_' --glob '*.php'

# Classes / files
rg 'NCS_Cart_|class-ncs-cart-' --glob '*.php'
rg 'ScrtOrder|ScrtSubscription|ScrtCollection|SC_Rest_|NCS_Helper|NCS_Stripe'

# Functions Pro should stop calling or defining
rg 'function sc_|function ncs_|sc_cart_is_pro|sc_cart_supports' --glob '*.php'

# Hooks Pro still listens to or fires
rg "add_action\(\s*'sc_|add_filter\(\s*'sc_|do_action\(\s*'sc_|apply_filters\(\s*'sc_" --glob '*.php'
rg "studiocart_|ncs-cart-" --glob '*.php'

# Admin slugs that will miss the Free hard cutover
rg "studiocart'|sc-admin|sc-white-label|ncs-cart-reports|sc_affiliate" --glob '*.php'

# AJAX Pro still registers or posts
rg "wp_ajax_sc_|wp_ajax_ncs_|'sc_check_username'|'sc_capture_lead'|'sc_validate_coupon'|'sc_process_upsell'" \
  --glob '*.{php,js}'

# HTML ids / classes / JS selectors
rg 'id="sc_|id="studiocart|for="sc_|class="[^"]*(sc-|sc_|studiocart|ncs-cart)' \
  --glob '!vendor/**'
rg "['\"]#(sc_|sc-|studiocart)|['\"]\\.(sc-|sc_|studiocart|ncs-cart)" \
  --glob '*.{js,css,php}' --glob '!vendor/**'

# Quoted 'sc_ / "sc_ and _sc_ (meta keys, mid-name leftovers)
rg "['\"_]sc_" \
  --glob '!vendor/**' --glob '!lib/vendor/**' --glob '!node_modules/**' \
  --glob '!includes/compat/legacy.php'

# Reverted rebrand — must stay absent
rg 'PP_Cart_|pp_cart_|pp-cart-|_pp_cart_|PP_CART_' --glob '!vendor/**'
```

Expect remaining hits in frozen CPT/post-meta/shortcode strings. REST is
`publishpress-cart/v1` (Free slice 29); leftover `sc/v1` is not a Free Compat
alias. Those leftover tracker mentions are not PHP-identifier failures. Unlisted PHP
runtime globals were renamed in Free slice 22 — Pro must use
`$GLOBALS['ppcart_checkout_*']`, not leftover `sc_checkout_*`. Admin and
public JS localize names were renamed in Free slice 21 — Pro must match those
canonical names. PHP method names containing `sc_` were renamed in Free slice
20 — Pro must match those canonical names. The dashboard widget id was renamed
in Free slice 23 — Pro must use `ppcart_dashboard_widget`, not leftover
`studiocart_dashboard_widget`. Transient prefixes were renamed in Free slice
24 — Pro must use `ppcart_*` transients, not leftover `sc_*` keys. Meta helper
call sites pass the suffix as of Free slice 25a — Pro must not pass leftover
`'_sc_*'` strings into `ppcart_*_meta()`.

## How to run a Pro slice

1. Confirm PHP (shimable) vs persisted/URL-visible. Ask before persisted.
2. Rename Pro first-party code to `PPCart_` / `ppcart_` / `PPCART_` /
   `ppcart-`. For Free APIs, use the canonical name from this doc — do not
   add a Pro-side `class_alias` or function wrapper for StudioCart names.
   If the slice touches a Pro template or stylesheet, rename leftover HTML
   `id`, `class`, `for`, and matching JS/CSS selectors in the same PR.
3. If a shipped Pro PHP name is part of a **Pro** public API that third
   parties call, decide explicitly: either treat it as Pro-local BC (rare;
   document it) or require Compatibility Mode on Free for the StudioCart
   name. Default: Pro-local first-party only, no new shims.
4. Grep the old name is gone outside affiliate `legacy.php` and frozen
   strings.
5. Smoke Free + Pro with Compatibility Mode **off**, then once with it **on**.
6. Check off the slice in the Pro copy of this checklist.
7. Update [pro-plugin-extraction.md](pro-plugin-extraction.md) when Pro-side
   identifiers move, matching the Free leftover-slice contract.

Do not reopen a completed family. Commit messages match Free:
`refactor(prefix): rename <family> to ppcart (#629)`.

Examples from Free:

- `refactor(prefix): rename NCS_Cart_* classes to PPCart_*`
- `refactor(prefix): rename constants to PPCART_* with compat bridges`
- `refactor(prefix): rename ajax actions to ppcart (#629)`
- `refactor(prefix): rename nonce actions to ppcart (#629)`

## Pro acceptance checks

### Compatibility Mode off (required)

- [ ] `ppcart_is_pro()` is true; vendor assets load from Pro URL
- [ ] Pro modules load via `ppcart_load_pro_modules`
- [ ] Product tabs Pro adds still appear (`ppcart_product_setting_tabs`)
- [ ] White label and affiliates screens open under `ppcart-white-label` /
      `ppcart-affiliates`
- [ ] Coupons, bumps, upsells, 2-step / split-in checkout
- [ ] Pro AJAX: username check / lead capture on canonical `ppcart_*` only;
      coupon validate / upsell may still dual-register leftover until those slices land
- [ ] REST routes Pro owns still respond on `publishpress-cart/v1` (not leftover `sc/v1`)
- [ ] No fatals on `PPCart_Order` / `PPCart_Subscription` (not `Scrt*`)

### Compatibility Mode on (third-party, not Pro’s contract)

- [ ] A dummy `add_action( 'sc_order', … )` still runs
- [ ] Legacy `NCS_CART_*` names remain defined via Free outbound aliases
- [ ] Pro itself still uses only canonical names in its source

## Coordinated Free work

Keep these in lockstep; do not finish them only on one side.

| Item | Free | Pro |
|------|------|-----|
| Pro-contract constants | Reads `PPCART_*`; inbound `NCS_*` Compatibility Mode on only (UT-194). Do **not** ungate. Free commit `docs(prefix): record Pro-contract constants dual-define (#629)` records the contract — **not done**. | **Blocked:** dual-define `PPCART_*` before requiring Free, then alias `NCS_CART_*`. After that, detection works with Compatibility Mode off. Modules need the later Pro hook slice. |
| Admin slugs | Hard cutover done | Slice 6 must match or Pro screens vanish |
| `sc_check_username` / `sc_capture_lead` | Free JS posts `ppcart_*` (slice 30); Compat maps leftover | Register canonical only; leftover via Free Compat |
| Hook names Free never mapped | Add to `maps/hooks.php` if a real Pro/third-party name is missing | Do not invent a second bridge in Pro |
| Option keys (slice 13a) | Canonical `wp_options` rows; leftover copied then deleted | Yes — `get_option('_ppcart_*')`; do not write leftover option names |
| Post-meta keys (slice 13b) | First-party save/read `_ppcart_*` only; leftover names in Compatibility Mode; leftover rows via Data migration | Yes — call `ppcart_meta_key()` / `ppcart_get_post_meta()`; do not hardcode `_sc_*` or `_ppcart_*`; do not dual-read leftover rows |
| Shortcode tags (slice 14a) | First-party `add_shortcode` `ppcart_*` only; Compatibility Mode dual-registers leftover | Leftover tags only via Free Compatibility Mode |
| First-party shortcode content (slice 14b) | Emitters, detectors, fixtures, docs use canonical | Emit/detect canonical |
| Leftover shortcode detectors (slice 54) | Canonical `has_shortcode` only; leftover tags via Free Compat when on | Do not `has_shortcode()` leftover tags; leftover enqueue / account body class / email replace only via Free Compat |
| Divi module identity / namespaces (slice 57) | No live Free or Compat source — historical Divi code was removed as Pro ghost files | Pro-owned follow-up must use `PPCart_Divi_Order_Form` (final name to match live source) and `namespace PPCart`; leftover aliases only through Free Compat after a canonical target exists |
| Shortcode content Data migration (slice 14c) | Leftover tags in posts and email HTML | Do not invent a Pro-side rewrite; run/rely on Free Data migration |
| Persisted data (slice 16) | Live helpers + opt-in Data migration `RENAME`; no Compat table alias | Yes — `ppcart_live_table( 'tax_rate' )`; do not hardcode leftover or canonical table names |
| Pro VAT / Tax classes (slice 52) | Calls `PPCart_VAT` / `PPCart_Tax` only; no fallback or Compat alias | Define the canonical classes in Pro’s classes slice; do not retain `NCS_Cart_VAT` / `NCS_Cart_Tax` in Pro first-party |
| On-disk debug log directory (slice 17) | Keep `{uploads}/publishpress-cart/logs` (`wp_upload_dir()['basedir']`; override `PPCART_DEBUG_LOG_DIR`); no filesystem shim; UT-221 | Do not invent `ncs-cart/logs` |
| Mangled leftover hooks (slice 70) | **Done** — fire/listen `ppcart_activate` / `ppcart_upgrade` / `ppcart_product` / `ppcart_product_price`; do not alias mangled names | Listen/fire canonical only; leftover `ncs_activate` / `sc_product` filter via Free Compat if shipped |
| Revoke GET keys (slice 71) | **Done** — emit/read `ppcart-revoke` / `ppcart-revoked`; leftover GET via Free Compat | Emit/read canonical; leftover GET only via Free Compat |
| Escaped HTML/JS (slice 72) | **Done** — `row-ppcart-`; Gutenberg build `ppcart-nav-tabs` | Match Free markup/JS; no leftover `ncs-nav-tabs` in Pro builds |
| Download upload directory (slice 73) | **Done** — Cart `ppcart-uploads` only; leftover `sc-uploads` Compat extra root; no file move | Write/allow `ppcart-uploads`; leftover dir only via Free Compat |
| HTML ids / classes (slice 19) | Hard cutover to `ppcart_` / `ppcart-`; leftover `_sc_*` field ids done in 25b | Yes — match Free markup/JS/CSS; tax `wp.template('ppcart-tax-table-row')` |
| Settings CSS custom properties | `--ppcart-*` on `.ppcart-settings-page`; `--pp-cs-*` retired | Yes — match Free tokens; no `--pp-cs-*`. Register: [Settings CSS custom properties](#settings-css-custom-properties) |
| Request field names (slice 26) | Canonical emit/read; leftover POST/GET via Free Compat; leftover `admin_action_sc_duplicate_product` when Compat on | Yes — post/read Free canonical keys; leftover names only via Free Compat |
| PHP method names (slice 20) | Canonical methods; no Compat method aliases | Yes — call `save_post_order`, `update_ppcart_*_amount`, `set_custom_edit_*_columns`, `ppcartLogger`; do not call leftover `*_sc_*` methods or `NCSLogger` |
| Admin JS localize (slice 21a) | Canonical `ppcart_reg_vars` / `ppcartSettings*` / `ppcartReports`; no Compat JS aliases | Yes — Pro admin JS must read the new names; leftover `sc_reg_vars` / `ncsCart*` will miss data |
| Public JS localize (slice 21b) | Canonical `ppcart` / `ppcart_translate_frontend` / `ppcart_popup` / `window.ppcart_coupon`; no Compat JS aliases | Yes — Pro public JS must read the new names; leftover `studiocart.ajax` will miss data |
| Checkout JS functions / orderform events (slice 39) | Canonical `ppcart_parse_json_response` / `ppcart_validate` / `ppcart_do_lead_capture` and `ppcart/orderform/*`; no Compat JS aliases | Yes — Pro checkout JS must use the new names; leftover `studiocart/orderform/*` listeners no-op |
| Unlisted PHP runtime globals (slice 22) | Canonical `$ppcart_checkout_request_rendered` / `$ppcart_checkout_block_arrangement`; leftover `sc_checkout_*` aliases when Compat on | Yes — do not write leftover `$GLOBALS['sc_checkout_*']`; leftover keys exist only while Compatibility Mode is on |
| Dashboard widget id (slice 23) | Canonical `ppcart_dashboard_widget`; no Compat leftover id | Yes — do not register or target leftover `studiocart_dashboard_widget` |
| Transient prefixes (slice 24) | Canonical `ppcart_*`; leftover rows copy at boot; leftover names when Compat on | Yes — do not write leftover `sc_*` transients |
| Meta helper leftover literals (slice 25a) | Helper call sites pass suffix, not leftover `'_sc_*'` | Yes — pass `'amount'` into `ppcart_*_meta()` |
| Leftover HTML ids (slice 25b) | Canonical `_ppcart_*` / `rid_ppcart_*` / `repeater_ppcart_*`; metabox `ppcart-*` | Yes — match Free markup/JS/CSS; no leftover HTML id aliases |
| Request field names (slice 26) | Canonical `ppcart_*` / `ppcart-*` emit/read; leftover POST/GET when Compat on | Yes — do not post leftover `sc-nonce` / `sc_product_id`; leftover names only via Free Compat |
| Request `name=` / GET leftovers (slice 62) | Canonical hidden `$atts['name']` / GET `ppcart-slm-order`; leftover GET `sc-slm-order` when Compat on | Yes — emit/read canonical; leftover GET only via Free Compat |
| Stripe object metadata (slice 27) | Canonical `metadata.ppcart_*` write/read; leftover `sc_*` when Compat on | Yes — write `ppcart_product_id` / `ppcart_order_id`; leftover names only via Free Compat |
| Leftover CPT / taxonomy strings (slice 28) | Canonical source strings; live helpers row-state; leftover FSE/rewrite when Compat on; Pro queries include leftover from maps | Yes — register `ppcart_collection` / `ppcart_us_path` / `ppcart_membership` / `ppcart_upgrade_path`; dual-register leftover until Pro migrates; call `ppcart_query_pro_post_types()` |
| REST namespace (slice 29) | Gutenberg `publishpress-cart/v1`; leftover `sc/v1` unregistered in Free (hard cutover) | Yes — register developer REST on `publishpress-cart/v1` only; no leftover dual-register; `SC_Rest_*` on Pro’s classes slice |
| Free CPT / cap Data migration | Live helpers + opt-in rename of `sc_product`/`sc_order`/`sc_subscription` | Call live helpers; Pro-owned CPT **source** is now `ppcart_*`; leftover rows until Pro migrates |
| Post-30 leftovers (slices 31–57) | See [Post-30 leftovers](prefix-standardization.md#post-30-leftovers-31-42) and [Post-42 leftovers](prefix-standardization.md#post-42-leftovers-43). | Do not copy leftover names. After each Free slice, match canonical. |
| Post-57 leftovers (slices 58–69) | **Complete** — see [Remaining leftovers (58–69)](prefix-standardization.md#remaining-leftovers-58-69). | Do not copy leftover names. Match new Free emit/read after each slice. Gutenberg leftover block dual-registers only in Free Compat. No leftover recognition in Pro. Compat metadata bridges use `ppcartcomp_*`. CPT/taxonomy option filters are `ppcart_cpt_options` / `ppcart_taxonomy_options`; leftover `sc-cart-*` via Free Compat. Tax CSV import slug is `ppcart_tax_rate_csv`; Pro registers importer on `ppcart_register_importers`. Cosmetic table/bootstrap locals use `$ppcart_*` names only. First-party docblocks/comments use canonical names only. |
| Post-69 leftovers (slices 70–73) | **Complete** — see [Remaining leftovers (70–73)](prefix-standardization.md#remaining-leftovers-70-73). | Match new Free emit/read. Listen/fire `ppcart_activate` / `ppcart_upgrade` / `ppcart_product` / `ppcart_product_price`. Do not copy mangled `ppcart_ctivate` / `ppcart_pgrade` / `ppcart_roduct`. Leftover `ncs_activate` / `sc_product` filter via Free Compat. Emit/read `ppcart-revoke` / `ppcart-revoked`; leftover GET via Free Compat. Match `row-ppcart-` / `ppcart-nav-tabs`. Write/allow `ppcart-uploads`; leftover `sc-uploads` via Free Compat extra root only. No silent file move. |
| Post-73 leftovers (slices 74–76) | **Complete** — see [Remaining leftovers (74–76)](prefix-standardization.md#remaining-leftovers-74-76). | Match canonical. Log-id regex `PPCart_Order` / `PPCart_Subscription` only (no `Scrt*`). Merchant copy `ppcart_field_id` / `plugin/publishpress-cart` / PublishPress KB default. Comments use canonical names. Hard cutover; no Compat. |
| Post-76 leftovers (slice 77) | **Complete** — see [Remaining leftovers (77)](prefix-standardization.md#remaining-leftovers-77). | Match `data-ppcart-qty-price` and `originalPpcartSettings`. Do not emit leftover `data-scq-price` / `originalNcs`. Hard cutover; no Compat. |
| Post-77 leftovers (slice 78) | **Complete** — see [Remaining leftovers (78)](prefix-standardization.md#remaining-leftovers-78). | Match `$ppcart_order` / `$ppcart_subscription`. Do not copy `$scrt_order` / `$scorder` / `$scsub`. Hard cutover; no Compat. |
| Post-78 leftovers (slice 79) | **Complete** — see [Remaining leftovers (79–81)](prefix-standardization.md#remaining-leftovers-79-81). | Match `elementor/popup/show.ppcart-pe-`. Do not copy `.scPE-`. Hard cutover; no Compat. |
| Post-79 leftovers (slice 80) | **Complete** — see [Remaining leftovers (79–81)](prefix-standardization.md#remaining-leftovers-79-81). | Ship `sample_ppcart_tax_rates.csv` only. Mirror phpmd `PPCart_Public` / `PPCart_` comments. Hard cutover; no Compat. |
| Post-80 leftovers (slice 81) | **Complete** — see [Remaining leftovers (79–81)](prefix-standardization.md#remaining-leftovers-79-81). | Do not copy `_sc_*` seeder keys. Never strip `_sc_` in Cart meta helpers. Hard cutover; no Compat. |
| Post-81 leftovers (slice 82) | **Complete** — see [Remaining leftovers (82)](prefix-standardization.md#remaining-leftovers-82). | Match `ppcart_supports` / `ppcart_is_pro` / `ppcart_loaded` / `ppcart_pro_*` / `ppcart_manager`. Product checkout-window meta/object properties are `checkout_starts` / `checkout_ends` / `checkout_ended_*`. Do not read `cart_open` / `_ppcart_cart_*`. Leftover `_sc_cart_open` only via Free Compat. |

## Free branch log (what already shipped)

Source: `wporg-review/studiocart-compat-mode`, chronological. Append each new
Free prefix commit here in the same session as the Free slice. Pro does not
repeat Compatibility Mode itself. Use this as the family list and the commit
style, not as a copy-paste of Free diffs.

| Commit subject | Pro? |
|----------------|------|
| `feat(compat): add toggleable StudioCart Compatibility Mode` | No — Free-only package |
| `fix(compat): align Compatibility card intro with field padding` | No |
| `docs(compat): add Compatibility Mode skill for issue 629` | No |
| `refactor(prefix): rename NCS_Cart_* classes to PPCart_*` | Yes — Pro classes + Free class callers |
| `refactor: drop class- prefix from procedural schedule-event file` | Only if Pro has the same procedural file |
| `refactor(prefix): rename ncs- and sc- filenames to ppcart-` | Yes |
| `refactor(prefix): rename globals and methods to ppcart_* (#629)` | Yes — methods Pro defines; `$ppcart_*` if Pro sets them |
| `refactor(prefix): rename option keys to ppcart_* with DB bridges (#629)` | Yes — PHP names already canonical; storage cut over in the later commit below |
| `refactor(prefix): rename hooks to ppcart_* with compat bridges (#629)` | Yes — listeners/fires only; no Pro-side bridges |
| `refactor(prefix): rename CSS classes and HTML IDs to ppcart-* (#629)` | Yes |
| `refactor(prefix): rename constants to PPCART_* with compat bridges` | Yes — **do this first** on Pro (contract constants) |
| `docs(prefix): add remaining #629 identifier leak tracker` | This playbook |
| `refactor(prefix): rename bootstrap functions to ppcart_* (#629)` | Yes if Pro prefixes activate/deactivate |
| `refactor(prefix): rename Compatibility Mode class to PPCart_* (#629)` | No |
| `fix(prefix): create missing legacy option rows` | Superseded by the option storage cutover |
| `refactor(prefix): rename runtime globals to $ppcart_* (#629)` | Yes if Pro reads/writes them |
| `refactor(prefix): rename plugin identity to ppcart (#629)` | Yes for admin/script identity; text domain stays |
| `refactor(prefix): rename local ncs_cart vars to $ppcart_* (#629)` | Yes — mechanical, no shim |
| `refactor(prefix): rename integration templates to ppcart-* (#629)` | Only Pro-owned templates |
| `refactor(prefix): rename test helpers to ppcart (#629)` | Yes |
| `refactor(prefix): rename admin icon fonts to ppcart (#629)` | Only if Pro bundles the same font |
| `refactor(prefix): finish admin icon font rename (#629)` | Same — Free needed a second commit |
| `refactor(prefix): rename admin screen slugs to ppcart (#629)` | Yes — hard cutover, no redirects |
| `refactor(prefix): rename public query vars to ppcart (#629)` | Only extra Pro public routes |
| `refactor(prefix): rename ajax actions to ppcart (#629)` | Yes — Pro-owned actions; username/lead leftovers via Free Compat after slice 30; coupon/upsell dual-register until later |
| `refactor(prefix): rename nonce actions to ppcart (#629)` | Yes — separate commit from AJAX on Free |
| `refactor(prefix): add opt-in CPT and capability migration (#629)` | Yes — call live helpers; do not hardcode `ppcart_product` |
| `fix(prefix): move leftover CSS selectors into Compatibility Mode` | Yes if Pro CSS still targets `post-type-sc_*` / leftover HTML ids |
| `refactor(prefix): rename leftover pp-cart- testids to ppcart-` | Yes if Pro Playwright/templates still use `pp-cart-` testids |
| `refactor(prefix): store options under canonical keys (#629)` | Yes — Pro must `get_option('_ppcart_*')`; leftover option rows go away |
| `refactor(prefix): write post meta under _ppcart_ keys (#629)` | Yes — call `ppcart_meta_key()` / `ppcart_get_post_meta()`; first-party save/read `_ppcart_*` only; leftover names and leftover query keys only in Compatibility Mode |
| `refactor(prefix): rename shortcode tags to ppcart_* (#629)` | Yes — emit/detect canonical tags; leftover tags dual-register only in Free Compatibility Mode; leftover stored content/emails via Free Data migration |
| `refactor(prefix): keep on-disk log dir publishpress-cart/logs (#629)` | Yes — keep `{uploads}/publishpress-cart/logs` (override `PPCART_DEBUG_LOG_DIR`); do not invent `ncs-cart/logs` |
| `test(prefix): lock default log dir without process isolation` | No — Free unit lock (UT-221) |
| `docs(prefix): record Pro-contract constants dual-define (#629)` | Yes — **intended Pro follow-up**, not done. Dual-define `PPCART_*` before requiring Free; alias `NCS_CART_*`. Free inbound copy stays gated (UT-194). Do not treat this row as slice 18 `[x]`. |
| `refactor(prefix): rename leftover checkout HTML ids (#629)` | Yes — `#ppcart_card_button`, terms ids, `$ppcart_uid`; frozen `name=` |
| `refactor(prefix): rename leftover admin HTML ids (#629)` | Yes — refund/subscription actions, tax `tmpl-ppcart-tax-table-row`, repeater `ppcart-unique` |
| `docs(prefix): check off HTML id slice 19 (#629)` | Yes — Pro tax `wp.template('ncs-tax-table-row')` must follow |
| `refactor(prefix): rename custom tables to ppcart (#629)` | Yes — call `ppcart_live_table()`; do not hardcode `{prefix}ncs_*` or `{prefix}ppcart_*` table names. No Compatibility Mode table-name shim. |
| `refactor(prefix): rename leftover sc_ methods to ppcart (#629)` | Yes — Pro subclasses/callers of list columns, price-format, order-admin save, and Kit wrappers must use the new method names. No Free method aliases. |
| `refactor(prefix): rename admin JS globals to ppcart (#629)` | Yes — Pro admin JS must read `ppcart_reg_vars` / `ppcartSettings*` / `ppcartReports` / `ppcartNotificationI18n`. No Compat JS aliases. |
| `refactor(prefix): rename public JS globals to ppcart (#629)` | Yes — Pro public JS must read `ppcart.ajax` / `ppcart_translate_frontend` / `ppcart_popup` / `window.ppcart_coupon`. No Compat JS aliases. |
| `refactor(prefix): rename checkout runtime globals to ppcart (#629)` | Yes — Pro must use `$ppcart_checkout_request_rendered` / `$ppcart_checkout_block_arrangement`. Leftover `sc_checkout_*` keys exist only while Compatibility Mode is on. |
| `refactor(prefix): rename dashboard widget id to ppcart (#629)` | Yes — Pro must use `ppcart_dashboard_widget`. No Compatibility Mode leftover widget id. |
| `refactor(prefix): rename transient prefixes to ppcart (#629)` | Yes — Pro must use `ppcart_*` transients. Leftover rows copy at Free boot; leftover names only via Free Compat. |
| `refactor(prefix): pass meta helper suffixes not leftover keys (#629)` | Yes — Pro must pass suffixes into `ppcart_*_meta()`, not leftover `'_sc_*'` literals. |
| `refactor(prefix): rename leftover HTML ids to ppcart (#629)` | Yes — Pro markup/JS/CSS must use `#_ppcart_*` / `rid_ppcart_*` / `repeater_ppcart_*` and metabox `ppcart-*`. No leftover HTML id aliases. |
| `refactor(prefix): rename request field names to ppcart (#629)` | Yes — Pro markup/JS/PHP must post and read Free canonical keys; leftover names only via Free Compat |
| `refactor(prefix): rename stripe metadata keys to ppcart (#629)` | Yes — Pro must write/read `metadata.ppcart_*`. Leftover Stripe keys match only via Free Compat. Product save stamps canonical when missing. |
| `refactor(prefix): rename leftover cpt taxonomy strings to ppcart (#629)` | Yes — register Pro-owned CPTs as `ppcart_*`; dual-register leftover until Pro migrates; FSE `single-ppcart_product`; rewrite source `ppcart_product_*`; call `ppcart_query_pro_post_types()`. |
| `refactor(prefix): lock rest namespace publishpress-cart/v1 (#629)` | Yes — hard cutover; Pro later. Register developer REST on `publishpress-cart/v1` only; do not dual-register leftover `sc/v1`. `SC_Rest_*` stays on Pro’s classes slice. |
| `refactor(prefix): post canonical Pro AJAX actions from Free JS (#629)` | Yes — register `wp_ajax(_nopriv)_ppcart_check_username` / `ppcart_capture_lead` only; leftover `sc_*` via Free Compat. Coupon/upsell leftovers stay Pro until later. |
| `docs(prefix): inventory remaining first-party leftovers (#629)` | Yes — do not copy `$studiocart` / `$scFiles` / `sc_make_*` / `sc_view_log` / `sc_orderbumps` / `sc-csv-export`. Slices 31–42. |
| `refactor(prefix): rename log request fields to ppcart (#629)` | Yes — Pro must emit/read `ppcart_view_log` / `ppcart_*_nonce`; leftover names only via Free Compat |
| `refactor(prefix): rename integration trigger tags to ppcart (#629)` | Yes — Pro must listen/fire `ppcart_order_*` / `ppcart_subscription_*` / `ppcart_renewal_*`; leftover `sc_order_complete` only via Free Compat |
| `refactor(prefix): rename checkout leftovers to ppcart (#629)` | Yes — Pro must register `ppcart_orderbumps` on `ppcart_card_details_fields`; post/read `ppcart_qty`; leftover `sc_qty` only via Free Compat |
| `refactor(prefix): rename csv export query var to ppcart (#629)` | Yes — Pro must emit/read `ppcart-csv-export` and call `ppcart_csv_escape_cell`; leftover `sc-csv-export` only via Free Compat |
| `refactor(prefix): rename checkout JS events to ppcart (#629)` | Yes — Pro checkout JS must use `ppcart_parse_json_response` / `ppcart_validate` / `ppcart_do_lead_capture` and `ppcart/orderform/*`. No Compat JS aliases. Leftover `studiocart/orderform/*` no-ops. |
| `refactor(prefix): rename integration repeater ids to ppcart (#629)` | Yes — Pro must emit/read `ppcart_sub_prod_id` / `ppcart_sub_plan_id` / `ppcart_sub_cancel`; leftover inner keys only via Free Compat |
| `refactor(prefix): rename invoice format values to ppcart (#629)` | Yes — Pro must emit/read `ppcart_pns` / `ppcart_pn` / `ppcart_ns` / `ppcart_n`; leftover `sc_*` codes only via Free Compat |
| `refactor(prefix): rename tax-rate inner keys to ppcart (#629)` | Yes — Pro must emit/read `_ppcart_tax_rate_title` / `_ppcart_tax_rate_slug` / `_ppcart_tax_rate`; leftover `_sc_*` inner keys only via Free Compat |
| `refactor(prefix): rename cosmetic locals to ppcart (#629)` | Yes — mechanical `$ppcart_*` locals; no shim |
| `refactor(prefix): rename theme override dir to publishpress-cart (#629)` | Yes — Pro theme copies and docs must use `yourtheme/publishpress-cart/`. Leftover `yourtheme/studiocart/` only via Free Compat. |
| `refactor(prefix): rename leftover shortcode detectors to ppcart (#629)` | No — leftover detectors are Free Compat only. Pro must not `has_shortcode()` leftover tags. |
| `refactor(prefix): rename webhook query vars to ppcart (#629)` | Yes — Pro must emit/read `ppcart-webhook` / `ppcart-api` / `ppcart-invoice`; leftover `sc-*` only via Free Compat |
| `refactor(prefix): rename leftover hook fires to ppcart (#629)` | Yes — listen/fire `ppcart_before_order_refund` and preload `ppcart_*`; leftover `before_sc_order_refund` / `sc_*` / `studiocart_*` only via Free Compat |
| `refactor(prefix): rename leftover JS identifiers to ppcart (#629)` | Yes — Pro JS must read `is_ppcart_checkout` / `ppcart_oto_get` / `search_ppcart_user`. No Compat JS aliases. Dead `sc_payment_pay` dropped. |
| `refactor(prefix): rename NCSLogger to ppcartLogger (#629)` | Yes — Pro callers must use `ppcartLogger`. No leftover method alias. |
| `refactor(prefix): rename admin notice keys to ppcart (#629)` | Yes — Pro/addons must redirect with GET `ppcart_extension_notice`. Leftover `sc_extension_notice` only via Free Compat. Webhook `admin_post` and price-format dismiss are hard cutover. |
| `refactor(prefix): rename Pro lock keys to ppcart (#629)` | Yes — Pro must register product lock tabs as `ppcart_pro_*`. Leftover `sc_pro_*` no-ops. Hard cutover; no Compat tab aliases. |
| `refactor(prefix): rename integration service ids to ppcart (#629)` | Yes — Pro must emit/read `ppcart_subscription` / `ppcart_refund_order` / `ppcart_wpdomainchecker` service ids; leftover `services` only via Free Compat |
| `refactor(prefix): rename leftover option-name literals to ppcart (#629)` | Yes — Pro must emit/read `_ppcart_*` / `add_option__ppcart_*` only. Leftover `_sc_*` option names and secret sensitivity only via Free Compat. |
| `refactor(prefix): rename leftover meta comments to ppcart (#629)` | No — Free-only comment cleanup; no runtime Free/Pro contract change. |

| `refactor(prefix): rename VAT and Tax class callers to ppcart (#629)` | Yes — Pro defines `PPCart_VAT` / `PPCart_Tax`; Free has no leftover fallback or Compat alias. |
| `refactor(prefix): rename cosmetic locals to ppcart (#629)` | Yes — Stripe portal locals use `$is_ppcart_stripe_*`; no shim. |
| `refactor(prefix): rename leftover meta comments to ppcart (#629)` | No — Free-only comment cleanup; no runtime Free/Pro contract change. |
| `refactor(prefix): rename Compatibility Mode feature id to ppcart (#629)` | No — Free/Compat-only canonical gate is `ppcart-compatibility-mode`; historical toggle rows stay as read-only Compat inputs. |
| `docs(prefix): close removed Divi ghost-file tracker (#629)` | Yes — Free/Compat audit confirmed the Divi sources were Pro ghost files; Pro owns any future canonical class/namespace migration, with aliases only through Compat after a canonical target exists. |

Related Free follow-ups that were not rename slices (expect the same class of
breakage on Pro):

- `fix(checkout): sync data-form-wrapper with ppcart form wrapper id`
- `fix(admin): restore settings helpers and fix Playwright selectors`
- `fix(options): align email and debug fixtures with ppcart keys`
- `fix: stub get_option before Compatibility Mode init`

| `refactor(prefix): rename storefront HTML classes to ppcart (#629)` | Yes — Pro templates/JS/CSS must emit/select `ppcart-current` / `ppcart-checkout-1` / `ppcart-shortcode` / `#ppcart-preloader` and related storefront classes. No leftover `sc-current` / `scshortcode` / `#sc-preloader`. Hard cutover; no Compat class aliases. |
| `refactor(prefix): rename admin HTML classes to ppcart (#629)` | Yes — Pro admin templates/JS must emit/select `ppcart-selectize` / `data-ppcart-editor-*` / `ppcart*Tab`. Hard cutover; no Compat class aliases. |
| `refactor(prefix): rename Gutenberg leftover block to ppcart (#629)` | Yes — Pro must register/emit only `publishpress-cart/checkout-form`. Do not dual-register leftover `sc-products-shortcode/product-shortcode` in Pro; leftover persisted blocks via Free Compat. |
| `refactor(prefix): rename admin AJAX JS globals to ppcart (#629)` | Yes — Pro must post/read `ppcart_ajax_nonce` / `ppcart_action`, use `ppcartStripe`, localize `ppcart_tax_settings`. Leftover POST/globals via Free Compat while on. |
| `refactor(prefix): rename request name GET leftovers to ppcart (#629)` | Yes — Pro must emit/read `$atts['name']` / `_ppcart_*` hidden fields and GET `ppcart-slm-order`. Leftover GET `sc-slm-order` only via Free Compat. |
| `refactor(prefix): rename leftover recognition to ppcart (#629)` | Yes — Pro must read `_ppcart_myaccount_page_id` only (no `_my_account` silent migrate), use canonical Stripe owned-field suffixes only, and must not register leftover FSE templates. Leftover read-through via Free Compat. |
| `refactor(prefix): rename Compat prefix to ppcartcomp (#629)` | No — Compat-only metadata bridge callback rename (`ppcartcomp_*`). Cart live helpers stay `ppcart_*`. |
| `refactor(prefix): rename admin TinyMCE JS to ppcart (#629)` | Yes — Pro admin JS must use `.data('ppcart-editor-*')` and `'ppcart-' + optionId` TinyMCE lookups. Match `ppcart-admin-field-editor.php` ids. Hard cutover; no Compat JS aliases. |
| `refactor(prefix): rename CPT taxonomy filters to ppcart (#629)` | Yes — Pro must listen on `ppcart_cpt_options` / `ppcart_taxonomy_options` only. Leftover `sc-cart-cpt-options` / `sc-cart-taxonomy-options` via Free Compat. |
| `refactor(prefix): rename tax CSV import slug to ppcart (#629)` | Yes — Pro must listen on `ppcart_register_importers`, register slug `ppcart_tax_rate_csv`, and drop `sc_register_importers` / `ncs-cart_tax_rate_csv`. |
| `refactor(prefix): rename cosmetic ncs locals to ppcart (#629)` | Yes — match Free `$ppcart_tax_table` / `$ppcart_order_items_table` / `$ppcart_order_itemmeta_table` / `$ppcart_downloads_table` / `$ppcart_stripe` in vendored copies of activator, upgrade, order items, files storage, and Stripe card update template. Hard cutover; no Compat shim. |
| `refactor(prefix): rename docblocks to ppcart (#629)` | No — Free-only comment/docblock cleanup; mirror when touching the same files in Pro. |
| `refactor(prefix): rename mangled leftover hooks to ppcart (#629)` | Yes — Pro must listen/fire `ppcart_activate` / `ppcart_upgrade` / `ppcart_product` / `ppcart_product_price`. Do not copy mangled `ppcart_ctivate` / `ppcart_pgrade` / `ppcart_roduct`. Leftover `ncs_activate` / `sc_product` filter via Free Compat. |
| `refactor(prefix): rename revoke GET keys to ppcart (#629)` | Yes — Pro must emit/read `ppcart-revoke` / `ppcart-revoked`. Leftover GET `sc-revoke` / `sc-revoked` only via Free Compat. |
| `refactor(prefix): rename escaped HTML leftovers to ppcart (#629)` | Yes — Pro must emit `row-ppcart-` ids and rebuild editor bundles to ship `ppcart-nav-tabs`. No leftover `ncs-nav-tabs`. Hard cutover; no dual-class. |
| `refactor(prefix): rename download upload directory to ppcart (#629)` | Yes — Pro must write/allow `ppcart-uploads`. Leftover `sc-uploads` only via Free Compat extra root. No silent file move. |
| `refactor(prefix): rename debug-log Scrt recognition to ppcart (#629)` | Yes — Pro must match Free canonical-only `PPCart_Order` / `PPCart_Subscription` log-id regex. Do not recognize leftover `ScrtOrder` / `ScrtSubscription`. Hard cutover; no Compat shim. |
| `refactor(prefix): rename merchant copy URLs to ppcart (#629)` | Yes — Pro must match Free `ppcart_field_id` help text, w.org `plugin/publishpress-cart` reviews URL, and PublishPress KB default for `ppcart_stripe_subscriptions_documentation_url`. Hard cutover; no Compat shim. |
| `refactor(prefix): rename leftover comment names to ppcart (#629)` | No — Free-only comment/docblock cleanup; mirror when touching the same files in Pro. |
| `refactor(prefix): rename glued HTML/JS leftovers to ppcart (#629)` | Yes — Pro must emit `data-ppcart-qty-price` on quantity custom fields and use `originalPpcartSettings` in settings JS. Do not ship `data-scq-price` / `originalNcs`. Hard cutover; no Compat shim. |
| `refactor(prefix): rename glued sc locals to ppcart (#629)` | Yes — Pro must match Free `$ppcart_order` / `$ppcart_subscription` in vendored CSV export, my-account order detail, order product-form, and subscription column templates. Do not copy `$scrt_order` / `$scorder` / `$scsub`. Hard cutover; no Compat shim. |
| `refactor(prefix): rename jQuery scPE namespace to ppcart-pe (#629)` | Yes — Pro must match Free `elementor/popup/show.ppcart-pe-` on the Elementor popup handler. Do not copy `.scPE-`. Hard cutover; no Compat shim. |
| `refactor(prefix): rename sample tax CSV to ppcart (#629)` | Yes — Pro must ship `sample_ppcart_tax_rates.csv` only. Mirror phpmd `PPCart_Public` / `PPCart_` comments. Do not ship `sample_sc_tax_rates.csv`. Hard cutover; no Compat shim. |
| `refactor(prefix): rename test seeder _sc_* keys (#629)` | No — Free test seeders only. Do not copy leftover `_sc_*` seeder keys into Pro tests. Never strip `_sc_` in Cart meta helpers. |
| `refactor(prefix): rename settings CSS tokens to --ppcart-*` | Yes — Pro settings CSS must use `--ppcart-*` on `.ppcart-settings-page`. `--pp-cs-*` is retired. Hard cutover; no Compat aliases. Register: [Settings CSS custom properties](#settings-css-custom-properties). |
| `refactor(prefix): drop doubled ppcart_cart_ prefix (#629)` | Yes — Match `ppcart_supports` / `ppcart_is_pro` / `ppcart_loaded` / `ppcart_pro_*` / `ppcart_manager`. Product checkout-window meta is `checkout_starts` / `checkout_ends` / `checkout_ended_*`. Do not read `cart_open` / `_ppcart_cart_*`. Leftover `_sc_cart_open` via Free Compat only. |

Docs note: 2026-09-10 leftover family **82** is complete. Post-79 leftover
queue is **complete**. Slice **18** stays `[~]`. See
[Remaining leftovers (82)](prefix-standardization.md#remaining-leftovers-82).

---

## Superseded naming notes (Pro tree today)

The Pro tree still ships the pre-rebrand families below. They are an
**inventory of what exists**, not a convention to follow — every new or
touched Pro symbol uses the canonical map in
[Canonical prefix map (Pro)](#canonical-prefix-map-pro).

| Kind | Pro ships today | Canonical target |
|------|-----------------|------------------|
| Pro constants | `NCS_CART_PRO_*` | `PPCART_PRO_*` |
| Shared / Free constants | `NCS_CART_*` | `PPCART_*` |
| PHP classes | `NCS_Cart_*`, `Scrt*`, `SC_Rest_*` | `PPCart_*` (Pro-owned: `PPCart_Pro_*`) |
| PHP files | `class-ncs-cart-*.php` | `class-ppcart-*.php` (Pro-owned: `class-ppcart-pro-*.php`) |
| Functions | `sc_*` (public API), `ncs_*` (internal) | `ppcart_*` (Pro-only: `ppcart_pro_*`) |
| Hooks / filters | `sc_*`, `studiocart_*` | `ppcart_*` |
| JS/CSS handles | `ncs-cart-*` | `ppcart-*` |
| Options / meta | `_sc_*` | `_ppcart_*` via `ppcart_meta_key()` / `get_option('_ppcart_*')` |

**Never introduce** `PP_Cart_*`, `PP_CART_*`, `pp-cart-*`, or `_pp_cart_*` in
either tree. That family was reverted; the canonical prefix is `PPCart_` /
`PPCART_` / `ppcart-`.

Legacy Free symbols Pro may still reference — rename the Pro call site to the
canonical class; the leftover name resolves only through Free Compatibility
Mode:

| Leftover Free symbol | Canonical |
|----------------------|-----------|
| `ScrtOrder` / `ScrtSubscription` / `ScrtCollection` | `PPCart_Order` / `PPCart_Subscription` / `PPCart_Collection` |
| `SC_Rest_Orders`, `SC_Rest_Products`, … | `PPCart_Rest_Orders`, `PPCart_Rest_Products`, … |
| `PP_Cart_Checkout_Renderer` | `PPCart_Checkout_Renderer` |
| `Studiocart\Elementor` | `PPCart_Integration_Elementor` |
