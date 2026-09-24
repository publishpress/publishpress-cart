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

test('RT-005 purchase subscription Stripe checkout flow monthly', { tag: ["@stripe","@regression"] }, async ({ page }) => {
  test.setTimeout(300000);
  skipUnlessRegressionUrl(config, 'url_subs_stripe');

  await page.goto(config.pagePath('url_subs_stripe'));

  await fillCheckoutContact(page, config, 'tc005');

  await fillCard(page, config);

  await clickOrderNow(page);
  await assertOrderReceived(page);
});
