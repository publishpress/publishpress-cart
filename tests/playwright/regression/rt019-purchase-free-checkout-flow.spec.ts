import { test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  fillCheckoutContact,
} from '../support/checkout-flow-steps';

import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test('RT-019 purchase free checkout flow', { tag: ["@free","@regression"] }, async ({ page }) => {
  test.setTimeout(300000);
  skipUnlessRegressionUrl(config, 'url_product_free');

  await page.goto(config.pagePath('url_product_free'));

  await fillCheckoutContact(page, config, 'tc019');

  await clickOrderNow(page);
  await assertOrderReceived(page);
});
