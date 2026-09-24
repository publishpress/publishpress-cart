import { test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  expectPaymentPlan,
  expectSalePaymentPlan,
  fillCheckoutContact,
  selectPaymentPlan,
} from '../support/checkout-flow-steps';

import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';
import { fillCard } from '../support/stripe-steps';

const config = RegressionConfig.fromEnv();

test('RT-008 purchase subscription Stripe checkout flow annually sale price', { tag: ["@stripe","@regression"] }, async ({ page }) => {
  test.setTimeout(300000);
  skipUnlessRegressionUrl(config, 'url_subs_stripe_with_sale_price');

  await page.goto(config.pagePath('url_subs_stripe_with_sale_price'));

  await expectPaymentPlan(page, 1);
  await expectSalePaymentPlan(page, 2);
  await selectPaymentPlan(page, 2);

  await fillCheckoutContact(page, config, 'tc008');

  await fillCard(page, config);

  await clickOrderNow(page);
  await assertOrderReceived(page);
});
