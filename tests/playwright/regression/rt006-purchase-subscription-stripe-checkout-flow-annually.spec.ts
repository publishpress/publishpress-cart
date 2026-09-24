import { test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  expectPaymentPlan,
  fillCheckoutContact,
  selectPaymentPlan,
} from '../support/checkout-flow-steps';

import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';
import { fillCard } from '../support/stripe-steps';

const config = RegressionConfig.fromEnv();

test('RT-006 purchase subscription Stripe checkout flow annually', { tag: ["@stripe","@regression"] }, async ({ page }) => {
  test.setTimeout(300000);
  skipUnlessRegressionUrl(config, 'url_subs_stripe');

  await page.goto(config.pagePath('url_subs_stripe'));

  await expectPaymentPlan(page, 1);
  await selectPaymentPlan(page, 2);

  await fillCheckoutContact(page, config, 'tc006');

  await fillCard(page, config);

  await clickOrderNow(page);
  await assertOrderReceived(page);
});
