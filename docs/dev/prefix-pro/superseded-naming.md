# Superseded naming notes (Pro tree today)

Pre-rebrand families still present in the Pro tree, mapped to their canonical targets. Part of [Pro prefix compliance](../prefix-pro.md).

---

The Pro tree still ships the pre-rebrand families below. They are an
**inventory of what exists**, not a convention to follow — every new or
touched Pro symbol uses the canonical map in
[Canonical prefix map (Pro)](../prefix-pro.md#canonical-prefix-map-pro).

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
