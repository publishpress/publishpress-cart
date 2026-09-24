# Constants and bootstrap hooks

What Pro defines before requiring Free, and the Free hooks Pro bootstraps on. Part of [Pro prefix compliance](../prefix-pro.md).

---

## Constants Pro must define before requiring Free

Define the canonical names **before** `require` of `publishpress-cart.php`.
Inbound Compatibility Mode copy is a temporary crutch, not the contract.
Free does **not** ungate `ncs_cart_resolve_canonical_constants` in this
slice; completing slice 18 is **blocked on this Pro dual-define**. Keep the
Compatibility Mode aliases in sibling `publishpress-cart-compat`
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

## Bootstrap hooks Pro listens to

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
