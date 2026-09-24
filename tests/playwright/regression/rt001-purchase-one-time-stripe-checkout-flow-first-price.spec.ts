import { test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  fillCheckoutContact,
} from '../support/checkout-flow-steps';

import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';
import { fillCard } from '../support/stripe-steps';

const config = RegressionConfig.fromEnv();

test('RT-001 purchase one-time Stripe checkout flow first price', { tag: ["@stripe","@regression"] }, async ({ page }) => {
  test.setTimeout(300000);
  skipUnlessRegressionUrl(config, 'url_one_time_stripe');

  await page.goto(config.pagePath('url_one_time_stripe'));

  await fillCheckoutContact(page, config, 'tc001');

  await fillCard(page, config);

  await clickOrderNow(page);
  await assertOrderReceived(page);
});
