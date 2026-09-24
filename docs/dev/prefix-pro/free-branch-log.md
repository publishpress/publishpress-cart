# Free branch log and coordinated work

What Free already shipped, whether Pro must match it, and the items that have to land on both sides together. Part of [Pro prefix compliance](../prefix-pro.md).

---

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
| Settings CSS custom properties | `--ppcart-*` on `.ppcart-settings-page`; `--pp-cs-*` retired | Yes — match Free tokens; no `--pp-cs-*`. Register: [Settings CSS custom properties](settings-css.md) |
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
| Post-30 leftovers (slices 31–57) | See [Post-30 leftovers](../prefix-standardization.md#post-30-leftovers-31-42) and [Post-42 leftovers](../prefix-standardization.md#post-42-leftovers-43). | Do not copy leftover names. After each Free slice, match canonical. |
| Post-57 leftovers (slices 58–69) | **Complete** — see [Remaining leftovers (58–69)](../prefix-standardization.md#remaining-leftovers-58-69). | Do not copy leftover names. Match new Free emit/read after each slice. Gutenberg leftover block dual-registers only in Free Compat. No leftover recognition in Pro. Compat metadata bridges use `ppcartcomp_*`. CPT/taxonomy option filters are `ppcart_cpt_options` / `ppcart_taxonomy_options`; leftover `sc-cart-*` via Free Compat. Tax CSV import slug is `ppcart_tax_rate_csv`; Pro registers importer on `ppcart_register_importers`. Cosmetic table/bootstrap locals use `$ppcart_*` names only. First-party docblocks/comments use canonical names only. |
| Post-69 leftovers (slices 70–73) | **Complete** — see [Remaining leftovers (70–73)](../prefix-standardization.md#remaining-leftovers-70-73). | Match new Free emit/read. Listen/fire `ppcart_activate` / `ppcart_upgrade` / `ppcart_product` / `ppcart_product_price`. Do not copy mangled `ppcart_ctivate` / `ppcart_pgrade` / `ppcart_roduct`. Leftover `ncs_activate` / `sc_product` filter via Free Compat. Emit/read `ppcart-revoke` / `ppcart-revoked`; leftover GET via Free Compat. Match `row-ppcart-` / `ppcart-nav-tabs`. Write/allow `ppcart-uploads`; leftover `sc-uploads` via Free Compat extra root only. No silent file move. |
| Post-73 leftovers (slices 74–76) | **Complete** — see [Remaining leftovers (74–76)](../prefix-standardization.md#remaining-leftovers-74-76). | Match canonical. Log-id regex `PPCart_Order` / `PPCart_Subscription` only (no `Scrt*`). Merchant copy `ppcart_field_id` / `plugin/publishpress-cart` / PublishPress KB default. Comments use canonical names. Hard cutover; no Compat. |
| Post-76 leftovers (slice 77) | **Complete** — see [Remaining leftovers (77)](../prefix-standardization.md#remaining-leftovers-77). | Match `data-ppcart-qty-price` and `originalPpcartSettings`. Do not emit leftover `data-scq-price` / `originalNcs`. Hard cutover; no Compat. |
| Post-77 leftovers (slice 78) | **Complete** — see [Remaining leftovers (78)](../prefix-standardization.md#remaining-leftovers-78). | Match `$ppcart_order` / `$ppcart_subscription`. Do not copy `$scrt_order` / `$scorder` / `$scsub`. Hard cutover; no Compat. |
| Post-78 leftovers (slice 79) | **Complete** — see [Remaining leftovers (79–81)](../prefix-standardization.md#remaining-leftovers-79-81). | Match `elementor/popup/show.ppcart-pe-`. Do not copy `.scPE-`. Hard cutover; no Compat. |
| Post-79 leftovers (slice 80) | **Complete** — see [Remaining leftovers (79–81)](../prefix-standardization.md#remaining-leftovers-79-81). | Ship `sample_ppcart_tax_rates.csv` only. Mirror phpmd `PPCart_Public` / `PPCart_` comments. Hard cutover; no Compat. |
| Post-80 leftovers (slice 81) | **Complete** — see [Remaining leftovers (79–81)](../prefix-standardization.md#remaining-leftovers-79-81). | Do not copy `_sc_*` seeder keys. Never strip `_sc_` in Cart meta helpers. Hard cutover; no Compat. |
| Post-81 leftovers (slice 82) | **Complete** — see [Remaining leftovers (82)](../prefix-standardization.md#remaining-leftovers-82). | Match `ppcart_supports` / `ppcart_is_pro` / `ppcart_loaded` / `ppcart_pro_*` / `ppcart_manager`. Product checkout-window meta/object properties are `checkout_starts` / `checkout_ends` / `checkout_ended_*`. Do not read `cart_open` / `_ppcart_cart_*`. Leftover `_sc_cart_open` only via Free Compat. |

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
| `refactor(prefix): rename settings CSS tokens to --ppcart-*` | Yes — Pro settings CSS must use `--ppcart-*` on `.ppcart-settings-page`. `--pp-cs-*` is retired. Hard cutover; no Compat aliases. Register: [Settings CSS custom properties](settings-css.md). |
| `refactor(prefix): drop doubled ppcart_cart_ prefix (#629)` | Yes — Match `ppcart_supports` / `ppcart_is_pro` / `ppcart_loaded` / `ppcart_pro_*` / `ppcart_manager`. Product checkout-window meta is `checkout_starts` / `checkout_ends` / `checkout_ended_*`. Do not read `cart_open` / `_ppcart_cart_*`. Leftover `_sc_cart_open` via Free Compat only. |

Docs note: 2026-09-10 leftover family **82** is complete. Post-79 leftover
queue is **complete**. Slice **18** stays `[~]`. See
[Remaining leftovers (82)](../prefix-standardization.md#remaining-leftovers-82).
