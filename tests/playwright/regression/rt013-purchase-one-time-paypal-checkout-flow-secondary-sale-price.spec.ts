import { test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  expectPaymentPlan,
  expectSalePaymentPlan,
  fillCheckoutContact,
  selectPayPal,
  selectPaymentPlan,
} from '../support/checkout-flow-steps';
import { loginAndComplete } from '../support/paypal-steps';
import { resolveMerchantPage, resolvePayPalPage } from '../support/paypal-page';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl, skipUnlessPaypal } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test('RT-013 purchase one-time PayPal checkout flow secondary sale price', { tag: ["@paypal","@regression"] }, async ({ page, request }) => {
  test.setTimeout(300000);
  skipUnlessRegressionUrl(config, 'url_one_time_paypal_with_sale_price');
  await skipUnlessPaypal(config, request);

  await page.goto(config.pagePath('url_one_time_paypal_with_sale_price'));

  await expectPaymentPlan(page, 1);
  await expectSalePaymentPlan(page, 2);
  await selectPaymentPlan(page, 2);
  await fillCheckoutContact(page, config, 'tc013');
  await selectPayPal(page);
  await clickOrderNow(page);

  const paypalPage = await resolvePayPalPage(page);
  await loginAndComplete(paypalPage, config, false);
  await assertOrderReceived(await resolveMerchantPage(page, config.host()));
});
