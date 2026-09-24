# Tests

## Composer suites

Codeception suites live under `tests/codeception/`. Browser regression tests use Playwright under `tests/playwright/`.

```bash
# PHP unit tests (no WordPress)
composer test:unit

# PHP integration tests (WordPress + WPLoader)
composer test:integration

# Parallel Codeception (optional). UNIT_WORKERS / INTEGRATION_WORKERS
# default to 1. Integration shares one WordPress + DB — keep it at 1
# unless you are experimenting.
UNIT_WORKERS=8 composer test:unit

# Browser checkout regression (Playwright; requires site setup — see below)
composer test:regression

# Browser admin suite (Playwright core suite with reusable auth setup)
composer test:admin

# All of the above
composer test:all
```

Filter a single test by passing arguments after `--`:

```bash
composer test:unit Regression/SideToCestConverterTest
composer test:integration CheckoutBlock/CheckoutRenderingTest:testNewCheckoutBlockRendersCustomizedTwoStepTabText
composer test:regression RT-001
```

More examples: .cursor/rules/running-tests.mdc.

## GitHub Actions

Unit and integration workflows copy `.env.example` → `.env`, then `composer install`.
That install writes `REPO_ROOT`, `DEV_WORKSPACE_REAL`, and absolute `CACHE_PATH`
(`tests/bin/sync-env-bind-paths.sh`). Do not put `/absolute/path/to/...`
placeholders in `.env.example` — `composer info:version` would `mkdir /absolute`.

Playwright mocked regression (`.github/workflows/test-regression-mocked.yml`) and
admin (`.github/workflows/test-admin.yml`) run on PRs to `development`. Real-gateway
regression (`.github/workflows/test-regression.yml`) is workflow_dispatch only.

## Regression (checkout flows)

Browser regression tests (RT-001–RT-019) run via Playwright against a real WordPress site. Setup, env vars, and troubleshooting: [docs/dev/testing/regression-tests.md](../docs/dev/testing/regression-tests.md).

```bash
composer test:regression:setup   # first time (or after changing .env payment settings)
composer test:regression         # full suite (19 tests)

# Optional npm shortcuts (same Playwright runner)
npm run test:e2e:stripe
npm run test:e2e:paypal
npm run test:e2e:free
```

## Admin browser suite

The Playwright admin suite covers AD-001–AD-211 using the same definitions as
the Ghost Inspector admin suite:

```bash
composer test:setup
composer test:reset
composer test:admin:setup
composer test:admin
composer test:admin:fixtures
PUBLISHPRESS_CART_ADMIN_WORKFLOWS=1 composer test:admin:workflows
composer test:admin:validate
composer test:admin:reset
```

`composer test:admin:setup` prepares the admin fixture bundle and writes the
`ADMIN_*` values into `.env`.

`composer test:setup` prepares both regression and admin fixtures. `composer
test:reset` clears both.

`composer test:admin` runs the core suite. Fixture-dependent specs still
require `ADMIN_*` values in `.env`, and workflow specs should be run only on an
isolated site.

Run one case with `npm run test:e2e:admin -- --grep "AD-020"`. Full setup,
workflow cleanup behavior, and synchronization details are documented in
[docs/dev/testing/admin-tests.md](../docs/dev/testing/admin-tests.md).

Ghost Inspector `.side` sync and the Cest converter are documented in .agents/skills/side-cest-sync/SKILL.md.

Feature-specific test setup lives under its own folder:

```text
tests/
  ppcart-fixtures/          # WP plugin for dummy data (see below)
  build-fixtures.sh         # Zip helper (composer fixtures:build)
  link-fixtures.sh          # Symlink helper (composer fixtures:link)
  e2e/
    checkout-block/
      README.md
      run.sh
  integration/
    checkout-block/
      README.md
      run.sh
      gutenberg-checkout-block.php
```

This keeps checkout block fixtures isolated while leaving room for future suites such as coupons, products, reports, or settings.

## Focused Suites

Checkout block-specific setup is documented beside each suite:

```bash
bash tests/e2e/checkout-block/run.sh
bash tests/integration/checkout-block/run.sh
```

See `tests/e2e/checkout-block/README.md` and `tests/integration/checkout-block/README.md` for fixture and dependency details.

## Test Fixtures Plugin

`tests/ppcart-fixtures/` is a WordPress plugin for creating dummy PublishPress Cart data in a local or CI WordPress install. Use it to stand up checkout pages, account history, and admin lists without manual setup.

