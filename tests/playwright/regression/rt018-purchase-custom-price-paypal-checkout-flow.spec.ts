import { test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  fillCheckoutContact,
  fillCustomPrice,
  selectPayPal,
} from '../support/checkout-flow-steps';
import { loginAndComplete } from '../support/paypal-steps';
import { resolveMerchantPage, resolvePayPalPage } from '../support/paypal-page';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl, skipUnlessPaypal } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test('RT-018 purchase custom price PayPal checkout flow', { tag: ["@paypal","@regression"] }, async ({ page, request }) => {
  test.setTimeout(300000);
  skipUnlessRegressionUrl(config, 'url_custom_price_paypal');
  await skipUnlessPaypal(config, request);

  await page.goto(config.pagePath('url_custom_price_paypal'));
  await fillCustomPrice(page, config.var('modify_price'));

  await fillCheckoutContact(page, config, 'tc018');
  await selectPayPal(page);
  await clickOrderNow(page);

  const paypalPage = await resolvePayPalPage(page);
  await loginAndComplete(paypalPage, config, false);
  await assertOrderReceived(await resolveMerchantPage(page, config.host()));
});
