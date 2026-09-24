# Current Free/Pro contract

The names Free already ships and Pro must match — live maps, classes, admin slugs, extension hooks, AJAX, JS globals, storage helpers. Part of [Pro prefix compliance](../prefix-pro.md).

Leftover maps below live in sibling `publishpress-cart-compat` (paths are companion-relative). Free Cart has no `includes/compat/`.

---

These are the names Free already uses. Pro must match them. Live leftover maps in `publishpress-cart-compat`:

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
- Public query vars: Free registers `ppcart-preview` / `ppcart-order` /
  `ppcart-plan` / `ppcart-manage` / `ppcart-download` / `ppcart-coupon` /
  `ppcart-pay-plan`. Pro URL coupons read `get_query_var('ppcart-coupon')`;
  do not `get_query_var('coupon')`. Checkout pay-plan preselect is
  `ppcart-pay-plan`; account subscription detail stays `ppcart-plan`. Hard
  cutover; no Compat alias for unprefixed `coupon` / `plan` (#777).
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
  [Settings CSS custom properties](settings-css.md).
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
  `get_mailchimp_*` / `get_activecampaign_*`, `get_convertkit_form_options` /
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

Use the [mechanical hook map](../prefix-standardization.md#hook-prefix-map-mechanical) above. Hooks Pro is
known to use against Free (verify in the Pro tree):

| Canonical | Was |
|-----------|-----|
| `ppcart_product_setting_tabs` | `sc_product_setting_tabs` |
| `ppcart_product_setting_tab_{$tab}_fields` | `sc_product_{$tab}_fields` |
| `ppcart_confirmation_fields` | `sc_confirmation_fields` |
| `ppcart_setting_tabs` | `sc_setting_tabs` |
| `ppcart_register_sections` / `_ppcart_register_sections` | `sc_register_sections` |
| `ppcart_coupon_fields` / `ppcart_coupon_status` | `sc_coupon_fields` / `sc_coupon_status` |
| `ppcart_checkout_template_path` | checkout template filter |
| `ppcart_enqueue_scripts_upsell_downsell` / `ppcart_script_vars` | upsell script vars |

After renaming Pro call sites, grep Pro for leftover `add_action( 'sc_` /
`apply_filters( 'sc_`. If Pro still needs a name Free never mapped, add it to
Free `maps/hooks.php` `override` / `extra` — do not dual-fire from Pro.

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
