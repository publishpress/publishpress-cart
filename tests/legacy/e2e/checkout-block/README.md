# Checkout Block E2E

This suite verifies the PublishPress Cart Checkout block in both the Gutenberg editor and the frontend.

Run all E2E suites from the plugin root:

```bash
npm --prefix includes/integrations/gutenberg install
npm --prefix includes/integrations/gutenberg exec -- playwright install chromium
composer test:e2e
```

Run only this checkout block E2E suite:

```bash
bash tests/e2e/checkout-block/run.sh
```

The runner creates a temporary WordPress admin user, `sc_product`, and page unless these values are supplied:

- `WP_ADMIN_USER`
- `WP_ADMIN_PASSWORD`
- `PPCART_E2E_PRODUCT_ID`
- `PPCART_E2E_PAGE_ID`

The test is not tied to a specific local domain. By default, it reads the site URL from WP-CLI:

```bash
wp option get siteurl
```

Use these environment variables when WP-CLI needs more context:

```bash
WP_PATH=/path/to/wordpress bash tests/e2e/checkout-block/run.sh
WP_BASE_URL=https://example.local bash tests/e2e/checkout-block/run.sh
WP_CLI_ARGS="--url=https://example.local" bash tests/e2e/checkout-block/run.sh
```

To save success screenshots for human review:

```bash
PPCART_E2E_SCREENSHOTS=1 bash tests/e2e/checkout-block/run.sh
```

Screenshots are written to:

```text
tests/e2e-results/screenshots/
```

Override the screenshot folder with:

```bash
PPCART_E2E_SCREENSHOT_DIR=/tmp/checkout-screenshots PPCART_E2E_SCREENSHOTS=1 bash tests/e2e/checkout-block/run.sh
```
