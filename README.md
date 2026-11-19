# PublishPress Cart

PublishPress Cart provides checkout, product, order, and payment features for WordPress. This repository contains the Free plugin and the shared code that Pro bundles as its base package.

WordPress.org ships this Free plugin only. StudioCart leftover names, Compatibility Mode, and merchant Data migration live in the sibling [publishpress-cart-compat](https://github.com/publishpress/publishpress-cart-compat) plugin.

## Requirements

- WordPress 6.7+ with PHP 7.4+.
- Composer for PHP dependencies.
- npm for Playwright browser tests.
- Gateway sandbox credentials for checkout regression tests that use external providers.

## Prefixes

First-party symbols use `PPCART_`, `ppcart_`, and `PPCart_`. Leftover `sc_*` / `studiocart_*` / `NCS_*` / `ncs_*` names belong only in Compat.

## Layout

| Path | What lives here |
|------|-----------------|
| `publishpress-cart.php` | Bootstrap, constants, activation |
| `includes/class-ppcart.php` | Hook registration; requires admin/public/models |
| `includes/functions.php` + `includes/functions/` | Global helpers (checkout, pricing, orders) |
| `public/class-ppcart-public.php` + `public/controllers/` | Storefront checkout, shortcodes, coordinators |
| `public/webhooks/` | `stripe.php`, `paypal.php` — keep thin |
| `admin/` | Settings, product/order metaboxes, reports |
| `models/` | Order and subscription objects |
| `docs/dev/architecture.md` | Full runtime map |

## Documentation

- [Architecture](docs/dev/architecture.md) — bootstrap, folder map, and module boundaries.
- [Regression tests](docs/testing/regression-tests.md) — browser checkout suite setup and run guide.
- [tests/README.md](tests/README.md) — unit, integration, regression, admin, and smoke suites.

## Tests

```bash
composer test:up
composer dev:shell
composer test:regression:setup
composer test:unit
composer test:integration
composer test:regression
composer test:admin
composer test:all
composer metrics
```

See [tests/README.md](tests/README.md) for single-test examples and environment setup.
