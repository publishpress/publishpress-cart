import { expect, test } from '@playwright/test';

import { frameOf, HOST_URL, loaderTag, openHostPage, POPUP_FRAME, productUrl, withEmbedFlag } from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test('RT-113 popup link opens the checkout in a dialog on a page with the loader', { tag: ['@free', '@regression', '@embed'] }, async ({ page, request }) => {
  test.setTimeout(180_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_free');

  const product = productUrl(config, 'url_embed_product_free');

  await openHostPage(page, `<p><a href="${withEmbedFlag(product, 'popup')}">Buy now</a></p>${await loaderTag(request, product)}`);

  const link = page.getByRole('link', { name: 'Buy now' });
  await link.click();

  await expect(page.getByRole('dialog', { name: 'Checkout' }), 'the link opens the checkout dialog').toBeVisible();
  const frame = await frameOf(page.locator(POPUP_FRAME));
  await expect(frame.getByTestId('ppcart-order-submit'), 'the checkout form renders in the popup').toBeVisible();
  expect(new URL(frame.url()).searchParams.get('ppcart-embed'), 'the popup frame asks for the embed shell').toBe('1');
  expect(page.url(), 'the link does not navigate the host page').toBe(HOST_URL);

  await page.keyboard.press('Escape');
  await expect(page.getByRole('dialog'), 'Esc closes the dialog').toHaveCount(0);
  await expect(link, 'focus returns to the link').toBeFocused();
});
