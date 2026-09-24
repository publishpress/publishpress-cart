# Running a Pro slice

Inventory greps for the Pro repo, the slice procedure, and the acceptance checks that close it. Part of [Pro prefix compliance](../prefix-pro.md).

---

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
7. Update [pro-plugin-extraction.md](../pro-plugin-extraction.md) when Pro-side
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
