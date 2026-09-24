# PublishPress Cart — Architecture

Runtime map of how the Free plugin is organized today. Read this before structural changes.

WordPress.org ships this Free plugin only. Leftover StudioCart names (`sc_*`, `studiocart_*`, `NCS_*`, `ncs_*`) live in the companion [publishpress-cart-compat](https://github.com/publishpress/publishpress-cart-compat) plugin, not here.

**Principles**

- Keep the WordPress plugin shape: hooks, procedural helpers, `require_once`.
- Split by **feature**, not by abstract layers (no mandatory Domain/Infrastructure folders).
- **Move code, don't rewrite it** — extracted files keep the same public APIs (`ppcart_*` functions, existing hooks). Treat Free hooks, filters, internals, and data shapes Pro depends on as a public contract.
- One concern per new PHP file; cap new files at **500 lines**.
- First-party never emits, reads, writes, or recognizes leftover `sc_*` / `studiocart_*` / `NCS_*` / `ncs_*` names. Dual-read, leftover `has_shortcode`, and leftover asset detectors belong only in Compat.

---

## Bootstrap flow

```
publishpress-cart.php
  → PPCart (includes/class-ppcart.php)
       → PPCart_Dependency_Loader::load() — requires admin, public, models, Stripe, secrets, Gutenberg
       → PPCart_Admin_Hook_Registrar / PPCart_Public_Hook_Registrar
  → includes/functions.php — thin barrel; modules in includes/functions/
```

`$ppcart_public` (`PPCart_Public`) is the storefront coordinator. Checkout, account, assets, orders, and hosted checkout are delegated to `public/controllers/`.

Activation, deactivation, and upgrade live in `includes/class-ppcart-activator.php`, `class-ppcart-deactivator.php`, and `class-ppcart-upgrade.php`. When Compat is active, `PPCart_Companion_Loader` requires its packages before Cart’s live stubs.

There is **no** first-party `api/` REST directory.

---

## Folder map

| Path | Role |
|------|------|
| `publishpress-cart.php` | Plugin header, constants, activation hooks, `ppcart_run()` |
| `includes/class-ppcart.php` | Core bootstrap, locale, hook registration |
| `includes/class-ppcart-version-notices.php` | wordpress-version-notices (Free upgrade nag) |
| `includes/class-ppcart-reviews.php` | wordpress-reviews (WP.org review notice on dashboard/settings) |
| `includes/bootstrap/` | Dependency loader and admin/public hook registrars |
| `includes/functions.php` + `includes/functions/` | Global helpers (checkout, pricing, orders, integrations) |
| `includes/stripe/`, `includes/class-ppcart-stripe.php` | First-party Stripe helpers (`StripeClient` wrapper, order-save traits/templates) |
| Composer `publishpress/stripe-php` (`lib/`) | Vendored SDK, namespace `PublishPress\Stripe\`; client `new \PublishPress\Stripe\StripeClient` |
| `includes/stripe-sync/`, `includes/class-ppcart-stripe-sync.php` | Stripe webhook / subscription sync |
| `includes/secrets/` | Credential encryption/storage |
| `includes/logging/` | Debug log and Stripe webhook request log |
| `includes/files/` | Product file downloads |
| `includes/email/` | Transactional email templates |
| `includes/order-items/` | Order line items and details renderer |
| `includes/integrations/gutenberg/` | Checkout and account blocks |
| `public/class-ppcart-public.php` | Storefront coordinator (hooks, order-status integrations) |
| `public/controllers/` | Checkout, account, assets, payment, orders, hosted checkout |
| `public/class-ppcart-paypal.php` + `public/paypal/` | PayPal checkout (direct cURL) |
| `public/webhooks/` | `stripe.php`, `paypal.php` — keep thin |
| `public/templates/` | Storefront PHP templates |
| `admin/` | Settings, product/order metaboxes, reports, contacts |
| `admin/dashboard/` | WP dashboard monthly overview widget |
| `admin/traits/` | Admin notices, assets, privacy, integrations, profile, Stripe portal, product duplicate |
| `models/` | `PPCart_Order` and `PPCart_Subscription` |
| Companion `publishpress-cart-compat` | StudioCart Compatibility Mode + merchant Data migration |

## Order and subscription post statuses

Logical status strings (`paid`, `pending-payment`, `active`, …) stay on
`_ppcart_status` meta, `$order->status`, and `PPCart_Order::$paid_str`. That is
the Pro contract. WordPress `register_post_status()` and `wp_posts.post_status`
use prefixed slugs (`ppcart_paid`, `ppcart_completed`, …). `pending-payment`
registers as `ppcart_pending` because `ppcart_pending-payment` exceeds the
varchar(20) column.

`PPCart_Status_Labels` maps both directions. `PPCart_Post_Status_Sync` prefixes
canonical CPT writes and expands queries to include legacy slugs. Plugin
upgrade rewrites unprefixed status on `ppcart_order` / `ppcart_subscription`
only. Leftover `sc_order` rows are not silently rewritten; Compat maps status
during merchant CPT slug migration.

---

## Storefront coordinators

`PPCart_Public` constructs `PPCart_Public_Account_Controller` and wires order-status hooks. Other coordinators are constructed from the public hook registrar:

| Class | Responsibility |
|-------|----------------|
| `PPCart_Public_Asset_Controller` | Storefront styles/scripts |
| `PPCart_Public_Checkout_Controller` | Checkout shortcodes and form render |
| `PPCart_Public_Payment_Controller` | Payment processing |
| `PPCart_Public_Order_Controller` | Order status AJAX |
| `PPCart_Public_Account_Controller` | My-account shortcodes, login/password |
| `PPCart_Public_Page_Controller` | Product templates, redirects, hosted-checkout return |
| `PPCart_Public_Hosted_Checkout_Controller` | Stripe hosted checkout |
| `PPCart_Public_Subscription_Checkout_Controller` | Subscription checkout helpers |
| `PPCart_Paypal` | PayPal checkout |

---

## Global helpers

`includes/functions.php` is a barrel. Feature helpers live under `includes/functions/`:

| Module | Responsibility |
|--------|----------------|
| `product-and-order-setup.php` | `ppcart_setup_product`, `ppcart_setup_order` |
| `prices-and-marketing.php` / `currencies.php` | Price formatters, currency lists |
| `plans-and-subscriptions.php` | `ppcart_plan`, subscription date helpers |
| `orders-products-and-formatting.php` | Order/product display helpers |
| `order-items-and-details.php` | Line items and item lists |
| `payment-actions-and-refunds.php` | Cancel, refund |
| `integrations-and-stock.php` | Mail/CRM integrations, stock |
| `ajax-and-fields.php` / `ajax-security.php` | Checkout AJAX and field helpers |
| `users-and-notifications.php` | Customer notifications |
| `merge-tags-and-dates.php` | `{customer_firstname}` personalization |

New checkout code must not add `global $`. Prefer passing `$ppcart_public` or options as parameters. Existing globals (`$ppcart_public`, `$ppcart_stripe`, `$ppcart_currency`, `$ppcart_currency_symbol`, `$ppcart_debug_logger`) may remain until touched.

---

## Webhooks

Entry scripts stay in `public/webhooks/`:

```
HTTP request → public/webhooks/stripe.php | paypal.php
            → includes/stripe-sync/ / public/paypal / order models
```

Business logic must not grow in `stripe.php` / `paypal.php`.

---

## Rules for new and moved code

1. **File size:** aim ≤ 500 LOC.
2. **API stability:** keep function and hook names; use thin wrappers in `functions.php` if needed. Do not “clean up” unused-looking APIs Pro may still call.
3. **Loading:** feature code lives in a folder with a loader; no scattered `require` in random files.
4. **Templates:** no new business rules in `public/templates/` — call `ppcart_*` helpers or coordinators.
5. **Prefixes:** `PPCART_`, `ppcart_`, `PPCart_`. Leftover names belong only in Compat.
6. **Tests:** run the relevant suite after each extraction (`composer test:unit`, `composer test:integration`, or `composer test:regression`).

---

## Related docs

- [prefix-standardization.md](prefix-standardization.md) — leftover → canonical map (Free)
- [prefix-pro.md](prefix-pro.md) — Pro prefix contract
- [pro-plugin-extraction.md](pro-plugin-extraction.md) — Free/Pro architecture split
- [launch-readiness.md](launch-readiness.md) — launch checklist
- [AGENTS.md](../../AGENTS.md) — agent overlay (layout, tests, hard rules)
