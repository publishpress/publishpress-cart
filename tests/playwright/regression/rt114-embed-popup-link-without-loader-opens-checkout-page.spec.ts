import { expect, test } from '@playwright/test';

import { fillCheckoutContact } from '../support/checkout-flow-steps';
import { expectReceipt, openHostPage, productUrl, submitOrder, withEmbedFlag } from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test('RT-114 popup link on a page without the loader opens a working checkout page', { tag: ['@free', '@regression', '@embed'] }, async ({ page }) => {
  test.setTimeout(180_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_free');

  const product = productUrl(config, 'url_embed_product_free');
  const popupLink = withEmbedFlag(product, 'popup');

  await openHostPage(page, `<p><a href="${popupLink}">Buy now</a></p>`);
  await page.getByRole('link', { name: 'Buy now' }).click();

  await expect(page, 'the link opens the checkout in the browser window').toHaveURL(popupLink);
  await expect(page.getByRole('dialog'), 'no popup opens without the loader').toHaveCount(0);
  await expect(page.locator('body'), 'the checkout page is the embed shell').toHaveClass(/\bppcart-embed\b/);

  await fillCheckoutContact(page, config, 'rt114');
  await submitOrder(page.mainFrame());
  await expectReceipt(page.mainFrame());
  expect(new URL(page.url()).origin, 'the receipt stays on the shop').toBe(new URL(config.host()).origin);
});
