# Pro plugin extraction plan

> **Historical.** This is a pre-rebrand extraction plan. Do not treat
> `class-ncs-cart-*`, leftover `sc_*` / `NCS_*` symbols, or `api/` as current
> Free paths. Resolve every file and symbol against
> [prefix-pro.md](prefix-pro.md) and [architecture.md](architecture.md) before
> acting. Live Free names are `PPCart_` / `ppcart_` / `PPCART_`. Leftover names
> live only in `publishpress-cart-compat`.
>
> **Naming is out of scope here.** Every identifier Pro must call, emit, or
> register lives in [prefix-pro.md](prefix-pro.md). Path lists below are a
> snapshot of the Free tree when this plan was written, not today's layout.
> Extraction and prefix work are independent: a module can move without a
> rename, and rename without moving.

Implementation guide for moving Pro-specific logic out of PublishPress Cart (Free) and into PublishPress Cart Pro, leaving Free as a hook-driven library.

**Related:** [launch-readiness.md](launch-readiness.md) §4 — *Integrate Free and Pro cleanly: treat Free as a library consumed by Pro, with no Pro logic present in the Free plugin.*

**Status:** Mid-migration. Pro detection and conditional loading exist; most implementation still lives in the Free tree behind `ppcart_is_pro()`. Per-gate removal work is tracked on GitHub with label [`pro-gate`](https://github.com/publishpress/publishpress-cart/issues?q=label%3Apro-gate) (Free drops `ppcart_supports('pro')` call sites; Pro hooks extension points). The `ppcart_pro_is_locked()` upsell layer in Free is intentionally out of scope for that label.

**Scope:** Free plugin (`publishpress-cart`) + Pro plugin (`publishpress-cart-pro`).

---

## Goals

1. Free contains **no Pro business logic** — only core checkout, orders, subscriptions, and extension points.
2. Pro **requires Free**, sets `PPCART_LOADED_BY_PRO` **before** requiring Free, and registers premium behavior via hooks and filters.
3. Both plugins can be active without fatal errors; Free takes precedence when loaded by Pro.
4. No duplicated code between Free and Pro.

---

## Current architecture

### Pro detection

```php
function ppcart_is_pro() {
    return defined('PPCART_LOADED_BY_PRO') && true === PPCART_LOADED_BY_PRO;
}
```

The architectural requirement is ordering: Pro defines its contract constants
**before** it requires Free, so Free bootstraps knowing Pro is present.
Which names to define, the dual-define transition, and the Compatibility Mode
caveats are the prefix contract — see
[Constants Pro must define before requiring Free](prefix-pro.md#constants-pro-must-define-before-requiring-free).

Pro must define these **before** Free bootstraps (typically in `publishpress-cart-pro.php`):

| Constant | Purpose |
|----------|---------|
| `PPCART_LOADED_BY_PRO` | `true` — enables `ppcart_is_pro()` |
| `PPCART_PRO_BASE_FILE` | Pro main plugin file path (Pro-local; Free does not read this today) |
| `PPCART_PRO_BASE_DIR` | Pro plugin directory (`plugin_dir_path`) (Pro-local; Free does not read this today) |
| `PPCART_PRO_BASE_URL` | Pro plugin URL (`plugin_dir_url`) — used for shared vendor assets |
| `PPCART_PRO_PLUGIN_NAME` | Display name (optional; Free defaults to “PublishPress Cart Pro”) |
| `PPCART_PRO_LIB_VENDOR_DIR` | Pro `lib/vendor` directory for bundled translations / autoload fallback |

Free already reads `PPCART_PRO_BASE_URL` and `PPCART_PRO_LIB_VENDOR_DIR` in `publishpress-cart.php` when Pro is active. Pro-local `PPCART_PRO_BASE_FILE` / `PPCART_PRO_BASE_DIR` should be defined even though Free does not read them today.

License checks use filters (not direct Pro class calls):

- `sc_pro_license_is_valid`
- `sc_pro_can_use_premium_code`

### Loading pattern today

`includes/class-ncs-cart.php` conditionally:

- `require_once` Pro-only modules (API, collections, shipping, etc.)
- Calls `define_hooks__premium_only()` for REST routes and Pro file downloads
- Registers Pro AJAX handlers on `NCS_Cart_Public`

Most Pro code is **gated but co-located** — same files, wrapped in `if (sc_cart_is_pro())` or named `*__premium_only`.

---

## Migration strategy

Work in this order to minimize breakage:

| Phase | Scope | Risk |
|-------|--------|------|
| 1 | Move already conditionally-loaded modules | Low |
| 2 | Extract `__premium_only` methods into Pro classes | Low–medium |
| 3 | Split metabox field definitions via existing filters | Medium |
| 4 | Refactor order model (`ScrtOrder` → target `PPCart_Order`) — hooks instead of inline Pro branches | Medium–high |
| 5 | Fix always-loaded Pro code (upsell metaboxes, PayPal upsell AJAX) | Medium |
| 6 | Split JS assets (coupon/upsell handlers) | Low |

After each phase: run existing tests; smoke-test checkout, admin product settings, and My Account with Free-only and Free+Pro.

---

## Phase 1 — Move entire modules to Pro

These files are already behind `sc_cart_is_pro()` in `NCS_Cart::load_dependencies()` and `define_hooks__premium_only()`. Move them to the Pro repo; Free replaces the block with a bootstrap hook.

### Files to move

Historical pre-rebrand Free paths. `api/` and most of these Pro modules are gone from Free; confirm against [architecture.md](architecture.md) before moving anything.

| Module | Path(s) |
|--------|---------|
| REST API | `api/class-ncs-cart-api.php` (`NCS_Cart_API`), `api/ncs-rest/*` |
| Collections | `includes/collections/*` |
| Upgrade paths | `includes/upgrade-paths/*` |
| Subscription sync | `includes/subscription-sync/*` |
| Shipping | `includes/shipping/*` |
| Quantity | `includes/quantity/*` |
| White label | `admin/class-ncs-cart-white-label.php` |
| Pro file downloads | `includes/files/class-ncs-cart-files-pro.php` |
| Divi order form | `includes/integrations/divi/studiocart-order-form.php` (+ related Divi assets if Pro-only) |
| Third-party integrations | `includes/integrations/pro/*` (all files in that directory) |
| Elementor Pro widgets | `includes/integrations/elementor/{checkoutform,countdown,bumpheading,bumptext,bumpcheckbox,bumpimage}.php` — loaded via `includes/integrations/pro/Elementor.php` (`Studiocart\Elementor` today) |

### Free replacement

In `includes/class-ncs-cart.php`, replace `if (sc_cart_is_pro()) { require_once ... }` blocks with:

```php
do_action('ppcart_load_pro_modules', $this);
```

Pro hooks that action and requires its own copies of the modules above.

Also replace `define_hooks__premium_only()` (registers `NCS_Cart_API`, `SC_Rest_Customers`, `SC_Rest_Products`, `SC_Rest_Orders`, `SC_Rest_Subscriptions` — **target** `NCS_Cart_Rest_*`) with:

```php
do_action('ppcart_register_pro_hooks', $this->loader, $this);
```

### CPT registration

`sc_us_path` is registered only when Pro is active (`includes/class-ncs-cart-post_types.php`). Move registration to Pro:

```php
add_filter('studiocart_post_types', [ NCS_Cart_Pro_Post_Types::class, 'register_upsell_path' ]);
```

Implement `NCS_Cart_Pro_Post_Types` in Pro (`includes/class-ncs-cart-pro-post-types.php`).

`sc_collection` is registered by `NCS_Cart_Collections::register_post_type()` — move with the collections module (`NCS_Cart_Collections`, `NCS_Cart_Collections_Metabox_Fields`; model `ScrtCollection` → target `NCS_Cart_Collection`).

---

## Phase 2 — Extract `__premium_only` methods

Full Pro implementations inside shared Free classes. Move method bodies to Pro classes; Free registers generic extension hooks instead of `if (sc_cart_is_pro())` branches.

### `public/class-ncs-cart-public.php`

| Method | Pro registers on |
|--------|------------------|
| `sc_invoices_download__premium_only` | `template_redirect` |
| `sc_process_upsell__premium_only` | `wp_ajax_sc_process_upsell`, `wp_ajax_nopriv_sc_process_upsell` |
| `sc_validate_coupon__premium_only` | `wp_ajax_sc_validate_coupon`, `wp_ajax_nopriv_sc_validate_coupon` |
| `sc_check_username__premium_only` | `wp_ajax_sc_check_username`, `wp_ajax_nopriv_sc_check_username` |
| `sc_capture_lead__premium_only` | `wp_ajax_sc_capture_lead`, `wp_ajax_nopriv_sc_capture_lead` |
| `do_order_lead_functions__premium_only` | `sc_order_lead` |

Also move related Pro-only logic in the same class: upsell URL building (~579–653), hide product page redirect (~1395), and other `sc_cart_is_pro()` branches in enqueue/redirect paths.

### `public/class-ncs-cart-paypal.php`

| Method | Notes |
|--------|-------|
| `paypal_process_upsell__premium_only` | AJAX hooks at lines 77–78 are registered **unconditionally** today — move hooks and method to Pro |

### `admin/class-ncs-cart-add-stripe-product.php`

| Method | Notes |
|--------|-------|
| `create_coupon__premium_only` | Stripe coupon sync for product coupons |

### `admin/class-ncs-cart-metaboxes.php`

| Method | Hook |
|--------|------|
| `add_conditional_confirmations__premium_only` | `sc_confirmation_fields` |

### Free replacement pattern

In `includes/class-ncs-cart.php`:

```php
// define_public_hooks()
do_action('ppcart_register_public_ajax_handlers', $this->loader, $plugin_public);

// define_hooks__premium_only() — remove or empty; Pro owns REST too
do_action('ppcart_register_pro_hooks', $this->loader, $this);
```

Pro bootstraps dedicated classes that hook the same actions/filters the `__premium_only` methods use today:

| Pro class | File (in Pro plugin) | Role |
|-----------|----------------------|------|
| `NCS_Cart_Pro_Bootstrap` | `includes/class-ncs-cart-pro-bootstrap.php` | Module loading, hook registration |
| `NCS_Cart_Pro_Public` | `includes/class-ncs-cart-pro-public.php` | AJAX, invoices, upsell URLs |
| `NCS_Cart_Pro_Paypal` | `includes/class-ncs-cart-pro-paypal.php` | PayPal upsell AJAX |
| `NCS_Cart_Pro_Admin` | `admin/class-ncs-cart-pro-admin.php` | Stripe coupon sync, order admin labels |
| `NCS_Cart_Pro_Metaboxes` | `admin/class-ncs-cart-pro-metaboxes.php` | Product settings tabs/fields filters |

### Invoice / PDF assets

Move with the Pro public class:

- `public/partials/invoice-pdf.php`
- `public/templates/pdf-invoice/invoice.php`

Free may keep query var `sc-invoice` registration if themes/links depend on it; Pro handles the download handler.

---

## Phase 3 — Metabox and settings field extraction

### `admin/class-ncs-cart-metaboxes.php`

Large Pro-only field definitions live inside `if (sc_cart_is_pro())` in `set_field_groups()`:

- **General** — hide title, header color/image, disable product page, page template
- **Payments** — disable Stripe/PayPal/COD per product
- **Fields** — form skins (2-step, opt-in, split-in), custom fields repeater, address fields, button icons
- **Coupons** — `_sc_coupons` repeater, CSV import UI
- **Order bump** — `_sc_order_bump`, `_sc_order_bump_options`
- **Upsell path** — `_sc_upsell_path` linkage to `sc_us_path` CPT

Create Pro classes that filter:

```php
add_filter('sc_product_setting_tabs', ...);
add_filter('sc_product_field_groups', ...);
add_filter('sc_product_payments_fields', ..., 10, 2);
add_filter('sc_product_general_fields', ...);
add_filter('sc_product_fields_fields', ...);
add_filter('sc_confirmation_fields', ..., 10, 2);
```

Add dedicated filters for coupons, order bump, and upsell path if the generic `sc_product_{tab}_fields` pattern is insufficient.

Free keeps the base tab list (general, pricing, access, fields, confirmation, notifications, integrations, tracking) and applies filters. Remove all Pro field arrays from Free.

Tab list divergence today (~lines 232–247): Pro adds `payments`, `coupons`, `orderbump`, `upsellPath`. Pro adds those via `sc_product_setting_tabs`.

### `admin/class-ncs-cart-upsell-metaboxes.php`

Always loaded and hooked in `define_admin_hooks()` even though `sc_us_path` is Pro-only. Move the entire class to Pro; hook `admin_init` for upsell metaboxes from Pro bootstrap only.

### `admin/class-ncs-cart-settings.php`

Pro-only settings (~2264–2300+):

- Premium email templates (paused, trial ending, reminder) and upgrade nags in Free
- Invoice notes/footer
- API key field

Pro registers via `sc_setting_tabs` and `sc_register_sections` (see `admin/partials/ncs-cart-admin-page-settings.php`).

**Note:** Free currently deletes some Pro options when Pro is inactive. Avoid data loss on deactivate/reactivate — hide UI in Free but leave options in the database.

### `admin/class-ncs-metabox-fields.php`

Pro helpers: `get_bumps()`, integration trigger options for bump/upsell/downsell (~648+). Move to Pro admin helpers or filter integration plan options.

---

## Phase 4 — Checkout, templates, and order model

### `public/templates/template-functions.php`

Pro block (~lines 247–1817) registers and defines:

- `sc_do_coupon_fields`, `sc_do_coupon_status`
- `sc_orderbumps` on `sc_card_details_fields`
- `sc_custom_fields` on `sc_checkout_form_fields`
- Footer and step-1 footer text
- Payment method disable logic
- URL coupon prefill

Free keeps the `do_action()` calls in checkout templates (`sc_coupon_fields`, `sc_do_orderbump`, etc.).

Pro adds `add_action('sc_coupon_fields', 'sc_do_coupon_fields')` and the implementations in Pro (e.g. `includes/ncs-cart-pro-template-functions.php` or methods on `NCS_Cart_Pro_Public`).

`sc_do_bump_template()` and `public/templates/order-form/bump.php` can stay in Free as a generic bump renderer, or move to Pro if bumps are strictly premium — see [Open decisions](#open-decisions).

### Checkout layout templates

Pro-specific layouts:

- `public/templates/checkout-shortcode-2-step.php`
- `public/templates/checkout-shortcode-split-in.php`
- Pro branches in `checkout1.php`, `checkout-shortcode.php`

Free resolves template via a new filter:

```php
apply_filters('sc_checkout_template', $template, $product_id);
```

Pro returns `two_step`, `split_in`, etc., and provides template paths.

### Order model (`PPCart_Order`)

Pro logic embedded in core order flow. Refactor so Free calls hooks; Pro implements behavior.

| Area | Methods | Suggested hook |
|------|---------|----------------|
| Coupons | `load_coupon_from_post()`, `apply_plan_coupon_to_items()`, cart coupon apply | `sc_order_load_coupon`, `sc_order_apply_coupon` |
| Order bumps | `add_bump_items_from_post()` | `sc_order_add_bumps_from_post` |
| Upsell/downsell | `child_of()`, `get_upsell()` | Pro-only; Free does not call unless Pro active |
| Tax per item | `maybe_apply_tax_to_item()` | Keep if Free has basic tax; else `sc_order_apply_tax_to_item` |
| Purchase notes | scattered `sc_cart_is_pro()` checks | `sc_order_purchase_note` filter |

Existing hooks to use: `sc_after_setup_atts_from_post`, `sc_after_load_from_post`, `sc_order_pre_calculate_tax`.

**Important:** `load_coupon_from_post()` calls `sc_get_coupon()`, which is defined only inside `if (sc_cart_is_pro())` in `includes/functions.php`. Free must not call it without Pro — guard in Free or move coupon loading entirely to Pro hooks.

### `includes/functions.php`

Move entire block **5247–5502** to Pro:

- `sc_do_webhooks()`
- `sc_maybe_update_coupon_limit()`
- `sc_get_coupon()`

Move or filter-gate Pro branches in:

- `ppcart_setup_product()` — display modes, upsell path (~3462–3510)
- Order action handler — bump integrations, `sc_maybe_rebuild_custom_post_data` (~1572–1636+)
- Integration services — WishList, RCP, Tutor, custom webhooks (~1873+)

Pro hooks into `sc_order_complete`, `sc_order_*`, and integration action hooks Free already fires.

### Gutenberg checkout block

The checkout renderer in `includes/integrations/gutenberg/lib/` gates coupon, order bumps, and payment methods with `ppcart_is_pro()`.

Add filter:

```php
apply_filters('sc_checkout_block_sections', $sections, $product_id);
```

Pro sets `coupon`, `order_bumps`, and gateway availability. Free returns minimal defaults.

---

## Phase 5 — Always-loaded Pro code

| Item | Current issue | Action |
|------|---------------|--------|
| `admin/class-ncs-cart-upsell-metaboxes.php` | Required in Free `load_dependencies()` | Move to Pro only |
| `admin/class-ncs-cart-metaboxes.php` | Pro fields in same file | Split via filters (Phase 3) |
| `public/class-ncs-cart-paypal.php` upsell AJAX | Hooks always registered | Register from Pro |
| `admin/class-ncs-metabox-fields.php` | Bump/coupon admin helpers | Move to Pro |
| My Account templates | `subscription-detail.php`, `order-history.php` Pro sections | Pro template parts or `do_action` |
| `admin/class-ncs-cart-admin.php` | Order line item upsell/bump labels (~1982+) | `do_action('sc_order_admin_line_items', $order)` |
| `admin/class-ncs-cart-reports.php` | Pro-only report columns | Filter e.g. `sc_reports_columns` |

---

## Phase 6 — JavaScript

### `public/js/ncs-cart-public.js`

- Upsell handlers: `/* <fs_premium_only> */` block (~66–193) → Pro bundle `public/js/ncs-cart-public-pro.js` (handle: `ncs-cart-public-pro`)
- Coupon AJAX (`try_coupon`, `sc_validate_coupon`) → same Pro bundle or guard with localized `studiocart.isPro`

Free enqueues `ncs-cart-public`; Pro enqueues `ncs-cart-public-pro` when `sc_cart_is_pro()`.

---

## Extension points reference

Pro should extend Free through these hooks. Add new ones in Free only when a gap appears.

### Bootstrap

| Hook | Purpose |
|------|---------|
| `sc_before_load` | Early Pro setup (exists) |
| `ppcart_load_pro_modules` | **New** — require Pro module files |
| `ppcart_register_pro_hooks` | **New** — REST, admin, public Pro hooks |
| `ppcart_register_public_ajax_handlers` | **New** — Pro AJAX on public class |
| `ppcart_db_table_schemas` | **New** — Pro registers `PPCart_DB_Table_Schema` objects for extra custom tables |
| `ppcart_db_schema_repaired` | **New** — after Maintenance schema repair completes (`$report` array) |

### Product admin

| Filter | Purpose |
|--------|---------|
| `sc_product_setting_tabs` | Add/remove product settings tabs |
| `sc_product_field_groups` | Tab groups for save validation |
| `sc_product_{tab}_fields` | Fields per tab |
| `sc_product_payments_fields` | Payment method toggles |
| `sc_confirmation_fields` | Conditional confirmations |
| `sc_pricing_fields` | Payment plans (collections use this) |

### Settings

| Filter / action | Purpose |
|-----------------|---------|
| `sc_setting_tabs` | Global plugin settings tabs |
| `sc_register_sections` | Settings sections |

### Post types

| Filter | Purpose |
|--------|---------|
| `studiocart_post_types` | Register `sc_us_path`, etc. |
| `studiocart_taxonomies` | Taxonomies |

### Checkout / frontend

| Hook | Purpose |
|------|---------|
| `sc_coupon_fields` / `sc_coupon_status` | Coupon UI |
| `sc_do_orderbump` / `sc_orderbump_args` | Order bump render |
| `sc_checkout_form` / `sc_checkout_form_fields` | Form layout |
| `sc_payment_method_fields` | Gateway selection |
| `sc_card_details_fields` | Card + bump placement |
| `sc_after_buy_button` / `sc_after_step_1_button` | Footer text |
| `sc_checkout_template` | **New** — template file selection |
| `sc_checkout_block_sections` | **New** — Gutenberg block sections |

### Order lifecycle

| Hook | Purpose |
|------|---------|
| `sc_after_setup_atts_from_post` | After parsing POST into order |
| `sc_after_load_from_post` | After full order build from POST |
| `sc_order_pre_calculate_tax` | Before tax calculation |
| `sc_order_complete` / `sc_order_*` | Status transitions |
| `sc_order_lead` | Lead capture |

### Upsell

| Hook / filter | Purpose |
|---------------|---------|
| `studiocart_upsell_urls` | Accept/decline URLs |
| `studiocart_before_load_upsell` | Before upsell page render |
| `studiocart_checkout_complete` | Post-checkout routing |

### License bridge

| Filter | Purpose |
|--------|---------|
| `sc_pro_license_is_valid` | License check |
| `sc_pro_can_use_premium_code` | Feature gate |

---

## Pro plugin bootstrap sketch

```php
// publishpress-cart-pro.php (simplified)
// Legacy aliases and the dual-define transition: see prefix-pro.md.
define('PPCART_LOADED_BY_PRO', true);
define('PPCART_PRO_BASE_FILE', __FILE__);
define('PPCART_PRO_BASE_DIR', plugin_dir_path(__FILE__));
define('PPCART_PRO_BASE_URL', plugin_dir_url(__FILE__));
define('PPCART_PRO_PLUGIN_NAME', 'PublishPress Cart Pro');
define('PPCART_PRO_LIB_VENDOR_DIR', PPCART_PRO_BASE_DIR . 'lib/vendor');

require_once WP_PLUGIN_DIR . '/publishpress-cart/publishpress-cart.php';

require_once PPCART_PRO_BASE_DIR . 'includes/class-ppcart-pro-bootstrap.php';

add_action('ppcart_load_pro_modules', [ PPCart_Pro_Bootstrap::class, 'load_modules' ]);
add_action('ppcart_register_pro_hooks', [ PPCart_Pro_Bootstrap::class, 'register_hooks' ], 10, 2);
add_action('ppcart_register_public_ajax_handlers', [ PPCart_Pro_Public::class, 'register_ajax' ], 10, 2);
```

Pro plugin layout (naming per [prefix-pro.md](prefix-pro.md)):

```
publishpress-cart-pro/
  publishpress-cart-pro.php
  includes/
    class-ppcart-pro-bootstrap.php        # PPCart_Pro_Bootstrap
    class-ppcart-pro-public.php           # PPCart_Pro_Public
    class-ppcart-pro-paypal.php           # PPCart_Pro_Paypal
    class-ppcart-pro-post-types.php       # PPCart_Pro_Post_Types
    ppcart-pro-template-functions.php     # coupon fields, order bumps, etc.
  admin/
    class-ppcart-pro-metaboxes.php        # PPCart_Pro_Metaboxes
    class-ppcart-pro-admin.php            # PPCart_Pro_Admin
  public/js/ppcart-public-pro.js
  api/ …                                  # moved from Free (Phase 1)
```

Ensure Pro lists Free as a dependency and does not load Free twice (see [launch-readiness.md](launch-readiness.md) §4).

---

## Testing checklist

### Free only

- [ ] Product CRUD, payment plans, basic checkout (Stripe/PayPal)
- [ ] Orders and subscriptions admin
- [ ] No Pro tabs (coupons, bumps, upsell) in product settings
- [ ] No fatals when POST contains `coupon_id` or `sc-orderbump` (ignore safely)
- [ ] Gutenberg checkout block renders without coupon/bump sections
- [ ] Version notices / upgrade CTAs behave as expected

### Free + Pro

- [ ] All Pro product tabs appear and save correctly
- [ ] Coupons validate via AJAX
- [ ] Order bumps add line items; replace-main-bump works
- [ ] Upsell path: accept/decline, Stripe and PayPal upsell AJAX
- [ ] 2-step / split-in / opt-in templates
- [ ] REST API routes respond with auth
- [ ] Collections checkout
- [ ] Invoice PDF download
- [ ] White label settings
- [ ] Third-party integrations in `integrations/pro/` fire on order complete

### Deactivate Pro

- [ ] Site remains functional on Free
- [ ] Pro CPT data (`sc_us_path`, etc.) not corrupted (may be hidden until Pro returns)

---

## Files quick reference

### Move entirely to Pro (Phase 1)

```
api/
includes/collections/
includes/upgrade-paths/
includes/subscription-sync/
includes/shipping/
includes/quantity/
includes/integrations/pro/
includes/integrations/divi/studiocart-order-form.php
includes/integrations/elementor/checkoutform.php
includes/integrations/elementor/countdown.php
includes/integrations/elementor/bump*.php
includes/files/class-ncs-cart-files-pro.php
admin/class-ncs-cart-white-label.php
admin/class-ncs-cart-upsell-metaboxes.php
public/partials/invoice-pdf.php
public/templates/pdf-invoice/
public/templates/checkout-shortcode-2-step.php
public/templates/checkout-shortcode-split-in.php
```

### Strip Pro logic from (keep hooks)

```
includes/class-ncs-cart.php
public/class-ncs-cart-public.php
public/class-ncs-cart-paypal.php
admin/class-ncs-cart-metaboxes.php
admin/class-ncs-cart-settings.php
admin/class-ncs-cart-add-stripe-product.php
admin/class-ncs-metabox-fields.php
admin/class-ncs-cart-admin.php
public/templates/template-functions.php
models/class-ppcart-order.php
includes/functions.php
includes/integrations/gutenberg/lib/  # checkout renderer
public/js/ncs-cart-public.js
```

---

## Open decisions

1. **Bump template in Free?** If bumps are 100% Pro, move `order-form/bump.php` and `sc_do_bump_template()` to Pro.
2. **Pro option cleanup on Free?** Avoid `delete_option` for Pro settings when Pro is inactive.
3. **Backward compatibility:** Keep `sc_cart_is_pro()` in Free as the bridge; Pro defines the constant. Third-party code may still call `sc_cart_is_pro()` — document that it remains supported.
4. **Class aliases:** Leftover class names resolve through StudioCart Compatibility Mode in Free, not through new aliases in Pro. See [prefix-pro.md](prefix-pro.md).
5. **Prefix cleanup:** Extraction PRs do not carry renames. If a file needs both, land the move and the rename as separate PRs — [prefix-pro.md](prefix-pro.md) owns the rename.

---

## Suggested PR breakdown

1. Add `ppcart_load_pro_modules` / `ppcart_register_pro_hooks` / `ppcart_register_public_ajax_handlers` hooks; no behavior change.
2. Move REST + API files to Pro; wire bootstrap.
3. Move collections, shipping, upgrade paths, subscription sync.
4. Extract public `__premium_only` AJAX and invoice download.
5. Extract metabox fields to Pro filter classes.
6. Refactor order model hooks (`ScrtOrder` / `PPCart_Order`) and move coupon/bump functions.
7. Move upsell metaboxes, templates, and JS split.
8. Remove dead `sc_cart_is_pro()` branches from Free; update tests.

Each PR should keep CI green and be reviewable in isolation.
