# PublishPress Cart — Prefix Standardization

Official prefix map and leftover tracker for **issue #629**. The Pro-side
playbook lives in [prefix-pro.md](prefix-pro.md).
First-party code targets `PPCart_` / `ppcart_` / `ppcart-` / `PPCART_`.
StudioCart / NCS / `sc` names belong in StudioCart Compatibility Mode
(**sibling plugin `publishpress-cart-compat`**, not this Free tree). Paths
written as `includes/compat/…` below are **companion-repo-relative**. Cart
has no `includes/compat/` directory.

Post-42 leftovers **43–82** are done except slice **18** (`[~]`). Post-79 leftover
queue **complete** (slice **82** done). No pending post-42 families.

Tackle **one slice per session**. Do not mix a PHP rename with a persisted-data
migration. This repo does not contain Pro source; [prefix-pro.md](prefix-pro.md)
is the contract for a later Pro PR.

Canonical target: issue-629.md.

**Legend:** `[ ]` pending · `[x]` done · `[~]` partial · `[-]` skip / not a leak (exceptions)

Next catalog IDs: **UT-321** / **IT-362**. Post-69 leftover queue **70–73**
**complete**. Post-73 leftover queue **complete** (slices **74–76** done).
Post-76 leftover queue **complete** (slice **77** done). Post-77 leftover
queue **complete** (slice **78** done). Post-78 leftover queue **complete**
(slice **79** done). Post-79 leftover queue **complete** (slices **80–81** done).
Slice **82** doubled `ppcart_cart_` prefix is `[x]`. Slice 18 stays `[~]`.
**UT-309** is the revoke-access nonce follow-up (not a leftover family) — do
not reuse. See [Post-42 leftovers](#post-42-leftovers-43),
[Remaining leftovers (58–69)](#remaining-leftovers-58-69) (done),
[Remaining leftovers (70–73)](#remaining-leftovers-70-73) (done),
[Remaining leftovers (74–76)](#remaining-leftovers-74-76) (done),
[Remaining leftovers (77)](#remaining-leftovers-77) (done),
[Remaining leftovers (78)](#remaining-leftovers-78) (done), and
[Remaining leftovers (79–81)](#remaining-leftovers-79-81).

---

## What this replaced

An earlier pass reverted mistaken `PP_Cart_` / `pp_cart_` / `pp-cart-` symbols
back to StudioCart-era names (`NCS_Cart_*`, `sc_*`, `_sc_*`, `studiocart`).
That pass is finished and **superseded**. Canonical code is now the PublishPress
Cart rebrand, not the StudioCart prefixes.

Do **not**:

- Restore `NCS_Cart_*` / `sc_*` as the live first-party API
- Reintroduce `PP_Cart_` / `pp_cart_` / `pp-cart-` / `PP_CART_*` (underscore
  after `PP`)
- Dual-read leftover `_sc_*` post-meta rows, leftover `$_POST` names, or
  leftover metabox field ids in first-party code. First-party save/read is
  `_ppcart_*` only. Leftover **names** are Compatibility Mode; leftover
  **rows** move in the merchant Data migration. Options stay copy-on-read
  (slice 13a); do not copy that pattern onto post-meta.

This file also replaces the former `prefix-leaks.md`, `prefix-leaks-pro.md`,
and `prefix-migration-inventory.md` leftover→canonical map.

### Product paths (not code identifiers)

These are on-disk / product-branding paths, independent of class/function prefix work.

- [x] **17** On-disk log directory names — **keep** `{uploads}/publishpress-cart/logs` via `wp_upload_dir()` (`wp-content/uploads/publishpress-cart/logs` on default installs; override `PPCART_DEBUG_LOG_DIR`). Not a leftover rename. PHP constants already `PPCART_*`. No filesystem shim; do not dual-write leftover plugin-dir `logs/` or fictional `ncs-cart/logs`. Locked by **UT-221**. **Pro contract:** Free default remains uploads-based; Pro must not invent `ncs-cart/logs`.
- [x] **73** Product download upload directory — first-party `wp-content/uploads/ppcart-uploads`. Leftover `sc-uploads` is an extra allowed root only while Compatibility Mode is on. No silent file move. See family **73**.

Catalog IDs for parallel prefix slices (do not reuse): slice 14 → UT-204–206 / IT-282–284; slice 16 → UT-220+; slice **17 → UT-221**; slice 18 → UT-222+; slice 19 → UT-223+; slice **20 → UT-224 / IT-311**; slice **21a → UT-225 / IT-312**; slice **21b → UT-226 / IT-313**; slice **22 → UT-227 / IT-314**; slice **23 → UT-228 / IT-315**; slice **24 → UT-229 / IT-316**; slice **25a → UT-230**; slice **25b → UT-234 / IT-319**; slice **26 → UT-231–232 / IT-317–318**; slice **27 → UT-235 / IT-327**; slice **28 → UT-236 / IT-328**; slice **29 → UT-237 / IT-329**; slice **30 → UT-238 / IT-330**; slice **31 → UT-248 / IT-332 / UT-251**; slice **32 → UT-252 / IT-334**; slice **33 → UT-253 / UT-254 / IT-335**; slice **34 → UT-255 / UT-256 / IT-336**; slice **35 → UT-257 / UT-258 / IT-337**; slice **40 → UT-259 / UT-260 / IT-338**; slice **39 → UT-261**; slice **36 → UT-262 / UT-263 / IT-339**; slice **37 → UT-264 / UT-265 / IT-340**; slice **43 → UT-273 / UT-274 / IT-344**; slice **64 → UT-299 / IT-355**; slice **65 → UT-300 / IT-356**; slice **66 → UT-301 / IT-357**; slice **67 → UT-302 / IT-358**; slice **68 → UT-303**; slice **69 → UT-304**; slice **70 → UT-305 / IT-359**; slice **71 → UT-306 / IT-360**; slice **72 → UT-307**; slice **73 → UT-308 / IT-361**; slice **74 → UT-310**; slice **75 → UT-311**; slice **76 → UT-312**; slice **77 → UT-313**; slice **78 → UT-314**; slice **79 → UT-315**; slice **80 → UT-316**; slice **81 → UT-317**; slice **82 → UT-320**. **UT-309** is the revoke-access nonce follow-up, not a leftover family.

---

Team briefing (why the rename was dozens of families, not one search-replace):
[prefix-families-summary.md](prefix-families-summary.md).
Decision record: [ADR-0004](../adr/0004-canonical-ppcart-prefix.md).

## Official prefix standard

| Kind | Prefix | Example |
|------|--------|---------|
| Classes | `PPCart_` | `PPCart_Order`, `PPCart_Studiocart_Compatibility_Mode` |
| Functions, hooks, options, globals | `ppcart_` / `_ppcart_` | `ppcart_format_price()`, `ppcart_after_order_paid` |
| Constants | `PPCART_` | `PPCART_VERSION` |
| Files, asset handles, CSS/BEM | `ppcart-` | `class-ppcart-order.php`, `.ppcart-settings` |
| CSS custom properties | `--ppcart-` | `--ppcart-bg`, `--ppcart-primary` on `.ppcart-settings-page` |
| HTML `id` / `class` / `for` | `ppcart-` / `ppcart_` | `id="ppcart_form"`, `class="ppcart-row"` — match the existing separator |
| Shortcode tags | `ppcart_` | `ppcart_form`, `ppcart_account` |
| Top-level admin menu slug | `ppcart` | `add_menu_page(..., 'ppcart', ...)` |
| Admin menu hook prefix | `ppcart_page_` | `ppcart_page_ppcart-settings` |
| Pro-only functions / constants | `ppcart_pro_` / `PPCART_PRO_` | `PPCART_PRO_BASE_URL` |

**Word separators:** `snake_case` in PHP identifiers and shortcode tags;
`kebab-case` in file names, asset handles, CSS classes, and public query vars.
HTML `id` / `class` / `for` keep the separator they already use (`sc_accept_terms`
→ `ppcart_accept_terms`; `.sc-row` → `.ppcart-row`).

### Markup, CSS, and DOM selectors

Every prefix slice that touches a screen must also rebrand leftover HTML if it
exists there. Do not leave StudioCart / `sc` / `ncs` markup behind while
renaming the PHP around it.

| Attribute / selector | Canonical | Same-commit follow-through |
|----------------------|-----------|----------------------------|
| HTML `id`, `for` | `ppcart_` / `ppcart-` | Matching `label for`, `aria-labelledby`, `aria-controls` |
| HTML `class` (BEM and utility) | `ppcart-` / `ppcart_` | Matching CSS rules and JS (`.sc-`, `#sc_`) |
| Inline `style` hooks / body classes the plugin adds | `ppcart-*` | Core stylesheets, not Compatibility Mode CSS |
| CSS custom properties | `--ppcart-*` | Settings tokens on `.ppcart-settings-page`; no `--pp-cs-*` |
| JS `getElementById` / `$('#…')` / `querySelector` | canonical ids/classes | Same commit as the markup |
| `data-testid` | already `ppcart-*` | Do not reintroduce `sc-` testids |

Form **`name`** attributes that are request field names (`sc-nonce`,
`sc_accept_terms`, `sc_related_product`, …) are slice **26**. First-party
emit and read canonical `ppcart_*` / `ppcart-*`. Leftover POST/GET keys work
only while Compatibility Mode is on, via that package.

Leftover CPT body classes (`post-type-sc_product`, …) are styled only by
Compatibility Mode `css/ppcart-compat-*.css` (toggle-gated). Inner first-party
`id` / `class` are canonical `ppcart_*` / `ppcart-*` (slice 19). Hardcoded
leftover CPT/taxonomy **strings** and leftover body-class selectors in
first-party were renamed in slice **28**. First-party templates and plugin CSS
must not keep leftover HTML selectors as the live API.

Merchant custom CSS that targets `.sc-*` is not a Compatibility Mode feature.
Do not dual-class first-party markup unless a slice explicitly documents an
exception.

### Hook prefix map (mechanical)

| Legacy | Canonical |
|--------|-----------|
| `sc_*` | `ppcart_*` |
| `_sc_*` | `_ppcart_*` |
| `ncs_*` / `nsc_*` | `ppcart_*` |
| `studiocart_*` | `ppcart_*` |
| `ncs-cart-*` | `ppcart-*` |

Overrides (not the mechanical rule):

| Legacy | Canonical |
|--------|-----------|
| `studiocart_checkout_complete` | `ppcart_after_order_paid` |
| `before_sc_order_refund` | `ppcart_before_order_refund` |
| `studiocart_page_metabox` | `ppcart_page_metabox` |
| `ncs-cart-currencies` | `ppcart_currencies` |
| `ncs-cart-currency-symbols` | `ppcart_currency_symbols` |
| `ncs-cart-zero-decimal-currency` | `ppcart_zero_decimal_currency` |

Cron leftovers follow the same mechanical rule (`ncs_email_schedule_hook` →
`ppcart_email_schedule_hook`, `studiocart_daily_events` →
`ppcart_daily_events`, `sc_cancel_subscription_event` →
`ppcart_cancel_subscription_event`, and the other shipped schedule tags).
Compat drains leftover scheduled names onto the canonical handler.

Named constant/function leftovers that are **not** `NCS_CART_*` / `sc_*`
mechanical: `NCS_STYLESHEETPATH` → `PPCART_STYLESHEET_PATH`;
`pp_cart_testid` → `ppcart_testid` (reverted-rebrand leftover).

Leftover → canonical for every Cart hook Pro or a third party can reuse:
[Hooks (Pro / third-party contract)](#hooks-pro--third-party-contract).
Live fire/listen file inventory: [hooks-manifest.md](../hooks-manifest.md).
After intentional hook changes:

```bash
php tests/bin/hook-manifest.php --update
php tests/bin/hook-prefix-policy.php
```

Dynamic hook patterns must include a canonical prefix in the literal portion
of the name. Cron: first-party schedules `ppcart_*`; Compatibility Mode drains
leftover scheduled names onto the canonical handler.

---

## Compatibility Mode

Shipped StudioCart PHP names stay available only in sibling
`publishpress-cart-compat` (`includes/compat/studiocart-compatibility-mode/`),
when the merchant toggle is
on (default **off**). Canonical Cart never branches on
`ppcart_supports( 'ppcart-compatibility-mode' )` except inside that
package. Compatibility Mode is a **Free** merchant toggle for third-party
StudioCart-era plugins, not Pro’s runtime.

| Tier | Scope | Toggleable? |
|------|--------|-------------|
| **1 — Public API** | Leftover CPT/REST/AJAX-field **names** | Never from first-party. Compat package only, and only while the toggle is on |
| **2 — PHP shims** | `class_alias`, function wrappers, hook/option/AJAX/nonce/query-var/POST/GET/Stripe-metadata bridges | Yes — Compatibility Mode |
| **3 — Strict mode** | Compat off in PHPUnit bootstrap | Dev/CI only |

Merchant option: `_ppcart_compatibility_mode`. Historical
`_ppcart_studiocart_compatibility_mode` and `_sc_studiocart_compatibility_mode`
rows are read only by Compat and stay in place; keep the oldest key excluded
from option storage maps. wp-config override:
`PPCART_STUDIOCART_COMPATIBILITY_MODE`. Runtime gate:
`ppcart_supports( 'ppcart-compatibility-mode' )`.

Compat maps live under cart-compat
`includes/compat/studiocart-compatibility-mode/maps/` (Free first-party no
longer owns that package). Class maps: `bootstrap.php`, `admin.php`,
`public.php`, `models.php`, `helpers.php`, `gutenberg.php`,
`admin-controllers.php`, `stripe-logging.php`, `fixtures.php`. Function
wrappers (~324) live in that package’s `functions.php`. Leftover `sc_*`
**method names** have no aliases (slice **20**). Keep
`ncs_cart_legacy_compat_enabled()` inside the Compat package (developer/CI
gate, not a first-party leftover).

| Package (under `publishpress-cart-compat/`) | Role |
|---------|------|
| `includes/compat/studiocart-compatibility-mode/` | PHP shims when Compatibility Mode is on |
| `includes/compat/cpt-slug-migration/` | Settings card — CPT/taxonomy + roles/caps (12 + 15) |
| `includes/compat/meta-key-migration/` | Settings card — leftover `_sc_*` rows (13b) |
| `includes/compat/option-key-migration/` | Copy-on-read leftover option rows (13a); not a Settings card |
| `includes/compat/shortcode-tag-migration/` | Settings card — leftover shortcode tags in posts/emails (14c) |
| `includes/compat/custom-table-migration/` | Settings card sibling — leftover `{prefix}ncs_*` tables (16); opt-in `RENAME`, no Compat table alias, no restore |

### Leftover → canonical by kind

Kind-level map (Compat + Data migration). Per-identifier leftover →
canonical names: [Leftover → canonical identifiers](#leftover--canonical-identifiers).
Slice checklists and family tables **58–81** keep the same pairs with
files, Compat, and tests.

| Kind | Leftover → canonical | Slice | Status | Compat | Data migration |
|------|----------------------|-------|--------|--------|----------------|
| PHP class | `NCS_Cart_*` / `Scrt*` / `SC_*` → `PPCart_*` | classes + 2 | done | alias when on | n/a |
| PHP function | `sc_*` / `ncs_*` / `studiocart_*` → `ppcart_*` | functions + 1 | done | wrapper when on | n/a |
| PHP method | leftover `sc_` in method names → drop / `ppcart_*` | 20 | done | no | n/a |
| PHP constant | `NCS_CART_*` / `SC_*` → `PPCART_*` | constants | done | alias when on | n/a |
| Pro-contract constant | `NCS_CART_LOADED_BY_PRO` / `NCS_CART_PRO_*` → `PPCART_*` | 18 | `[~]` | inbound copy when on | n/a |
| Hook | `sc_*` / `_sc_*` / `ncs_*` / `studiocart_*` / `ncs-cart-*` → `ppcart_*` / `_ppcart_*` / `ppcart-*` | hooks + 4; `admin_action_sc_*` (26); `sc_order_*` (34) | done | dual-register when on | n/a |
| Option key | `_sc_*` / `sc_*` / `ncs_*` / `current_schedule_val` / `_my_account` → `_ppcart_*` / `ppcart_*` | 13a | done | name bridge when on | copy-on-read (not a Settings card) |
| Post/user-meta key | `_sc_*` → `_ppcart_*`; literals (25); invoice `sc_pns` (37); tax-rate inner `_sc_*` (38) | 13b + 25 + 37 + 38 | done | name bridge when on | Settings card (13b rows) |
| CPT / taxonomy slug | `sc_product` / `sc_order` / `sc_subscription` / cat/tag → `ppcart_*` | 12 | done | no | Settings card |
| CPT leftover strings | `sc_collection` / rewrite / FSE `single-sc_product` → `ppcart_*` | 28 | done | leftover names when on | n/a |
| Role / cap | `sc_cart_manager` / `edit_sc_product` → `ppcart_*` | 15 | done | no | Settings card (with 12) |
| Custom table | `{prefix}ncs_*` → `{prefix}ppcart_*` | 16 | done | no | Settings card, opt-in `RENAME` |
| Shortcode tag | leftover tags → `ppcart_*` | 14a+14b+14c | done | dual-register when on | posts/emails (14c) |
| REST namespace | `sc/v1` → `publishpress-cart/v1` | 29 | done | no (hard cutover) | n/a |
| AJAX action | `wp_ajax_sc_*` / `ncs_*` → `wp_ajax_ppcart_*` | 11 + 30 | done (coupon/upsell still Pro) | dual-register when on | n/a |
| Query var | `sc-*` → `ppcart-*`; leftover `sc-csv-export` 302 | 10 + 40 | done | leftover vars + 302 when on | n/a |
| HTML `id` / `class` / `for` | leftover → `ppcart_*` / `ppcart-` | 8 + 9 + 19 + 25b + 35 | done | leftover CPT body CSS when on | n/a |
| JS selector / localize | leftover → `ppcart_*` / `ppcart/orderform/*` | 19 + 21 + 39 | done | no (hard cutover) | n/a |
| PHP runtime global | leftover `$sc_*` / `$studiocart` → `$ppcart_*` | 22 + 31 | done | alias when on | n/a |
| Dashboard widget id | leftover → `ppcart_dashboard_widget` | 23 | done | no | n/a |
| Transient prefix | leftover → `ppcart_*` | 24 | done | leftover names when on | leftover rows copy then canonical |
| Form `name` / request | leftover `sc-nonce` / `sc_*` → `ppcart-*` / `ppcart_*` | 26 + 33 + 35 | done | POST/GET bridge when on | n/a |
| Admin bulk action | `sc_make_*` / `sc_sync_stripe` → `ppcart_make_*` / `ppcart_sync_stripe` | 32 | done | no (hard cutover) | n/a |
| Stripe object metadata | `metadata.sc_*` → `metadata.ppcart_*` | 27 | done | read leftover when on | n/a |
| On-disk log directory | keep `{uploads}/publishpress-cart/logs` | 17 | keep | n/a | n/a |
| Download upload directory | `sc-uploads` → `ppcart-uploads` | 73 | done | extra allowed root when on | later Data migration card |
| Text domain / plugin slug | text domain stay `publishpress-cart`; admin slugs `ppcart` | 4 keep; 9 done | keep / done | no | n/a |
| Mangled `ppcart_*` hooks | `ppcart_ctivate` → `ppcart_activate` (never alias mangled) | 70 | done | leftover `ncs_activate` / `sc_product` when on | n/a |
| Request GET revoke | `sc-revoke` → `ppcart-revoke` | 71 | done | leftover GET when on | n/a |
| Escaped HTML/JS | `row-sc-` / `ncs-nav-tabs` → `row-ppcart-` / `ppcart-nav-tabs` | 72 | done | no | n/a |
| Debug-log `Scrt*` | `ScrtOrder` → `PPCart_Order` only | 74 | done | no | n/a |
| Merchant copy / URLs | `studiocart_field_id` / `plugin/studiocart` → canonical | 75 | done | no | n/a |
| Comments | leftover names in comments → canonical | 76 | done | no | n/a |
| Glued HTML/JS | `data-scq-price` / `originalNcs` → `data-ppcart-qty-price` / `originalPpcartSettings` | 77 | done | no | n/a |
| Glued `$sc*` locals | `$scrt_order` / `$scorder` / `$scsub` → `$ppcart_order` / `$ppcart_subscription` | 78 | done | no | n/a |
| jQuery `scPE` | `.scPE-` → `.ppcart-pe-` | 79 | done | no | n/a |
| Sample tax CSV | `sample_sc_tax_rates.csv` → `sample_ppcart_tax_rates.csv` | 80 | done | no | n/a |
| Test seeder keys | `_sc_*` seeder keys → suffixes / `_ppcart_*` | 81 | done | never strip `_sc_` in Cart meta helpers | n/a |

### Leftover → canonical identifiers

**Public reuse contract (Pro and third-party plugins).** This section is
the leftover → canonical map for PHP and request APIs other plugins and
Pro can call, listen to, or POST. Fire and listen **canonical** names.
Leftover names work only while Compatibility Mode is on, except hard
cutovers. Cosmetic locals, HTML/CSS, comments, and test seeders are not
this contract — those stay in family tables **58–81**.

Mechanical families (`sc_*` → `ppcart_*`, `_sc_*` → `_ppcart_*`, `ncs_*`
→ `ppcart_*`, `ncs-cart-*` → `ppcart-*`, `NCS_Cart_*` → `PPCart_*`,
`NCS_CART_*` → `PPCART_*`, `studiocart_*` → `ppcart_*`, `studiocart-` →
`ppcart-`) apply unless a row overrides.

Shortcodes: [Shortcodes (slice 14)](#shortcodes-slice-14). Full hook
leftover → canonical: [Hooks (Pro / third-party contract)](#hooks-pro--third-party-contract).

#### PHP class

| Leftover | Canonical | Slice |
|----------|-----------|-------|
| `NCS_Cart` | `PPCart` | classes |
| `NCS_Cart_Loader` | `PPCart_Loader` | classes |
| `NCS_Cart_Activator` | `PPCart_Activator` | classes |
| `NCS_Cart_Deactivator` | `PPCart_Deactivator` | classes |
| `NCS_Cart_I18n` | `PPCart_I18n` | classes |
| `NCS_Cart_Upgrade` | `PPCart_Upgrade` | classes |
| `NCS_Cart_Admin_Screens` | `PPCart_Admin_Screens` | classes |
| `NCS_Cart_Version_Notices` | `PPCart_Version_Notices` | classes |
| `NCS_Cart_Post_Types` | `PPCart_Post_Types` | classes |
| `NCS_Cart_Dependency_Loader` | `PPCart_Dependency_Loader` | classes |
| `NCS_Cart_Admin_Hook_Registrar` | `PPCart_Admin_Hook_Registrar` | classes |
| `NCS_Cart_Public_Hook_Registrar` | `PPCart_Public_Hook_Registrar` | classes |
| `NCS_Cart_Status_Labels` | `PPCart_Status_Labels` | classes |
| `NCS_Cart_Sanitize` | `PPCart_Sanitize` | classes |
| `NCS_Cart_Product_Duplicator` | `PPCart_Product_Duplicator` | classes |
| `NCS_Cart_Order_Refunds` | `PPCart_Order_Refunds` | classes |
| `NCS_Cart_Currencies` | `PPCart_Currencies` | classes |
| `NCS_Cart_Admin` | `PPCart_Admin` | classes |
| `NCS_Cart_Admin_Ajax` | `PPCart_Admin_Ajax` | classes |
| `NCS_Cart_Admin_Filters` | `PPCart_Admin_Filters` | classes |
| `NCS_Cart_Admin_Settings` | `PPCart_Admin_Settings` | classes |
| `NCS_Cart_Admin_Stripe_Connect_Settings` | `PPCart_Admin_Stripe_Connect_Settings` | classes |
| `NCS_Cart_Admin_Stripe_Webhook_Settings` | `PPCart_Admin_Stripe_Webhook_Settings` | classes |
| `NCS_Cart_Admin_Reports` | `PPCart_Admin_Reports` | classes |
| `NCS_Cart_Customer_Reports` | `PPCart_Customer_Reports` | classes |
| `NCS_Cart_Contacts_Page` | `PPCart_Contacts_Page` | classes |
| `NCS_Cart_Extension_Page` | `PPCart_Extension_Page` | classes |
| `NCS_Cart_Order_Admin` | `PPCart_Order_Admin` | classes |
| `NCS_Cart_Product_Admin` | `PPCart_Product_Admin` | classes |
| `NCS_Cart_Product_Metaboxes` | `PPCart_Product_Metaboxes` | classes |
| `NCS_Cart_Product_Metabox_Option_Sources` | `PPCart_Product_Metabox_Option_Sources` | classes |
| `NCS_Cart_Order_Metaboxes` | `PPCart_Order_Metaboxes` | classes |
| `NCS_Cart_Public` | `PPCart_Public` | classes |
| `NCS_Cart_Paypal` | `PPCart_Paypal` | classes |
| `NCS_Cart_Public_*_Controller` | `PPCart_Public_*_Controller` | classes |
| `NCS_Cart_Order` / `ScrtOrder` | `PPCart_Order` | classes |
| `NCS_Cart_Subscription` / `ScrtSubscription` | `PPCart_Subscription` | classes |
| `NCS_Cart_Order_Item` / `ScrtOrderItem` | `PPCart_Order_Item` | classes |
| `NCS_Cart_Order_Items` | `PPCart_Order_Items` | classes |
| `NCS_Cart_Collection` / `ScrtCollection` | `PPCart_Collection` | classes |
| `NCS_Cart_Helper` / `NCS_Helper` | `PPCart_Helper` | classes |
| `NCS_Cart_Order_Helper` / `NCS_Order_Helper` | `PPCart_Order_Helper` | classes |
| `NCS_Cart_Stripe` / `NCS_Stripe` | `PPCart_Stripe` | classes |
| `NCS_Cart_Price_Format` / `NCS_Price_Format` | `PPCart_Price_Format` | classes |
| `NCS_Cart_Elementor_*` / `NCS_Elementor_*` | `PPCart_Elementor_*` | classes |
| `NCS_Cart_Bump_*` / `SC_Bump_*` | `PPCart_Bump_*` | classes |
| `NCS_Cart_Gutenberg_*` / `NCS_Cart_Checkout_*` / `NCS_Cart_Account_*` / `NCS_Cart_Product_Template` | `PPCart_*` | classes |
| `NCS_Cart_Admin_Order_*` / `NCS_Cart_Admin_Subscription_Controller` / `NCS_Cart_Admin_Test_Mode_Notice_Controller` / `NCS_Cart_Admin_Page_Notices` | `PPCart_*` | classes |
| `NCS_Cart_Dashboard_*` / `Studiocart_Dashboard_Widget` | `PPCart_Dashboard_*` | classes |
| `NCS_Cart_Stripe_Sync*` / `NCS_Cart_Debug_*` / `NCS_Cart_Secrets` / `NCS_Cart_Files` | `PPCart_*` | classes |
| `NCS_Cart_Fixtures*` / `NCS_Cart_Regression_*` / `NCS_Cart_Smoke_Fixtures` / `NCS_Cart_Admin_Workflow_State` | `PPCart_*` | 7 |
| `NCS_Cart_Studiocart_Compatibility_Mode` (never shipped) | `PPCart_Studiocart_Compatibility_Mode` | 2 |
| `NCS_Cart_VAT` / `NCS_Cart_Tax` | `PPCart_VAT` / `PPCart_Tax` | 52 |
| `namespace Studiocart` / `STOF_StudiocartOrderForm` / `StudioCartOrderForm` | `namespace PPCart` / `PPCart_Divi_Order_Form` (Pro) | 57 |
| `NCS_Cart_Pro_*` (Pro repo) | `PPCart_Pro_*` | 18 `[~]` |

#### PHP function / method

Mechanical `sc_*()` / `ncs_*()` / `ncs_cart_*()` / `studiocart_*()` →
`ppcart_*()` (~324 Compat wrappers). Locals `$ncs_cart_*` /
`$__ncs_cart_template_result` → `$ppcart_*` / `$__ppcart_template_result`
(slice 5, no shim).

| Leftover | Canonical | Slice |
|----------|-----------|-------|
| `activate_ncs_cart` | `ppcart_activate` | 1 |
| `deactivate_ncs_cart` | `ppcart_deactivate` | 1 |
| `upgrade_ncs_cart` | `ppcart_upgrade` | 1 |
| `run_ncs_cart` | `ppcart_run` | 1 |
| `sc_cart_is_pro` | `ppcart_is_pro` | functions |
| `sc_cart_supports` | `ppcart_supports` | functions |
| `studiocart_default_fields_filter` | `ppcart_default_fields_filter` | functions |
| `studiocart_notification_send` | `ppcart_notification_send` | functions |
| `studiocart_plan` | `ppcart_plan` | functions |
| `pp_cart_testid` | `ppcart_testid` | functions |
| `NCSLogger` | `ppcartLogger` | 46 |
| `sc_csv_escape_cell` | `ppcart_csv_escape_cell` | 40 |
| `sc_is_checkout_context` | `ppcart_is_checkout_context` | 51 |
| `save_post_sc_order` | `save_post_order` | 20 |
| `register_sc_importers` | `register_importers` | 20 |
| `get_sc_*` / `update_sc_*_amount` / `custom_sc_*_column` / `register_sc_tab_section` | drop `sc_` / `update_ppcart_*_amount` / Kit `get_convertkit_form_options` / `get_converkit_tag_options` | 20 |
| `$this->prefix = 'sc_'` | `$this->prefix = 'ppcart_'` | 13b |
| `ncs_cart_legacy_compat_enabled()` | keep in Compat package | keep |

#### PHP constant

| Leftover | Canonical | Slice |
|----------|-----------|-------|
| `NCS_CART_FREE_LOADED` | `PPCART_FREE_LOADED` | constants |
| `NCS_CART_VERSION` | `PPCART_VERSION` | constants |
| `NCS_CART_BASE_DIR` / `NCS_CART_BASE_URL` / `NCS_CART_BASE_FILE` | `PPCART_BASE_*` | constants |
| `NCS_STYLESHEETPATH` | `PPCART_STYLESHEET_PATH` | constants |
| `NCS_CART_LIB_VENDOR_PATH` / `NCS_CART_VENDOR_ASSETS_URL` | `PPCART_LIB_VENDOR_PATH` / `PPCART_VENDOR_ASSETS_URL` | constants |
| `NCS_CART_ENCRYPT_SECRETS` / `NCS_CART_SECRETS_KEY` | `PPCART_ENCRYPT_SECRETS` / `PPCART_SECRETS_KEY` | constants |
| `NCS_CART_DEBUG_LOG_DIR` / `NCS_CART_DEBUG_LOG_MAX_BYTES` | `PPCART_DEBUG_LOG_*` | constants |
| `NCS_CART_STRIPE_WEBHOOK_LOG_DIR` / `NCS_CART_STRIPE_WEBHOOK_LOG_MAX_BYTES` | `PPCART_STRIPE_WEBHOOK_LOG_*` | constants |
| `NCS_CART_PAYPAL_SSL_VERIFY` | `PPCART_PAYPAL_SSL_VERIFY` | constants |
| `SC_STRIPE_CONNECT_SERVER_URL` | `PPCART_STRIPE_CONNECT_SERVER_URL` | constants |
| `SC_STRIPE_CONNECT_ALLOW_INSECURE_LOCAL_CREDENTIALS` | `PPCART_STRIPE_CONNECT_ALLOW_INSECURE_LOCAL_CREDENTIALS` | constants |
| `NCS_CART_LOADED_BY_PRO` / `NCS_CART_PRO_*` | `PPCART_LOADED_BY_PRO` / `PPCART_PRO_*` | 18 `[~]` |
| `NCS_CART_PRO_BASE_URL` | `PPCART_PRO_BASE_URL` | 18 `[~]` |
| `NCS_CART_PRO_LIB_VENDOR_DIR` | `PPCART_PRO_LIB_VENDOR_DIR` | 18 `[~]` |
| `NCS_CART_PRO_PLUGIN_NAME` | `PPCART_PRO_PLUGIN_NAME` | 18 `[~]` |
| `NCS_CART_PRO_BASE_FILE` / `NCS_CART_PRO_BASE_DIR` | `PPCART_PRO_BASE_FILE` / `PPCART_PRO_BASE_DIR` | 18 `[~]` |
| `PPCART_STUDIOCART_COMPATIBILITY_MODE` | keep (wp-config override) | keep |
| `PP_CART_*` / `PP_Cart_*` | do not reintroduce | keep |

#### Hooks (Pro / third-party contract)

Listen and fire **canonical** names in Pro and in third-party plugins.
Leftover names work only while Compatibility Mode is on, via
`publishpress-cart-compat` (dual-register / bridge). Hard cutover rows
have leftover names that must not be used — they do not bridge.

Live fire/listen inventory (files, `do_action` / `apply_filters`):
[hooks-manifest.md](../hooks-manifest.md). Leftover → canonical **map**
is this table. After intentional hook changes:

```bash
php tests/bin/hook-manifest.php --update
php tests/bin/hook-prefix-policy.php
```

**Mechanical leftover aliases** (Compat on, unless excluded): leftover
`sc_*` / `ncs_*` / `nsc_*` / `studiocart_*` → canonical `ppcart_*`;
`_sc_*` → `_ppcart_*`; `ncs-cart-*` → `ppcart-*`. AJAX **actions**,
option-key WordPress hooks (`add_option_*`), and CPT-derived tags are
excluded from that mechanical hook rewrite — AJAX uses the action map;
options use the option bridge; CPT tags follow the live post type after
Data migration.

The **Leftover** column is the primary shipped StudioCart-era name
(plus non-mechanical extras). Compat still aliases the other mechanical
prefixes onto the same canonical unless excluded.

Do **not** alias mangled `ppcart_ctivate` / `ppcart_pgrade` /
`ppcart_roduct` / `ppcart_roduct_price`. Leftover shipped names for
those tags are `ncs_activate` / `sc_activate` / `sc_product` (filter,
not the shortcode tag).

##### Overrides and cron (not mechanical)

| Leftover | Canonical |
|----------|-----------|
| `studiocart_checkout_complete` | `ppcart_after_order_paid` |
| `before_sc_order_refund` | `ppcart_before_order_refund` |
| `studiocart_page_metabox` | `ppcart_page_metabox` |
| `ncs-cart-currencies` | `ppcart_currencies` |
| `ncs-cart-currency-symbols` | `ppcart_currency_symbols` |
| `ncs-cart-zero-decimal-currency` | `ppcart_zero_decimal_currency` |
| `sc-cart-cpt-options` | `ppcart_cpt_options` |
| `sc-cart-taxonomy-options` | `ppcart_taxonomy_options` |
| `ppcart_sc_subscription_integrations` | `ppcart_ppcart_subscription_integrations` |
| `ppcart_sc_refund_order_integrations` | `ppcart_ppcart_refund_order_integrations` |
| `ppcart_sc_wpdomainchecker_integrations` | `ppcart_ppcart_wpdomainchecker_integrations` |
| `sc_run_price_formatting` / `nsc_run_price_formatting` | `ppcart_run_price_formatting` |
| `ncs_email_schedule_hook` | `ppcart_email_schedule_hook` |
| `mt_daily` / `mt_weekly` / `mt_semi_monthly` | `ppcart_daily` / `ppcart_weekly` / `ppcart_semi_monthly` |
| `mt_none` (stored `ppcart_report_schedule`) | `ppcart_none` |
| `studiocart_daily_events` | `ppcart_daily_events` |
| `studiocart_subscription_reminder_event` | `ppcart_subscription_reminder_event` |
| `studiocart_cleanup_preloaded_intents` | `ppcart_cleanup_preloaded_intents` |
| `sc_cancel_subscription_event` | `ppcart_cancel_subscription_event` |
| `sc_stripe_reconcile_stale_subscriptions` | `ppcart_stripe_reconcile_stale_subscriptions` |

##### Admin screen tags (slice 9 — hard cutover, no Compat)

| Leftover | Canonical |
|----------|-----------|
| `studiocart_page_*` | `ppcart_page_*` |
| `toplevel_page_studiocart` | `toplevel_page_ppcart` |
| `studiocart-*` / `ncs-cart-*` (dynamic `$this->plugin_name`) | `ppcart-*` (runtime `PPCart::$plugin_name` is `ppcart`; leftover `ncs-cart-*` Compat on) |

##### CPT-derived WordPress tags

| Leftover | Canonical |
|----------|-----------|
| `save_post_sc_*` / `manage_sc_*` / `bulk_actions-edit-sc_*` | Follow the **live** CPT slug (`save_post_ppcart_*` after Data migration). Callback methods were renamed in slice 20. |

##### Plugin hooks (`ppcart_*` / `_ppcart_*`)

| Leftover | Canonical | Type |
|----------|-----------|------|
| `_sc_custom_option_list` | `_ppcart_custom_option_list` | filter |
| `_sc_emails_tab_section` | `_ppcart_emails_tab_section` | filter |
| `_sc_integrations_option_list` | `_ppcart_integrations_option_list` | filter |
| `_sc_integrations_tab_section` | `_ppcart_integrations_tab_section` | filter |
| `_sc_invoice_option_list` | `_ppcart_invoice_option_list` | filter |
| `_sc_invoice_tab_section` | `_ppcart_invoice_tab_section` | filter |
| `_sc_option_list` | `_ppcart_option_list` | filter |
| `_sc_payment_field_option_list` | `_ppcart_payment_field_option_list` | filter |
| `_sc_payment_gateway_tab_section` | `_ppcart_payment_gateway_tab_section` | filter |
| `_sc_plan` | `_ppcart_plan` | filter |
| `_sc_register_gateways` | `_ppcart_register_gateways` | action |
| `_sc_register_sections` | `_ppcart_register_sections` | action |
| `_sc_taxes_tab_section` | `_ppcart_taxes_tab_section` | filter |
| `_sc_{$ppcart_tab_key}_tab_section` | `_ppcart_{$ppcart_tab_key}_tab_section` | filter |
| `sc_account_before_subscription_details` | `ppcart_account_before_subscription_details` | action |
| `sc_account_block_navigation_options` | `ppcart_account_block_navigation_options` | filter |
| `sc_account_subscription_action_links` | `ppcart_account_subscription_action_links` | action |
| `sc_account_tabs` | `ppcart_account_tabs` | filter |
| `ncs_activate` / `sc_activate` | `ppcart_activate` | action |
| `sc_admin_ajax_dispatch` | `ppcart_admin_ajax_dispatch` | filter |
| `sc_admin_bar_test_mode_processors` | `ppcart_admin_bar_test_mode_processors` | filter |
| `sc_admin_notification_email` | `ppcart_admin_notification_email` | filter |
| `sc_admin_order_child_order_label` | `ppcart_admin_order_child_order_label` | filter |
| `sc_admin_order_item_type_labels` | `ppcart_admin_order_item_type_labels` | filter |
| `sc_admin_order_related_context` | `ppcart_admin_order_related_context` | filter |
| `sc_admin_reports_product_columns` | `ppcart_admin_reports_product_columns` | action |
| `sc_admin_reports_summary_columns` | `ppcart_admin_reports_summary_columns` | action |
| `sc_admin_reports_transactions_label` | `ppcart_admin_reports_transactions_label` | filter |
| `sc_after_buy_button` | `ppcart_after_buy_button` | action |
| `sc_after_downloads_attached_to_order` | `ppcart_after_downloads_attached_to_order` | action |
| `sc_after_load_from_post` | `ppcart_after_load_from_post` | action |
| `sc_after_order_load_from_post` | `ppcart_after_order_load_from_post` | filter |
| `studiocart_checkout_complete` / `sc_after_order_paid` | `ppcart_after_order_paid` | action |
| `sc_after_payment_info` | `ppcart_after_payment_info` | action |
| `sc_after_product_list` | `ppcart_after_product_list` | action |
| `sc_after_product_setup` | `ppcart_after_product_setup` | action |
| `sc_after_setup_atts_from_post` | `ppcart_after_setup_atts_from_post` | action |
| `sc_after_step_1_button` | `ppcart_after_step_1_button` | action |
| `sc_after_subscription_load_from_order` | `ppcart_after_subscription_load_from_order` | action |
| `sc_after_summary_items` | `ppcart_after_summary_items` | action |
| `sc_after_update_stock` | `ppcart_after_update_stock` | action |
| `sc_after_user_is_created` | `ppcart_after_user_is_created` | action |
| `sc_after_validate_meta` | `ppcart_after_validate_meta` | action |
| `sc_backend_message_{$k}` | `ppcart_backend_message_{$k}` | filter |
| `sc_before_buy_button` | `ppcart_before_buy_button` | action |
| `sc_before_create_main_order` | `ppcart_before_create_main_order` | action |
| `sc_before_create_stripe_payment_intent` | `ppcart_before_create_stripe_payment_intent` | action |
| `sc_before_email_footer` | `ppcart_before_email_footer` | action |
| `sc_before_load` | `ppcart_before_load` | action |
| `before_sc_order_refund` / `sc_before_order_refund` | `ppcart_before_order_refund` | action |
| `sc_before_payment_info` | `ppcart_before_payment_info` | action |
| `sc_before_product_list` | `ppcart_before_product_list` | action |
| `sc_before_show_download` | `ppcart_before_show_download` | action |
| `sc_before_validate_meta` | `ppcart_before_validate_meta` | action |
| `sc_bulk_sync_subscription_limit` | `ppcart_bulk_sync_subscription_limit` | filter |
| `sc_cancel_subscription` | `ppcart_cancel_subscription` | filter |
| `sc_cancel_subscription_event` | `ppcart_cancel_subscription_event` | action |
| `sc_card_details_fields` | `ppcart_card_details_fields` | action |
| `sc_cart_before_order_save` | `ppcart_before_order_save` | filter |
| `sc_cart_extension_action` | `ppcart_extension_action` | filter |
| `sc_cart_extensions_list` | `ppcart_extensions_list` | filter |
| `sc_cart_loaded` | `ppcart_loaded` | action |
| `sc_cart_order_save_override` | `ppcart_order_save_override` | filter |
| `sc_cart_post_sanitize` | `ppcart_post_sanitize` | action |
| `sc_cart_pre_sanitize` | `ppcart_pre_sanitize` | action |
| `sc_cart_pro_locked_emails` | `ppcart_pro_locked_emails` | filter |
| `sc_cart_pro_locked_fields` | `ppcart_pro_locked_fields` | filter |
| `sc_cart_pro_locked_integration_keys` | `ppcart_pro_locked_integration_keys` | filter |
| `sc_cart_pro_locked_payment_methods` | `ppcart_pro_locked_payment_methods` | filter |
| `sc_cart_pro_locked_product_fields` | `ppcart_pro_locked_product_fields` | filter |
| `sc_cart_pro_locked_product_tabs` | `ppcart_pro_locked_product_tabs` | filter |
| `sc_cart_pro_locked_settings_field_sections` | `ppcart_pro_locked_settings_field_sections` | filter |
| `sc_cart_pro_locked_tabs` | `ppcart_pro_locked_tabs` | filter |
| `sc_cart_pro_upgrade_url` | `ppcart_pro_upgrade_url` | filter |
| `sc_cart_supports_feature` | `ppcart_supports_feature` | filter |
| `sc_charge_amount` | `ppcart_charge_amount` | filter |
| `sc_checkout_arranged_core_card_details_callbacks` | `ppcart_checkout_arranged_core_card_details_callbacks` | filter |
| `sc_checkout_block_arrangement` | `ppcart_checkout_block_arrangement` | filter |
| `sc_checkout_block_sections` | `ppcart_checkout_block_sections` | filter |
| `sc_checkout_complete` | `ppcart_checkout_complete` | action/filter |
| `sc_checkout_coupon` | `ppcart_checkout_coupon` | filter |
| `sc_checkout_form` | `ppcart_checkout_form` | action |
| `sc_checkout_form_close` | `ppcart_checkout_form_close` | action |
| `sc_checkout_form_fields` | `ppcart_checkout_form_fields` | action |
| `sc_checkout_form_open` | `ppcart_checkout_form_open` | action |
| `sc_checkout_form_pay_options` | `ppcart_checkout_form_pay_options` | filter |
| `sc_checkout_form_price_checked` | `ppcart_checkout_form_price_checked` | filter |
| `sc_checkout_form_scripts` | `ppcart_checkout_form_scripts` | action |
| `sc_checkout_form_validation_messafes` | `ppcart_checkout_form_validation_messafes` | filter |
| `sc_checkout_page_error` | `ppcart_checkout_page_error` | filter |
| `sc_checkout_page_heading` | `ppcart_checkout_page_heading` | action |
| `sc_checkout_page_privacy_text` | `ppcart_checkout_page_privacy_text` | filter |
| `sc_checkout_page_terms_text` | `ppcart_checkout_page_terms_text` | filter |
| `sc_checkout_payment_method_enabled` | `ppcart_checkout_payment_method_enabled` | filter |
| `sc_checkout_should_guard_duplicate_render` | `ppcart_checkout_should_guard_duplicate_render` | filter |
| `sc_checkout_stripe_subscription_args` | `ppcart_checkout_stripe_subscription_args` | filter |
| `sc_checkout_template_path` | `ppcart_checkout_template_path` | filter |
| `studiocart_cleanup_preloaded_intents` / `sc_cleanup_preloaded_intents` | `ppcart_cleanup_preloaded_intents` | action |
| `sc_closed_message` | `ppcart_closed_message` | action |
| `sc_confirmation_fields` | `ppcart_confirmation_fields` | filter |
| `sc_confirmation_match_conditions` | `ppcart_confirmation_match_conditions` | action |
| `sc_consent_required` | `ppcart_consent_required` | filter |
| `sc_countries` | `ppcart_countries` | filter |
| `sc_coupon_fields` | `ppcart_coupon_fields` | action |
| `sc_coupon_status` | `ppcart_coupon_status` | action |
| `sc-cart-cpt-options` | `ppcart_cpt_options` | filter |
| `sc_create_stripe_intent` | `ppcart_create_stripe_intent` | filter |
| `sc_create_user_integrations` | `ppcart_create_user_integrations` | filter |
| `ncs-cart-currencies` / `sc_currencies` | `ppcart_currencies` | filter |
| `sc_currency_countries_code` | `ppcart_currency_countries_code` | filter |
| `ncs-cart-currency-symbols` / `sc_currency_symbols` | `ppcart_currency_symbols` | filter |
| `sc_current_user_orders_meta_query_args` | `ppcart_current_user_orders_meta_query_args` | filter |
| `sc_customer_defaults` | `ppcart_customer_defaults` | filter |
| `sc_customer_report_admin_notices` | `ppcart_customer_report_admin_notices` | action |
| `studiocart_daily_events` / `sc_daily_events` | `ppcart_daily_events` | action |
| `sc_default_field_settings_attributes` | `ppcart_default_field_settings_attributes` | filter |
| `sc_default_fields` | `ppcart_default_fields` | filter |
| `sc_default_fields_ids` | `ppcart_default_fields_ids` | filter |
| `sc_default_{$field[...]}]_field_settings_attributes` | `ppcart_default_{$field[...]}]_field_settings_attributes` | filter |
| `sc_defualt_fields_html` | `ppcart_defualt_fields_html` | filter |
| `sc_download` | `ppcart_download` | filter |
| `sc_download_allowed_redirect_hosts` | `ppcart_download_allowed_redirect_hosts` | filter |
| `sc_download_allowed_roots` | `ppcart_download_allowed_roots` | filter |
| `sc_download_slug` | `ppcart_download_slug` | filter |
| `sc_download_tab_name` | `ppcart_download_tab_name` | filter |
| `sc_email_after_order_table` | `ppcart_email_after_order_table` | action |
| `ncs_email_schedule_hook` / `sc_email_schedule_hook` | `ppcart_email_schedule_hook` | action |
| `sc_email_template_customer_info` | `ppcart_email_template_customer_info` | filter |
| `sc_email_template_option_names` | `ppcart_email_template_option_names` | filter |
| `sc_email_templates` | `ppcart_email_templates` | filter |
| `sc_enabled_payment_gateways` | `ppcart_enabled_payment_gateways` | filter |
| `sc_enabled_processors` | `ppcart_enabled_processors` | filter |
| `sc_enqueue_admin_tax_settings` | `ppcart_enqueue_admin_tax_settings` | action |
| `sc_enqueue_frontend_assets` | `ppcart_enqueue_frontend_assets` | action |
| `sc_enqueue_public_tax_settings` | `ppcart_enqueue_public_tax_settings` | action |
| `sc_enqueue_scripts_upsell_downsell` | `ppcart_enqueue_scripts_upsell_downsell` | filter |
| `sc_export_columns` | `ppcart_export_columns` | filter |
| `sc_express_payment_method_fields` | `ppcart_express_payment_method_fields` | action |
| `sc_file_download_db_args` | `ppcart_file_download_db_args` | filter |
| `sc_format_price` | `ppcart_format_price` | filter |
| `sc_format_subscription_order_detail` | `ppcart_format_subscription_order_detail` | filter |
| `sc_frontend_assets_needed` | `ppcart_frontend_assets_needed` | filter |
| `sc_frontend_message_{$k}` | `ppcart_frontend_message_{$k}` | filter |
| `sc_fter_order_created` | `ppcart_fter_order_created` | action |
| `sc_gateway_webhook` | `ppcart_gateway_webhook` | action |
| `sc_get_sub_args` | `ppcart_get_sub_args` | filter |
| `sc_host_purchase_url` | `ppcart_host_purchase_url` | filter |
| `sc_integration_fields` | `ppcart_integration_fields` | filter |
| `sc_integration_service_action_field_logic_options` | `ppcart_integration_service_action_field_logic_options` | filter |
| `sc_integration_validation_error` | `ppcart_integration_validation_error` | filter |
| `sc_integrations` | `ppcart_integrations` | filter |
| `sc_invoice_date_documentation_url` | `ppcart_invoice_date_documentation_url` | filter |
| `sc_invoice_format` | `ppcart_invoice_format` | filter |
| `sc_is_order_complete` | `ppcart_is_order_complete` | filter |
| `sc_is_sensitive_option` | `ppcart_is_sensitive_option` | filter |
| `sc_is_sub_type_valid_for_cancel` | `ppcart_is_sub_type_valid_for_cancel` | filter |
| `sc_is_sub_type_valid_for_pause_restart` | `ppcart_is_sub_type_valid_for_pause_restart` | filter |
| `sc_js_purchase_tracking` | `ppcart_js_purchase_tracking` | action |
| `sc_load_pro_modules` | `ppcart_load_pro_modules` | action |
| `sc_login_after_{$template_name}` | `ppcart_login_after_{$template_name}` | action |
| `sc_login_before_{$template_name}` | `ppcart_login_before_{$template_name}` | action |
| `sc_low_stock_threshold` | `ppcart_low_stock_threshold` | filter |
| `sc_mailchimp_merge_data` | `ppcart_mailchimp_merge_data` | filter |
| `sc_my_account_show_order_detail_link` | `ppcart_my_account_show_order_detail_link` | filter |
| `sc_my_account_subscription_detail_after_details` | `ppcart_my_account_subscription_detail_after_details` | action |
| `sc_my_account_tab_content` | `ppcart_my_account_tab_content` | filter |
| `sc_notification_email_to` | `ppcart_notification_email_to` | filter |
| `sc_order` | `ppcart_order` | filter |
| `sc_order_add_bump_items_from_post` | `ppcart_order_add_bump_items_from_post` | action |
| `sc_order_after_apply_cart_coupon` | `ppcart_order_after_apply_cart_coupon` | action |
| `sc_order_after_apply_plan_coupon` | `ppcart_order_after_apply_plan_coupon` | action |
| `sc_order_after_primary_integration_action` | `ppcart_order_after_primary_integration_action` | action |
| `sc_order_apply_cart_coupon` | `ppcart_order_apply_cart_coupon` | filter |
| `sc_order_apply_plan_coupon` | `ppcart_order_apply_plan_coupon` | filter |
| `sc_order_apply_tax_to_item` | `ppcart_order_apply_tax_to_item` | filter |
| `sc_order_before_apply_cart_coupon` | `ppcart_order_before_apply_cart_coupon` | action |
| `sc_order_before_apply_plan_coupon` | `ppcart_order_before_apply_plan_coupon` | action |
| `sc_order_before_complete_integrations` | `ppcart_order_before_complete_integrations` | action |
| `sc_order_calculate_tax` | `ppcart_order_calculate_tax` | filter |
| `sc_order_child_of` | `ppcart_order_child_of` | filter |
| `sc_order_complete` | `ppcart_order_complete` | action |
| `sc_order_created` | `ppcart_order_created` | action |
| `sc_order_details` | `ppcart_order_details` | action |
| `sc_order_details_link` | `ppcart_order_details_link` | filter |
| `sc_order_download_shortcode_tags` | `ppcart_order_download_shortcode_tags` | filter |
| `sc_order_form_address_fields` | `ppcart_order_form_address_fields` | filter |
| `sc_order_form_fields` | `ppcart_order_form_fields` | filter |
| `sc_order_get_downsell` | `ppcart_order_get_downsell` | filter |
| `sc_order_get_upsell` | `ppcart_order_get_upsell` | filter |
| `sc_order_item_meta` | `ppcart_order_item_meta` | filter |
| `sc_order_lead` | `ppcart_order_lead` | action |
| `sc_order_load_coupon_from_post` | `ppcart_order_load_coupon_from_post` | filter |
| `sc_order_pending` | `ppcart_order_pending` | action |
| `sc_order_pre_calculate_tax` | `ppcart_order_pre_calculate_tax` | action |
| `sc_order_refund_paypal` | `ppcart_order_refund_paypal` | action |
| `sc_order_refund_{$order}{->}{pay_method}` | `ppcart_order_refund_{$order}{->}{pay_method}` | action |
| `sc_order_refunded` | `ppcart_order_refunded` | action |
| `sc_order_related_orders` | `ppcart_order_related_orders` | action |
| `sc_order_setup_purchase_note` | `ppcart_order_setup_purchase_note` | filter |
| `sc_order_setup_tax` | `ppcart_order_setup_tax` | action |
| `sc_order_store_pro_metadata` | `ppcart_order_store_pro_metadata` | action |
| `sc_order_summary` | `ppcart_order_summary` | action |
| `sc_order_summary_bump_text` | `ppcart_order_summary_bump_text` | filter |
| `sc_order_summary_coupon_text` | `ppcart_order_summary_coupon_text` | filter |
| `sc_order_summary_item_name` | `ppcart_order_summary_item_name` | filter |
| `sc_order_summary_items` | `ppcart_order_summary_items` | action |
| `sc_order_updated` | `ppcart_order_updated` | action |
| `sc_orderform_before_payment_plans` | `ppcart_orderform_before_payment_plans` | action |
| `studiocart_page_metabox` / `sc_page_metabox` | `ppcart_page_metabox` | action |
| `sc_pay_plan_fields` | `ppcart_pay_plan_fields` | filter |
| `sc_payment_confirmation` | `ppcart_payment_confirmation` | action |
| `sc_payment_intent` | `ppcart_payment_intent` | filter |
| `sc_payment_method` | `ppcart_payment_method` | filter |
| `sc_payment_method_change` | `ppcart_payment_method_change` | action |
| `sc_payment_method_fields` | `ppcart_payment_method_fields` | action |
| `sc_payment_methods` | `ppcart_payment_methods` | filter |
| `sc_paypal_after_checkout_complete` | `ppcart_paypal_after_checkout_complete` | filter |
| `sc_paypal_checkout_url` | `ppcart_paypal_checkout_url` | filter |
| `sc_paypal_class` | `ppcart_paypal_class` | filter |
| `sc_paypal_custom_payment_vars` | `ppcart_paypal_custom_payment_vars` | filter |
| `sc_paypal_payment_vars` | `ppcart_paypal_payment_vars` | filter |
| `sc_paypal_recurring_payment_data` | `ppcart_paypal_recurring_payment_data` | action |
| `sc_personalize_replacements` | `ppcart_personalize_replacements` | filter |
| `sc_plan` | `ppcart_plan` | filter |
| `sc_plan_at_checkout` | `ppcart_plan_at_checkout` | filter |
| `sc_plan_heading` | `ppcart_plan_heading` | filter |
| `sc_plan_subscription_features` | `ppcart_plan_subscription_features` | filter |
| `sc_plan_text_and_a` | `ppcart_plan_text_and_a` | filter |
| `sc_plan_text_day_free_trial` | `ppcart_plan_text_day_free_trial` | filter |
| `sc_plan_text_loading_processing` | `ppcart_plan_text_loading_processing` | filter |
| `sc_plan_text_sign_up_fee` | `ppcart_plan_text_sign_up_fee` | filter |
| `sc_plan_text_with_a` | `ppcart_plan_text_with_a` | filter |
| `sc_plugin_title` | `ppcart_plugin_title` | filter |
| `sc_post_types` | `ppcart_post_types` | filter |
| `sc_preloaded_intent_cleanup_force_archive_attempts` | `ppcart_preloaded_intent_cleanup_force_archive_attempts` | filter |
| `sc_preloaded_intent_ttl` | `ppcart_preloaded_intent_ttl` | filter |
| `sc_pricing_fields` | `ppcart_pricing_fields` | filter |
| `sc_process_payment_upsell_flow` | `ppcart_process_payment_upsell_flow` | filter |
| `sc_process_stripe_products` | `ppcart_process_stripe_products` | filter |
| `sc_product` / `ncs_product` / `studiocart_product` | `ppcart_product` | filter |
| `sc_product_archive_args` | `ppcart_product_archive_args` | filter |
| `sc_product_duplicate_excluded_meta_keys` | `ppcart_product_duplicate_excluded_meta_keys` | filter |
| `sc_product_duplicate_meta_key` | `ppcart_product_duplicate_meta_key` | filter |
| `sc_product_field_groups` | `ppcart_product_field_groups` | filter |
| `sc_product_field_scripts` | `ppcart_product_field_scripts` | filter |
| `sc_product_general_fields` | `ppcart_product_general_fields` | filter |
| `sc_product_metabox_post_type` | `ppcart_product_metabox_post_type` | filter |
| `sc_product_payments_fields` | `ppcart_product_payments_fields` | filter |
| `sc_product_post_type` | `ppcart_product_post_type` | filter |
| `sc_product_price` / `ncs_product_price` | `ppcart_product_price` | filter |
| `sc_product_print_field_scripts` | `ppcart_product_print_field_scripts` | action |
| `sc_product_save_stripe_meta` | `ppcart_product_save_stripe_meta` | action |
| `sc_product_setting_tab_fields` | `ppcart_product_setting_tab_fields` | filter |
| `sc_product_setting_tab_files_fields` | `ppcart_product_setting_tab_files_fields` | filter |
| `sc_product_setting_tab_{$tab_id}_fields` | `ppcart_product_setting_tab_{$tab_id}_fields` | filter |
| `sc_product_setting_tabs` | `ppcart_product_setting_tabs` | filter |
| `sc_product_{$tab_id}_fields` | `ppcart_product_{$tab_id}_fields` | filter |
| `sc_ransaction_id` | `ppcart_ransaction_id` | filter |
| `sc_receipt_after_order_details` | `ppcart_receipt_after_order_details` | action |
| `sc_register_importers` | `ppcart_register_importers` | action |
| `sc_register_pro_hooks` | `ppcart_register_pro_hooks` | action |
| `sc_register_public_ajax_handlers` | `ppcart_register_public_ajax_handlers` | action |
| `sc_register_sections` | `ppcart_register_sections` | action |
| `sc_renew_integrations_lists` | `ppcart_renew_integrations_lists` | action |
| `sc_renewal_failed` | `ppcart_renewal_failed` | action |
| `sc_renewal_payment` | `ppcart_renewal_payment` | action |
| `sc_renewal_uncollectible` | `ppcart_renewal_uncollectible` | action |
| `sc_repeater_saved_rows` | `ppcart_repeater_saved_rows` | filter |
| `sc_run_after_integrations` | `ppcart_run_after_integrations` | action |
| `sc_run_price_formatting` / `nsc_run_price_formatting` | `ppcart_run_price_formatting` | action |
| `sc_script_vars` | `ppcart_script_vars` | filter |
| `sc_send_new_user_email` | `ppcart_send_new_user_email` | filter |
| `sc_sensitive_option_names` | `ppcart_sensitive_option_names` | filter |
| `sc_setting_tab_pages` | `ppcart_setting_tab_pages` | filter |
| `sc_setting_tabs` | `ppcart_setting_tabs` | filter |
| `sc_settings_admin_notices` | `ppcart_settings_admin_notices` | action |
| `sc_setup_order_from_meta` | `ppcart_setup_order_from_meta` | filter |
| `sc_setup_product_display_mode` | `ppcart_setup_product_display_mode` | filter |
| `sc_setup_product_from_meta` | `ppcart_setup_product_from_meta` | filter |
| `sc_setup_product_post_type` | `ppcart_setup_product_post_type` | filter |
| `sc_setup_product_upsell_path` | `ppcart_setup_product_upsell_path` | filter |
| `sc_should_send_product_notification` | `ppcart_should_send_product_notification` | filter |
| `sc_show_optin_checkbox_services` | `ppcart_show_optin_checkbox_services` | filter |
| `sc_show_orderbump` | `ppcart_show_orderbump` | filter |
| `sc_show_stripe_payment_method` | `ppcart_show_stripe_payment_method` | action |
| `sc_show_upsell` | `ppcart_show_upsell` | filter |
| `sc_show_{$subscription_order}{->}{pay_method}_payment_method` | `ppcart_show_{$subscription_order}{->}{pay_method}_payment_method` | action |
| `sc_skip_default_order_summary` | `ppcart_skip_default_order_summary` | filter |
| `sc_states` | `ppcart_states` | filter |
| `sc_stripe_checkout_session_args` | `ppcart_stripe_checkout_session_args` | filter |
| `sc_stripe_checkout_session_completed` | `ppcart_stripe_checkout_session_completed` | action |
| `sc_stripe_connect_credentials_http_args` | `ppcart_stripe_connect_credentials_http_args` | filter |
| `sc_stripe_invoice_response` | `ppcart_stripe_invoice_response` | action |
| `sc_stripe_platform_publishable_key` | `ppcart_stripe_platform_publishable_key` | filter |
| `sc_stripe_platform_secret_key` | `ppcart_stripe_platform_secret_key` | filter |
| `sc_stripe_subscription_invoice_args` | `ppcart_stripe_subscription_invoice_args` | filter |
| `sc_stripe_subscriptions_documentation_url` | `ppcart_stripe_subscriptions_documentation_url` | filter |
| `sc_sub_details` | `ppcart_sub_details` | action |
| `sc_sub_item_id` | `ppcart_sub_item_id` | filter |
| `sc_subscription_active` | `ppcart_subscription_active` | action |
| `sc_subscription_apply_coupon` | `ppcart_subscription_apply_coupon` | action |
| `sc_subscription_cancel_credit_note_args` | `ppcart_subscription_cancel_credit_note_args` | filter |
| `sc_subscription_cancel_refund_amount` | `ppcart_subscription_cancel_refund_amount` | filter |
| `sc_subscription_canceled` | `ppcart_subscription_canceled` | action |
| `sc_subscription_completed` | `ppcart_subscription_completed` | action |
| `sc_subscription_created` | `ppcart_subscription_created` | action |
| `sc_subscription_detail_modals` | `ppcart_subscription_detail_modals` | action |
| `sc_subscription_details_link` | `ppcart_subscription_details_link` | filter |
| `sc_subscription_past_due` | `ppcart_subscription_past_due` | action |
| `sc_subscription_pause_restart` | `ppcart_subscription_pause_restart` | filter |
| `sc_subscription_paused` | `ppcart_subscription_paused` | action |
| `sc_subscription_related_orders` | `ppcart_subscription_related_orders` | action |
| `studiocart_subscription_reminder_event` / `sc_subscription_reminder_event` | `ppcart_subscription_reminder_event` | action |
| `sc_subscription_store_address_fields` | `ppcart_subscription_store_address_fields` | action |
| `sc_subscription_store_pro_metadata` | `ppcart_subscription_store_pro_metadata` | action |
| `sc_subscription_transaction_id` | `ppcart_subscription_transaction_id` | filter |
| `sc_subscription_updated` | `ppcart_subscription_updated` | action |
| `sc_subtotal_label` | `ppcart_subtotal_label` | filter |
| `sc_tab_content_tab-files` | `ppcart_tab_content_tab-files` | action |
| `sc_tab_content_{$account_tab[...]}]` | `ppcart_tab_content_{$account_tab[...]}]` | action |
| `sc_tab_content_{$tab_id}` | `ppcart_tab_content_{$tab_id}` | action |
| `sc_tax_rates` | `ppcart_tax_rates` | filter |
| `sc_taxonomies` | `ppcart_taxonomies` | filter |
| `sc-cart-taxonomy-options` | `ppcart_taxonomy_options` | filter |
| `sc_template_after_{$slug}` | `ppcart_template_after_{$slug}` | action |
| `sc_template_before_{$slug}` | `ppcart_template_before_{$slug}` | action |
| `sc_theme_template_path` | `ppcart_theme_template_path` | filter |
| `sc_trigger_order_integrations` | `ppcart_trigger_order_integrations` | filter |
| `sc_trigger_subscription_integrations` | `ppcart_trigger_subscription_integrations` | filter |
| `sc_update_stripe_invoice_during_checkout` | `ppcart_update_stripe_invoice_during_checkout` | filter |
| `ncs_upgrade` / `sc_upgrade` | `ppcart_upgrade` | action |
| `sc_use_block_product_template` | `ppcart_use_block_product_template` | filter |
| `sc_use_default_authentication_logic` | `ppcart_use_default_authentication_logic` | filter |
| `sc_valid_sub_statuses_for_cancel` | `ppcart_valid_sub_statuses_for_cancel` | filter |
| `sc_valid_sub_statuses_for_pause_restart` | `ppcart_valid_sub_statuses_for_pause_restart` | filter |
| `sc_valid_sub_statuses_for_update` | `ppcart_valid_sub_statuses_for_update` | filter |
| `sc_validate_custom_fields` | `ppcart_validate_custom_fields` | filter |
| `sc_vat_title` | `ppcart_vat_title` | filter |
| `sc_webhook_order_data` | `ppcart_webhook_order_data` | filter |
| `sc_webhook_url_type` | `ppcart_webhook_url_type` | filter |
| `ncs-cart-zero-decimal-currency` / `sc_zero_decimal_currency` | `ppcart_zero_decimal_currency` | filter |
| `sc_{$ppcart_services}_integrations` | `ppcart_{$ppcart_services}_integrations` | action |
| `sc_{$this}{->}{service_name}_integrations` | `ppcart_{$this}{->}{service_name}_integrations` | action |
| `sc_{$trigger}_integrations` | `ppcart_{$trigger}_integrations` | action |

_344 unique Cart plugin hooks from `docs/hooks-manifest.json`._

##### Dynamic public patterns

| Leftover | Canonical |
|----------|-----------|
| `_sc_{$ppcart_tab_key}_tab_section` | `_ppcart_{$ppcart_tab_key}_tab_section` |
| `sc_backend_message_{$k}` | `ppcart_backend_message_{$k}` |
| `sc_default_{$field[...]}]_field_settings_attributes` | `ppcart_default_{$field[...]}]_field_settings_attributes` |
| `sc_frontend_message_{$k}` | `ppcart_frontend_message_{$k}` |
| `sc_login_after_{$template_name}` | `ppcart_login_after_{$template_name}` |
| `sc_login_before_{$template_name}` | `ppcart_login_before_{$template_name}` |
| `sc_order_refund_{$order}{->}{pay_method}` | `ppcart_order_refund_{$order}{->}{pay_method}` |
| `sc_product_setting_tab_{$tab_id}_fields` | `ppcart_product_setting_tab_{$tab_id}_fields` |
| `sc_product_{$tab_id}_fields` | `ppcart_product_{$tab_id}_fields` |
| `sc_show_{$subscription_order}{->}{pay_method}_payment_method` | `ppcart_show_{$subscription_order}{->}{pay_method}_payment_method` |
| `sc_tab_content_{$account_tab[...]}]` | `ppcart_tab_content_{$account_tab[...]}]` |
| `sc_tab_content_{$tab_id}` | `ppcart_tab_content_{$tab_id}` |
| `sc_template_after_{$slug}` | `ppcart_template_after_{$slug}` |
| `sc_template_before_{$slug}` | `ppcart_template_before_{$slug}` |
| `sc_{$ppcart_services}_integrations` | `ppcart_{$ppcart_services}_integrations` |
| `sc_{$this}{->}{service_name}_integrations` | `ppcart_{$this}{->}{service_name}_integrations` |
| `sc_{$trigger}_integrations` | `ppcart_{$trigger}_integrations` |

##### AJAX WordPress hooks (`wp_ajax_*`)

POST **action** leftover → canonical is the Compat ajax map. Coupon /
upsell (`sc_validate_coupon` / `sc_process_upsell`) stay Pro slice **18**
`[~]`.

| Leftover | Canonical | Type |
|----------|-----------|------|
| `wp_ajax_nopriv_sc_create_checkout_session` | `wp_ajax_nopriv_ppcart_create_checkout_session` | action |
| `wp_ajax_nopriv_sc_create_payment_intent` | `wp_ajax_nopriv_ppcart_create_payment_intent` | action |
| `wp_ajax_nopriv_sc_create_setup_intent` | `wp_ajax_nopriv_ppcart_create_setup_intent` | action |
| `wp_ajax_nopriv_sc_create_subscription` | `wp_ajax_nopriv_ppcart_create_subscription` | action |
| `wp_ajax_nopriv_sc_paypal_request` | `wp_ajax_nopriv_ppcart_paypal_request` | action |
| `wp_ajax_nopriv_sc_save_order_to_db` | `wp_ajax_nopriv_ppcart_save_order_to_db` | action |
| `wp_ajax_nopriv_sc_update_cart_amount` | `wp_ajax_nopriv_ppcart_update_cart_amount` | action |
| `wp_ajax_nopriv_sc_update_payment_intent_amt` | `wp_ajax_nopriv_ppcart_update_payment_intent_amt` | action |
| `wp_ajax_nopriv_sc_update_stripe_order_status` | `wp_ajax_nopriv_ppcart_update_stripe_order_status` | action |
| `wp_ajax_ncs_ajax_action` | `wp_ajax_ppcart_ajax_action` | action |
| `wp_ajax_sc_create_checkout_session` | `wp_ajax_ppcart_create_checkout_session` | action |
| `wp_ajax_sc_create_payment_intent` | `wp_ajax_ppcart_create_payment_intent` | action |
| `wp_ajax_sc_create_setup_intent` | `wp_ajax_ppcart_create_setup_intent` | action |
| `wp_ajax_sc_create_subscription` | `wp_ajax_ppcart_create_subscription` | action |
| `wp_ajax_sc_dismissed_notice_handler` | `wp_ajax_ppcart_dismissed_notice_handler` | action |
| `wp_ajax_sc_fresh_product` | `wp_ajax_ppcart_fresh_product` | action |
| `wp_ajax_sc_get_payment_options` | `wp_ajax_ppcart_get_payment_options` | action |
| `wp_ajax_sc_json_search_user` | `wp_ajax_ppcart_json_search_user` | action |
| `wp_ajax_sc_mailchimp_groups_tags` | `wp_ajax_ppcart_mailchimp_groups_tags` | action |
| `wp_ajax_sc_migrate_secrets` | `wp_ajax_ppcart_migrate_secrets` | action |
| `wp_ajax_sc_order_refund` | `wp_ajax_ppcart_order_refund` | action |
| `wp_ajax_sc_pause_restart_subscription` | `wp_ajax_ppcart_pause_restart_subscription` | action |
| `wp_ajax_sc_paypal_request` | `wp_ajax_ppcart_paypal_request` | action |
| `wp_ajax_sc_preview_email_template` | `wp_ajax_ppcart_preview_email_template` | action |
| `wp_ajax_sc_preview_product_notification_email` | `wp_ajax_ppcart_preview_product_notification_email` | action |
| `wp_ajax_sc_product_plans` | `wp_ajax_ppcart_product_plans` | action |
| `wp_ajax_sc_renew_integrations_lists` | `wp_ajax_ppcart_renew_integrations_lists` | action |
| `wp_ajax_sc_resend_purchase_confirmation_email` | `wp_ajax_ppcart_resend_purchase_confirmation_email` | action |
| `wp_ajax_sc_reset_email_template` | `wp_ajax_ppcart_reset_email_template` | action |
| `wp_ajax_sc_save_order_to_db` | `wp_ajax_ppcart_save_order_to_db` | action |
| `wp_ajax_ncs_cart_search_report_customers` | `wp_ajax_ppcart_search_report_customers` | action |
| `wp_ajax_ncs_cart_search_report_products` | `wp_ajax_ppcart_search_report_products` | action |
| `wp_ajax_sc_send_email_test` | `wp_ajax_ppcart_send_email_test` | action |
| `wp_ajax_sc_send_product_notification_test` | `wp_ajax_ppcart_send_product_notification_test` | action |
| `wp_ajax_sc_set_encrypt_secrets` | `wp_ajax_ppcart_set_encrypt_secrets` | action |
| `wp_ajax_sc_sync_order` | `wp_ajax_ppcart_sync_order` | action |
| `wp_ajax_sc_sync_subscription` | `wp_ajax_ppcart_sync_subscription` | action |
| `wp_ajax_sc_unsubscribe_customer` | `wp_ajax_ppcart_unsubscribe_customer` | action |
| `wp_ajax_sc_update_cart_amount` | `wp_ajax_ppcart_update_cart_amount` | action |
| `wp_ajax_sc_update_payment_intent_amt` | `wp_ajax_ppcart_update_payment_intent_amt` | action |
| `wp_ajax_sc_update_stripe_order_status` | `wp_ajax_ppcart_update_stripe_order_status` | action |
| `wp_ajax_sc_update_stripe_payment_method` | `wp_ajax_ppcart_update_stripe_payment_method` | action |
| `wp_ajax_sc_update_user_profile` | `wp_ajax_ppcart_update_user_profile` | action |
| `wp_ajax_sc_check_username` / `wp_ajax_nopriv_sc_check_username` | `wp_ajax_ppcart_check_username` / `wp_ajax_nopriv_ppcart_check_username` | action |
| `wp_ajax_sc_capture_lead` / `wp_ajax_nopriv_sc_capture_lead` | `wp_ajax_ppcart_capture_lead` / `wp_ajax_nopriv_ppcart_capture_lead` | action |
| `wp_ajax_sc_validate_coupon` / `wp_ajax_sc_process_upsell` (Pro) | `wp_ajax_ppcart_validate_coupon` / `wp_ajax_ppcart_process_upsell` | action |

##### `admin_action_*` / `admin_post_*`

| Leftover | Canonical | Type |
|----------|-----------|------|
| `admin_action_sc_duplicate_product` | `admin_action_ppcart_duplicate_product` | action |
| `admin_post_sc_stripe_webhook_manual_setup` | `admin_post_ppcart_stripe_webhook_manual_setup` | action |

#### Option / post-meta (named)

Mechanical `_sc_*` → `_ppcart_*`. Named exceptions:

| Leftover | Canonical | Slice |
|----------|-----------|-------|
| `current_schedule_val` | `ppcart_*` (mechanical option map) | 13a |
| `_my_account` | `_ppcart_myaccount_page_id` | 13a / 63 |
| `_ppcart_studiocart_compatibility_mode` / `_sc_studiocart_compatibility_mode` | `_ppcart_compatibility_mode` | 56 |
| `_sc_phone` (user-meta) | `_ppcart_phone` | 13b |
| `sc_pns` / `sc_pn` / `sc_ns` / `sc_n` | `ppcart_pns` / `ppcart_pn` / `ppcart_ns` / `ppcart_n` | 37 |
| `_sc_tax_rate_title` / `_sc_tax_rate_slug` / `_sc_tax_rate` | `_ppcart_tax_rate_title` / `_ppcart_tax_rate_slug` / `_ppcart_tax_rate` | 38 |
| `sc_pro_payment_methods` / `sc_pro_coupons` / `sc_pro_order_bumps` / `sc_pro_upsell_path` / `sc_pro_shipping` / `sc_pro_affiliates` | `ppcart_pro_*` | 48 |

#### CPT / taxonomy / role / cap

| Leftover | Canonical | Slice |
|----------|-----------|-------|
| `sc_product` | `ppcart_product` | 12 |
| `sc_order` | `ppcart_order` | 12 |
| `sc_subscription` | `ppcart_subscription` | 12 |
| `sc_product_cat` / `sc_product_tag` | `ppcart_product_cat` / `ppcart_product_tag` | 12 / 28 |
| `sc_collection` | `ppcart_collection` | 28 |
| `sc_us_path` | `ppcart_us_path` | 28 |
| `sc_membership` | `ppcart_membership` | 28 |
| `sc_upgrade_path` | `ppcart_upgrade_path` | 28 |
| `single-sc_product` | `single-ppcart_product` | 28 |
| `sc_cart_manager` | `ppcart_manager` | 15 |
| `sc_cart_administrator` | `ppcart_administrator` | 15 |
| `sc_manager_option` | `ppcart_manager_option` | 15 |
| `sc_manage_orders` | `ppcart_manage_orders` | 15 |
| `edit_sc_product` / `read_sc_product` / `delete_sc_product` / `edit_sc_products` / `publish_sc_products` / … | `edit_ppcart_product` / … | 15 |

#### Custom table / filename / asset handle

| Leftover | Canonical | Slice |
|----------|-----------|-------|
| `{prefix}ncs_tax_rate` | `{prefix}ppcart_tax_rate` | 16 |
| `{prefix}ncs_order_items` | `{prefix}ppcart_order_items` | 16 |
| `{prefix}ncs_order_itemmeta` | `{prefix}ppcart_order_itemmeta` | 16 |
| metadata type `ncs_order_item` / column `ncs_order_item_id` | `ppcart_order_item` / `ppcart_order_item_id` | 16 |
| `{prefix}ncs_downloads` | `{prefix}ppcart_downloads` | 16 |
| `class-ncs-cart-*.php` / `ncs-cart-*.php` / `sc-*.php` | `class-ppcart-*.php` / `ppcart-*.php` | filenames |
| `ncs-cart-admin` / `ncs-cart-settings` / `ncs-cart-orders` / `ncs-cart-daterangepicker` / `ncs-cart-debug-log-viewer` / `ncs-cart-stripe-webhook-log` / `ncs-my-account` | `ppcart-admin` / `ppcart-settings` / `ppcart-orders` / `ppcart-daterangepicker` / `ppcart-debug-log-viewer` / `ppcart-stripe-webhook-log` / `ppcart-my-account` | 8 / 9 |
| `ncs-cart-kit-*` / `ncs-cart-googlerecaptcha-*` | `ppcart-kit-*` / `ppcart-googlerecaptcha-*` | 6 |
| `studiocart.{svg,eot,woff,woff2,ttf}` | `ppcart-icons.*` | 8 |
| `import=ncs-cart_tax_rate_csv` | `import=ppcart_tax_rate_csv` | 67 |
| `sample_sc_tax_rates.csv` | `sample_ppcart_tax_rates.csv` | 80 |

#### AJAX action / nonce

| Leftover | Canonical | Slice |
|----------|-----------|-------|
| `ncs_ajax_action` | `ppcart_ajax_action` | 11 / 61 |
| `ncs_ajax_nonce` | `ppcart_ajax_nonce` | 11 / 61 |
| `sc_create_checkout_session` | `ppcart_create_checkout_session` | 11 |
| `sc_create_payment_intent` | `ppcart_create_payment_intent` | 11 |
| `sc_create_setup_intent` | `ppcart_create_setup_intent` | 11 |
| `sc_create_subscription` | `ppcart_create_subscription` | 11 |
| `sc_dismissed_notice_handler` | `ppcart_dismissed_notice_handler` | 11 |
| `sc_fresh_product` | `ppcart_fresh_product` | 11 |
| `sc_get_payment_options` | `ppcart_get_payment_options` | 11 |
| `sc_json_search_user` | `ppcart_json_search_user` | 11 |
| `sc_mailchimp_groups_tags` | `ppcart_mailchimp_groups_tags` | 11 |
| `sc_migrate_secrets` | `ppcart_migrate_secrets` | 11 |
| `sc_order_refund` | `ppcart_order_refund` | 11 |
| `sc_pause_restart_subscription` | `ppcart_pause_restart_subscription` | 11 |
| `sc_paypal_request` | `ppcart_paypal_request` | 11 |
| `sc_preview_email_template` | `ppcart_preview_email_template` | 11 |
| `sc_preview_product_notification_email` | `ppcart_preview_product_notification_email` | 11 |
| `sc_product_plans` | `ppcart_product_plans` | 11 |
| `sc_renew_integrations_lists` | `ppcart_renew_integrations_lists` | 11 |
| `sc_resend_purchase_confirmation_email` | `ppcart_resend_purchase_confirmation_email` | 11 |
| `sc_reset_email_template` | `ppcart_reset_email_template` | 11 |
| `sc_save_order_to_db` | `ppcart_save_order_to_db` | 11 |
| `ncs_cart_search_report_customers` | `ppcart_search_report_customers` | 11 |
| `ncs_cart_search_report_products` | `ppcart_search_report_products` | 11 |
| `sc_send_email_test` | `ppcart_send_email_test` | 11 |
| `sc_send_product_notification_test` | `ppcart_send_product_notification_test` | 11 |
| `sc_set_encrypt_secrets` | `ppcart_set_encrypt_secrets` | 11 |
| `sc_sync_order` / `sc_sync_subscription` | `ppcart_sync_order` / `ppcart_sync_subscription` | 11 |
| `sc_unsubscribe_customer` | `ppcart_unsubscribe_customer` | 11 |
| `sc_update_cart_amount` | `ppcart_update_cart_amount` | 11 |
| `sc_update_payment_intent_amt` | `ppcart_update_payment_intent_amt` | 11 |
| `sc_update_stripe_order_status` | `ppcart_update_stripe_order_status` | 11 |
| `sc_update_stripe_payment_method` | `ppcart_update_stripe_payment_method` | 11 |
| `sc_update_user_profile` | `ppcart_update_user_profile` | 11 |
| `sc_check_username` | `ppcart_check_username` | 30 |
| `sc_capture_lead` | `ppcart_capture_lead` | 30 |
| `sc_validate_coupon` / `sc_process_upsell` (Pro) | `ppcart_validate_coupon` / `ppcart_process_upsell` | 18 `[~]` |
| `sc-ajax-nonce` / `sc_purchase_nonce` / `sc-cart` | `ppcart_ajax_nonce` / `ppcart_purchase_nonce` / `ppcart_cart` | 11 |
| `studiocart_downsell-` / `studiocart_upsell-` / `sc_duplicate_product_` | `ppcart_downsell-` / `ppcart_upsell-` / `ppcart_duplicate_product_` | 11 |

#### Query var / admin slug / REST

| Leftover | Canonical | Slice |
|----------|-----------|-------|
| `sc-preview` / `sc-order` / `sc-plan` / `sc-manage` / `sc-download` | `ppcart-preview` / `ppcart-order` / `ppcart-plan` / `ppcart-manage` / `ppcart-download` | 10 |
| unprefixed `coupon` / `plan` (not leftover `sc-*`) | `ppcart-coupon` / `ppcart-pay-plan` | #777 |
| `sc-csv-export` | `ppcart-csv-export` | 40 |
| `sc-webhook` / `sc-api` / `sc-invoice` | `ppcart-webhook` / `ppcart-api` / `ppcart-invoice` | 43 |
| `sc/v1` | `publishpress-cart/v1` | 29 |
| `studiocart` (menu) | `ppcart` | 9 |
| `sc-admin` | `ppcart-settings` | 9 |
| `ncs-cart-reports` | `ppcart-reports` | 9 |
| `ncs-cart-customer-reports` | `ppcart-customer-reports` | 9 |
| `ncs-cart-contacts-page` | `ppcart-contacts` | 9 |
| `ncs-cart-extensions-page` | `ppcart-extensions` | 9 |
| `sc-white-label` | `ppcart-white-label` | 9 |
| `sc_affiliate_dashboard_callback` | `ppcart-affiliates` | 9 |
| `studiocart-compatibility-mode` | `ppcart-compatibility-mode` | 56 |
| `sc-products-shortcode/product-shortcode` | `publishpress-cart/checkout-form` | 60 |

#### HTML / JS / global / request / Stripe / bulk

| Leftover | Canonical | Slice |
|----------|-----------|-------|
| `id="sc_accept_terms"` / `sc_accept_privacy` / `sc_consent` | `id="ppcart_accept_terms"` / … | 19 |
| `id="sc_card_button"` / `sc_update_card_button` / `sc_payment_method` | `id="ppcart_*"` | 19 |
| `class="sc-row"` / `sc_button` / `.sc-plan-id-copy-icon` | `ppcart-row` / `ppcart_button` / `.ppcart-plan-id-copy-icon` | 19 |
| `#_sc_currency` / `rid_sc_break` / `_sc_bump_bg_color` / `repeater_sc_*` | `_ppcart_*` / `rid_ppcart_*` / `repeater_ppcart_*` | 25b |
| `sc-product-settings` / `sc-edit-order-details` / `sc-order-notes` / `sc-product` | `ppcart-*` | 25b |
| `tmpl-sc-tax-table-row` | `tmpl-ppcart-tax-table-row` | 19 |
| `studiocart` (`wp_localize_script`) | `ppcart` | 21 |
| `sc_translate_frontend` / `sc_translate_backend` | `ppcart_translate_frontend` / `ppcart_translate_backend` | 21 |
| `sc_currency` / `sc_user` / `sc_popup` / `window.sc_coupon` | `ppcart_currency` / `ppcart_user` / `ppcart_popup` / `window.ppcart_coupon` | 21 |
| `sc_mc_tags` / `sc_mc_groups` / `sc_admin_i18n` / `sc_reg_vars` | `ppcart_mc_*` / `ppcart_admin_i18n` / `ppcart_reg_vars` | 21 |
| `ncsCartSettingsI18n` / `ncsCartReports` / `ncsCartNotificationI18n` | `ppcartSettings*` / `ppcartReports` / `ppcartNotificationI18n` | 21 |
| `studiocart/orderform/*` | `ppcart/orderform/*` | 39 |
| `is_sc_checkout` / `sc_oto_get` / `search_sc_user` | `is_ppcart_checkout` / `ppcart_oto_get` / `search_ppcart_user` | 45 |
| `$sc_stripe` / `$sc_currency` / `$sc_currency_symbol` / `$sc_debug_logger` / `$scp` | `$ppcart_stripe` / `$ppcart_currency` / `$ppcart_currency_symbol` / `$ppcart_debug_logger` / `$ppcart_product` | 3 |
| `$GLOBALS['sc_checkout_request_rendered']` / `sc_checkout_block_arrangement` | `$GLOBALS['ppcart_checkout_*']` | 22 |
| `$studiocart` / `$scFiles` / `$sc_product_fields` / `$sc_is_studiocart_admin_screen` | `$ppcart_public` / `$ppcart_files` / `$ppcart_product_fields` / `$ppcart_is_admin_screen` | 31 |
| `studiocart_dashboard_widget` | `ppcart_dashboard_widget` | 23 |
| `sc_hosted_session_*` / `sc_stripe_evt_*` / `sc_paypal_ipn_*` / … | `ppcart_*` | 24 |
| `metadata.sc_product_id` / `sc_order_id` / `sc_subscription_id` / `sc_option_id` / `sc_stripe_subscription_id` | `metadata.ppcart_*` | 27 |
| `sc-nonce` / `sc_accept_terms` / `sc_product_id` / `sc-orderbump[]` / … | `ppcart-nonce` / `ppcart_accept_terms` / `ppcart_product_id` / `ppcart-orderbump[]` / … | 26 |
| `sc_view_log` / `sc_view_debug_log_nonce` / `sc_view_stripe_webhook_log` | `ppcart_view_log` / `ppcart_*_nonce` / `ppcart_view_stripe_webhook_log` | 33 |
| `sc_qty` | `ppcart_qty` | 35 |
| `sc-revoke` / `sc-revoked` | `ppcart-revoke` / `ppcart-revoked` | 71 |
| `sc-slm-order` | `ppcart-slm-order` | 62 |
| `sc_make_*` / `sc_sync_stripe` / `bulk_sc_*` | `ppcart_make_*` / `ppcart_sync_stripe` / `bulk_ppcart_*` | 32 |
| `sc_extension_notice` / `sc_price_formatted` | `ppcart_extension_notice` / `ppcart_price_formatted` | 47 |
| `sc_sub_prod_id` / `sc_sub_plan_id` / `sc_sub_cancel` | `ppcart_sub_prod_id` / `ppcart_sub_plan_id` / `ppcart_sub_cancel` | 36 |
| `sc_tax_settings` / `var ncs` | `ppcart_tax_settings` / `ppcart` localize | 61 |
| `{studiocart}` | `{publishpress_cart}` | 51 |
| `yourtheme/studiocart/` | `yourtheme/publishpress-cart/` | 42 |
| `wp-content/uploads/sc-uploads` | `wp-content/uploads/ppcart-uploads` | 73 |

Post-57 leftover | canonical rows live in
[Remaining leftovers (58–69)](#remaining-leftovers-58-69),
[Remaining leftovers (70–73)](#remaining-leftovers-70-73),
[Remaining leftovers (74–76)](#remaining-leftovers-74-76),
[Remaining leftovers (77)](#remaining-leftovers-77),
[Remaining leftovers (78)](#remaining-leftovers-78), and
[Remaining leftovers (79–81)](#remaining-leftovers-79-81).

### First-party vs leftover storage

This is the live contract for remaining slices. Do not revive mixed leftover /
canonical support in Free or Pro first-party PHP.

| Surface | First-party (Free and Pro) | Compatibility Mode | Merchant Data migration |
|---------|----------------------------|--------------------|-------------------------|
| Post-meta / user-meta | Save and read `_ppcart_*` only. No leftover POST keys, no leftover metabox hydration, no leftover `$this->meta['_sc_*']` lookups. | Leftover **names** (`get_post_meta( $id, '_sc_*' )`) via metadata API bridges. | Leftover **rows** rename to `_ppcart_*`. Canonical wins if both exist. |
| Options | Write `_ppcart_*` / `ppcart_*`. | Leftover PHP **names** via option bridges. | Copy-on-read already copies leftover option rows (slice 13a). |
| Shortcode and email tags | First-party `add_shortcode()` / emit / detect `ppcart_*`; email uses `{publishpress_cart}`. | Leftover shortcode registration and `{studiocart}` merge-tag replacement only while Compatibility Mode is on. | Leftover tags inside posts and email HTML rewrite to canonical through the explicit migration (`includes/compat/shortcode-tag-migration/`). |
| WP_Query / SQL meta keys | Canonical `_ppcart_*`. | Include leftover query keys only while the mode is on and meta migration is incomplete (`ppcart_query_meta_keys()`). | After complete, query canonical only. |

Unmigrated leftover-only post-meta rows are not a first-party read path. Third
parties that still pass `_sc_*` names need Compatibility Mode on, or the
merchant must run Data migration.

Free `ppcart_get_post_meta()` / twins are canonical-only. Leftover `_sc_*`
read-through, query expansion, and product-duplicate rewrite live only in
publishpress-cart-compat. Do not add leftover fallbacks in Free or Pro
first-party code.

---

## How to run a Free slice

Post-42 leftover families (43+): one family via
`.agents/skills/prefix-leftover-slice/`.
Remaining queue: `.agents/skills/prefix-leftover-orchestrator/`.
Open follow-ups: [Open follow-ups](#open-follow-ups-leftover-slices).
Kind map: [Leftover → canonical by kind](#leftover--canonical-by-kind).
Identifiers: [Leftover → canonical identifiers](#leftover--canonical-identifiers).
Inventory: [Post-42 leftovers](#post-42-leftovers-43),
[Remaining leftovers (58–69)](#remaining-leftovers-58-69) (done),
[Remaining leftovers (70–73)](#remaining-leftovers-70-73) (done),
[Remaining leftovers (74–76)](#remaining-leftovers-74-76) (done),
[Remaining leftovers (77)](#remaining-leftovers-77) (done),
[Remaining leftovers (78)](#remaining-leftovers-78) (done), and
[Remaining leftovers (79–81)](#remaining-leftovers-79-81).

Silent migrate is always denied. Any migration should be on the compat plugin.
Dual-register, dual-reading, only in the compat plugin also. Free plugin is a
complete new and clean plugin. The only exception is keep the studiocart
conflict checker at the beginning of the plugin.

1. Leftover names belong in Compat only. Never dual-read or silent-migrate in Cart.
2. Rename first-party code to `PPCart_` / `ppcart_` / `PPCART_` / `ppcart-`.
3. If the slice touches a template or stylesheet, rename leftover HTML `id`,
   `class`, `for`, matching JS/CSS selectors, and form `name=` (slice 26) in
   the **same slice**. See [Markup, CSS, and DOM selectors](#markup-css-and-dom-selectors).
4. If it was a shipped StudioCart PHP API, add the shim only in
   `publishpress-cart-compat` Compatibility Mode.
5. If the slice touches post-meta or user-meta, keep first-party get/update/
   delete/query and form field ids on `_ppcart_*`. Do not add leftover
   fallbacks. See [First-party vs leftover storage](#first-party-vs-leftover-storage).
6. Grep the old name is gone outside Compat (and tests/docs as needed).
7. Relevant catalog tests first, then Cart `composer test:all:mocked`, then
   Compat `composer test:all` (always both full suites).
8. Slice commit after both suites pass; docs commit for trackers.
9. Check off the slice below. Update [prefix-pro.md](prefix-pro.md) (see
   **Keep the Pro contract in sync**). A Free prefix slice is not done until
   the Pro contract matches the new names.

Do **not** reopen completed families below.

One identifier family per commit. Commit subject:
`refactor(prefix): rename <family> to ppcart (#629)`. Follow-ups that are not
a new family use `fix(prefix):` (or `fix(compat):` if it is the Compatibility
Mode package). `docs(prefix):` only for tracker updates.

### Keep the Pro contract in sync

Every Free prefix slice, follow-up `fix(prefix):` / `fix(compat):`, and
tracker-only `docs(prefix):` commit must land a matching edit in
[prefix-pro.md](prefix-pro.md). Do not defer it to “when we start Pro.” Stale
contract rows become wrong Pro PRs.

All section names below refer to **prefix-pro.md**, not this file.

In the same session:

1. Append the Free commit subject to **Free branch log** (`Pro?` yes/no plus
   a one-line note). Follow-up fixes go in the follow-ups list under that
   table, not as a new rename family.
2. If canonical names, maps, slugs, AJAX, or hooks changed, update **Current
   Free/Pro contract** so Pro still copies live Free names.
3. If the leftover is Pro-facing (constants Pro defines, JS Pro still posts,
   admin slugs Pro registers), update **Coordinated Free work** and slice 18
   notes.
4. If the family is new or split (as AJAX vs nonces were), add or split a row
   in **Suggested Pro slice order**.
5. Drop or rewrite any sentence that says Pro still depends on the *old*
   Free name when Free no longer uses it.
6. Add or update the family entry under **Per-slice Pro contract** so Pro can
   be refactored the same way as Free (canonical `PPCart_` / `ppcart_` /
   `PPCART_`; leftover names only via Compat).
7. Update [pro-plugin-extraction.md](pro-plugin-extraction.md) only when the
   slice changes the Free/Pro *architecture* (a hook Pro bootstraps on, a
   module boundary) — not for renames.

Skip prefix-pro.md only for Free-only chores that do not change identifiers
(Playwright glyphs, Docker, `.cursorignore`). When unsure, update it.

Workers record slice status here (family checkbox and this
follow-ups table) and in prefix-pro.md (**Free branch log**). Do not keep a second leftover tracker. Process and the
report template: leftover-slice skill.

### Open follow-ups (leftover slices)

Durable optional work and known bugs. Not the leftover queue. Orchestrator
warns about this table at end of queue. Do not dump per-slice test diaries
into chat.

| Slice | Follow-up |
|-------|-----------|
| 58 | Optional `esc_attr()` on literal `ppcart-splitin-form` echo in `checkout1.php` |
| 58 | Low — dead `includes/compat/` skip in scanner copied from HtmlIdsTest |
| 71 | Catalog “canonical wins when both keys are set” is true in the copy helper but not asserted in IT-360 |
| 71 | `order-downloads-metabox.php` appends `&ppcart-revoke=` after `wp_nonce_url(get_edit_post_link())` (default action `-1`) while the handler verifies `update-post_{id}` — pre-existing nonce mismatch vs product-form link |

---

## Already done (do not redo)

| Slice | Notes |
|-------|--------|
| Classes | Canonical `PPCart_*`. `NCS_Cart_*` / `Scrt*` / `SC_*` are Compatibility Mode aliases. |
| PHP filenames | `class-ppcart-*.php` (no remaining `ncs-cart-*` PHP files outside the package). |
| Methods | Canonical first-party methods. Leftover `sc_` method names (`save_post_sc_order`, `get_sc_*`, `update_sc_*_amount`, …) were renamed in slice **20**. No Compatibility Mode method aliases. |
| Options | Canonical `_ppcart_*` / `ppcart_*` rows in `wp_options`. Leftover `_sc_*` / `sc_*` rows are read through in Compat and move only through explicit Data migration. API bridges when Compatibility Mode is on. Merchant toggle `_ppcart_compatibility_mode`; historical toggle rows stay in place. |
| Hooks | Canonical `ppcart_*` / `_ppcart_*` and plugin-identity `ppcart-*` with Compatibility Mode bridges, including shipped `ncs-cart-*` names. CPT-derived hook **tags** follow the live helper after Data migration. Leftover `admin_action_sc_duplicate_product` dual-registers in Compatibility Mode (slice **26**). Admin-screen hooks use `ppcart_page_*` only. |
| CSS / HTML IDs | Hard cutover: first-party `id` / `class` / `for` and matching JS/CSS use `ppcart_` / `ppcart-` / `_ppcart_*` / `rid_ppcart_*` / `repeater_ppcart_*` (slices 19 + **25b**). Settings CSS custom properties are `--ppcart-*` (not `--pp-cs-*`). Form `name=` is canonical (slice **26**). Compatibility Mode CSS covers leftover CPT **body** classes while the toggle is on. |
| Admin icon font | Family, filenames, SVG id, and `@font-face` URLs use `ppcart-icons`. |
| Constants | Canonical `PPCART_*`. Legacy `NCS_CART_*` / `SC_*` only when Compatibility Mode is on. |
| Functions | Canonical `ppcart_*()`. Compat-package wrappers (~324) stay on purpose. |
| Bootstrap functions | Canonical `ppcart_activate`, `ppcart_deactivate`, `ppcart_upgrade`, `ppcart_run`. Legacy `*_ncs_cart` names only when Compatibility Mode is on. |
| Compatibility Mode class | Canonical `PPCart_Studiocart_Compatibility_Mode`. No alias — `NCS_Cart_Studiocart_Compatibility_Mode` was never released. Settings API page is canonical `ppcart` only. |
| Runtime globals | Canonical `$ppcart_stripe`, `$ppcart_currency`, `$ppcart_currency_symbol`, `$ppcart_debug_logger`, `$ppcart_product`, `$ppcart_checkout_request_rendered`, `$ppcart_checkout_block_arrangement`, `$ppcart_public`, `$ppcart_files`, `$ppcart_product_fields`, `$ppcart_is_admin_screen`. Compatibility Mode aliases `$sc_stripe`, `$sc_currency`, `$sc_currency_symbol`, `$sc_debug_logger`, `$scp`, leftover `$GLOBALS['sc_checkout_*']`, leftover `$studiocart`, leftover `$scFiles`, leftover `$sc_product_fields`, leftover `$sc_is_studiocart_admin_screen`. |
| Plugin identity | Runtime `PPCart::$plugin_name` is `ppcart`; text domain/public slug stay `publishpress-cart`. wp-admin slugs use the canonical `ppcart` family only. |
| Local variables | Canonical `$ppcart_*` bootstrap locals, `$__ppcart_template_result` include wrappers, former cosmetic `$sc_*` / `$posted_sc_*` / `$request_sc_*` / `$query_sc_*` locals (slice **41**), `$ncs_*` table/bootstrap locals (slice **68**), and glued `$scorder` / `$scsub` / `$scrt_order` locals (slice **78**). No Compatibility Mode shim. |
| Integration templates | Canonical `ppcart-kit-*` / `ppcart-googlerecaptcha-*`. No Compatibility Mode shim. |
| AJAX actions / nonces | Canonical `wp_ajax_ppcart_*` and `ppcart_*` nonce actions. Compatibility Mode dual-registers shipped `sc_*` / `ncs_*` POST actions (including Pro-owned username/lead from slice **30**) and accepts mapped legacy nonce strings. Request **field** names are slice **26**. |
| Testids / Playwright / CI slugs | `data-testid` values, Playwright selectors, and admin-workflow fixture slugs use `ppcart-*`. Not a PHP API; no Compatibility Mode shim. |
| Admin menu / screen IDs | Hard cutover to `ppcart` / `ppcart-*` / `ppcart_page_*`. No redirects or Compatibility Mode bridge. |
| Public query vars | Canonical `ppcart-*` routes including `ppcart-coupon` / `ppcart-pay-plan` (#777; distinct from account `ppcart-plan`). Compatibility Mode registers leftover `sc-*` vars and 302-redirects safe GET/HEAD requests. Leftover `sc-csv-export` 302s to `ppcart-csv-export` when Compat is on (slice **40**). Leftover `sc-api` / `sc-invoice` 302; leftover `/sc-webhook/` rewrite and POST `sc-api` copy onto `ppcart-api` (slice **43**). |
| CPT / taxonomy / roles / caps | Opt-in Data migration plus live helpers for Free product/order/subscription. Hardcoded leftover CPT/taxonomy **strings** (including Pro-owned slugs, FSE template, JS body class, and taxonomy rewrite) were renamed in slice **28**. |
| Post-meta / user-meta | Canonical `_ppcart_*` writes and reads. First-party helper call sites pass the suffix (slice 25a). Leftover `_sc_*` **names** are Compatibility Mode metadata bridges. Leftover **rows** move in merchant Data migration. First-party must not dual-read leftover rows or leftover POST/metabox keys. |
| On-disk debug log directory | Keep `{uploads}/publishpress-cart/logs` (`wp_upload_dir()['basedir']`; override `PPCART_DEBUG_LOG_DIR`). Slice 17. Not a rename. No filesystem shim. UT-221. |
| Download upload directory | Done family **73**. First-party `wp-content/uploads/ppcart-uploads`. Leftover `sc-uploads` is Compat extra root only. No silent file move. |
| Theme override directory | Canonical `yourtheme/publishpress-cart/` (slice **42**). Leftover `yourtheme/studiocart/` copies resolve only while Compatibility Mode is on. |
| JS localize / `window.*` | Canonical `ppcart` / `ppcart_reg_vars` / `ppcart_translate_*` / `ppcartSettings*` / `ppcartReports` / `ppcartNotificationI18n` / `ppcart_popup` / `window.ppcart_coupon`. No Compatibility Mode JS aliases. |
| Transients | Canonical `ppcart_*`. Leftover `_transient_sc_*` rows copy onto canonical keys at boot (slice 13a). Compatibility Mode leftover `get_transient('sc_*')` names write/read canonical. |
| Dashboard widget id | Canonical `ppcart_dashboard_widget` (slice 23). Hard cutover; no Compatibility Mode leftover id. |
| Request field names | Canonical form `name=` / POST / GET keys (slice 26 + **33** + **35**). Compatibility Mode copies leftover keys onto canonical when on. Leftover `admin_action_sc_duplicate_product` dual-registers when on. WordPress `action` is not mapped. POST `ppcart_qty` (leftover `sc_qty` copy when Compat on). Bulk list-table slugs are slice **32** (hard cutover). |
| Stripe object metadata | Canonical `metadata.ppcart_*` writes and reads (slice 27). Compatibility Mode reads leftover `sc_*` keys when on. Product save stamps canonical when missing. Webhooks/sync leftover-only match only while Compat is on. |
| CPT / taxonomy source strings | Canonical `ppcart_*` constants, FSE `single-ppcart_product`, taxonomy rewrite source `ppcart_product_*`. Live helpers stay row-state-based. Pro-owned CPT queries include leftover from maps. Leftover FSE/rewrite aliases when Compat is on (slice 28). |
| REST namespace | Canonical `publishpress-cart/v1` (Gutenberg). Leftover `sc/v1` is unregistered in Free with Compat off and on (slice 29 hard cutover). No Free developer-REST controllers. Pro later registers developer REST on `publishpress-cart/v1` only. |

---

## Remaining Free slices

**Contract.** First-party Free uses only `PPCart_` / `ppcart_` / `_ppcart_` /
`PPCART_` / `ppcart-`. Leftover StudioCart / `sc_*` / `ncs-*` / `studiocart`
**names** live only in `includes/compat/studiocart-compatibility-mode/`.
**Compat off:** leftover Pro AJAX names (`sc_check_username` /
`sc_capture_lead`) do **not** work (slice **30**; Free JS posts canonical).
Leftover REST `sc/v1` does not work in Free with Compat
off or on (slice **29** hard cutover). **Compat on:** leftover
names work, implemented only in that package. Data-migration packages under
`includes/compat/*-migration/` keep leftover **maps** as the migrate-from side.
First-party callers use helpers (`ppcart_live_post_type()`, `ppcart_meta_key()`),
never hardcoded leftover fallbacks like `: 'sc_product'`. Tests that assert
Compat-on leftover behavior stay; first-party tests use canonical names.

Post-30 first-party leftovers: [Post-30 leftovers](#post-30-leftovers-31-42)
and [Post-42 leftovers](#post-42-leftovers-43). Families **31–57** are done
except **18** (`[~]`). Families **58–81** are done —
[Remaining leftovers (58–69)](#remaining-leftovers-58-69),
[Remaining leftovers (70–73)](#remaining-leftovers-70-73),
[Remaining leftovers (74–76)](#remaining-leftovers-74-76),
[Remaining leftovers (77)](#remaining-leftovers-77),
[Remaining leftovers (78)](#remaining-leftovers-78), and
[Remaining leftovers (79–81)](#remaining-leftovers-79-81). Bulk
`sc_make_*` is done (slice **32**, hard cutover). Log-viewer `sc_view_log` /
nonce fields are done (slice **33**, Compat GET/nonce copy). Integration
trigger tags are done (slice **34**). Checkout leftovers are done (slice **35**,
Compat `sc_qty` copy). CSV export is done (slice **40**, Compat `sc-csv-export`
302). Checkout JS functions and `ppcart/orderform/*` events are done
(slice **39**, hard cutover). Integration repeater ids are done (slice **36**).
Invoice format values are done (slice **37**, leftover `sc_*` codes when Compat on).
Tax-rate inner keys are done (slice **38**, leftover `_sc_*` copy when Compat on).

### PHP identifiers (shimable)

Do these first. They are WordPress.org uniqueness leaks and do not require a
database migration.

- [x] **1. Bootstrap functions** in `publishpress-cart.php`
  - Canonical: `ppcart_activate`, `ppcart_deactivate`, `ppcart_upgrade`, `ppcart_run`
  - Compatibility Mode wrappers: `activate_ncs_cart`, `deactivate_ncs_cart`, `upgrade_ncs_cart`, `run_ncs_cart`

- [x] **2. Compatibility Mode class** (called “set 10” in issue 629)
  - Canonical: `PPCart_Studiocart_Compatibility_Mode`
  - No `class_alias` — `NCS_Cart_Studiocart_Compatibility_Mode` was never released
  - Settings API page: canonical `ppcart` only after slice 4 removed the temporary bridge

- [x] **3. Runtime globals**
  - Canonical: `$ppcart_stripe`, `$ppcart_currency`, `$ppcart_currency_symbol`, `$ppcart_debug_logger`, `$ppcart_product`
  - Compatibility Mode aliases: `$sc_stripe`, `$sc_currency`, `$sc_currency_symbol`, `$sc_debug_logger`, `$scp`

- [x] **4. Plugin identity strings**
  - Runtime `PPCart::$plugin_name` is `ppcart`; text domain/public slug stay `publishpress-cart`
  - Wrong `'ncs-cart'` translation/identity arguments were corrected in:
    - `public/templates/functions/checkout-core.php`
    - `admin/partials/ppcart-admin-field-repeater.php`
    - `admin/partials/ppcart-admin-field-default-fields.php`
    - `admin/controllers/order/templates/product-form.php`
    - `admin/controllers/order/templates/product-info-metabox.php`
  - Currency filters are canonical `ppcart_currencies`, `ppcart_currency_symbols`, and `ppcart_zero_decimal_currency`; Compatibility Mode maps their shipped `ncs-cart-*` names
  - Dynamic plugin-identity hooks now fire as `ppcart-*`; Compatibility Mode bridges generic `ncs-cart-*` names in both directions
  - The temporary `SETTINGS_PAGE_LEGACY` bridge is removed; the Compatibility section registers only on the `ppcart` Settings API page
  - The wp-admin slugs were initially decoupled from runtime plugin identity; slice 9 later replaced them with canonical `ppcart` slugs
  - `PPCart::$prefix` later became `ppcart_` in slice 13b

- [x] **5. Local `$ncs_cart_*` variables**
  - Mechanical rename to `$ppcart_*`
  - Includes `publishpress-cart.php` (`$ppcart_lib_vendor_path`, …)
  - Includes `$__ppcart_template_result` (~560 trait-template includes)
  - No production shim; tests only if assertions mention the old names

- [x] **6. Integration template filenames**
  - Canonical: `includes/integrations/templates/ppcart-*.php`
  - Kit and reCAPTCHA includes updated; no Compatibility Mode shims
  - Mechanical prefix swap preserved historical suffixes (`ppcart-kit-get-ppcart-*`, `converkit` typo, `---construct`)

- [x] **7. Test-only helpers** (no production aliases)
  - Canonical helper class/file: `PPCartBlockTestConfirmationPublic`
  - Canonical test post type: `ppcart_filter_prod`
  - Legacy fixture internals use `ppcart_block_test_*`, `ppcart_stripe_sync_*`, `$ppcart_test_*`, and `PPCart_Test_*`
  - Test fixture registries, form actions, users, titles, and temporary paths use `ppcart_` / `ppcart-` / PublishPress Cart naming
  - Playwright smoke sources use `[PublishPress_Cart]_Smoke_-_TC-*.json` filenames and `[PublishPress Cart] Smoke` titles

- [x] **8. Last CSS / font leftovers**
  - Renamed `admin/font/studiocart.{svg,eot,woff,woff2,ttf}` to `ppcart-icons.*` and updated the font family, SVG id, and `@font-face` URLs
  - The extension screen selector moved to `.ppcart_page_ppcart-extensions` in slice 9
  - Leftover `sc_*` / `studiocart` CSS selectors for leftover CPT **body**
    classes live in `includes/compat/studiocart-compatibility-mode/css/`.
    Core CSS uses canonical `ppcart_*` CPT body classes. Template `id` /
    `class` leftovers were renamed in slice 19; Compat inner-class rules
    were retargeted to `ppcart_*` / `ppcart-*`.

### URL and admin screens

Admin slugs have an approved hard cutover. Stop and confirm before changing
the remaining public query/rewrite values because existing links break.

- [x] **9. Admin menu and screen IDs**
  - Hard cutover: canonical slugs only; no redirects, dual registration,
    legacy slug recognition, or Compatibility Mode bridge
  - Top-level: `studiocart` → `ppcart`
  - Settings: `sc-admin` → `ppcart-settings`
  - Reports: `ncs-cart-reports` → `ppcart-reports`
  - Customer reports: `ncs-cart-customer-reports` → `ppcart-customer-reports`
  - Contacts: `ncs-cart-contacts-page` → `ppcart-contacts`
  - Extensions: `ncs-cart-extensions-page` → `ppcart-extensions`
  - White label: `sc-white-label` → `ppcart-white-label`
  - Affiliates: `sc_affiliate_dashboard_callback` → `ppcart-affiliates`
  - Hook prefixes: `studiocart_page_` → `ppcart_page_`;
    `toplevel_page_studiocart` → `toplevel_page_ppcart`

- [x] **10. Public query vars and rewrites**
  - Canonical core map: `ppcart-preview`, `ppcart-order`, `ppcart-plan`,
    `ppcart-manage`, and `ppcart-download`; secure downloads use rewrite tag
    `%ppcart-download%`
  - WP.org #777: also `ppcart-coupon` (Pro URL coupons) and `ppcart-pay-plan`
    (checkout pay-plan preselect). Distinct from account `ppcart-plan`. Hard
    cutover; no `coupon` / `plan` query vars; no Compat alias. Coverage: IT-371
  - Compatibility Mode alone registers the equivalent legacy `sc-*` query
    vars, then redirects explicit legacy query strings and defensively resolved
    stale `sc-download` vars to one same-site canonical URL with status 302
  - The canonical pretty-download rewrite remains active and unchanged in all
    modes because its public path is identical to the former StudioCart path
  - Redirects preserve unrelated query arguments and scalar values (including
    `0`), prefer existing canonical values, ignore array-shaped legacy values,
    and skip POST, REST, AJAX, admin, and already-canonical requests
  - The already-canonical `ppcart_download_slug` option now uses its exact
    derived hooks: `add_option_ppcart_download_slug` and
    `update_option_ppcart_download_slug`
  - Coverage: UT-198 / IT-267

- [x] **11. AJAX action and nonce strings**
  - Canonical first-party routes: `wp_ajax_ppcart_*` / `wp_ajax_nopriv_ppcart_*`
  - Compatibility Mode dual-registers shipped `sc_*` / `ncs_*` POST actions with the same auth/nopriv exposure
  - Canonical nonce actions: `ppcart_purchase_nonce`, `ppcart_ajax_nonce`, and the rest of `maps/nonces.php`
  - Compatibility Mode accepts mapped legacy nonce strings via `ppcart_verify_nonce()`
  - Request field names (`sc-nonce`, `sc_related_product`, …) completed in slice **26**
  - `admin_action_sc_duplicate_product` dual-registers in Compatibility Mode (slice **26**)
  - Free JS leftover Pro actions `sc_check_username` / `sc_capture_lead` were completed in slice **30**
  - Coverage: UT-199 / IT-268

### Persisted data (migration — ask first)

Do **not** start these without an explicit go-ahead. Compatibility Mode cannot
`class_alias` database strings.

- [x] **12. CPT / taxonomy slugs** — leftover `sc_product` / `sc_order` /
  `sc_subscription` plus `sc_product_cat` / `sc_product_tag` **rows** rename to
  `ppcart_*` via merchant-run Data migration (`includes/compat/cpt-slug-migration/`).
  Activate/upgrade only detect leftover rows. Hardcoded leftover CPT/taxonomy
  **strings**, taxonomy rewrite slugs, and Pro-owned CPT constants were renamed
  in slice **28**. Leftover CPT body CSS lives in Compatibility Mode
  `css/ppcart-compat-*.css` (toggle-gated), not core stylesheets. Coverage:
  UT-200 / IT-269–IT-274

- [x] **13a. Option keys** — leftover `_sc_*` / `sc_*` / `ncs_*` /
  `current_schedule_val` / `_my_account` `wp_options` rows move onto
  `_ppcart_*` / `ppcart_*` only through explicit Data migration. Writes persist
  canonical rows. Compat read-through serves leftover-only rows (including
  mixed Pro versions that still `update_option('_sc_*')`) without mutation.
  Compatibility Mode API bridges stay (legacy PHP name → canonical
  `get_option`). Package: `includes/compat/option-key-migration/` (copy-on-read;
  not a Settings card). Historical Compatibility Mode toggle keys stay excluded
  (UT-201 / IT-275). Coverage: UT-188, UT-202 / IT-258, IT-280.

- [x] **13b. Post-meta keys and `$this->prefix = 'sc_'`** — first-party
  save/read is `_ppcart_*` only. Leftover `_sc_*` **names** (and user-meta
  `_sc_phone`) are Compatibility Mode metadata bridges, not a first-party
  dual-read. `$this->prefix` is `ppcart_`. Query leftover keys only while
  Compatibility Mode is on. Merchant-run Data migration in
  `includes/compat/meta-key-migration/`. Coverage: UT-203 / IT-281.

- [x] **14a. Shortcode tags** — first-party `add_shortcode()` uses `ppcart_*`
  only; Compatibility Mode dual-registers leftover tags onto the same
  callbacks. Coverage: UT-204 / IT-282.
- [x] **14b. Shortcode usage** — first-party emitters, detectors, fixtures,
  and docs use canonical tags. No second shim. Email leftover
  `[studiocart-order-downloads]` is replaced only while Compatibility Mode
  is on. Coverage: UT-205 / IT-283.
- [x] **14c. Shortcode content Data migration** — leftover tags in
  `post_content` / `post_excerpt`, email template options, and product
  notification HTML rewrite to canonical via the merchant Data migration
  card (`includes/compat/shortcode-tag-migration/`). Not a Compatibility
  Mode shim. Coverage: UT-206 / IT-284.

- [x] **15. Roles / capabilities** — leftover `sc_cart_manager`,
  `sc_cart_administrator`, `sc_manager_option`, `edit_sc_product`, … rename in
  the same Data migration (step 1 caps, step 2 role slugs). Coverage: UT-200 / IT-269–IT-274

- [x] **16. Custom tables / metadata types** — leftover `{prefix}ncs_*`
  tables keep leftover names until the merchant runs Settings → Data
  migration. Fresh installs `CREATE` canonical `{prefix}ppcart_*` names.
  Activate/upgrade must **not** `RENAME`. No Compatibility Mode table-name
  shim and no restore. Package: `includes/compat/custom-table-migration/`.
  Live helpers: `ppcart_live_table( $family )`, `ppcart_live_metadata_type()`,
  `$wpdb` live meta table on `plugins_loaded`. **Pro contract:**
  `PPCart_Tax` must use `ppcart_live_table( 'tax_rate' )`. Coverage:
  UT-220 / IT-300–305.
  - `{prefix}ncs_tax_rate` → `{prefix}ppcart_tax_rate`
  - `{prefix}ncs_order_items` → `{prefix}ppcart_order_items`
  - `{prefix}ncs_order_itemmeta` → `{prefix}ppcart_order_itemmeta`
  - metadata type `ncs_order_item` → `ppcart_order_item` (with column
    `ncs_order_item_id` → `ppcart_order_item_id`)
  - `{prefix}ncs_downloads` → `{prefix}ppcart_downloads`

- [x] **17. On-disk log directory names** — **keep** `{uploads}/publishpress-cart/logs` (`wp_upload_dir()['basedir']`; override `PPCART_DEBUG_LOG_DIR`). Not a leftover rename. PHP constants already `PPCART_*`. No filesystem shim; do not dual-write leftover plugin-dir `logs/` or fictional `ncs-cart/logs`. Locked by **UT-221**. **Pro contract:** Free default remains uploads-based; Pro must not invent `ncs-cart/logs`.

- [~] **18. Sibling Pro plugin / Pro-contract constants** — Free inbound remains gated; **blocked on Pro dual-define**. Do **not** mark `[x]`. Free bootstrap reads only `PPCART_*`. `ncs_cart_resolve_canonical_constants` stays Compatibility Mode on only (UT-194: mode off, `NCS_CART_LOADED_BY_PRO` must not create `PPCART_LOADED_BY_PRO`). Completing this slice requires a coordinated Pro PR that dual-defines `PPCART_*` **before** requiring Free, then aliases `NCS_CART_*`. Free JS leftover Pro AJAX actions moved to slice **30** and do not wait on that PR.

### Markup leftovers

- [x] **19. HTML `id` / `class` / `for` and matching JS/CSS selectors** —
  Hard cutover to `ppcart_` / `ppcart-`. No Compatibility Mode PHP aliases
  and no dual-class (`.sc-row.ppcart-row`). Form `name=` is remaining slice
  **26**. Leftover HTML `id`s `_sc_*` / `rid_sc_*` / `repeater_sc_*` were
  renamed in slice **25b**.
  Highest-risk submit id is `#ppcart_card_button`. Tax script templates are
  `tmpl-ppcart-tax-table-row` (and `-empty` / `-pagination`). Do not rename DB
  `{prefix}ncs_tax_rate` (slice 16 live helpers + opt-in Data migration; do not
  confuse with HTML class `ppcart_tax_rate_table`). Leftover CPT body-class
  **selectors** in first-party were renamed in slice **28**; Compat CSS that
  scoped those bodies is toggle-gated. Coverage: IT-138, IT-279, IT-310.

### Leftover first-party families (20–30)

Shimable PHP/JS first, then request-visible, then persisted-external. One
family per session. Leftover names after each slice exist only in
`includes/compat/studiocart-compatibility-mode/` (and Compat-on tests).

- [x] **20. PHP method names containing `sc_`** — Mechanical. No Compat method
  aliases. WP hook **tags** that include the live CPT slug stay on
  `ppcart_live_post_type()` / `ppcart_query_post_types()`; only the callback
  method moved. Leftover **parameter** names (`$_sc_mail_tags`, integration
  `$sc_product_id`, `$sc_tabs`) are canonical `$ppcart_*`. Checkout POST-bound
  leftover request keys were completed in slice **26**. Coverage:
  UT-224 / IT-311.
  - `save_post_sc_order` → `save_post_order` (`admin/class-ppcart-order-admin.php`)
  - `register_sc_importers` → `register_importers` (`admin/traits/trait-ppcart-admin-product-duplicate.php`)
  - `get_sc_mailchimp_*` / `get_sc_activecampaign_*` → drop `sc_`
  - `get_sc_convertkit_forms` → `get_convertkit_form_options` (API fetcher `get_convertkit_forms` already existed)
  - `get_sc_converkit_tags` → `get_converkit_tag_options` (keep the `converkit` typo)
  - `get_sc_products` / `get_sc_products_payment` → `get_products` / `get_products_payment`
  - `get_sc_service_type` / `get_sc_trigger_option` / `get_sc_tab_fields` → drop `sc_`
  - `register_sc_tab_section` → `register_tab_section`
  - `set_custom_edit_sc_*_columns` / `custom_sc_*_column` → drop `sc_`
  - `update_sc_*_amount` → `update_ppcart_*_amount` (`includes/class-ppcart-price-format.php`)

- [x] **21. JS `wp_localize_script` / `window.*`** — Hard cutover in first-party
  PHP+JS. No Compatibility Mode JS aliases. Canonical: `ppcart`,
  `ppcart_translate_*`, `ppcart_reg_vars`, `ppcartSettings*`, `ppcartReports`,
  `ppcartNotificationI18n`, `ppcart_currency`, `ppcart_user`, `ppcart_popup`,
  `window.ppcart_coupon`. Default: **no** Compat JS globals unless a later
  slice documents them (merchant custom JS is not a Compat feature).
  Coverage: UT-225 / IT-312 (admin), UT-226 / IT-313 (public).
  - Admin: `ppcart_reg_vars` (inner `ajax_url`), `ppcart_translate_backend`,
    `ppcart_admin_i18n`, `ppcart_mc_*`, `ppcartReports`, `ppcartSettingsI18n`,
    `ppcartNotificationI18n`, `ppcart_settings`
  - Public: `ppcart` (checkout object; was `studiocart`),
    `ppcart_translate_frontend`, `ppcart_currency`, `ppcart_user`,
    `ppcart_popup`, `window.ppcart_coupon`. jQuery events
    `ppcart/orderform/*` (slice 39 hard cutover).

- [x] **22. Unlisted PHP runtime globals** — Canonical `$GLOBALS['ppcart_*']`.
  Compatibility Mode aliases leftover keys when on. Coverage: UT-227 / IT-314.
  - `$GLOBALS['sc_checkout_request_rendered']` → `$GLOBALS['ppcart_checkout_request_rendered']`
  - `$GLOBALS['sc_checkout_block_arrangement']` → `$GLOBALS['ppcart_checkout_block_arrangement']`

- [x] **23. Dashboard widget id** — `'studiocart_dashboard_widget'` →
  `ppcart_dashboard_widget` in `admin/dashboard/class-ppcart-dashboard-widget.php`.
  Hard cutover; no Compatibility Mode leftover widget id (dual-register would
  show two widgets). Coverage: UT-228 / IT-315.

- [x] **24. Transient prefixes** — First-party `ppcart_*`. Leftover `_transient_sc_*` /
  `_transient_timeout_sc_*` rows copy onto canonical keys at boot (slice 13a
  mapper + scan). Compatibility Mode leftover `get_transient('sc_*')` names
  read/write canonical. Object-cache leftovers expire in place. Coverage:
  UT-229 / IT-316 (IT-042 updated).
  - `sc_hosted_session_*` → `ppcart_hosted_session_*`
  - `sc_stripe_evt_*` → `ppcart_stripe_evt_*`
  - `sc_stripe_connect_admin_notice` → `ppcart_stripe_connect_admin_notice`
  - `sc_stripe_webhook_need_signing_secret` → `ppcart_stripe_webhook_need_signing_secret`
  - `sc_customer_portal_settings_error` / `sc_express_payment_settings_error` /
    `sc_stripe_settings_error` → `ppcart_*`
  - `sc_stripe_connect_pending_*` / `_user_*` / `_ref_*` /
    `sc_stripe_connect_prereg_public_key_*` → `ppcart_stripe_connect_*`
  - `sc_stripe_webhook_rejected_*` → `ppcart_stripe_webhook_rejected_*`
  - `sc_reports_customer_lifetime_value` → `ppcart_reports_customer_lifetime_value`
  - `sc_paypal_ipn_*` → `ppcart_paypal_ipn_*`
  - `sc_hosted_claim_*` option lock → `ppcart_hosted_claim_*`

- [x] **25. Leftover `_sc_*` literals and leftover HTML `id`s** — Split 25a/25b.
  CPT fallbacks like `: 'sc_product'` stay until slice **28**. Request `name=` /
  `$_POST` are slice **26**.
  - [x] **25a. Helper-call leftover `_sc_*` literals** — First-party
    `ppcart_*_meta()` call sites pass the suffix (`'amount'`), not leftover
    `'_sc_amount'`. Meta-key lists, logger `get_record_value()` 4th args, and
    native WP meta leftovers follow. No Compatibility Mode aliases. Coverage:
    UT-230.
  - [x] **25b. Leftover HTML `id`s / JS selectors / first-party CSS** —
    Leftover ids (`#_sc_currency`, `id="rid_sc_break"`,
    `id="_sc_bump_bg_color"`, `#repeater_sc_*`) moved to `_ppcart_*` /
    `rid_ppcart_*` / `repeater_ppcart_*`. Metabox ids `sc-product-settings` /
    `sc-edit-order-details` / `sc-order-notes` / `sc-product` moved to
    `ppcart-*`. Form `name=` is slice 26. Leftover `#rid_sc_*` CSS copied into
    first-party with canonical selectors. No Compat JS shims. Coverage:
    UT-234 / IT-319.

- [x] **26. Form `name=` / AJAX / GET request field names** — First-party emit
  and read canonical `ppcart_*` / `ppcart-*`. Compat POST/GET bridge copies
  leftover keys onto canonical when the toggle is on (same pattern as leftover
  AJAX **actions**). First-party PHP that `filter_input(INPUT_POST, 'sc-nonce')`
  moved to `ppcart_filter_input(INPUT_POST, 'ppcart-nonce')`; leftover POST keys
  are read **only** inside the Compat package. JS posts canonical field names.
  Coverage: UT-231–232 / IT-317–318.
  - Checkout / public: `sc-nonce` → `ppcart-nonce`; `sc_product_id` →
    `ppcart_product_id`; `sc_product_name` → `ppcart_product_name`;
    `sc_amount` → `ppcart_amount`; `sc_process_payment` →
    `ppcart_process_payment`; `sc_page_id` → `ppcart_page_id`; `sc_page_url` →
    `ppcart_page_url`; `sc_accept_terms` → `ppcart_accept_terms`;
    `sc_accept_privacy` → `ppcart_accept_privacy`; `sc_consent` →
    `ppcart_consent`; `sc_product_option` → `ppcart_product_option`;
    `sc-orderbump[]` → `ppcart-orderbump[]`; `sc-show-password` →
    `ppcart-show-password`; `sc-auto-login` → `ppcart-auto-login`;
    `sc-lead-captured` → `ppcart-lead-captured`; `sc_currency_code` →
    `ppcart_currency_code`; `sc_currency_country_code` →
    `ppcart_currency_country_code`; `sc_profile_nonce` →
    `ppcart_profile_nonce`; `_sc_phone` / `_sc_address*` → `_ppcart_phone` /
    `_ppcart_address*` (account profile, even disabled); `sc_subscription_id`
    → `ppcart_subscription_id`; `sc_payment_intent` → `ppcart_payment_intent`;
    `sc_payment_method` → `ppcart_payment_method`
  - Admin: `sc_fields_nonce` → `ppcart_fields_nonce`; `sc_related_product` /
    `_sc_related_product` → `ppcart_related_product` / `_ppcart_related_product`;
    `_sc_product_id` → `_ppcart_product_id`; `_sc_pay_options[...]` →
    `_ppcart_pay_options[...]`; `_sc_bump_bg_color` → `_ppcart_bump_bg_color`;
    `sc_stripe_mode` → `ppcart_stripe_mode`; `sc_stripe_webhook_*` →
    `ppcart_stripe_webhook_*`; `sc_view_stripe_webhook_log` →
    `ppcart_view_stripe_webhook_log`; `sc_view_log` → `ppcart_view_log`;
    `sc_process_downloads` → `ppcart_process_downloads`; `sc_refund_amount` →
    `ppcart_refund_amount`; `sc_restock_refunded` → `ppcart_restock_refunded`;
    `sc_subscription_cancel_timing` → `ppcart_subscription_cancel_timing`;
    `sc_subscription_refund_action` → `ppcart_subscription_refund_action`;
    `sc_checkout_experience` → `ppcart_checkout_experience`; GET
    `sc_product_duplicated` → `ppcart_product_duplicated`
  - Hook `admin_action_sc_duplicate_product` →
    `admin_action_ppcart_duplicate_product` (Compat dual-registers leftover
    when on)

- [x] **27. Stripe object metadata keys** — First-party writes and reads
  `ppcart_product_id` / `ppcart_order_id` / `ppcart_subscription_id` /
  `ppcart_option_id` / `ppcart_stripe_subscription_id`. Upsell writes
  `'ppcart_' . $order_type`. Compat, when on, reads leftover `sc_*` keys
  (canonical wins). Product save stamps canonical when missing even if leftover
  already matched. Webhooks/sync/hosted-return leftover-only objects do not
  match unless Compat is on. No Stripe-side data migration. Helper:
  `ppcart_stripe_metadata()`. Map: `maps/stripe-metadata.php`. Coverage:
  UT-235 / IT-327.

- [x] **28. Leftover CPT / taxonomy strings in first-party** — Not the DB rows
  (Data migration already exists). Hardcoded leftover slugs moved to canonical
  source strings. Live helpers stay row-state-based. Coverage: UT-236 / IT-328.
  - `PPCart_Admin_Screens::POST_TYPE_*` (`sc_collection`, `sc_us_path`,
    `sc_membership`, `sc_upgrade_path`) and `TAXONOMY_PRODUCT_*` (`sc_product_cat`)
    → `ppcart_collection` / `ppcart_us_path` / `ppcart_membership` /
    `ppcart_upgrade_path` / `ppcart_product_cat` / `ppcart_product_tag`. Screen
    matching uses `all_post_types()` / maps so leftover slugs still match.
  - Fallback `: 'sc_product'` / `'sc_collection'` → live helper or canonical, plus
    `ppcart_query_pro_post_types()` / `ppcart_query_product_and_collection_post_types()`
  - FSE `TEMPLATE_SLUG = 'single-ppcart_product'`. Registration uses the live
    product slug. Compat registers leftover `single-sc_product` when on.
  - JS uses localized `ppcart.product_singular_selector`; checkout1.php emits the
    live body class from `ppcart_product_singular_body_class()`.
  - Taxonomy rewrite source is canonical `ppcart_product_*`; helper returns leftover
    until Data migration step 1 completes. Compat adds leftover rewrite aliases
    when on after migration.
  - Map: `includes/compat/cpt-slug-migration/maps.php` (`pro_post_types`,
    `rewrite_legacy`). Compat map: `maps/cpt-taxonomy.php`.

- [x] **29. REST `sc/v1`** — Hard cutover. Gutenberg is
  `publishpress-cart/v1`. Leftover `sc/v1` stays unregistered in Free with
  Compat off and on. No Free `SC_Rest_*` / `api/ncs-rest` controllers. No
  Compatibility Mode leftover REST alias. Feature catalog presents
  `publishpress-cart/v1` as live. Pro developer REST cutover is later
  (`publishpress-cart/v1` only; no leftover dual-register). Coverage:
  UT-237 / IT-329.

- [x] **30. Free JS leftover Pro AJAX actions** —
  First-party `public/js/ppcart-public.js` posts `ppcart_check_username` /
  `ppcart_capture_lead`. Leftover `sc_check_username` / `sc_capture_lead` are
  in Compat `maps/ajax.php` (auth + nopriv) and work only when Compat is on.
  Handlers stay Pro-owned; Free does not stub them. Coverage: UT-238 / IT-330.

### Post-30 leftovers (31–42)

One family per session. Persisted values (36–38) are done.

- [x] **31. Runtime globals missed by 3 + 22** — Canonical `$ppcart_public`,
  `$ppcart_files`, `$ppcart_product_fields`, `$ppcart_is_admin_screen`.
  Leftover `$studiocart` / `$scFiles` / `$sc_product_fields` /
  `$sc_is_studiocart_admin_screen` only with Compat on (slice 22 pattern).
  Coverage: UT-248 / IT-332 / UT-251. Compat companion: UT-250 / IT-333.
- [x] **32. Admin bulk actions** — Canonical `ppcart_make_*` /
  `ppcart_sync_stripe` / GET `bulk_ppcart_sync_stripe*`. Hard cutover; leftover
  `sc_make_*` / `sc_sync_stripe` / `bulk_sc_*` no-op. Coverage: UT-252 / IT-334.
- [x] **33. Debug / Stripe log request fields** — Canonical `ppcart_view_log` /
  `ppcart_*_nonce` / `ppcart_view_stripe_webhook_log`. Leftover GET/nonce keys
  copy only while Compat is on. Coverage: UT-253 / UT-254 / IT-335.
- [x] **34. Integration trigger tags** — Canonical `ppcart_order_*` /
  `ppcart_subscription_*` / `ppcart_renewal_*`. Leftover `sc_order_*` /
  `sc_subscription_*` / `sc_renewal_*` listeners only with Compat on
  (mechanical hook bridge). Coverage: UT-255 / UT-256 / IT-336.
- [x] **35. Checkout leftovers** — Canonical `$wp_filter['ppcart_card_details_fields']`,
  `ppcart_orderbumps()`, POST `ppcart_qty`, CSS `[for^=ppcart-orderbump]`,
  error-bag `ppcart_accept_terms`, `ppcart_custom_fields`. Leftover POST
  `sc_qty` copies only while Compat is on. Coverage: UT-257 / UT-258 / IT-337.
- [x] **36. Integration repeater field ids** — Canonical
  `ppcart_sub_prod_id` / `ppcart_sub_plan_id` / `ppcart_sub_cancel`. Leftover
  inner keys copy only while Compat is on (stored rows stay leftover).
  Coverage: UT-262 / UT-263 / IT-339.
- [x] **37. Invoice format option values** — Canonical `ppcart_pns` /
  `ppcart_pn` / `ppcart_ns` / `ppcart_n` via `ppcart_get_invoice_format()`.
  Leftover `sc_*` codes map only while Compat is on. Option row not rewritten.
  Coverage: UT-264 / UT-265 / IT-340.
- [x] **38. Tax-rate option inner keys** — Canonical
  `_ppcart_tax_rate_title` / `_ppcart_tax_rate_slug` / `_ppcart_tax_rate` via
  `ppcart_get_tax_data()` / `ppcart_tax_rates`. Leftover `_sc_*` inner keys
  copy only while Compat is on. Option row not rewritten. Coverage: UT-266 /
  UT-267 / IT-341.
- [x] **39. JS inner functions and jQuery events** — Canonical
  `ppcart_parse_json_response` / `ppcart_validate` / `ppcart_do_lead_capture`
  and `ppcart/orderform/*`. Hard cutover; leftover `studiocart/orderform/*`
  no-ops. No Compat JS aliases. Coverage: UT-261.
- [x] **40. CSV export query var** — Canonical `ppcart-csv-export` /
  `ppcart_csv_escape_cell`. Leftover `sc-csv-export` 302s only while Compat
  is on (slice 10 map). Coverage: UT-259 / UT-260 / IT-338.
- [x] **41. Cosmetic `$sc_*` locals** — Canonical `$ppcart_*` /
  `$posted_ppcart_*` / `$request_ppcart_*` / `$query_ppcart_*`. Mechanical;
  no shim. Coverage: UT-268.
- [x] **42. Theme override directory** — Canonical
  `yourtheme/publishpress-cart/`. Leftover `yourtheme/studiocart/` copies
  resolve only while Compat is on (`ppcart_theme_template_path`). Coverage:
  UT-269. Compat companion: UT-270 / IT-342.

### Post-42 leftovers (43+)

One family per session. Slice **18** stays `[~]`. Families **58–81** are
done.

- [x] **43. Public webhook / invoice query vars** — Canonical
  `ppcart-webhook` / `ppcart-api` / `ppcart-invoice`. Leftover `sc-webhook` /
  `sc-api` / `sc-invoice` 302 when Compat on; leftover `/sc-webhook/` rewrite
  and POST `sc-api` copy onto `ppcart-api`. Coverage: UT-273 / UT-274 / IT-344.
- [x] **44. Leftover hook fires / lookups** — Canonical `ppcart_before_order_refund`
  and preload `ppcart_*` hooks. Leftover `before_sc_order_refund` /
  `sc_before_create_*` / `studiocart_*` when Compat on. Coverage: UT-275 /
  UT-276 / IT-345.
- [x] **45. JS leftovers** — Canonical `is_ppcart_checkout` / `ppcart_oto_get` /
  `search_ppcart_user`. Dead `sc_payment_pay` onclick dropped. Hard cutover.
  Coverage: UT-277.
- [x] **46. `NCSLogger` method** — Canonical `ppcartLogger`. Hard cutover.
  Coverage: UT-278.
- [x] **47. Admin leftover POST / notice keys** — Canonical
  `admin_post_ppcart_stripe_webhook_manual_setup` / `ppcart_extension_notice` /
  `ppcart_price_formatted`. Leftover GET `sc_extension_notice` when Compat on;
  leftover admin_post and dismiss hard cutover. Coverage: UT-279 / UT-280 /
  IT-346.
- [x] **48. Pro lock keys** — Canonical `ppcart_pro_*`. Leftover `sc_pro_*`
  dropped. Hard cutover; no Compat tab aliases. Coverage: UT-281.
- [x] **49. Integration service ids** — Canonical `ppcart_subscription` /
  `ppcart_refund_order` / `ppcart_wpdomainchecker`. Leftover `services` copy
  when Compat on; leftover `ppcart_sc_*_integrations` listeners when Compat
  on. Coverage: UT-282 / UT-283 / IT-347.
- [x] **50. Leftover option-name literals** — Canonical `_ppcart_*` /
  `add_option__ppcart_*`. Leftover `_sc_*` names sensitive when Compat on.
  Secrets raw read is canonical-only. Coverage: UT-284 / UT-285 / IT-348.
- [x] **51. Identity leftovers** — Canonical `ppcart_is_checkout_context()`,
  `ppcart_exporter`, `{publishpress_cart}`, and `ppcart_offer`. Compat preserves
  `{studiocart}` at runtime and the explicit Data migration rewrites stored email
  templates with backups. Coverage: UT-286 / UT-287 / IT-349.
- [x] **52. Pro class callers** — Free calls `PPCart_VAT` / `PPCart_Tax`.
  Coordinated hard cutover with Pro; no Free fallback or Compat alias. Kept
  separate from 18. Coverage: UT-288.
- [x] **53. Cosmetic leftovers** — `$is_ppcart_stripe_*`. Hard cutover; no shim. Coverage: UT-289.
- [x] **54. Leftover shortcode detectors** — Cart `has_shortcode` canonical only.
  Leftover tags via Compat (`ppcart_frontend_assets_needed`). Coverage: UT-271 /
  UT-272 / IT-343.
- [x] **55. Leftover meta names in comments** — First-party comments name
  `_ppcart_display` / `_ppcart_tax_data` / `_ppcart_option_list` only. No runtime
  meta-key read changed and no Compatibility Mode bridge is needed. Coverage: UT-290.
- [x] **56. Feature id / Compat option key** — Canonical
  `ppcart-compatibility-mode` / `_ppcart_compatibility_mode`. Compat alone
  accepts the leftover feature id and reads historical option rows in place.
  Coverage: UT-291 / IT-350.
- [x] **57. Divi leftover classes / `namespace Studiocart`** — Closed by
  Free/Compat source audit. Historical Divi sources were removed from Free
  as Pro ghost files. No Free/Compat alias target. Pro-owned follow-up:
  `PPCart_Divi_Order_Form` / `namespace PPCart`.
- [x] **58. Storefront HTML/JS/CSS leftovers** — Canonical `ppcart-current` /
  `ppcart-checkout-1` / `ppcart-shortcode` / `#ppcart-preloader` (and related).
  Leftover `sc-current` / `sc-checkout-1` / `scshortcode` / `#sc-preloader`
  dropped. Hard cutover. Coverage: **UT-292**.
- [x] **59. Admin HTML/JS leftovers** — HTML attrs / selectize / localStorage
  cut over (`ppcart-selectize`, `data-ppcart-editor-*`, `ppcart*Tab`).
  TinyMCE leftover lookups closed in **65**. Coverage: **UT-293** /
  **UT-300** / **IT-356**.
- [x] **60. Gutenberg leftover block** — Canonical
  `publishpress-cart/checkout-form`. Leftover
  `sc-products-shortcode/product-shortcode` dual-register Compat on. Coverage: **UT-294** / **UT-295** / **IT-351**.
- [x] **61. Leftover admin AJAX + JS globals** — Canonical `ppcart_ajax_nonce` /
  `ppcart_tax_settings`. Leftover `ncs_ajax_*` / `var ncs` / `sc_tax_settings`
  only in Compat if still needed. Coverage: **UT-296** / **IT-352**.
- [x] **62. Request `name=` / GET leftovers** — Canonical emit/read
  `ppcart-*` / `ppcart-slm-order`. Leftover hidden `sc-` emit dropped; leftover
  GET `sc-slm-order` Compat on. Coverage: **UT-297** / **IT-353**.
- [x] **63. Leftover recognition** — Cart canonical-only; leftover
  read-through Compat; **no silent migrate** (`_my_account`, duplicator
  `_sc_`, Stripe `sc_` strip, leftover FSE loop). Coverage: **UT-298** /
  **IT-354**.
- [x] **64. Compat `ppcart_compat_*` → `ppcartcomp_*`** — Rename in Compat +
  tests. Not a StudioCart leftover.
- [x] **65. Admin JS TinyMCE / editor follow-up (59 gap)** — jQuery
  `.data('sc-editor-initialized')` / `sc-editor-repairing` →
  `ppcart-editor-*`; `'sc-' + optionId` → `'ppcart-' + optionId` to match
  `ppcart-admin-field-editor.php`. Hard cutover. Extend **UT-293** scanner.
  Coverage: **UT-300** / **IT-356**.
- [x] **66. CPT/taxonomy leftover filters** — Drop Cart
  `apply_filters('sc-cart-cpt-options')` /
  `apply_filters('sc-cart-taxonomy-options')`; keep canonical
  `ppcart_cpt_options` / `ppcart_taxonomy_options` only. Compat bridges
  leftover filter names when on. Coverage: **UT-301** / **IT-357**.
- [x] **67. Tax CSV import slug** — Settings import link
  `import=ncs-cart_tax_rate_csv` → `ppcart_tax_rate_csv`; register importer
  under canonical slug (audit `ppcart_register_importers` listeners). Hard
  cutover. Coverage: **UT-302** / **IT-358**.
- [x] **68. Cosmetic `$ncs_*` locals (41 follow-up)** — `$ncs_tax` /
  `$ncs_meta` / `$ncs_stripe` → `$ppcart_*` in activator, upgrade, order
  items, files storage, Stripe card update template. Hard cutover; no shim.
  Coverage: **UT-303**.
- [x] **69. Docblocks and stale comment refs** — `@package NCS_Cart` /
  `@subpackage NCS_Cart/*`; comment-only `_sc_notifications` /
  `sc_personalize()` / `Mirror sc_setup_*()` strings → canonical names. No
  runtime change. Coverage: **UT-304**.

### Remaining leftovers (58–69)

2026-09-08 audit: named PHP/hook/URL families **43–57** are clean in Cart
runtime. Compat owns those leftover maps. Slice **18** stays `[~]` (Pro
dual-define `PPCART_*` before require Free, then alias `NCS_CART_*`).

**2026-09-08 post-review scan** found five first-party leaks missed by slices
**59** / **41** and untested dual filters. New families **65–69** track them.
Queue **64** first (Compat prefix), then **65–69** one family per session.

Slices **19** (HTML/JS/CSS), **11** (AJAX), **21** (JS globals), and **26**
(request fields) were marked `[x]` with leftovers still in first-party.
Several leftover names no longer match canonical CSS/JS, so checkout and
admin miss styles and handlers.

**Unbreakable:** Cart first-party never emits, reads, writes, or *recognizes*
leftover names. Leftover paths live only in `publishpress-cart-compat`.
Intentional Cart exception: `PPCart_Studiocart_Conflict` (external plugin
basename guard). Do not add leftover detectors in Cart so unmigrated content
still works — that is Compat (slices 60–63).

One family per session. Hard cutover for HTML/JS unless noted. Same-commit
leftover `id` / `class` / `for` + matching JS/CSS. Post-57 queue **complete**
(family **69** done). Post-73 queue **complete** (slices **74–76** done).
Post-76 leftover queue **complete** (slice **77** done). Post-77 leftover
queue **complete** (slice **78** done). Post-78 leftover queue **complete**
(slices **79–81** done).
Next unused IDs: **UT-318** / **IT-362** (do not reuse **UT-309**).

#### Likely bugs (rename is also a fix)

| Leftover still emitted | Canonical sibling already in CSS/JS | Family |
|------------------------|----------------------------------------|--------|
| `sc-current` | `ppcart-current` | 58 |
| `sc-checkout-1` / `sc-splitin-form` | `ppcart-checkout-1` / `ppcart-splitin-form` | 58 |
| `sc-address-*` | `.ppcart-address-*` | 58 |
| `scshortcode` | `ppcart-shortcode` (rename emit **and** CSS) | 58 |
| `#sc-preloader` | `#ppcart-preloader` | 58 |
| `sc-selectize` / `sc-user-search-custom` | `.ppcart-selectize` / `.ppcart-user-search-custom` | 59 |
| `sc-payment-subtab-link` | `ppcart-payment-subtab-link` | 59 |
| jQuery `.data('sc-editor-*')` / `'sc-' + optionId` | `ppcart-editor-*` / `'ppcart-' + optionId` | 65 |
| `apply_filters('sc-cart-cpt-options')` | `ppcart_cpt_options` (+ Compat bridge) | 66 |
| `import=ncs-cart_tax_rate_csv` | `import=ppcart_tax_rate_csv` | 67 |
| `ppcart_ctivate` / `ppcart_pgrade` / `ppcart_roduct` | `ppcart_activate` / `ppcart_upgrade` / `ppcart_product` | 70 |
| `&sc-revoke=` emit vs `$_GET['ppcart-revoke']` read | `ppcart-revoke` / `ppcart-revoked` | 71 |

#### 58. Storefront HTML/JS/CSS leftovers — `[x]` Hard

| Leftover | Canonical |
|----------|-----------|
| `sc-current` | `ppcart-current` |
| `sc-checkout-1` | `ppcart-checkout-1` |
| `sc-splitin-form` | `ppcart-splitin-form` |
| `sc-address-1` / `sc-address-2` | `ppcart-address-*` |
| `scshortcode` | `ppcart-shortcode` |
| `#sc-preloader` | `#ppcart-preloader` |
| `sc-payment-element` | `ppcart-payment-element` |
| `sc-selected` | `ppcart-selected` |
| `sc-show-coupon` | `ppcart-show-coupon` |
| `sc-password` | `ppcart-password` |

**Files:** `public/js/ppcart-public.js`, `public/js/ppcart-stripe.js`,
`public/templates/checkout1.php`, address / coupon / password templates,
storefront SCSS/CSS, Gutenberg checkout inline `.scshortcode` selectors
(those selectors track the emitted class, not the leftover block name).

**Pro:** match the new Free class/id emit. Do not keep leftover storefront
class names in Pro templates, JS, or CSS.

**Tests:** UT-292 scanner must fail on `sc-current` / `sc-checkout-1` /
`scshortcode` / `#sc-preloader` in `admin|includes|public|models` (not
tests/docs). Optional CSS/SCSS positives and `esc_attr()` on the split-in
form echo: [Open follow-ups](#open-follow-ups-leftover-slices).

#### 59. Admin HTML/JS leftovers — `[x]` Hard (TinyMCE finished in **65**)

| Leftover | Canonical |
|----------|-----------|
| `sc-selectize` | `ppcart-selectize` |
| `sc-user-search-custom` | `ppcart-user-search-custom` |
| `sc-payment-subtab-link` | `ppcart-payment-subtab-link` |
| `data-sc-editor-*` | `data-ppcart-editor-*` |
| `data-ncs-*` (secrets UI) | `data-ppcart-*` |
| localStorage `scSettingsMainTab` | `ppcartSettingsMainTab` |
| localStorage `scPaymentMethodsSubtab` | `ppcartPaymentMethodsSubtab` |
| jQuery `.data('sc-editor-*')` | `.data('ppcart-editor-*')` (**65**) |
| `'sc-' + optionId` TinyMCE id | `'ppcart-' + optionId` (**65**) |

HTML attrs / selectize / localStorage are cut over. TinyMCE leftover lookups
closed in **65**. Modern settings shell uses `ppcartSettingsActiveTab`
separately from legacy `ppcartSettingsMainTab`; no dual-read of old
localStorage keys.

**Files (65):** `admin/js/ppcart-repeater.js`, `admin/js/ppcart-admin.js`,
`admin/js/ppcart-settings.js`. Source-shaped `Unit/Prefix` coverage was
removed in #679.

**Pro:** match Free admin class / `data-*` / localStorage / TinyMCE id prefix.

#### 60. Gutenberg leftover block — `[x]` Compat dual-register when on

Confirmed: Cart registers only `publishpress-cart/checkout-form`. Compat
dual-registers leftover `sc-products-shortcode/product-shortcode` while
Compatibility Mode is on.

| Leftover | Canonical |
|----------|-----------|
| `sc-products-shortcode/product-shortcode` | `publishpress-cart/checkout-form` |

**Files:** `includes/integrations/gutenberg/lib/class-ppcart-checkout-renderer.php`
(`LEGACY_BLOCK_NAME`), Gutenberg bootstrap, editor CSS
`.wp-block-sc-products-shortcode-product-shortcode`, `order-form.js`
(source fallback leftover; build already drifted). Move leftover
registration, localize, editor CSS, and IT-313 leftover assertions to Compat.

**Pro:** register/emit only `publishpress-cart/checkout-form`. Do not
dual-register the leftover block in Pro. Cart JS does not read
`legacyBlockName`; Compat editor JS hardcodes the leftover block name.
Do not silent-migrate persisted leftover block comments.

#### 61. Leftover admin AJAX + JS globals — `[x]` Canonical emit

| Leftover | Canonical |
|----------|-----------|
| `$_POST['ncs_ajax_nonce']` / `ncs_action` / `ncs_*` methods | `ppcart_ajax_nonce` / canonical `ppcart_*` AJAX |
| `var ncs = {}` | existing `ppcart` localize / canonical object |
| `sc_tax_settings` | `ppcart_tax_settings` |

**Files:** `admin/templates/ppcart-admin-ajax-ppcart-ajax-action.php`;
storefront Stripe JS. Canonical JS already posts `ppcart_ajax_nonce`.
Leftover POST/global copy belongs in Compat only if something external
still posts `ncs_*`.

**Pro:** post/read canonical AJAX keys and tax settings. Do not emit
`ncs_ajax_*` or `var ncs`.

#### 62. Request `name=` / GET leftovers — `[x]` Canonical emit/read

| Leftover | Canonical |
|----------|-----------|
| Hidden field emit `str_replace('_ppcart_', 'sc-', …)` | emit canonical `ppcart-*` / `ppcart_*` |
| GET `sc-slm-order` | `ppcart-slm-order` |

**Files:** `admin/partials/ppcart-admin-field-hidden.php`;
`public/templates/my-account/slm-plan-detail.php`. Hidden fields emit
`$atts['name']` (`_ppcart_*` / repeater array names). Leftover GET copy in
Compat `maps/request-fields.php` while on (slice 26 pattern). Leftover
`sc-functionality` invoice icon had no Cart asset; replaced with “Download
Invoice” text.

**Pro:** emit/read canonical request fields. Leftover GET `sc-slm-order`
only via Free Compat.

#### 63. Leftover recognition — `[x]` Cart canonical-only

No silent migrate.

| Leftover recognition | Canonical / Compat |
|----------------------|--------------------|
| `get_option('_my_account')` then `delete_option` + `update_option('_ppcart_myaccount_page_id')` | Cart reads `_ppcart_myaccount_page_id` only. Leftover row stays until merchant Data migration. Compat may read-through while on. |
| Duplicator `strpos($write_key, '_sc_')` skip | Allowlist `_ppcart_*` (and core keys) without naming leftover |
| Stripe owned-fields fallback strips `sc_` when `ppcart_meta_key_suffix` is missing | Canonical suffix only; leftover Stripe key read-through Compat |
| Cart loops `ppcart_cpt_leftover_fse_template_slugs()` to register leftover FSE templates | Canonical FSE only; leftover CPT template registration in Compat |

**Pro:** no leftover recognition. Do not silent-migrate `_my_account`,
skip-on-`_sc_`, or strip `sc_` in Pro first-party.

#### 64. Compat prefix `ppcart_compat_*` → `ppcartcomp_*` — `[x]` Compat rename

Not a StudioCart leftover. Forbidden Compat prefix.
`includes/compat/meta-key-migration/metadata-bridges.php` (+ tests).
Cart live helpers stay `ppcart_*`. **Pro:** none.

#### 65. Admin JS TinyMCE / editor follow-up — `[x]` Hard (closes **59**)

**2026-09-08 audit leak.** PHP already uses `ppcart-{id}` editor ids
(`admin/partials/ppcart-admin-field-editor.php`). JS still looks up TinyMCE
and jQuery state under leftover names.

| Leftover | Canonical |
|----------|-----------|
| `.data('sc-editor-initialized')` | `.data('ppcart-editor-initialized')` |
| `.data('sc-editor-repairing')` | `.data('ppcart-editor-repairing')` |
| `editorId = 'sc-' + optionId` | `editorId = 'ppcart-' + optionId` |

**Files:** `admin/js/ppcart-repeater.js`, `admin/js/ppcart-admin.js`,
`admin/js/ppcart-settings.js`.

**Tests:** extend **UT-293** / add **UT-300** patterns for jQuery
`.data('sc-editor` and `'sc-' +`; **IT-356** email modal preview reads TinyMCE
body via canonical id.

**Pro:** match Free TinyMCE id prefix and jQuery data keys.

#### 66. CPT/taxonomy leftover filters — `[x]` Compat bridge (Cart cutover)

**2026-09-08 audit leak.** Cart dual-fires leftover filters beside canonical
ones:

| Leftover | Canonical |
|----------|-----------|
| `sc-cart-cpt-options` | `ppcart_cpt_options` |
| `sc-cart-taxonomy-options` | `ppcart_taxonomy_options` |

**Files:** `includes/templates/post-types-register-single-post-type.php`,
`includes/templates/post-types-register-single-taxonomy.php`.

Remove leftover `apply_filters()` from Cart. Compat bridges leftover filter
names onto canonical hooks when Compatibility Mode is on (same pattern as
other hook maps). Update `docs/hooks-manifest.md` when done.

**Tests:** **UT-301** scanner; **IT-357** Compat-on listener on leftover name.

**Pro:** listen on canonical `ppcart_cpt_options` / `ppcart_taxonomy_options`
only; leftover names via Free Compat if needed.

#### 67. Tax CSV import slug — `[x]` Hard

**2026-09-08 audit leak.** Settings tax tab still links to WordPress importer
slug `ncs-cart_tax_rate_csv`. Free fires `ppcart_register_importers` but does
not register an importer under either slug in this repo — confirm Pro or Free
registration and align URL + slug.

| Leftover | Canonical |
|----------|-----------|
| `admin.php?import=ncs-cart_tax_rate_csv` | `admin.php?import=ppcart_tax_rate_csv` |

**Files:** `admin/partials/ppcart-admin-page-settings-tax.php`; importer class
registration (wherever it lives — hook `ppcart_register_importers`).

**Tests:** **UT-302** link slug; **IT-358** importer registered and reachable.

**Pro:** register/listen on canonical slug if Pro owns the importer.

#### 68. Cosmetic `$ncs_*` locals — `[x]` Hard (closes **41** gap)

Slice **41** renamed `$sc_*` locals only. **2026-09-08 audit** found `$ncs_*`
table/bootstrap locals still in first-party PHP.

| Leftover | Canonical |
|----------|-----------|
| `$ncs_tax` | `$ppcart_tax_table` (or `$ppcart_tax`) |
| `$ncs_meta` | `$ppcart_order_itemmeta_table` (or similar) |
| `$ncs_stripe` | `$ppcart_stripe` |

**Files:** `includes/class-ppcart-activator.php`,
`includes/class-ppcart-upgrade.php`,
`includes/order-items/class-ppcart-order-items.php`,
`includes/files/traits/trait-ppcart-files-storage.php`,
`public/controllers/order/traits/templates/order-stripe-ppcart-update-stripe-card.php`.

Extend **CosmeticLocalsTest** (**UT-303**). No Compat shim.

**Pro:** match Free local names if Pro copies those templates.

#### 69. Docblocks and stale comment refs — `[x]` Docs-only

Low-risk comment/docblock cleanup. No runtime API change.

| Leftover | Canonical |
|----------|-----------|
| `@package NCS_Cart` / `@subpackage NCS_Cart/*` | `@package PublishPressCart` or drop |
| Comment `_sc_notifications` | `_ppcart_notifications` |
| Comment `sc_personalize()` / `sc_setup_*()` | `ppcart_*` equivalents |

**Files:** `includes/files/class-ppcart-files.php`,
`includes/class-ppcart-stripe-save-helper.php`,
`includes/functions/users-and-notifications.php`,
`admin/settings/traits/trait-ppcart-admin-settings-email.php`, and other
comment-only hits from the post-review grep.

**Tests:** **UT-304** optional scanner for `@package NCS_Cart` in first-party
PHP outside Compat.

**Pro:** mirror comment hygiene when touching the same files.

#### Ride-alongs (not their own family)

Merchant-facing leftover comment names that this pass skipped are family
**76**. `sc-functionality` image path and
sample CSV filename are already gone. **`ppcsc_action`** is the Stripe Connect
server contract — not a #629 leftover.

Docs/tools (not a product leak): shared catalog holes (Cart missing UT-272 /
UT-287 / UT-291 / IT-349 / IT-350; Compat missing UT-271 / UT-286 /
UT-288–290); `tools/extract-legacy-css.py` would recreate Cart
`includes/compat/` — retarget or remove; `languages/**` stale `#:` paths are
POT comments.

#### Grep (remaining families)

From Free plugin root. Hits in Compat-on tests, docs, and `languages/**` are
expected until that slice lands.

```bash
rg "sc-current|sc-checkout-1|sc-splitin-form|sc-address-|scshortcode|sc-preloader|sc-payment-element|sc-selected|sc-show-coupon|sc-password" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**'

rg "sc-selectize|sc-user-search-custom|sc-payment-subtab-link|data-sc-editor|data-ncs-|scSettingsMainTab|scPaymentMethodsSubtab" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**'

rg "sc-products-shortcode|LEGACY_BLOCK_NAME" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**'

rg "ncs_ajax_|ncs_action|var ncs|sc_tax_settings" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**'

rg "sc-slm-order|str_replace\\('_ppcart_', 'sc-'" \
  --glob '*.php' --glob '!vendor/**' --glob '!tests/**'

rg "_my_account|strpos\\(.*_sc_|ppcart_cpt_leftover_fse_template_slugs" \
  --glob '*.php' --glob '!vendor/**' --glob '!tests/**'

rg "function ppcart_compat_|ppcart_compat_" \
  --glob '*.php' --glob '!vendor/**' --glob '!tests/**'

rg 'PP_Cart_|pp_cart_|pp-cart-|_pp_cart_|PP_CART_' \
  --glob '!vendor/**' --glob '!node_modules/**'
```

Gold pattern: Cart unit scanner = first-party contains canonical, not leftover.
Compat unit = map/wrapper. Compat IT = leftover works **on**, absent **off**.
`composer test:all:mocked` in Cart; `composer test:all` in Compat when Compat
is touched.

### Suggested Free order

| # | Slice | Why this next |
|---|--------|----------------|
| 1 | Bootstrap functions | Done — `ppcart_activate` / `ppcart_deactivate` / `ppcart_upgrade` / `ppcart_run` |
| 2 | Compat class rename | Done — `PPCart_Studiocart_Compatibility_Mode`; no unreleased-name alias |
| 3 | Runtime globals `$sc_*` / `$scp` | Done — `$ppcart_stripe` / `$ppcart_currency` / `$ppcart_debug_logger` / `$ppcart_product` |
| 4 | Plugin identity strings | Done — runtime `ppcart`, corrected domains/handles, canonical hooks with Compatibility Mode bridges |
| 5 | `$ncs_cart_*` locals / template result | Done — `$ppcart_*` bootstrap locals and `$__ppcart_template_result` |
| 6 | Integration template filenames | Done — `ppcart-kit-*` / `ppcart-googlerecaptcha-*` |
| 7 | Test-only helpers | No production aliases |
| 8 | Fonts + last CSS | Done — font assets use `ppcart`; the leftover admin-screen selector followed slice 9 |
| 9 | Admin menu/screen IDs | Done — hard cutover to canonical `ppcart` slugs, no legacy URL support |
| 10 | Public query vars and rewrites | Done — canonical `ppcart-*` routes including `ppcart-coupon` / `ppcart-pay-plan` (#777) with gated leftover `sc-*` redirects |
| 11 | AJAX actions / nonces | Done — canonical `wp_ajax_ppcart_*` and `ppcart_*` nonces with gated legacy bridges |
| 12 | CPT / taxonomy slugs | Done — opt-in Data migration; live helpers |
| 13a | Option keys | Done — canonical `wp_options` rows; leftover copies then deletes |
| 13b | Post-meta / `$this->prefix` | Done — first-party save/read `_ppcart_*` only; leftover names in Compatibility Mode; leftover rows via Data migration |
| 14a | Shortcode tags | Done — leftover dual-register in Compatibility Mode |
| 14b | Shortcode usage | Done — emit/detect/docs/fixtures use canonical |
| 14c | Shortcode content Data migration | Done — leftover tags in posts and email HTML |
| 15 | Roles / capabilities | Done — same Data migration as slice 12 |
| 16 | Custom tables | Done — live helpers + opt-in Data migration; no Compat table alias |
| 17 | On-disk log directory | Done — keep `{uploads}/publishpress-cart/logs`; UT-221; override `PPCART_DEBUG_LOG_DIR`; no filesystem shim |
| 18 | Sibling Pro plugin / Pro-contract constants | `[~]` Free inbound stays gated (UT-194). Blocked on Pro dual-define — not `[x]`. Free JS leftover AJAX moved to 30. |
| 19 | HTML ids / classes / JS selectors | Done — hard cutover to `ppcart_` / `ppcart-`; form `name=` is 26; leftover `_sc_*` ids done in 25b |
| 20 | PHP method names containing `sc_` | Done — canonical methods; no Compat method aliases; UT-224 / IT-311 |
| 21 | JS localize / `window.*` | Done — admin + public; no Compat JS aliases; UT-225–226 / IT-312–313 |
| 22 | Unlisted PHP runtime globals | Done — `$ppcart_checkout_*`; leftover `sc_checkout_*` aliases when Compat on; UT-227 / IT-314 |
| 23 | Dashboard widget id | Done — `ppcart_dashboard_widget`; no Compat leftover id; UT-228 / IT-315 |
| 24 | Transient prefixes | Done — `ppcart_*`; leftover rows copy at boot; Compat leftover names; UT-229 / IT-316 |
| 25a | Helper-call leftover `_sc_*` literals | Done — suffixes not leftover keys; UT-230 |
| 25b | Leftover HTML ids / JS / first-party CSS | Done — `#_ppcart_*` / `rid_ppcart_*` / `repeater_ppcart_*`; metabox `ppcart-*`; UT-234 / IT-319 |
| 26 | Form `name=` / request field names | Done — canonical emit/read; Compat POST/GET bridge when on; leftover `admin_action_sc_duplicate_product` dual-register; UT-231–232 / IT-317–318 |
| 27 | Stripe object metadata keys | Done — write/read `ppcart_*`; Compat reads leftover when on; UT-235 / IT-327 |
| 28 | Leftover CPT / taxonomy strings | Done — canonical source strings; live helpers row-state; leftover FSE/rewrite when Compat on; UT-236 / IT-328 |
| 29 | REST `sc/v1` | Done — Gutenberg `publishpress-cart/v1`; leftover unregistered; hard cutover; UT-237 / IT-329 |
| 30 | Free JS leftover Pro AJAX | Done — Free JS posts `ppcart_*`; Compat maps leftover; UT-238 / IT-330 |
| 31 | Runtime globals `$studiocart` / `$scFiles` | Done — `$ppcart_public` / `$ppcart_files` / `$ppcart_product_fields` / `$ppcart_is_admin_screen`; leftover names Compat on; UT-248 / IT-332 / UT-251; Compat companion UT-250 / IT-333 |
| 32 | Admin bulk actions `sc_make_*` | Done — `ppcart_make_*` / `ppcart_sync_stripe` / `bulk_ppcart_*`; hard cutover; UT-252 / IT-334 |
| 33 | Log viewer `sc_view_log` / leftover nonces | Done — canonical emit/read; leftover GET/nonce via Compat copy; UT-253 / UT-254 / IT-335 |
| 34 | Integration `do_action( 'sc_*' )` tags | Done — `ppcart_order_*` / `ppcart_subscription_*` / `ppcart_renewal_*`; leftover listeners Compat on; UT-255 / UT-256 / IT-336 |
| 35 | Checkout leftovers | Done — `ppcart_card_details_fields` / `ppcart_orderbumps` / `ppcart_qty`; leftover `sc_qty` Compat on; UT-257 / UT-258 / IT-337 |
| 40 | CSV `sc-csv-export` / `sc_csv_escape_cell` | Done — `ppcart-csv-export` / `ppcart_csv_escape_cell`; leftover `sc-csv-export` Compat 302; UT-259 / UT-260 / IT-338 |
| 39 | JS `sc_*` functions + `studiocart/orderform/*` | Done — `ppcart_parse_json_response` / `ppcart_validate` / `ppcart_do_lead_capture` / `ppcart/orderform/*`; hard cutover; UT-261 |
| 36 | Integration repeater `sc_sub_*` ids | Done — `ppcart_sub_prod_id` / `ppcart_sub_plan_id` / `ppcart_sub_cancel`; leftover inner keys Compat on; UT-262 / UT-263 / IT-339 |
| 37 | Invoice format values `sc_pns` | Done — `ppcart_pns` / `ppcart_pn` / `ppcart_ns` / `ppcart_n`; leftover `sc_*` Compat on; UT-264 / UT-265 / IT-340 |
| 38 | Tax-rate inner `_sc_tax_rate_*` keys | Done — `_ppcart_tax_rate_title` / `_ppcart_tax_rate_slug` / `_ppcart_tax_rate`; leftover `_sc_*` Compat on; UT-266 / UT-267 / IT-341 |
| 41 | Cosmetic `$sc_*` locals | Done — `$ppcart_*` / `$posted_ppcart_*` / `$request_ppcart_*` / `$query_ppcart_*`; no shim; UT-268 |
| 42 | Theme override `yourtheme/studiocart` | Done — `yourtheme/publishpress-cart/`; leftover `studiocart/` Compat on; UT-269 / UT-270 / IT-342 |
| 43 | Webhook / invoice query vars | Done — `ppcart-webhook` / `ppcart-api` / `ppcart-invoice`; leftover Compat 302 |
| 44 | Leftover hook fires / lookups | Done — canonical fires; leftover listeners Compat on |
| 45 | JS leftovers | Done — `is_ppcart_checkout` / `ppcart_oto_get`; hard cutover |
| 46 | `NCSLogger` | Done — `ppcartLogger`; hard cutover |
| 47 | Admin POST / notice keys | Done — canonical `admin_post_ppcart_*`; leftover GET Compat on |
| 48 | Pro lock keys | Done — `ppcart_pro_*`; leftover `sc_pro_*` dropped |
| 49 | Integration service ids | Done — `ppcart_subscription` / `ppcart_refund_order` / `ppcart_wpdomainchecker` |
| 50 | Leftover option-name literals | Done — `_ppcart_*`; leftover `_sc_*` sensitive Compat on |
| 51 | Identity leftovers | Done — `{publishpress_cart}`; leftover `{studiocart}` Compat |
| 52 | Pro class callers | Done — Free calls `PPCart_VAT` / `PPCart_Tax` |
| 53 | Cosmetic leftovers | Done — `$is_ppcart_stripe_*`; hard cutover |
| 54 | Leftover shortcode detectors | Done — Cart canonical `has_shortcode` only |
| 55 | Leftover meta names in comments | Done — `_ppcart_*` comments only |
| 56 | Feature id / Compat option key | Done — `ppcart-compatibility-mode` |
| 57 | Divi leftover classes | Closed by audit — no live Free/Compat source |
| 58 | Storefront HTML/JS/CSS leftovers | Done — hard cutover `ppcart-*` classes/ids; UT-292 |
| 59 | Admin HTML/JS leftovers | Done — hard cutover `ppcart-*` / `data-ppcart-*` / `ppcart*Tab` + jQuery `ppcart-editor-*` / `'ppcart-' + optionId` (slice **65**); UT-293 / UT-300 |
| 60 | Gutenberg leftover block | Done — Cart canonical `publishpress-cart/checkout-form`; leftover Compat dual-register when on; UT-294 / UT-295 / IT-351 |
| 61 | Leftover admin AJAX + JS globals | Done — Cart `ppcart_ajax_nonce` / `ppcart_action` / `ppcartStripe` / `ppcart_tax_settings`; leftover POST + `sc_tax_settings` Compat on; UT-296 / IT-352 |
| 62 | Request `name=` / GET leftovers | Done — Cart hidden `$atts['name']` / GET `ppcart-slm-order`; leftover GET Compat on; UT-297 / IT-353 |
| 63 | Leftover recognition | Done — Cart canonical-only; Compat read-through + leftover FSE register; UT-298 / IT-354 |
| 64 | Compat `ppcart_compat_*` | Done — metadata bridges `ppcartcomp_*` in Compat; UT-299 / IT-355 |
| 65 | Admin JS TinyMCE follow-up | Done — `ppcart-editor-*` jQuery data + `'ppcart-' + optionId`; UT-300 / IT-356 |
| 66 | CPT/taxonomy leftover filters | Done — Cart `ppcart_cpt_options` / `ppcart_taxonomy_options` only; leftover Compat bridge; UT-301 / IT-357 |
| 67 | Tax CSV import slug | Done — settings link `ppcart_tax_rate_csv`; Free fires `ppcart_register_importers`; Pro registers importer; UT-302 / IT-358 |
| 68 | Cosmetic `$ncs_*` locals | Done — `$ppcart_tax_table` / `$ppcart_order_items_table` / `$ppcart_order_itemmeta_table` / `$ppcart_downloads_table` / `$ppcart_stripe`; UT-303 |
| 69 | Docblocks / stale comments | Done — `@package PublishPressCart`, `_ppcart_notifications`, `ppcart_*` comment names; UT-304 |
| 70 | Mangled leftover hooks | Done — `ppcart_activate` / `ppcart_upgrade` / `ppcart_product` / `ppcart_product_price`; leftover `ncs_activate` / `sc_product` filter Compat on; UT-305 / IT-359 |
| 71 | Revoke GET keys | Done — emit/read `ppcart-revoke` / `ppcart-revoked`; leftover GET Compat on; UT-306 / IT-360 |
| 72 | Escaped HTML/JS leftovers | Done — `row-ppcart-` id; Gutenberg build `ppcart-nav-tabs`; UT-307 |
| 73 | Download upload directory | Done — Cart `ppcart-uploads`; leftover `sc-uploads` Compat extra root; UT-308 / IT-361 |
| 74 | Debug-log `Scrt*` recognition | Done — log-id regex `PPCart_Order` / `PPCart_Subscription` only; leftover `Scrt*` dropped; UT-310 |
| 75 | Merchant leftover copy / URLs | Done — `ppcart_field_id`, w.org `plugin/publishpress-cart`, PublishPress KB default; UT-311 |
| 76 | Leftover names in comments | Done — canonical names in comments/docblocks; UT-312 |
| 77 | Glued HTML/JS leftovers | Done — `data-ppcart-qty-price`; ride-along `originalPpcartSettings`; UT-313 |
| 78 | Glued `$sc*` / `$scrt_*` locals | Done — `$ppcart_order` / `$ppcart_subscription`; ride-along SC debug-logger comment; UT-314 |
| 79 | jQuery `scPE` namespace | Done — Hard `elementor/popup/show.ppcart-pe-`; UT-315 |
| 80 | Sample tax CSV filename | Done — Hard `sample_ppcart_tax_rates.csv`; phpmd comments ride-along; UT-316 |
| 81 | Test seeder `_sc_*` keys | Done — Hard suffixes / `_ppcart_*`; never strip `_sc_` in Cart meta helpers; UT-317 |

### Remaining leftovers (70–73)

2026-09-08 post-69 scan: named PHP/HTML/GET families **58–69** are `[x]`,
but first-party still has leftovers the per-family scanners never grepped.
Mangled `ppcart_ctivate` / `ppcart_pgrade` / `ppcart_roduct` escaped `sc_` /
`ncs_` greps and UT-275’s leftover list (`ppcart_heckout_complete` is banned;
these three are not). Revoke GET keys emit leftover and read canonical
(slice **71** `[x]`).
`row-sc-` HTML ids and a stale Gutenberg build (`ncs-nav-tabs`) missed slice
**58** (fixed slice **72** `[x]`). `sc-uploads` is a leftover on-disk product path, not a PHP identifier
— do not mix it with a PHP rename.

Slice **18** stays `[~]`. Do **not** reopen **58–73**.

**Unbreakable:** Cart first-party never emits, reads, writes, or *recognizes*
leftover names. Leftover paths live only in `publishpress-cart-compat`.
Intentional Cart exception: `PPCart_Studiocart_Conflict`. Do not silent-migrate
files on disk. Do not Compat-bridge mangled `ppcart_ctivate` names — those were
never shipped StudioCart APIs.

Post-69 leftover queue **complete**. Post-73 leftover queue **complete** (slices **74–76**
done) — see [Remaining leftovers (74–76)](#remaining-leftovers-74-76). Post-76 leftover
queue **complete** (slice **77** done) — see [Remaining leftovers (77)](#remaining-leftovers-77).
Post-77 leftover queue **complete** (slice **78** done).
Next unused IDs: **UT-318** / **IT-362** (do not reuse **UT-309**).

#### Likely bugs (rename is also a fix)

| Leftover still emitted | Canonical sibling already in code | Family |
|------------------------|-----------------------------------|--------|
| `do_action('ppcart_ctivate')` / `add_action('ppcart_ctivate')` | `ppcart_activate` (bootstrap function already uses this name) | 70 |
| `do_action('ppcart_pgrade')` / `add_action('ppcart_pgrade')` | `ppcart_upgrade` | 70 |
| `apply_filters('ppcart_roduct')` / `ppcart_roduct_price` | `ppcart_product` / `ppcart_product_price` | 70 |
| Links `&sc-revoke=` vs handler `$_GET['ppcart-revoke']` | emit and read `ppcart-revoke` | 71 |
| Redirect `&sc-revoked=` vs notice `$_GET['ppcart-revoked']` | emit and read `ppcart-revoked` | 71 |
| `id="row-sc-{field_id}"` | `row-ppcart-` | 72 |
| Gutenberg **build** `ncs-nav-tabs` (source already `ppcart-nav-tabs`) | rebuild `account-blocks.js` | 72 |

#### 70. Mangled leftover hooks — `[x]` Hard (+ Compat leftover names if shipped)

Bad replace of leftover `sc_a` / `sc_u` / `sc_p` (or `ncs_a` / `ncs_u`) left
first-party hook tags that look `ppcart_*` but are missing letters. Internally
consistent today (fire and listen on the same mangled tag), so tables still
get created — Pro/third parties listening on documented `ppcart_activate` miss
them. `docs/hooks-manifest.md` wrongly lists them as `canonical`.

| Leftover (live, never a shipped StudioCart name) | Canonical |
|--------------------------------------------------|-----------|
| `ppcart_ctivate` | `ppcart_activate` |
| `ppcart_pgrade` | `ppcart_upgrade` |
| `ppcart_roduct` | `ppcart_product` |
| `ppcart_roduct_price` | `ppcart_product_price` |

**Files:** `includes/class-ppcart-activator.php`,
`includes/class-ppcart-upgrade.php`, `includes/files/class-ppcart-files.php`,
`includes/order-items/class-ppcart-order-items.php`,
`includes/functions/product-and-order-setup.php`. Update
`docs/hooks-manifest.md`. Same-slice comment hygiene in
`product-and-order-setup.php` (“leftover `_sc_*` custom meta”, “add studiocart
plan”).

**Compat:** do **not** alias mangled `ppcart_ctivate` / `ppcart_pgrade` /
`ppcart_roduct`. Those were never shipped. If leftover hook names
`ncs_activate` / `sc_activate` / `ncs_upgrade` / `sc_upgrade` /
`sc_product` / `sc_product_price` were shipped public APIs, dual-register them
onto the new canonical tags while Compatibility Mode is on (hook map).
**Review:** leftover filter `sc_product` vs leftover shortcode tag `sc_product`
are different WordPress APIs — do not collapse them. Bootstrap function
wrappers `activate_ncs_cart` already exist (slice 1); this family is the
**action/filter tags**, not those functions.

**Pro:** listen/fire `ppcart_activate` / `ppcart_upgrade` / `ppcart_product` /
`ppcart_product_price`. Do not copy mangled names. Leftover `ncs_activate` /
`sc_product` only via Free Compat.

**Tests:** **UT-305** scanner (first-party contains canonical, not mangled);
**IT-359** Compat leftover hook names work **on**, absent **off** (skip IT
rows that have no shipped leftover name). IT-359 strips Cart
`ppcart_activate` / `ppcart_upgrade` table-setup listeners so the hook-name
check does not CREATE custom tables and pollute IT-300. Mechanical leftover
rewrite also aliases `ncs_product` / `studiocart_product` onto the product
**filter**; leftover shortcode tag `sc_product` stays a separate map.

#### 71. Revoke GET keys — `[x]` Canonical emit/read + Compat GET copy

Slice 26/62 greps looked for `$_GET['sc-` and missed concatenated `'&sc-revoke='`.
Emit leftover, read canonical — **revoke was broken**.

| Leftover | Canonical |
|----------|-----------|
| GET / query `sc-revoke` | `ppcart-revoke` |
| GET / query `sc-revoked` | `ppcart-revoked` |

**Files:** `includes/files/traits/templates/files-admin-product-form-callback.php`,
`includes/files/templates/order-downloads-metabox.php`,
`includes/files/traits/templates/files-admin-maybe-revoke-access.php`
(handler already reads `ppcart-revoke`; redirect now writes `ppcart-revoked`),
`includes/files/traits/templates/files-admin-revoke-notice.php` (already reads
`ppcart-revoked`).

**Compat:** leftover GET `sc-revoke` / `sc-revoked` copy onto canonical while
on (slice 26 / 62 pattern). No silent migrate.

**Pro:** emit/read `ppcart-revoke` / `ppcart-revoked`. Leftover GET only via
Free Compat.

**Tests:** **UT-306** scanner; **IT-360** leftover GET works **on**, absent **off**.
Compat request-field map already had `sc-revoke` / `sc-revoked` from slice 26;
this family fixed Cart emit. Open follow-ups (unasserted canonical-wins,
metabox nonce mismatch): [Open follow-ups](#open-follow-ups-leftover-slices).

#### 72. Escaped HTML/JS leftovers — `[x]` Hard

| Leftover | Canonical |
|----------|-----------|
| `id="row-sc-{field_id}"` | `id="row-ppcart-{field_id}"` |
| Gutenberg **build** `ncs-nav-tabs` | `ppcart-nav-tabs` (source `account-navigation/edit.js` already canonical — **rebuild** `includes/integrations/gutenberg/build/account-blocks.js`) |

**Files:** `public/templates/functions/summary-and-scripts.php`; Gutenberg
account-blocks build (+ source map). No leftover `row-sc-` JS selectors found
in `public/js`. PHP account renderer already emits `ppcart-nav-tabs`.

**Compat:** none. Hard cutover. Do not dual-class.

**Pro:** match Free `row-ppcart-` and `ppcart-nav-tabs`. Do not ship leftover
`ncs-nav-tabs` in Pro editor builds.

**Tests:** **UT-307** scanner for `row-sc-` / `ncs-nav-tabs` in
`admin|includes|public|models` (include `**/build/**`; exclude tests/docs).

#### 73. Download upload directory — `[x]` Cart path + Compat leftover root

Persisted product path, not a PHP identifier. **Do not silent-move files.**
Do not mix with family **70**. Unlike slice **17** (keep
`publishpress-cart/logs`), first-party currently **emits leftover**
`sc-uploads`. Unbreakable: Cart must not emit leftover names.

| Leftover | Canonical |
|----------|-----------|
| `wp-content/uploads/sc-uploads` | `wp-content/uploads/ppcart-uploads` |

**Files:** `includes/files/traits/trait-ppcart-files-storage.php`,
`includes/files/traits/trait-ppcart-files-settings.php`. Tests/docs that lock
`sc-uploads` as the allowed root (UT-069–077 / `DownloadPathValidationTest`).

**Cart:** write and allow-list `ppcart-uploads` only.

**Compat:** extra allowed root `sc-uploads` while Compatibility Mode is on (in
memory; no disk copy). Append that root only when the directory already exists
(`realpath`); no phantom root. Existing merchant files under leftover dir work
only with Compat on until an explicit Data migration card (out of scope for
this family).

**Pro:** write/allow `ppcart-uploads`. Leftover `sc-uploads` only via Free
Compat. Do not invent `ncs-cart/uploads`.

**Tests:** **UT-308** Cart source uses `ppcart-uploads`, not `sc-uploads`;
**IT-361** leftover root allowed **on**, absent **off**.

#### Ride-alongs (not their own family)

Merchant leftover copy (`studiocart_field_id`, w.org `plugin/studiocart`,
`studiocart.co` docs URLs) is family **75**. Leftover comment names including
`_sc_product_id` in `report-filters.php` are family **76**. Debug-log
`ScrtOrder` / `ScrtSubscription` recognition is family **74**.
`sc-functionality` image path and sample CSV filename are already gone.
**`ppcsc_action`** is the Stripe Connect server contract — not a #629 leftover.
Conflict checker `studiocart.php` / `ncs-cart.php` stays in Cart.

`ppcart_filter_input()` leftover lookup is a Compat **extension point** in
Cart (slice 26); Cart does not name leftover keys there.

Docs/tools (not a product leak): `tools/extract-legacy-css.py` would recreate
Cart `includes/compat/` — retarget or remove; `languages/**` stale `#:` paths
are POT comments. `readme.txt` fork history naming Studiocart is not a leak.

#### Grep (families 70–73)

From Free plugin root. Hits in Compat-on tests, docs, and `languages/**` are
expected until that slice lands.

```bash
rg "ppcart_ctivate|ppcart_pgrade|ppcart_roduct" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**'

rg "sc-revoke|sc-revoked" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!languages/**'

rg "row-sc-|ncs-nav-tabs" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!languages/**'

rg "sc-uploads" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**'

rg 'PP_Cart_|pp_cart_|pp-cart-|_pp_cart_|PP_CART_' \
  --glob '!vendor/**' --glob '!node_modules/**'
```

Gold pattern: Cart unit scanner = first-party contains canonical, not leftover.
Compat unit = map/wrapper. Compat IT = leftover works **on**, absent **off**.
`composer test:all:mocked` in Cart; `composer test:all` in Compat when Compat
is touched.

### Remaining leftovers (74–76)

2026-09-08 post-73 scan: named PHP/HTML/GET/path families **58–73** are `[x]`.
Quoted first-party `sc_` / `sc-` / `_sc_` emit, leftover JS/CSS, `row-sc-`,
`ncs-nav-tabs`, `sc-uploads`, mangled `ppcart_ctivate` / `ppcart_pgrade` /
`ppcart_roduct`, leftover GET/POST keys, and leftover filenames are gone.

What escaped those scanners:

- Debug log viewer leftover class names `ScrtOrder` / `ScrtSubscription` in
  log text (family **74**, `[x]`). Unbreakable: Cart must not recognize leftover
  names.
- Merchant-facing copy still ships leftover `studiocart_field_id`, w.org
  `plugin/studiocart`, and `studiocart.co` docs URLs (family **75**). Earlier
  passes parked these as ride-alongs; they never got a slice.
- Family **69** only grepped `@package NCS_Cart` and a few `_sc_*` comments.
  Stale leftover names remain in comments (family **76**).

Slice **18** stays `[~]`. Do **not** reopen **58–73**.

**Unbreakable:** Cart first-party never emits, reads, writes, or *recognizes*
leftover names. Leftover paths live only in `publishpress-cart-compat`.
Intentional Cart exception: `PPCart_Studiocart_Conflict`. Do not silent-migrate.
Do not Compat-bridge leftover class names in log text — those are not a shipped
StudioCart API.

**Not leftovers:** `ppcsc_action` (Stripe Connect server contract);
`ppcart_filter_input()` leftover lookup (Compat extension point; Cart does not
name leftover keys); companion-loader path
`includes/compat/studiocart-compatibility-mode/`; `languages/**` POT comments;
`tools/extract-legacy-css.py`; `readme.txt` fork history.

Next unused IDs: **UT-318** / **IT-362**. Do not reuse **UT-309**. Slices
**74–81** used **UT-310–317**.

#### Likely bugs (rename is also a fix)

| Leftover still emitted | Canonical sibling already in code | Family |
|------------------------|-----------------------------------|--------|
| `preg_match('/(?:ScrtOrder|PPCart_Order)…/')` | `PPCart_Order` already in the same regex | 74 |
| `preg_match('/(?:ScrtSubscription|PPCart_Subscription)…/')` | `PPCart_Subscription` already in the same regex | 74 |
| Help text `studiocart_field_id` | example should be `ppcart_field_id` | 75 |
| `wordpress.org/support/plugin/studiocart` | `plugin/publishpress-cart` | 75 |
| Default `https://studiocart.co/docs/…` | PublishPress knowledge-base URL (filter already exists) | 75 |

#### 74. Debug-log `Scrt*` recognition — `[x]` Hard

Log-id detection still matches leftover model class names in stored debug-log
lines. Canonical-only would keep `PPCart_Order` / `PPCart_Subscription`.
Historical `Scrt*` lines stop linking. That is leftover **recognition**, not a
shipped PHP API.

| Leftover | Canonical |
|----------|-----------|
| `ScrtOrder` in log-id regex | `PPCart_Order` only |
| `ScrtSubscription` in log-id regex | `PPCart_Subscription` only |

**Files:** `includes/logging/traits/templates/debug-log-viewer-detection-detect-record-ids.php`.

**Compat:** none. Hard cutover. Do not add a Compat filter that restores
`Scrt*` matching in Cart.

**Pro:** match Free canonical-only regex if Pro copies the detector. Do not
recognize leftover class names in Pro first-party.

**Tests:** **UT-310** scanner (first-party contains `PPCart_Order` /
`PPCart_Subscription`, does not contain `ScrtOrder` / `ScrtSubscription`
outside tests/docs/languages). No IT unless a Compat shim is added (it must
not be).

#### 75. Merchant leftover copy / URLs — `[x]` Hard

Shipped admin copy still names StudioCart. Not a PHP identifier family; still
first-party emit.

| Leftover | Canonical |
|----------|-----------|
| Help-text example `studiocart_field_id` | `ppcart_field_id` |
| `https://wordpress.org/support/plugin/studiocart/reviews/#new-post` | `https://wordpress.org/support/plugin/publishpress-cart/reviews/#new-post` |
| Default `https://studiocart.co/docs/subscriptions/using-stripe-with-recurring-payment-plans/` | `PPCART_DOCS_URL` + `subscriptions/using-stripe-with-recurring-payment-plans` (`https://docs.rambleventures.com/publishpress/publishpress-cart/`; source: `docs/public/subscriptions/using-stripe-with-recurring-payment-plans.md`). Keep filter `ppcart_stripe_subscriptions_documentation_url`. Do not keep `studiocart.co`, `knowledge-base/introduction-cart`, or `publishpress.com/docs-category` as the default. |

**Files:** `admin/metaboxes/traits/templates/integration-fields/fields-01.php`,
`admin/metaboxes/traits/templates/integration-fields/fields-02.php`,
`includes/integrations/templates/ppcart-kit-add-integration-fields.php`,
`admin/settings/traits/trait-ppcart-admin-settings-screen.php`,
`admin/settings/traits/options/payment-fields.php`.

**Compat:** none. Hard cutover. Do not dual-emit leftover help text or URLs.

**Pro:** match Free copy/URLs. Do not ship `plugin/studiocart` or `studiocart.co`
defaults.

**Tests:** **UT-311** scanner for `studiocart_field_id`, `plugin/studiocart`,
and `studiocart.co` in `admin|includes|public|models` (exclude tests/docs/
languages). No IT.

#### 76. Leftover names in comments — `[x]` Docs-only

Family **69** missed these. No runtime API change.

| Leftover | Canonical |
|----------|-----------|
| Comment `Product matching uses \`_sc_product_id\` only` | `_ppcart_product_id` (code already uses `ppcart_meta_key('product_id')`) |
| `// Get StudioCart products.` | `// Get Cart products.` |
| Comment `sc_subscription` | `ppcart_subscription` / live helper |
| Comment `sc_session` | `ppcart_session` (or the live canonical name in that file) |
| Comment `sc_page_url` | `ppcart_page_url` (or the live canonical name in that file) |
| Comment `sc_register_public_ajax_handlers` | `ppcart_register_public_ajax_handlers` |
| Docblock `sc_cart_pro_locked_fields()` / `sc_cart_pro_upgrade_url()` | `ppcart_pro_locked_fields()` / `ppcart_pro_upgrade_url()` |

**Files:** `includes/functions/report-filters.php`,
`admin/order-metaboxes/traits/trait-ppcart-order-metabox-field-groups.php`,
`admin/controllers/subscription/traits/templates/admin-subscription-sync-ajax.php`,
`public/controllers/page/traits/templates/page-routing-ppcart-hosted-checkout-return.php`,
`public/controllers/templates/hosted-checkout-controller-create-checkout-session.php`,
`includes/bootstrap/class-ppcart-public-hook-registrar.php`,
`includes/helpers/pro-locks/field-and-email-locks.php`,
`includes/helpers/pro-locks/settings-locks.php`.

Comments that explain the unbreakable rule (`leftover \`_sc_*\` lives in
Compat`) and conflict-checker / companion-loader docs may keep the word
StudioCart when they name the external plugin or the Compat package.

**Compat:** none.

**Pro:** mirror comment hygiene when touching the same files.

**Tests:** **UT-312** scanner for the leftover comment tokens above in
first-party PHP (exclude tests/docs/languages and the conflict checker).

#### Ride-alongs (not their own family)

Do not park merchant copy or leftover comment names as ride-alongs again —
those are **75** and **76**. Glued HTML/JS leftovers from the post-76 scan are
family **77**. **`ppcsc_action`** is the Stripe Connect server contract.
Conflict checker stays in Cart. `readme.txt` fork history is not a leak.
`languages/**` POT comments rewrite on locale regen.
`tools/extract-legacy-css.py` is not a product leak.

#### Grep (families 74–76)

From Free plugin root. Hits in Compat-on tests, docs, and `languages/**` are
expected until that slice lands.

```bash
rg "ScrtOrder|ScrtSubscription" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**'

rg "studiocart_field_id|plugin/studiocart|studiocart\\.co" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**' --glob '!readme.txt'

rg "_sc_product_id|Get StudioCart products|sc_subscription|sc_session|sc_page_url|sc_register_public_ajax_handlers|sc_cart_pro_locked_fields|sc_cart_pro_upgrade_url" \
  --glob '*.php' --glob '!vendor/**' --glob '!tests/**' \
  --glob '!includes/class-ppcart-studiocart-conflict.php'

rg 'PP_Cart_|pp_cart_|pp-cart-|_pp_cart_|PP_CART_' \
  --glob '!vendor/**' --glob '!node_modules/**'
```

Gold pattern: Cart unit scanner = first-party contains canonical, not leftover.
No Compat IT for these families unless a shim is added (it must not be).
`composer test:all:mocked` in Cart; `composer test:all` in Compat when Compat
is touched.

### Remaining leftovers (77)

2026-09-08 post-76 scan: named PHP/HTML/GET/path/copy/comment families
**58–76** are `[x]`. Quoted first-party `sc_` / `sc-` / `_sc_` emit, leftover
JS/CSS with a separator, `row-sc-`, `ncs-nav-tabs`, `sc-uploads`, mangled
`ppcart_ctivate` / `ppcart_pgrade` / `ppcart_roduct`, leftover GET/POST keys,
`Scrt*`, merchant `studiocart_*` copy/URLs, and leftover comment tokens are
gone.

What escaped those scanners (glued names with **no** `_` / `-` after `sc` /
`ncs`) — **fixed slice 77**:

- Quantity custom-field markup now emits `data-ppcart-qty-price` (not glued
  `data-ppcartq-price`). Checkout JS does not read it (`data-price` /
  `.data('price')`); server-side qty pricing uses `$field['qty_price']`.
- Settings JS local is `originalPpcartSettings` (`window.ppcart_settings`
  stays canonical). Hard cutover; no Compat.

Slice **18** stays `[~]`. Do **not** reopen **58–77**. Gitignored `dist/` may
still contain stale leftovers; it is not first-party source. Post-77 leftover
queue **complete** (slice **78** done).

**Unbreakable:** Cart first-party never emits, reads, writes, or *recognizes*
leftover names. Leftover paths live only in `publishpress-cart-compat`.
Intentional Cart exception: `PPCart_Studiocart_Conflict`. Do not silent-migrate.
Do not Compat-bridge `data-scq-price` or `originalNcs` — those are not a shipped
StudioCart PHP API.

**Not leftovers:** `ppcsc_action` (Stripe Connect server contract);
`ppcart_filter_input()` leftover lookup (Compat extension point; Cart does not
name leftover keys); companion-loader path
`includes/compat/studiocart-compatibility-mode/`; `languages/**` POT comments;
`tools/extract-legacy-css.py`; `readme.txt` fork history; gitignored `dist/`.

Next unused IDs: **UT-318** / **IT-362**. Do not reuse **UT-309**. Slices
**77–81** used **UT-313–317**.

#### Likely bugs (rename is also a fix)

| Leftover still emitted | Canonical sibling already in code | Family |
|------------------------|-----------------------------------|--------|
| `data-scq-price` on quantity custom fields | qty pricing already uses `$field['qty_price']`; JS already uses `data-price` | 77 |
| `var originalNcs = window.ppcart_settings` | `window.ppcart_settings` is already canonical | 77 |

#### 77. Glued HTML/JS leftovers — `[x]` Hard

`scq` is StudioCart `sc` + quantity with no separator, so `sc-` / `sc_` greps
never matched. Hard cutover. Quantity price is not a request field (slice 26);
it is a `data-*` attribute.

| Leftover | Canonical |
|----------|-----------|
| `data-scq-price` | `data-ppcart-qty-price` |
| `originalNcs` (ride-along JS local) | `originalPpcartSettings` |

**Files:** `public/templates/functions/plan-coupon-fields.php`,
`admin/js/ppcart-settings.js`.

**Compat:** none. Hard cutover. Do not dual-emit leftover `data-scq-price`.
Do not add a Compat JS alias for `originalNcs`.

**Pro:** match Free `data-ppcart-qty-price` if Pro copies the quantity field
markup. Match `originalPpcartSettings` if Pro copies the settings override.
Do not emit leftover glued `scq` attributes.

**Tests:** **UT-313** scanner (first-party contains `data-ppcart-qty-price` /
`originalPpcartSettings`, does not contain `data-scq-price` / `originalNcs`
outside tests/docs/languages/`dist/`). No IT unless a Compat shim is added
(it must not be).

#### Ride-alongs (not their own family)

`originalNcs` rides with **77** (same post-76 glued-name scan; both HTML/JS
hard cutover; no Compat). Do not park merchant copy or leftover comment names
as ride-alongs — those are **75** and **76**. **`ppcsc_action`** is the Stripe
Connect server contract. Conflict checker stays in Cart. `readme.txt` fork
history is not a leak. `languages/**` POT comments rewrite on locale regen.
`tools/extract-legacy-css.py` is not a product leak. Gitignored `dist/` is
not a leak.

#### Grep (family 77)

From Free plugin root. Hits in Compat-on tests, docs, `languages/**`, and
gitignored `dist/` are expected until that slice lands.

```bash
rg "data-scq-price|originalNcs" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**' --glob '!dist/**'

rg 'PP_Cart_|pp_cart_|pp-cart-|_pp_cart_|PP_CART_' \
  --glob '!vendor/**' --glob '!node_modules/**'
```

Gold pattern: Cart unit scanner = first-party contains canonical, not leftover.
No Compat IT unless a shim is added (it must not be).
`composer test:all:mocked` in Cart; `composer test:all` in Compat when Compat
is touched.

### Remaining leftovers (78)

2026-09-08 post-77 scan: named PHP/HTML/GET/path/copy/comment/glued-HTML
families **58–77** are `[x]`. Quoted first-party `sc_` / `sc-` / `_sc_` emit,
leftover JS/CSS with a separator, glued `data-scq-price` / `originalNcs`,
`$sc_*` locals (underscore after `sc`), and `$ncs_*` locals are gone.

What escaped those scanners (glued PHP locals with **no** `_` after `sc`,
and leftover `Scrt` nickname as `$scrt_*`):

- `$scrt_order` — leftover `ScrtOrder` class nickname as a local. UT-268
  bans `$sc_*` only. Family **74** banned `ScrtOrder` the class, not this
  local.
- `$scorder` / `$scsub` — glued `$sc` + `order` / `sub` (same UT-268 miss).
- Ride-along comment `existing SC debug logger` — family **76** grepped a
  token list, not the `SC` abbreviation.

Slice **18** stays `[~]`. Do **not** reopen **58–77**.

**Unbreakable:** Cart first-party never emits, reads, writes, or *recognizes*
leftover names. Leftover paths live only in `publishpress-cart-compat`.
Intentional Cart exception: `PPCart_Studiocart_Conflict`. Do not silent-migrate.
Do not Compat-bridge `$scorder` / `$scsub` / `$scrt_order` — those are not a
shipped StudioCart PHP API.

**Not leftovers:** `$schedule` / `$screen` / `$script` / `$scheme` / `$score` /
`$schema` / `$scripts` / `$screens` (English `sc*` words). `ppcsc_action`
(Stripe Connect server contract); `ppcart_filter_input()` leftover lookup
(Compat extension point; Cart does not name leftover keys); companion-loader
path `includes/compat/studiocart-compatibility-mode/`; `languages/**` POT
comments; `tools/extract-legacy-css.py`; `readme.txt` fork history;
gitignored `dist/`. Conflict checker stays in Cart.

Next unused IDs: **UT-318** / **IT-362**. Do not reuse **UT-309**. Slices
**78–81** used **UT-314–317**. No IT unless a Compat shim is added (it must
not be).

#### Likely bugs (rename is also a fix)

| Leftover still emitted | Canonical sibling already in code | Family |
|------------------------|-----------------------------------|--------|
| `$scrt_order = new PPCart_Order` | `$ppcart_order = new PPCart_Order` (UT-268 bulk handler) | 78 |
| `$scorder = new PPCart_Order` | `$ppcart_order` | 78 |
| `$scsub = new PPCart_Subscription` | `$ppcart_subscription = new PPCart_Subscription` (UT-268 bulk handler) | 78 |
| Comment `existing SC debug logger` | Cart debug logger (`$ppcart_debug_logger`) | 78 |

#### 78. Glued `$sc*` / `$scrt_*` locals — `[x]` Hard

Cosmetic locals. Hard cutover. Same family as slices **41** / **68**, missed
because those greps required `_` after `sc` / `ncs`.

| Leftover | Canonical |
|----------|-----------|
| `$scrt_order` | `$ppcart_order` |
| `$scorder` | `$ppcart_order` |
| `$scsub` | `$ppcart_subscription` |
| Comment `existing SC debug logger` (ride-along) | Cart debug logger |

**Files:** `public/partials/csv-export.php`,
`public/templates/my-account/order-detail.php`,
`admin/controllers/order/templates/product-form.php`,
`admin/controllers/templates/order-list-subscription-column.php`,
`includes/stripe-sync/traits/trait-ppcart-stripe-sync-utilities.php`
(ride-along comment).

**Compat:** none. Hard cutover. Do not alias leftover local names.

**Pro:** match Free `$ppcart_order` / `$ppcart_subscription` in vendored copies
of those templates. Mirror the debug-logger comment when touching the Stripe
sync utilities trait. Hard cutover; no Compat shim.

**Tests:** **UT-314** scanner (first-party contains `$ppcart_order` /
`$ppcart_subscription` at the call sites above, does not contain `$scrt_order`
/ `$scorder` / `$scsub` / `existing SC debug logger` outside tests/docs/
languages). No IT unless a Compat shim is added (it must not be).

#### Ride-alongs (not their own family)

`existing SC debug logger` rides with **78** (same post-77 glued-name scan;
comment leftover that family **76** missed). Do not park merchant copy or
separator HTML as ride-alongs — those are **75** and **77**. **`ppcsc_action`**
is the Stripe Connect server contract. Conflict checker stays in Cart.
`readme.txt` fork history is not a leak. `languages/**` POT comments rewrite
on locale regen. `tools/extract-legacy-css.py` is not a product leak.
Gitignored `dist/` is not a leak.

#### Grep (family 78)

From Free plugin root. Hits in Compat-on tests, docs, `languages/**`, and
gitignored `dist/` are expected until that slice lands.

```bash
rg '\$scrt_order|\$scorder\b|\$scsub\b' \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**'

rg 'existing SC debug logger' \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**'

rg 'PP_Cart_|pp_cart_|pp-cart-|_pp_cart_|PP_CART_' \
  --glob '!vendor/**' --glob '!node_modules/**'
```

Gold pattern: Cart unit scanner = first-party contains canonical, not leftover.
No Compat IT unless a shim is added (it must not be).
`composer test:all:mocked` in Cart; `composer test:all` in Compat when Compat
is touched.

### Remaining leftovers (79–81)

2026-09-08 post-78 scan: families **79–81** are `[x]`. Named PHP/HTML/GET/path/copy/comment/glued-HTML/glued-local
families **58–78** are `[x]`. Separator greps (`sc_` / `_sc_` / `sc-` / `$sc_*` /
`$ncs_*` / `Scrt` emit) are clean in first-party product code outside documented
exceptions.

What escaped those scanners (glued names with **no** `_`/`-` after `sc`, plus
paths prior leftover greps excluded with `!tests/**`) — families **79–81**
fixed 2026-09-08:

- ~~`elementor/popup/show.scPE-` — leftover jQuery event namespace `scPE` (Product
  Element). Nearby un-namespaced `elementor/popup/show` is **not** this family.~~
  (slice **79** `[x]`)
- ~~`sample-data/sample_sc_tax_rates.csv` — unreferenced in PHP/docs but **not**
  in `.distignore` (ships in the WordPress.org zip). Family **67** only renamed
  the importer slug (`ppcart_tax_rate_csv`).~~ (slice **80** `[x]`)
- ~~Test seeders still pass leftover `_sc_*` keys. `ppcart_update_post_meta()`
  prepends `_ppcart_` and does **not** strip leftover `_sc_`. Passing
  `'_sc_disable_paypal'` persists `_ppcart__sc_disable_paypal`. Cart reads
  `_ppcart_disable_paypal`.~~ (slice **81** `[x]`)
- ~~Ride-along: `phpmd.ruleset.xml` comments still name `NCS_Cart_Public` /
  `NCS_` / `Scrt` (also ships).~~ (slice **80** `[x]`)

Slice **18** stays `[~]`. Do **not** reopen **43–81**.

**Unbreakable:** Cart first-party never emits, reads, writes, or *recognizes*
leftover names. Leftover paths live only in `publishpress-cart-compat`.
Intentional Cart exception: `PPCart_Studiocart_Conflict`. Do not silent-migrate.
Do not teach `ppcart_meta_key_suffix()` / `ppcart_update_post_meta()` to strip
`_sc_` — leftover recognition; Compat only. Do not Compat-bridge `.scPE-` or
the sample CSV filename.

**Not leftovers:** un-namespaced `elementor/popup/show` (not the `scPE`
namespace). `ppcsc_action` (Stripe Connect server contract);
`ppcart_filter_input()` leftover lookup (Compat extension point; Cart does
not name leftover keys); companion-loader path
`includes/compat/studiocart-compatibility-mode/`; `languages/**` POT comments;
`tools/extract-legacy-css.py`; `readme.txt` fork history; gitignored `dist/`.
Conflict checker stays in Cart. Leftover cap `edit_sc_products` in the same
legacy checkout-block test is **not** family **81** (capability names).
Cosmetic `$had_sc_order_request` / `$previous_sc_order` locals in that file
are **not** family **81**.

Next unused IDs: **UT-321** / **IT-362**. Do not reuse **UT-309**. Slice **80**
used **UT-316**; **81** used **UT-317**; **82** used **UT-320**.

#### Likely bugs (rename is also a fix)

| Leftover still emitted | Canonical sibling already in code | Family |
|------------------------|-----------------------------------|--------|
| `elementor/popup/show.scPE-` + form id | `elementor/popup/show.ppcart-pe-` + form id | 79 |
| `sample_sc_tax_rates.csv` in the zip | importer slug `ppcart_tax_rate_csv` (family **67**) | 80 |
| `ppcart_fixtures_update_post_meta(..., '_sc_disable_paypal', ...)` | Cart reads `_ppcart_disable_paypal` | 81 |
| phpmd comments `NCS_Cart_Public` / `NCS_` / `Scrt` | `PPCart_Public` / `PPCart_` | 80 |

#### 79. jQuery `scPE` namespace — `[x]` Hard

Leftover glued event namespace. Hard cutover. Missed because leftover JS greps
required `_` or `-` after `sc`.

| Leftover | Canonical |
|----------|-----------|
| `elementor/popup/show.scPE-` + form id | `elementor/popup/show.ppcart-pe-` + form id |

**Files:** `public/js/ppcart-public.js` (~line 1003).

**Compat:** none. Hard cutover. Do not alias `.scPE-`.

**Pro:** match Free `elementor/popup/show.ppcart-pe-` if Pro vendors or copies
this Elementor popup handler. Hard cutover; no Compat shim.

**Tests:** **UT-315** scanner (first-party contains `elementor/popup/show.ppcart-pe-`,
does not contain `.scPE-` outside tests/docs/languages). No IT unless a Compat
shim is added (it must not be).

**Free:** `eaedf4d1` — hard cutover; UT-315 green; Compat suite unchanged.

#### 80. Sample tax CSV filename — `[x]` Hard

Shipped sample filename. Hard cutover. Family **67** renamed the importer slug
only.

| Leftover | Canonical |
|----------|-----------|
| `sample-data/sample_sc_tax_rates.csv` | `sample-data/sample_ppcart_tax_rates.csv` |
| phpmd comment `NCS_Cart_Public` (ride-along) | `PPCart_Public` |
| phpmd comment `NCS_` / `Scrt` (ride-along) | `PPCart_` / drop leftover nicknames |

**Files:** `sample-data/sample_sc_tax_rates.csv` (rename the file),
`phpmd.ruleset.xml` (ride-along comments).

**Compat:** none. Hard cutover. Do not alias the leftover filename.

**Pro:** do not ship `sample_sc_tax_rates.csv`. Mirror phpmd comment names when
touching the ruleset. Hard cutover; no Compat shim.

**Tests:** **UT-316** scanner (first-party / shipped sample path contains
`sample_ppcart_tax_rates.csv`, does not contain `sample_sc_tax_rates.csv`;
`phpmd.ruleset.xml` does not contain `NCS_Cart_Public` / leftover `NCS_` /
`Scrt` comments). No IT unless a Compat shim is added (it must not be).

**Free:** `3368e2c5` — hard cutover; UT-316 green; Compat suite unchanged.

#### 81. Test seeder `_sc_*` keys — `[x]` Hard

Playwright / legacy seeders still pass leftover `_sc_*` keys. Hard cutover in
tests. Prior leftover greps used `!tests/**`.

Canonical: pass the **suffix** to `ppcart_update_post_meta()` /
`ppcart_fixtures_update_post_meta()` (`disable_paypal`, not `_sc_disable_paypal`).
Direct `update_post_meta` / `update_option` must use `_ppcart_*`.

| Leftover | Canonical |
|----------|-----------|
| `ppcart_fixtures_update_post_meta(..., '_sc_disable_paypal', ...)` | `ppcart_fixtures_update_post_meta(..., 'disable_paypal', ...)` |
| `update_post_meta(..., '_sc_plan_heading', ...)` | `update_post_meta(..., '_ppcart_plan_heading', ...)` or helper + `plan_heading` |
| `update_option('_sc_stripe_api', ...)` (legacy checkout-block seeder) | `update_option('_ppcart_stripe_api', ...)` |
| Fixture arrays keyed `'_sc_plan_heading'` etc. | suffix keys through the helper, or `_ppcart_*` on raw `update_post_meta` |

**Files:** `tests/ppcart-fixtures/ppcart-fixtures.php`, `tests/ppcart-fixtures/ppcart-smoke-fixtures.php`,
`tests/ppcart-fixtures/ppcart-regression-fixtures.php`, `tests/legacy/integration/**`,
`tests/legacy/e2e/checkout-block/run.sh`.

**Compat:** none. Do **not** strip `_sc_` in Cart meta helpers.

**Pro:** none (Free test seeders). Do not copy leftover `_sc_*` seeder keys
into Pro tests.

**Tests:** **UT-317** scanner (Playwright/legacy seeders do not pass leftover
`_sc_*` keys into Cart meta helpers or raw `update_post_meta` / `update_option`).
No IT unless a Compat shim is added (it must not be).

**Free:** `dd08ca94` — hard cutover; UT-317 green; Compat suite unchanged.

#### Ride-alongs (not their own family)

`phpmd.ruleset.xml` leftover class/prefix comments ride with **80** (same
post-78 shipped-file scan; comment leftover that family **76** missed). Do not
park JS namespaces or test seeder keys as ride-alongs — those are **79** and
**81**. **`ppcsc_action`** is the Stripe Connect server contract. Conflict
checker stays in Cart. `readme.txt` fork history is not a leak. `languages/**`
POT comments rewrite on locale regen. `tools/extract-legacy-css.py` is not a
product leak. Gitignored `dist/` is not a leak.

#### Grep (families 79–81)

From Free plugin root. Hits in Compat-on tests, docs, `languages/**`, and
gitignored `dist/` are expected until that slice lands.

```bash
rg 'scPE-' \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**'

rg 'sample_sc_tax_rates' \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!docs/**' \
  --glob '!languages/**'

rg 'NCS_Cart_Public|NCS_|Scrt' phpmd.ruleset.xml

rg "'_sc_|\"_sc_" tests/ppcart-fixtures/ppcart-fixtures.php tests/ppcart-fixtures/ppcart-smoke-fixtures.php \
  tests/ppcart-fixtures/ppcart-regression-fixtures.php tests/legacy/

rg 'PP_Cart_|pp_cart_|pp-cart-|_pp_cart_|PP_CART_' \
  --glob '!vendor/**' --glob '!node_modules/**'
```

Gold pattern: Cart unit scanner = first-party (and seeders for **81**) contain
canonical, not leftover. No Compat IT unless a shim is added (it must not be).
`composer test:all:mocked` in Cart; `composer test:all` in Compat when Compat
is touched.

---

### Remaining leftovers (82)

2026-09-10: StudioCart `sc_cart_*` was mechanically rewritten to a doubled
first-party prefix. Canonical drops the extra `cart_` segment.

- [x] **82** Doubled `ppcart_cart_` prefix — functions/hooks/roles
  `ppcart_supports` / `ppcart_is_pro` / `ppcart_loaded` / `ppcart_pro_*` /
  `ppcart_manager`. Leftover `sc_cart_*` Compat wrappers and hook collapse
  (`sc_cart_loaded` / `ncs_cart_loaded` → `ppcart_loaded`). Checkout-window
  product meta is `_ppcart_checkout_starts` / `_ppcart_checkout_ends` /
  `_ppcart_checkout_ended_action` / `_ppcart_checkout_ended_redirect` /
  `_ppcart_checkout_ended_message` (not `_ppcart_cart_open`; avoids
  `_ppcart_redirect`). Leftover `_sc_cart_open` (and siblings) maps in Compat
  only. Coverage **UT-320**. Free **82** done (`186e0f69`).

Do **not** reopen **43–82**. Slice **18** stays `[~]`. Next unused IDs:
**UT-321** / **IT-362**.

---

## Shortcodes (slice 14)

Canonical tags use the `ppcart_` prefix. Hyphenated StudioCart tags become
underscore tags (`studiocart-form` → `ppcart_form`). Compatibility Mode
dual-registers leftover tags so **unmigrated** post content still renders
while the toggle is on.

Stored leftover tags in `post_content` / `post_excerpt`, email template
options, and product notification HTML move through the merchant **Data
migration** card (`includes/compat/shortcode-tag-migration/`). That is a
one-way leftover → canonical string rewrite with a restore backup, not a
silent upgrade and not a second `add_shortcode()` shim. After a site
migrates, stored content is canonical; leftover registration stays a
Compatibility Mode safety net for rows that have not been rewritten.

| Legacy tag | Canonical tag | Callback / notes |
|------------|---------------|------------------|
| `studiocart-form` | `ppcart_form` | Product checkout form |
| `studiocart-receipt` | `ppcart_receipt` | Receipt |
| `studiocart-store` | `ppcart_store` | Product archive |
| `studiocart-order-downloads` | `ppcart_order_downloads` | Order file downloads |
| `studiocart_account` | `ppcart_account` | My Account page |
| `studiocart_account_link` | `ppcart_account_link` | My Account link |
| `studiocart_order_detail` | `ppcart_account_order_detail` | Account order-detail page. Distinct from `sc_order_detail`. |
| `studiocart_subscription_detail` | `ppcart_account_subscription_detail` | Account subscription-detail page |
| `sc_customer_bought_product` | `ppcart_customer_bought_product` | Purchase check |
| `sc_customer_has_subscription` | `ppcart_customer_has_subscription` | Subscription check |
| `sc_order_detail` | `ppcart_order_detail` | Order field extractor (`ppcart_order_detail()`) |
| `sc_plan` | `ppcart_plan` | Plan field extractor |
| `sc_product` | `ppcart_product` | Product field extractor |
| `sc_order_summary_items_view` | `ppcart_order_summary_items_view` | Checkout template helper |

`studiocart_order_detail` and `sc_order_detail` are different shortcodes. Do
not collapse them onto one canonical tag.

First-party `add_shortcode()` uses only `ppcart_*`. Compatibility Mode
dual-registers every shipped leftover tag onto the same callback. First-party
emitters and detectors use canonical tags only. Leftover `has_shortcode()`
checks and leftover-tag helpers live only in Compat (slice 54). Email HTML
always replaces `[ppcart_order_downloads]` and replaces leftover
`[studiocart-order-downloads]` only while Compatibility Mode is on.

---

## Exceptions (do not treat as prefix leaks)

These are canonical product strings or Compat-owned leftover homes — not Cart
first-party leftover APIs. Divi classes and `namespace Studiocart` remain
leftover family **57**, not exceptions.

| Item | Value | Reason |
|------|-------|--------|
| Text domain | `publishpress-cart` | WordPress i18n; translation files depend on it |
| Plugin header display name | PublishPress Cart | User-facing product branding |
| Public plugin slug | `publishpress-cart` | WP.org / directory slug |
| Compatibility Mode package | StudioCart / `sc_*` / `NCS_*` names **inside** Compat `includes/compat/studiocart-compatibility-mode/` | That is the shim home by design |
| Pro affiliate aliases | Pro `includes/compat/legacy.php` | Unrelated to #629 |
| On-disk debug log directory | `{uploads}/publishpress-cart/logs` | Product path (`wp_upload_dir()['basedir']`; override `PPCART_DEBUG_LOG_DIR`). **Keep.** PHP constants already `PPCART_*`. No filesystem shim. Slice 17; UT-221. |
| Download upload directory | `wp-content/uploads/ppcart-uploads` | Product path. Slice **73** done. Leftover `sc-uploads` extra allowed root while Compat on; no silent file move. |

---

## Grep after each Free PHP slice

From the Free plugin root. Ignore the compatibility package when hunting
leftover first-party prefixes:

```bash
rg 'NCS_Cart_|ncs_cart_|ncs-cart-|activate_ncs_cart|deactivate_ncs_cart|upgrade_ncs_cart|run_ncs_cart' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!vendor/**' --glob '!lib/vendor/**' --glob '!node_modules/**' \
  --glob '!tests/**' --glob '!languages/**'

rg '\$sc_stripe|\$sc_currency|\$sc_debug_logger|\$scp\b' \
  --glob '*.php' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!vendor/**' --glob '!tests/**'

rg "esc_html__?\([^)]*'ncs-cart'" --glob '*.php' \
  --glob '!vendor/**'

rg '\$ncs_cart_|\$__ncs_cart_template_result' \
  --glob '*.php' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!vendor/**' --glob '!lib/vendor/**' --glob '!tests/**'

rg 'PP_Cart_|pp_cart_|pp-cart-|_pp_cart_|PP_CART_' \
  --glob '!vendor/**' --glob '!node_modules/**'

rg 'class Scrt|class SC_|function sc_|function studiocart_' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!tests/**' --glob '!vendor/**'

rg "add_shortcode\(\s*'(studiocart_|studiocart-|sc_)" \
  --glob '*.php' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!tests/**' --glob '!vendor/**'

rg 'id="sc_|id="studiocart|for="sc_|class="[^"]*(sc-|sc_|studiocart|ncs-cart)' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!vendor/**' --glob '!lib/vendor/**' --glob '!node_modules/**'

rg "['\"]#(sc_|sc-|studiocart)|['\"]\\.(sc-|sc_|studiocart|ncs-cart)" \
  --glob '*.{js,css,php}' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!vendor/**' --glob '!tests/**'

# Quoted 'sc_ / "sc_ and _sc_ (meta keys, mid-name leftovers)
rg "['\"_]sc_" \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!vendor/**' --glob '!lib/vendor/**' --glob '!node_modules/**'

# Post-review audit (2026-09-08) — families 65–69
rg "apply_filters\s*\(\s*['\"]sc-cart-(cpt-options|taxonomy-options)" \
  --glob '*.php' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!tests/**'

rg "ncs-cart_tax_rate_csv|'sc-' \+|\\.data\\('sc-editor" \
  --glob '*.{php,js}' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!tests/**'

rg '\$ncs_(tax|meta|stripe)\b' \
  --glob '*.php' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!tests/**'

rg '@package NCS_Cart' \
  --glob '*.php' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!tests/**'

# Post-69 audit (2026-09-08) — families 70–73
rg "ppcart_ctivate|ppcart_pgrade|ppcart_roduct" \
  --glob '*.php' \
  --glob '!includes/compat/studiocart-compatibility-mode/**' \
  --glob '!tests/**'

rg "sc-revoke|sc-revoked|row-sc-|ncs-nav-tabs|sc-uploads" \
  --glob '!vendor/**' --glob '!node_modules/**' \
  --glob '!tests/**' --glob '!languages/**'

# Post-73 audit (2026-09-08) — families 74–76
rg "ScrtOrder|ScrtSubscription" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**'

rg "studiocart_field_id|plugin/studiocart|studiocart\\.co" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**' --glob '!readme.txt'

rg "_sc_product_id|Get StudioCart products|sc_subscription|sc_session|sc_page_url|sc_register_public_ajax_handlers|sc_cart_pro_locked_fields|sc_cart_pro_upgrade_url" \
  --glob '*.php' --glob '!vendor/**' --glob '!tests/**' \
  --glob '!includes/class-ppcart-studiocart-conflict.php'

# Post-76 audit (2026-09-08) — family 77
rg "data-scq-price|originalNcs" \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**' --glob '!dist/**'

# Post-77 audit (2026-09-08) — family 78
rg '\$scrt_order|\$scorder\b|\$scsub\b' \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**'

rg 'existing SC debug logger' \
  --glob '!vendor/**' --glob '!node_modules/**' --glob '!tests/**' \
  --glob '!docs/**' --glob '!languages/**'
```

Expect remaining hits **only** in `includes/compat/studiocart-compatibility-mode/`
(or cart-compat maps/migrations), Compat-on tests, Data-migration leftover
**maps**, option-key-migration transient LIKE/mapper prefixes, catalog/docs
that describe bridges, and remaining families in
[Remaining leftovers (79–81)](#remaining-leftovers-79-81). Free JS no
longer posts leftover Pro AJAX actions (slice **30** done). Remaining
first-party quoted `sc_` hits should not include `sc_check_username` /
`sc_capture_lead`. Also grep mangled leftover hooks
`ppcart_ctivate|ppcart_pgrade|ppcart_roduct` (family **70**, done) and
`ScrtOrder|ScrtSubscription` (family **74**). Also grep glued leftovers
`data-scq-price|originalNcs` (family **77**, done) and glued locals
`$scrt_order|$scorder|$scsub` (family **78**, done). Also grep
`sample_sc_tax_rates` / seeder `'_sc_*'` (family **80** done; **81** pending).

The `['"_]sc_` grep no longer flags checkout runtime globals (slice 22 renamed
them). The dashboard widget id was renamed in slice 23. Transient prefixes were
renamed in slice 24. Helper-call leftover `'_sc_*'` meta-key arguments were
renamed in slice 25a. Leftover HTML ids were renamed in slice 25b. Stripe object
metadata keys were renamed in slice 27. CPT/taxonomy source strings were renamed
in slice 28. REST `sc/v1` was locked absent in slice 29. Free JS Pro AJAX
actions were cut over in slice **30**. Admin and
public localize objects were renamed in slice 21.
Ignore `languages/**` (regenerate later). Post-30 first-party
leftovers are remaining slices 39, 36–38, 41–42, not
regressions of 1–40. Admin bulk
`sc_make_*` / `sc_sync_stripe` / `bulk_sc_*` were cut over in slice **32**.
Log-viewer `sc_view_log` / nonce fields were cut over in slice **33**.
Integration trigger tags were cut over in slice **34**. Checkout leftovers
were cut over in slice **35**. CSV export was cut over in slice **40**.

---

## PublishPress Cart Pro

The Pro prefix playbook now lives in [prefix-pro.md](prefix-pro.md): canonical
prefix map, per-slice Pro contract for leftover families 58–82, constants Pro
must define before requiring Free, bootstrap hooks, suggested Pro slice order,
frozen strings, inventory greps, acceptance checks, coordinated Free work, and
the Free branch log.

This file stays the **Free** #629 tracker. Every Free slice that changes an
identifier Pro must call still has to land a matching edit in
[prefix-pro.md](prefix-pro.md) in the same session — see
[Keep the Pro contract in sync](#keep-the-pro-contract-in-sync).

---

## Related

| Doc | Role |
|-----|------|
| [PREFIX_STANDARDIZATION.md](../PREFIX_STANDARDIZATION.md) | Stub — points here and to hooks-manifest regen |
| [ppcart-compatibility-mode.md](../features/ppcart-compatibility-mode.md) | Feature record |
| [prefix-pro.md](prefix-pro.md) | Pro prefix playbook; leftover slices keep it in sync so Pro can follow the same refactor |
| [pro-plugin-extraction.md](pro-plugin-extraction.md) | Free/Pro architecture split — hooks, module moves, phases |
| [hooks-manifest.md](../hooks-manifest.md) | Canonical hook inventory |

