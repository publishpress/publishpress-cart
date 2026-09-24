import { test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  expectSalePaymentPlan,
  fillCheckoutContact,
} from '../support/checkout-flow-steps';

import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';
import { fillCard } from '../support/stripe-steps';

const config = RegressionConfig.fromEnv();

test('RT-003 purchase one-time Stripe checkout flow first sale price', { tag: ["@stripe","@regression"] }, async ({ page }) => {
  test.setTimeout(300000);
  skipUnlessRegressionUrl(config, 'url_one_time_stripe_with_sale_price');

  await page.goto(config.pagePath('url_one_time_stripe_with_sale_price'));

  await expectSalePaymentPlan(page, 1);

  await fillCheckoutContact(page, config, 'tc003');

  await fillCard(page, config);

  await clickOrderNow(page);
  await assertOrderReceived(page);
});
