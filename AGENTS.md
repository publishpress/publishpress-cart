# PublishPress Cart Free

WordPress plugin for digital-product checkout, orders, and payments. Shared base that Pro bundles.

Full rules: [docs/dev/ai/plugin-rules.md](docs/dev/ai/plugin-rules.md)

Optional research (private): https://github.com/publishpress/team-handbook/tree/development/docs/dev — if a sibling `team-handbook` checkout exists, prefer that. If unreachable, follow this file and `docs/dev/ai/plugin-rules.md`. 

Do not invent process. Do not clone the handbook.

## Hard rules

1. WordPress.org ships only the **Free plugin**. No **Pro code** in Free. **Extension points** (hooks, filters, interfaces) in Free are allowed.
2. The **Pro plugin** is delivered through **Package Server**, vendors Free via Composer, and must not run beside standalone Free.
3. Treat Free hooks, filters, internals, and data shapes the Pro plugin depends on as a public contract. Do not “clean up” unused-looking APIs. If unsure, say so.
4. Product issues live in the **Free plugin** GitHub repo. From Pro work: `Fixes publishpress/<free-repo>#N`, never bare `#N`.
5. PHP: obey **this** plugin’s declared minimum (`composer.json` / plugin header). No newer syntax without a fallback.
6. After behavior changes, run the tests this repo already has. Do not invent a full suite.
7. Do not search `lib/vendor/`, `vendor/`, or `node_modules/` for how this plugin works.
8. **No leftover prefixes in Cart.** First-party never emits, reads, writes, or *recognizes* leftover `sc_*` / `studiocart_*` / `NCS_*` / `ncs_*` names (`has_shortcode`, asset detectors, dual-read, fallbacks). Any leftover or backward-compat path lives only in `publishpress-cart-compat`.

## This plugin

- Edition: Free
- Sibling repo: `publishpress-cart-compat` (StudioCart Compatibility Mode + Data migration)
- Composer package Pro uses for Free: this plugin (`publishpress/publishpress-cart`)
- Layout: entry `publishpress-cart.php`; see table below and [docs/dev/architecture.md](docs/dev/architecture.md)
- Extension points and internals Pro depends on: canonical `ppcart_*` / `PPCart_` / `PPCART_` hooks, filters, helpers, and data shapes — treat as a public contract. Leftover names live only in Compat.

## Layout

| Path | What lives here |
|------|-----------------|
| `publishpress-cart.php` | Bootstrap, constants, activation |
| `includes/class-ppcart.php` | Hook registration; requires admin/public/models |
| `includes/functions.php` + `includes/functions/` | Global helpers (checkout, pricing, orders) |
| `includes/stripe/`, `includes/class-ppcart-stripe.php` | First-party Stripe helpers (`StripeClient` wrapper) |
| Composer `publishpress/stripe-php` (`lib/`) | Vendored SDK, namespace `PublishPress\Stripe\` |
| `includes/stripe-sync/` | Stripe webhook / subscription sync |
| `includes/secrets/` | Credential encryption/storage |
| Companion `publishpress-cart-compat` | StudioCart Compatibility Mode + Data migration |
| `includes/integrations/gutenberg/` | Checkout/account blocks |
| `public/class-ppcart-public.php` | Storefront checkout, shortcodes |
| `public/class-ppcart-paypal.php` | PayPal checkout |
| `public/webhooks/` | `stripe.php`, `paypal.php` — keep thin |
| `public/templates/` | Storefront PHP templates |
| `admin/` | WP admin: settings, product/order metaboxes, reports |
| `models/` | Order and subscription objects |
| `docs/dev/architecture.md` | Full runtime map — read before structural changes |
| `.scribe.config.yaml` + [docs/README.md](docs/README.md) | Sellable feature catalog |
| `docs/testing/cases/` | Test catalog (`UT-*`, `IT-*`, `RT-*`, `AD-*`, `ST-*`) |

Do not search `lib/vendor/`, `vendor/`, or `node_modules/` (see `.cursorignore`).

## Tests

| Suite | Where | Run |
|-------|--------|-----|
| Unit | `tests/codeception/Unit/` | `composer test:unit` |
| Integration | `tests/codeception/Integration/` | `composer test:integration` |
| Regression | `tests/playwright/regression/` | `composer test:regression` |
| Admin e2e | `tests/playwright/admin/` | `composer test:admin` |
| Smoke | `tests/playwright/smoke/` | `composer test:smoke` (runbook: `docs/dev/testing/smoke-tests.md`) |

Single-test examples: `.cursor/rules/running-tests.mdc`. Setup: `tests/README.md`.

**Never write code-shape tests.** A test asserts *behavior* — call the code and
check the return value, the rendered markup, the stored data, the fired hook, the
HTTP response. Never assert on source text: no `file_get_contents()` over
`admin/`, `includes/`, `public/`, `models/`, no regex sweep for a string, no
"this file contains X", no assertions that a method or class name exists. Unit
tests are the cheapest place to prove behavior, but only behavior. When behavior
needs WordPress, use integration; when it needs a browser, use regression or
admin e2e. If no suite can reach it, write no test. The source-shaped
`Unit/Prefix` suite was removed in publishpress/publishpress-cart#679; do not
copy that style.

## Conventions

- Agent skills: canonical versions live in `.agents/skills/` (editor-agnostic). `.cursor/skills/` and `.claude/skills/` hold thin pointer stubs only — edit the `.agents/` copy, never a stub.
- Prefixes: `PPCART_`, `ppcart_`, `PPCart_`. Leftover `sc_*` / `NCS_*` PHP names, leftover-tag detectors, and leftover dual-read live only in `publishpress-cart-compat`.
- New PHP files: one concern, under 500 lines. Move code; don't rewrite public APIs.
- Test catalog + Playwright/Codeception: `.agents/skills/test-authoring/`.
- Feature records: `.agents/skills/plugin-features/`.
- Leftover prefix slices 43+: `.agents/skills/prefix-leftover-slice/` (one family). Queue: `.agents/skills/prefix-leftover-orchestrator/`. Inventory: `docs/dev/prefix-standardization.md`. Keep `docs/dev/prefix-pro.md` in sync so Pro can follow the same refactor.
