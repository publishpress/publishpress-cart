import { test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  expectSalePaymentPlan,
  fillCheckoutContact,
  selectPayPal,
} from '../support/checkout-flow-steps';
import { loginAndComplete } from '../support/paypal-steps';
import { resolveMerchantPage, resolvePayPalPage } from '../support/paypal-page';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl, skipUnlessPaypal } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test('RT-016 purchase subscription PayPal checkout flow monthly sale price', { tag: ["@paypal","@regression"] }, async ({ page, request }) => {
  test.setTimeout(300000);
  skipUnlessRegressionUrl(config, 'url_subs_paypal_with_sale_price');
  await skipUnlessPaypal(config, request);

  await page.goto(config.pagePath('url_subs_paypal_with_sale_price'));

  await expectSalePaymentPlan(page, 1);
  await fillCheckoutContact(page, config, 'tc016');
  await selectPayPal(page);
  await clickOrderNow(page);

  const paypalPage = await resolvePayPalPage(page);
  await loginAndComplete(paypalPage, config, true);
  await assertOrderReceived(await resolveMerchantPage(page, config.host()));
});
