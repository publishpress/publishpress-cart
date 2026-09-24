import { test } from '@playwright/test';

import { SmokeConfig } from './smoke-config';

export function skipUnlessSmokeUrl(config: SmokeConfig, urlKey: string): void {
  if (!config.isUrlConfigured(urlKey)) {
    const fallbackKey = SmokeConfig.regressionFallbackEnvKey(urlKey);
    const hint = fallbackKey
      ? `Set ${urlKey.toUpperCase()} or ${fallbackKey} in .env (run composer test:regression:setup)`
      : `Set ${urlKey.toUpperCase()} in .env`;

    test.skip(true, hint);
  }
}

export function skipUnlessPaypal(config: SmokeConfig): void {
  if (!config.requiresRealPaypal()) {
    test.skip(true, 'PayPal smoke tests require REGRESSION_REAL_PAYPAL=1 in .env');
  }

  if (!config.hasPaypalCredentials()) {
    test.skip(true, 'Set PAYPAL_USERNAME and PAYPAL_PASSWORD in .env');
  }
}
