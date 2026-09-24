# Regression tests

Browser regression tests live under `tests/playwright/regression/` (RT-001–RT-019). They were ported from the Ghost Inspector **[StudioCart] Full Regression** suite and run with [Playwright](https://playwright.dev/) against a real WordPress site.

## Running the suite

Real gateways (Stripe test API + PayPal sandbox):

```bash
composer test:regression:setup
composer test:regression
```

Mocked gateways (no external Stripe/PayPal network calls — local Card Element + PayPal mock page):

```bash
composer test:regression:setup:mocked
composer test:regression:mocked
```

`REGRESSION_MOCKED_GATEWAYS=1` enables both mocks. Playwright sends `X-PPCART-Regression-Mocked: 1` on every request so WordPress activates the mocks for that run even if site options are still in real mode. `composer test:regression:mocked` also best-effort flips WP options / `.env`; `composer test:regression` restores real mode. Real credentials are not required for the mocked suite.

CI: mocked regression runs on PRs to `development` (`.github/workflows/test-regression-mocked.yml`). Real-gateway regression is Actions → Regression tests → Run workflow only (`.github/workflows/test-regression.yml`).

`composer test:regression:setup` always syncs against real Stripe test keys (`REGRESSION_MOCKED_GATEWAYS=0`), even if `.env` still has mocks on. `composer test:regression:setup:mocked` is the mock-sync path. `composer test:regression` restores real mode and re-syncs fixtures when products still have `price_mock_*` IDs from a mocked setup.

| Script | Purpose |
|--------|---------|
| `composer test:regression` | Full RT-001–RT-019 against real Stripe test + PayPal sandbox |
| `composer test:regression:mocked` | Same specs with local Stripe/PayPal mocks |
| `composer test:jev:regression` | Jev oracle-quality dry-run (pilot; `-- --live` needs `TYPESAFE_API_KEY`) |
| `composer test:regression:setup` | Configure real gateways + fixtures |
| `composer test:regression:setup:mocked` | Configure mocked gateways + fixtures |
| `npm run test:e2e:stripe` / `:paypal` / `:free` | Subset by tag (or `composer test:regression -- --grep @stripe`) |

## Prerequisites

- PHP and Composer dependencies installed (`composer install --working-dir=./lib` and root `composer install` if needed)
- Node.js dependencies installed (`npm install`)
- Playwright browsers installed (`npx playwright install chromium`)
- [WP-CLI](https://wp-cli.org/) available on your `PATH`
- A WordPress site with PublishPress Cart installed and reachable at the URL you configure (for example `http://tests.local` via [Local WP](https://localwp.com/) or your own host mapping)
- For **real** Stripe tests: Stripe test credentials in `.env`
- For **real** PayPal tests: PayPal sandbox credentials in `.env`
- For **mocked** suite: neither Stripe nor PayPal credentials are required

## One-time configuration

1. Copy the environment template and edit it:

   ```bash
   cp .env.example .env
   ```

2. Set the regression target site in `.env`:

   | Variable | Purpose |
   |----------|---------|
   | `PUBLISHPRESS_CART_HOST` | Base URL for browser tests (e.g. `http://cart.test`) |
   | `REGRESSION_WP_PATH` | Optional host path to that site's WordPress root |
   | `REGRESSION_WP_CONTAINER` | Optional Docker container name when WordPress is not on the host filesystem |
   | `WP_PATH` | Alternative to `REGRESSION_WP_PATH` |

   When `REGRESSION_WP_PATH` is omitted (or points at a missing Local path), setup auto-detects WordPress from:
   - a running Docker container that bind-mounts this plugin into `wp-content/plugins/` (matches `PUBLISHPRESS_CART_HOST` when multiple sites share the mount)
   - Local WP `sites.json` on macOS
   - a WordPress tree that already contains this plugin
   - the PublishPress dev-workspace `wp_test` cache, when present

   GitHub Actions copies `.env.example` then provisions `.ci-wordpress` with host WP-CLI. Keep `REGRESSION_WP_CONTAINER` commented in the example; a DDEV name that is not running is ignored, and CI provisioning clears it.

3. Add Stripe test credentials (from your Stripe dashboard or Cart admin):

   | Variable | Purpose |
   |----------|---------|
   | `STRIPE_TEST_PUBLISHABLE_KEY` | `pk_test_...` — required for Stripe tests |
   | `STRIPE_TEST_SECRET_KEY` | `sk_test_...` — required for Stripe tests |
   | `STRIPE_TEST_KEY_SOURCE` | Use `direct` for normal dashboard test keys; use `oauth_access_token` only for credentials returned by the Cart Connect flow |
   | `STRIPE_TEST_CONNECT_ACCOUNT_ID` | Leave empty for `direct` keys; use the connected `acct_...` only with platform/Connect credentials |

   For PayPal tests, also set `PAYPAL_USERNAME` and `PAYPAL_PASSWORD` (buyer account — see [PayPal credentials](#paypal-sandbox-credentials)).

   Credentials are read from `.env` only. Setup does not copy keys from another WordPress install.

   Set `REGRESSION_CURRENCY` to match your PayPal sandbox Business account (`BRL` for Brazil, `USD` for United States), then run setup again after changes.

4. Install PublishPress Cart on the regression WordPress site (symlink, copy, or deploy as you prefer). The site must run the plugin before setup.

## Prepare the regression site

`composer test:regression:setup` prepares the WordPress install pointed to by `PUBLISHPRESS_CART_HOST`:

1. Creates `.env` from `.env.example` if missing
2. Symlinks `tests/ppcart-fixtures/` into `wp-content/plugins/`
3. Activates PublishPress Cart and the fixtures plugin
4. Applies Stripe settings from `.env` to WordPress (when keys are configured)
5. Creates 11 regression products and checkout pages with `[ppcart_form]` shortcodes
6. Writes checkout paths into `.env` as `URL_*` variables (for example `URL_ONE_TIME_STRIPE=/regression-one-time-stripe/`)

```bash
composer test:regression:setup
```

Re-running setup replaces previous regression fixtures. Fixture data is tracked in the `ppcart_regression_fixtures_registry` option.

To remove fixtures and reset `URL_*` paths to `"/"`:

```bash
composer test:regression:reset
```

## Run tests

Ensure the host in `PUBLISHPRESS_CART_HOST` resolves in your environment (for example via Local WP or `/etc/hosts`).

Run the full suite:

```bash
composer test:regression
```

That runs Playwright with the `@regression` tag (`npm run test:e2e -- --grep @regression`).

Regression specs are independent and run in parallel by default (Playwright workers). Override with:

```bash
PW_WORKERS=1 composer test:regression   # serial (useful if PayPal sandbox flakes)
PW_WORKERS=4 composer test:regression   # cap concurrency
```

Screenshots are captured only on failure (`PW_SCREENSHOT=on` restores always-on).

Run a subset:

```bash
# By tag (via composer)
composer test:regression -- --grep @stripe
composer test:regression -- --grep @paypal
composer test:regression -- --grep @free

# By test id
composer test:regression -- --grep "RT-001"

# npm shortcuts (same runner)
npm run test:e2e:stripe
npm run test:e2e:paypal
npm run test:e2e:free
npm run test:e2e:regression -- --grep "RT-019"
```

Test output, traces, screenshots, and videos are written to `test-results/` and `playwright-report/` (when enabled).

## Composer commands

| Command | Description |
|---------|-------------|
| `composer test:regression:setup` | Create regression products/pages and update `.env` (real gateways) |
| `composer test:regression:setup:mocked` | Same setup with Stripe/PayPal mocks (no provider secrets) |
| `composer test:regression:reset` | Remove regression fixtures and reset `URL_*` in `.env` |
| `composer test:regression` | Run all Playwright regression specs (RT-001–RT-019) against real gateways |
| `composer test:regression:mocked` | Same specs with mocked Stripe/PayPal |
| `composer test:regression:generate` | Regenerate Playwright specs from `.side` (converter tooling; see below) |

## Environment variables

Set these in the project root `.env` (see `.env.example`).

### Site and browser

| Variable | Description |
|----------|-------------|
| `PUBLISHPRESS_CART_HOST` | Base site URL for regression tests |
| `REGRESSION_WP_PATH` | Optional WordPress root path on the host |
| `REGRESSION_WP_CONTAINER` | Optional Docker container providing WordPress + this plugin mount |
| `REGRESSION_WP_CLI_IMAGE` | Docker image used for WP-CLI when running against a container (default: `wordpress:cli`) |

### Payment gateways

| Variable | Description |
|----------|-------------|
| `STRIPE_TEST_PUBLISHABLE_KEY` | Stripe test publishable key |
| `STRIPE_TEST_SECRET_KEY` | Stripe test secret key |
| `STRIPE_TEST_KEY_SOURCE` | `direct` for normal Stripe dashboard keys, or `oauth_access_token` for Cart Connect credentials |
| `STRIPE_TEST_CONNECT_ACCOUNT_ID` | Empty for direct keys; connected `acct_...` for Connect credentials |
| `PAYPAL_USERNAME` | PayPal sandbox **buyer** login email (`@personal.example.com`) |
| `PAYPAL_PASSWORD` | PayPal sandbox **buyer** password |
| `PAYPAL_SANDBOX_EMAIL` | PayPal sandbox **merchant** email (`@business.example.com`) |
| `PAYPAL_SANDBOX_CLIENT_ID` | PayPal REST app Client ID (not buyer username) |
| `PAYPAL_SANDBOX_SECRET` | PayPal REST app Secret (not buyer password) |
| `REGRESSION_CURRENCY` | Cart currency applied at setup (`USD` or `BRL`; must match PayPal Business account) |
| `REGRESSION_REAL_PAYPAL` | `1` = sandbox.paypal.com (default); `0` = local PayPal mock page |
| `REGRESSION_MOCKED_GATEWAYS` | `1` = Stripe + PayPal mocks (no external provider calls) |

### PayPal sandbox credentials

PayPal provides five different values. Map them to `.env` as follows:

| `.env` | Role | PayPal dashboard source |
|--------|------|-------------------------|
| `PAYPAL_SANDBOX_EMAIL` | Merchant (seller) | Sandbox **Business** account (`@business.example.com`) |
| `PAYPAL_SANDBOX_CLIENT_ID` | REST API | App → API Credentials → Client ID |
| `PAYPAL_SANDBOX_SECRET` | REST API | App → API Credentials → Secret |
| `PAYPAL_USERNAME` | Buyer login | Sandbox **Personal** account (`@personal.example.com`) |
| `PAYPAL_PASSWORD` | Buyer password | Personal account password |

**Real PayPal checkout** (default for `composer test:regression`): set in `.env`:

```bash
REGRESSION_REAL_PAYPAL=1
REGRESSION_MOCKED_GATEWAYS=0
```

Run `composer test:regression:setup` so WordPress disables the local mock. Tests then redirect to `sandbox.paypal.com` only; the mock at `/regression-paypal-mock/` is rejected.

Optional override in `wp-config.php`: `define('PPCART_REGRESSION_REAL_PAYPAL', true);`

**Mocked gateways** (`composer test:regression:mocked`):

```bash
REGRESSION_MOCKED_GATEWAYS=1
REGRESSION_REAL_PAYPAL=0
```

Or run `composer test:regression:setup:mocked`, which writes those flags and enables the local PayPal page plus the Stripe JS/HTTP mocks. Set `REGRESSION_REAL_PAYPAL=0` alone only when you intentionally want the PayPal HTML mock without the full mocked-gateways suite.

### Checkout paths

Populated automatically by `composer test:regression:setup`:

| Variable | Description |
|----------|-------------|
| `URL_ONE_TIME_STRIPE` | One-time Stripe checkout path |
| `URL_ONE_TIME_STRIPE_WITH_SALE_PRICE` | One-time Stripe sale checkout path |
| `URL_SUBS_STRIPE` | Subscription Stripe checkout path |
| `URL_SUBS_STRIPE_WITH_SALE_PRICE` | Subscription Stripe sale checkout path |
| `URL_CUSTOM_PRICE_STRIPE` | Custom-price Stripe checkout path |
| `URL_ONE_TIME_PAYPAL` | One-time PayPal checkout path |
| `URL_ONE_TIME_PAYPAL_WITH_SALE_PRICE` | One-time PayPal sale checkout path |
| `URL_SUBS_PAYPAL` | Subscription PayPal checkout path |
| `URL_SUBS_PAYPAL_WITH_SALE_PRICE` | Subscription PayPal sale checkout path |
| `URL_CUSTOM_PRICE_PAYPAL` | Custom-price PayPal checkout path |
| `URL_PRODUCT_FREE` | Free product checkout path |

### Test data

| Variable | Description |
|----------|-------------|
| `FIRST_NAME`, `LAST_NAME`, `EMAIL`, `PHONE`, `COMPANY` | Customer form data |
| `STRIPE_CARD_NUMBER_SUCCESS` | Stripe [test card](https://stripe.com/docs/testing) (e.g. `4242424242424242`) — used in the Stripe iframe |
| `STRIPE_CC_EXP` | Stripe expiry as `MM/YY` (e.g. `12/34` or `06/31`; `06/2031` is normalized) |
| `CC_EXP` | Deprecated alias for `STRIPE_CC_EXP` only — not for PayPal |
| `CVC`, `POSTAL` | Stripe CVC and ZIP (ZIP is filled only when Stripe shows the field) |
| `PAYPAL_CARD_NUMBER` | Optional. Card on your PayPal sandbox **Personal** account; tests log in with `PAYPAL_USERNAME` / `PAYPAL_PASSWORD` and do not type this value |
| `PAYPAL_CC_EXP` | Expiry for `PAYPAL_CARD_NUMBER` (`MM/YY` or `MM/YYYY`); required when `PAYPAL_CARD_NUMBER` is set |
| `MODIFY_PRICE` | Custom price amount for custom-price tests |

## Test tags

| Tag | Tests |
|-----|-------|
| `@regression` | All 19 tests |
| `@stripe` | RT-001 – RT-009 |
| `@paypal` | RT-010 – RT-018 |
| `@free` | RT-019 |

## Native Playwright coverage
The native RT-001–RT-019 Playwright specs remain hand-maintained because their
Stripe and PayPal helpers intentionally replace raw recorded steps.

## Legacy `.side` converter

Ghost Inspector exports **New Selenium IDE (`.side`)** files. The `side-to-playwright` converter (`tests/bin/side-to-playwright.php`) turns those exports into Playwright specs under `tests/playwright/regression/`.

> This replaces the former `side-to-cest.php` Codeception generator — **Cest is no longer used**. Specs are now generated directly as Playwright `rt*.spec.ts` files.

Generated specs can be regenerated from the `.side` export or refined by hand; either way they live in `tests/playwright/regression/rt*.spec.ts` and run with `composer test:regression`.

### Regenerate Playwright specs

1. Export the regression suite from Ghost Inspector as **New Selenium IDE (`.side`)**.
2. Replace `tests/playwright/regression/original.side`.
3. Run:

The older Ghost Inspector synchronization path used **New Selenium IDE
(`.side`)** exports. The `side-to-playwright` converter is retained as a
legacy migration utility, but it is no longer the authoritative checkout
source.

   This runs the `side-to-playwright` converter with default paths.

4. Review the git diff.
5. Run the affected specs, e.g. `composer test:regression -- --grep "RT-001"`.
The checkout suite is maintained as native `rt*.spec.ts` tests so its hardened
Stripe and PayPal helpers remain explicit. The historical export is retained
at `tests/playwright/regression/original.side`.

### Inspect legacy converted output

Write conversion output to a temporary directory for review:

```bash
php tests/bin/side-to-playwright.php \
  --source=tests/playwright/regression/original.side \
  --output=/tmp/publishpress-cart-side-conversion
```

Do not overwrite the native RT specs with generated output without reviewing
and adapting it to the current helpers.

### Converter script

```bash
php tests/bin/side-to-playwright.php [--source=path] [--output=path] [--test=RT-001]
```

| Option | Default | Purpose |
|--------|---------|---------|
| `--source` | `tests/playwright/regression/original.side` | Input `.side` file |
| `--output` | `tests/playwright/regression/` | Directory for generated specs |
| `--test` | (all tests) | Filter by test name substring (for example `RT-001`) |

Examples:

```bash
# Regenerate one test while iterating on the converter
php tests/bin/side-to-playwright.php --test=RT-001

# Convert a different export file
php tests/bin/side-to-playwright.php --source=/path/to/export.side
```

The converter:

- Maps Selenium IDE commands (`open`, `click`, `type`, `assertElementPresent`, etc.) to Playwright APIs (`page.goto()`, `locator.click()`, `locator.fill()`, `expect(...)`, etc.)
- Resolves Ghost Inspector `${variable}` placeholders via `RegressionConfig` (values from `.env`) — `tests/playwright/support/regression-config.ts`
- Routes repeated flows through the shared Playwright helpers in `tests/playwright/support/` (`stripe-steps`, `paypal-steps`, `checkout-flow-steps`)
- Assigns tags (`@stripe`, `@paypal`, `@free`, `@regression`) from test names
- Uses `config.uniqueEmail('rtNNN')` for Stripe test emails (avoids Stripe multi-currency customer errors)
- Maps the custom-price `Amount` field to `fillCustomPrice()`
- Skips redundant steps (for example Stripe iframe `type` commands handled by `fillCard()`)
- Prints warnings for unsupported commands; those appear as `// TODO` comments in generated specs

Generated specs are marked `AUTO-GENERATED`. Prefer changing shared behavior in `tests/playwright/support/` or the converter, then regenerate — hand-edits to a generated spec are overwritten the next time that test is regenerated.

If you add new Ghost Inspector commands, extend the converter's command mapping in `tests/bin/side-to-playwright.php`, document the pattern alongside the helpers in `tests/playwright/support/`, and regenerate.

## Helper conventions

Regression helpers encode sandbox and UI behavior that raw Ghost Inspector exports do not capture. **Always route through helpers** when converting new tests.

| Area | Helper | Why not raw Selenium |
|------|--------|----------------------|
| Stripe card | `fillCard()` (`support/stripe-steps`) | CVC auto-tab, optional postal, expiry normalization, test-card validation |
| Stripe email | `config.uniqueEmail('rtNNN')` | One Stripe customer per currency; batch runs reuse email otherwise |
| PayPal checkout | `loginAndComplete()` (`support/paypal-steps`) | Real vs mock, BR/PT UI, persisted buyer session, currency errors |
| Custom price | `fillCustomPrice()` (`support/checkout-flow-steps`) | Cart requires input/change/blur before checkout |
| Order button | `clickOrderNow()` (`support/checkout-flow-steps`) | Waits for button before click |

Full detail: `tests/playwright/support/`.

## Troubleshooting

- **WP-CLI cannot reach the site** — Prefer letting setup auto-detect a Docker bind-mount of this plugin, or set `REGRESSION_WP_CONTAINER` / `REGRESSION_WP_PATH` for the install that serves `PUBLISHPRESS_CART_HOST`. On Local WP (macOS), setup can still read `sites.json` and configure the MySQL socket when needed. Official `wordpress` images use the `wordpress:cli` sidecar; containers that already ship `wp` (including DDEV) are reached with `docker exec` instead — see `.env.example`.
- **Checkout page not found** — Run `composer test:regression:setup` and confirm `URL_*` values in `.env` match the created pages.
- **Stripe iframe or payment missing** — Confirm `STRIPE_TEST_PUBLISHABLE_KEY` and `STRIPE_TEST_SECRET_KEY` in `.env`, then re-run setup. Normal dashboard keys must use `STRIPE_TEST_KEY_SOURCE=direct` with an empty `STRIPE_TEST_CONNECT_ACCOUNT_ID`. Only credentials returned by the Cart Connect flow should use `oauth_access_token` and a connected account id.
- **Stripe says the transfer destination is your own account** — The API keys and `STRIPE_TEST_CONNECT_ACCOUNT_ID` point to the same account. For ordinary dashboard keys, switch to `STRIPE_TEST_KEY_SOURCE=direct`, clear the account id, and re-run setup. For a destination charge, use platform keys and a different connected account.
- **Stripe `postal` field not found** — Expected when ZIP is hidden; use `StripeSteps::fillCard()`. Use `STRIPE_CARD_NUMBER_SUCCESS` and `STRIPE_CC_EXP` (not PayPal card vars).
- **Stripe “cannot combine currencies on a single customer”** — Use per-test emails (`uniqueEmail`) in Stripe specs; regenerate from `.side` or set `test+rtNNN@example.com` manually.
- **Stripe CVC incomplete / order never completes** — Do not use `fillField` for iframe card fields; use `StripeSteps::fillCard()` (`pressKey` per digit).
- **PayPal timeout after Order Now** — Check `REGRESSION_CURRENCY` matches Business account country; run setup again. For custom price, use `fillCustomPrice()`.
- **PayPal sandbox never loads / tests stay on checkout** — Cart AJAX can still return a `sandbox.paypal.com` URL while Chromium GET requests to that host hang (HEAD may return 302/429). Live `paypal.com` working does not mean sandbox does. Confirm `https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_xclick` loads in the same browser; if it hangs, use `composer test:regression:mocked` until sandbox pages respond.
- **PayPal `INVALID_BUSINESS_ERROR`** — Set `PAYPAL_SANDBOX_EMAIL` to the Business account email, not buyer credentials.
- **PayPal `#btnNext` not found** — Buyer may already be logged in from a prior test; use `PayPalSteps::loginAndComplete()` (skips login when checkout is ready).
- **PayPal currency error (EN/PT)** — BR sandbox needs `REGRESSION_CURRENCY=BRL` and `composer test:regression:setup`.
- **Playwright browser missing** — Run `npx playwright install chromium`.
- **Flood of `PHP Deprecated` from `thecodingmachine/safe` during setup/WP-CLI/web** — Expected on PHP 8.4 with Safe v1.x (pulled in via `dompdf` → `sabberworm/php-css-parser`). Those signature deprecations ignore `set_error_handler`; only `error_reporting` works, and `WP_DEBUG` resets it before plugins load. Isolated test WordPress and this plugin's DDEV `.web` site install `tests/bin/lib/silence-php-deprecations.php` as an mu-plugin. `regression_wp()` still strips leftover `Deprecated:` lines from stderr. Do not raise Safe until the plugin can drop min PHP 7.4 / `lib/composer.json` `platform.php` 7.4 (Safe 2.x/3.x needs a newer PHP floor).

## Code layout

| Path | Purpose |
|------|---------|
| `tests/playwright/regression/` | Playwright specs (`rt001`–`rt019`) |
| `tests/playwright/support/` | Playwright helpers (`stripe-steps`, `paypal-steps`, `regression-config`, etc.) |
| `tests/ppcart-fixtures/ppcart-regression-paypal-mock.php` | Local PayPal checkout page for mocked mode |
| `tests/ppcart-fixtures/ppcart-regression-stripe-mock.php` | Stripe PHP HTTP + JS mocks for mocked mode |
| `tests/ppcart-fixtures/js/ppcart-regression-stripe-mock.js` | Local Stripe.js Card Element stand-in |
| `playwright.config.ts` | Playwright config (`testDir: ./tests/playwright`) |
| `tests/playwright/regression/original.side` | Historical Selenium IDE export used as converter input |
| `tests/bin/setup-site.sh` | Site setup script |
| `tests/bin/reset-site.sh` | Site reset script |
| `tests/bin/side-to-playwright.php` | `.side` → Playwright spec converter |
| `tests/bin/lib/` | WP-CLI helpers for setup/reset (`wp-env.sh`, fixture scripts) |
