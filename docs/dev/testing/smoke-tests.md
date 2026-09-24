# Smoke browser tests

Short Ghost Inspector smoke flows run with Playwright against a real WordPress
site. Specs live under `tests/playwright/smoke/` and are tagged `@smoke`.

## Prerequisites

- PHP and Composer dependencies installed
- Node.js dependencies installed (`npm install`)
- Playwright browsers installed (`npx playwright install chromium`)
- [WP-CLI](https://wp-cli.org/) on your `PATH`
- A WordPress site with PublishPress Cart installed at the URL in `.env`
  (`PUBLISHPRESS_CART_HOST`)

## Fixtures and setup

Smoke tests share the regression WordPress fixtures. The smoke fixture plugin is
`tests/ppcart-fixtures/ppcart-smoke-fixtures.php`.

Prepare the site (creates products, pages, and `.env` URL paths):

```bash
composer test:regression:setup
```

Re-run setup when smoke pages or products change.

## Run

```bash
composer test:smoke
npm run test:e2e:smoke
```

Run a subset by test id or tag:

```bash
composer test:smoke -- --grep "ST-001"
composer test:smoke -- --grep @smoke
```

For environment variables and troubleshooting, see
[regression-tests.md](regression-tests.md) — smoke uses the same
`PUBLISHPRESS_CART_HOST` and payment credentials.
