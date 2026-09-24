# Pro slice order and frozen strings

The order to run Pro slices in, and the stored or URL-visible strings that must not move in a prefix PR. Part of [Pro prefix compliance](../prefix-pro.md).

---

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
in Free, Free public query-var registration. Pro only changes query vars if
it **registers** extra public routes of its own.

## Frozen until a dedicated migration

Do not rename these in a prefix PR. They are stored or URL-visible in customer
sites. Free and Pro stay on the same strings until both migrate together.

- Pro-owned CPT **rows** stay leftover (`sc_collection`, `sc_us_path`, `sc_membership`, `sc_upgrade_path`) until a dedicated Pro migration. Free **source** is canonical `ppcart_*` (slice 28). Pro should dual-register leftover+canonical until Pro migrates.
- Free product/order/subscription CPT, taxonomy, role, and cap leftover strings migrate in Free via **Data migration**. Pro must call `ppcart_live_post_type()` / `ppcart_query_post_types()` / `ppcart_live_cap()` / `ppcart_user_can()` / `ppcart_live_role()` instead of hardcoding `sc_product` or `ppcart_product`.
- Post-meta **keys**: first-party helper call sites pass the suffix into
  `ppcart_meta_key()` / `ppcart_get_post_meta()` (slice 25a). Helpers store
  `_ppcart_*` rows. Leftover `_sc_*` names are Compatibility Mode; leftover
  rows move in Free Data migration. Option keys cut over in Free slice 13a — Pro must `get_option('_ppcart_*')` / `update_option('ppcart_*')`. Leftover option rows remain until explicit migration. Merchant Compatibility Mode toggle is canonical `_ppcart_compatibility_mode`; historical toggle rows are Compat-only read-through.
- Shortcode tags until Free 14a. Canonical tags are `ppcart_*` (see [Shortcodes (slice 14)](../prefix-standardization.md#shortcodes-slice-14)). Compatibility Mode dual-registers leftover tags. Free first-party content until 14b. Leftover tags in stored posts and email HTML move via Data migration (14c).
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
