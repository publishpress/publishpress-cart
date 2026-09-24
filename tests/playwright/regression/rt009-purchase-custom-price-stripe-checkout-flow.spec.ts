import { test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  fillCheckoutContact,
  fillCustomPrice,
} from '../support/checkout-flow-steps';

import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';
import { fillCard } from '../support/stripe-steps';

const config = RegressionConfig.fromEnv();

test('RT-009 purchase custom price Stripe checkout flow', { tag: ["@stripe","@regression"] }, async ({ page }) => {
  test.setTimeout(300000);
  skipUnlessRegressionUrl(config, 'url_custom_price_stripe');

  await page.goto(config.pagePath('url_custom_price_stripe'));
  await fillCustomPrice(page, config.var('modify_price'));

  await fillCheckoutContact(page, config, 'tc009');

  await fillCard(page, config);

  await clickOrderNow(page);
  await assertOrderReceived(page);
});
