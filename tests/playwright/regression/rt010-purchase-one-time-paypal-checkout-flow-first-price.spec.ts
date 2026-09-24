import { test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  fillCheckoutContact,
  selectPayPal,
} from '../support/checkout-flow-steps';
import { loginAndComplete } from '../support/paypal-steps';
import { resolveMerchantPage, resolvePayPalPage } from '../support/paypal-page';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl, skipUnlessPaypal } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test('RT-010 purchase one-time PayPal checkout flow first price', { tag: ["@paypal","@regression"] }, async ({ page, request }) => {
  test.setTimeout(300000);
  skipUnlessRegressionUrl(config, 'url_one_time_paypal');
  await skipUnlessPaypal(config, request);

  await page.goto(config.pagePath('url_one_time_paypal'));

  await fillCheckoutContact(page, config, 'tc010');
  await selectPayPal(page);
  await clickOrderNow(page);

  const paypalPage = await resolvePayPalPage(page);
  await loginAndComplete(paypalPage, config, false);
  await assertOrderReceived(await resolveMerchantPage(page, config.host()));
});
