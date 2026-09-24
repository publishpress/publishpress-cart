# Admin browser tests

The Playwright admin suite catalog is under `docs/testing/cases/admin/`
(39 cases: AD-001–AD-011, AD-020–AD-031, AD-040–AD-041, AD-101–AD-103,
AD-201–AD-211).

| Group | Cases | Behavior |
| --- | ---: | --- |
| Core | 25 | Admin pages, tabs, filters, controls, and log viewers |
| Fixture-dependent | 3 | Existing product, order, and subscription editors |
| Workflows | 11 | Product, taxonomy, and branding persistence workflows |

The suite runs against an isolated local or staging WordPress site. Do not run
the workflow group against production because it publishes a product, creates
terms, and temporarily updates branding settings.

## Configure and prepare

Set the target and administrator credentials in `.env`:

```dotenv
PUBLISHPRESS_CART_HOST="http://tests.local"
# Optional on Docker hosts — auto-detected from a container that mounts this plugin.
# REGRESSION_WP_PATH="/absolute/path/to/wordpress"
# REGRESSION_WP_CONTAINER="wp-lab-cart-1"
WP_TESTS_ADMIN_USER="admin"
WP_TESTS_ADMIN_PASSWORD="admin"
# Optional concurrency. Default is 1 (shared WordPress state).
# ADMIN_PLAYWRIGHT_WORKERS="2"
# Or set shared PW_WORKERS — admin falls back to it when ADMIN_PLAYWRIGHT_WORKERS is unset.
# PW_WORKERS="4"
```

## Run

```bash
composer test:setup
composer test:admin:setup
composer test:admin
composer test:admin:fixtures
PUBLISHPRESS_CART_ADMIN_WORKFLOWS=1 composer test:admin:workflows
composer test:admin:validate
composer test:admin:ui
```

`composer test:admin:setup` prepares the admin fixture bundle and updates the
`ADMIN_*` values in `.env` for the fixture-dependent suite.

`composer test:admin` runs the core admin suite and only needs
`PUBLISHPRESS_CART_HOST`, `WP_TESTS_ADMIN_USER`, and `WP_TESTS_ADMIN_PASSWORD`
in `.env`.

Worker count comes from `.env` (`ADMIN_PLAYWRIGHT_WORKERS`, then `PW_WORKERS`,
else `1`). Raise it only if core/fixture cases stay stable against your site.

The runner creates a unique `ADMIN_WORKFLOW_RUN_ID`, snapshots the current
branding values, and restores them in an exit trap. Products and taxonomy
terms created by that run are also removed after success, failure, or an
interrupted run.

Fixture-dependent specs still need `ADMIN_*` fixture IDs in `.env`. Use the
setup command when needed:

```bash
composer test:admin:setup
composer test:admin:fixtures
```

The workflow dependencies are isolated into four serial groups:

- AD-201–AD-203: product creation and public verification
- AD-204–AD-205: product category
- AD-206–AD-207: product tag
- AD-208–AD-209: branding persistence

Run a single admin case with:

```bash
npm run test:e2e:admin -- --grep "AD-020"
npm run test:e2e:admin:core -- --grep "AD-020"
```

Reset the fixture records when finished:

```bash
composer test:admin:reset
```

## Structure

Each admin case now lives as its own Playwright spec in
`tests/playwright/admin/`, following the same one-file-per-case pattern as the
checkout regression suite. Shared selectors, step data, and variable-aware
execution live under `tests/playwright/support/`.
