import { expect, test } from '@playwright/test';

import { INLINE_FRAME, loaderTag, openHostPage, POPUP_FRAME, productUrl } from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test.use({ viewport: { width: 375, height: 812 } });

test('RT-118 on a phone the popup fills the screen and the inline form does not scroll sideways', { tag: ['@free', '@regression', '@embed'] }, async ({ page, request }) => {
  test.setTimeout(180_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_free');

  const product = productUrl(config, 'url_embed_product_free');

  await openHostPage(
    page,
    `<p><button type="button" data-ppcart-popup="${product}">Buy now</button></p>`
      + `<div data-ppcart-checkout="${product}"></div>${await loaderTag(request, product)}`,
  );

  const inline = page.frameLocator(INLINE_FRAME);
  await expect(inline.getByTestId('ppcart-order-submit'), 'the inline form renders').toBeVisible();
  const overflow = await inline.locator('html').evaluate((html) => ({ scrollWidth: html.scrollWidth, clientWidth: html.clientWidth }));
  expect(overflow.scrollWidth, `the inline form is no wider than its frame: ${JSON.stringify(overflow)}`).toBeLessThanOrEqual(overflow.clientWidth);

  await page.getByRole('button', { name: 'Buy now' }).click();
  const dialog = page.getByRole('dialog', { name: 'Checkout' });
  await expect(page.frameLocator(POPUP_FRAME).getByTestId('ppcart-order-submit'), 'the popup form renders').toBeVisible();

  expect(await dialog.boundingBox(), 'the popup covers the whole screen').toEqual({ x: 0, y: 0, width: 375, height: 812 });
  const frameBox = await page.locator(POPUP_FRAME).boundingBox();
  expect(frameBox && frameBox.width === 375 && frameBox.y + frameBox.height === 812, `the popup form reaches the screen edges: ${JSON.stringify(frameBox)}`).toBe(true);
});