### Install

Copy or symlink the plugin directory into `wp-content/plugins/` and activate it. The source lives in the repo (the `tests/` folder is not synced to your local WordPress copy by rsync):

```bash
composer fixtures:link
wp plugin activate ppcart-fixtures
```

`composer fixtures:link` reads `LOCAL_SYNC_TARGET_DIR` from `.env` and links:

```text
{wp-content/plugins}/ppcart-fixtures -> {repo}/tests/ppcart-fixtures
```

Manual install on this checkout or another site:

```bash
ln -s /path/to/publishpress-cart/tests/ppcart-fixtures wp-content/plugins/ppcart-fixtures
# or: cp -R tests/ppcart-fixtures /path/to/wp-content/plugins/
wp plugin activate ppcart-fixtures
```

Zip for upload on another site (`tests/dist/ppcart-fixtures.zip`):

```bash
composer fixtures:build
```

### Requirements

- PublishPress Cart must be active (live CPTs from `ppcart_live_post_type()`).
- Fixture creation is allowed only when `WP_DEBUG` is true, or when `PPCART_FIXTURES_ENABLED` is set to `true` in `wp-config.php`.

### What gets created

Live post types come from `ppcart_live_post_type()` /
`ppcart_canonical_live_post_types()` (`ppcart_product`, `ppcart_order`,
`ppcart_subscription`). Leftover `sc_product` / `sc_order` /
`sc_subscription` names appear only in Compat or unmigrated data.

| Entity | Post type / object | Notes |
|--------|-------------------|-------|
| Product | `ppcart_product` | Payment plans, button labels, cart settings |
| Page | `page` | Optional checkout block markup |
| Customer | WP user | Logins prefixed `ppcart_` or `ppcart_seed_customer_*` |
| Order | `ppcart_order` | Customer, product, amount, and status meta |
| Subscription | `ppcart_subscription` | Linked to recurring products when possible |

All created IDs are stored in the `ppcart_fixtures_registry` option so they can be removed from the admin cleanup buttons.

### Admin UI

Open **Tools → PublishPress Cart Fixtures** in wp-admin (requires `manage_options`).

The page includes:

| Section | Actions |
|---------|---------|
| **Quick bundles** | Create E2E bundle, Create account bundle |
| **Seed used site** | Configure counts, then **Seed site** |
| **Create one item** | Product, customer, order, subscription, page |
| **Registry** | View counts/JSON, cleanup tracked or all fixtures |

After each action, a notice shows the result. Bundles and seed runs also display JSON details (IDs, URLs, generated passwords).

#### Bundles

**E2E bundle** — Admin user, E2E product (two plans), blank checkout page. Matches what `tests/e2e/checkout-block/run.sh` creates.

**Account bundle** — Customer with one paid order and one active subscription (account block tests).

#### Seed used site

Simulates a site with existing sales. Default counts:

| Field | Default | Description |
|-------|---------|-------------|
| Products | 6 | Mix of one-time and recurring products |
| Customers | 12 | Named customer accounts |
| Orders | 24 | Random product/customer pairs, backdated ~90 days |
| Subscriptions | 10 | Prefer recurring products, backdated ~180 days |
| Checkout pages | 3 | Pages with the Gutenberg checkout block |

Seed data uses the title prefix `PublishPress Cart Seed —` and customer logins `ppcart_seed_customer_001`, etc. Orders include mixed statuses (`paid`, `completed`, `refunded`, `failed`). Subscriptions include mixed statuses (`active`, `trialing`, `past_due`, `paused`, `canceled`, `completed`).

#### Individual items

Product types:

- **`e2e`** — Two day plans (`e2e_plan`, `e2e_plan_200`), coupon field enabled. Used by Playwright checkout tests.
- **`recurring`** — Single monthly recurring plan.
- **`one-time`** — Single one-time plan.

Orders and subscriptions accept optional user ID and product ID fields.

#### Cleanup

- **Cleanup tracked fixtures** — Deletes entities recorded in the registry.
- **Cleanup all PublishPress Cart fixtures** — Also removes posts titled with `PublishPress Cart` and users whose login matches `ppcart_*` (includes seed data).

### Typical workflows

**Local dev with a populated site:**

```bash
composer fixtures:link
wp plugin activate ppcart-fixtures
```

Then open **Tools → PublishPress Cart Fixtures** and click **Seed site**.

**Reset after testing:**

Use **Cleanup all PublishPress Cart fixtures** on the fixtures admin page.
