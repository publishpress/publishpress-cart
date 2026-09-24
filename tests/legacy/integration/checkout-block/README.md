# Checkout Block Integration

This suite boots the local WordPress install and verifies checkout block registration, REST route permissions, rendering, anchor handling, and the legacy block alias.

Run all integration suites from the plugin root:

```bash
composer test:integration
```

Run only this checkout block integration suite:

```bash
bash tests/integration/checkout-block/run.sh
```

The runner uses the first available PHP CLI runtime:

1. `php`
2. `frankenphp php-cli`
3. `/usr/local/bin/frankenphp php-cli`

FrankenPHP is optional. A developer with normal PHP CLI does not need FrankenPHP.
