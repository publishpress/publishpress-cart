import { expect, test } from '@playwright/test';

import { frameOf, INLINE_FRAME, loaderTag, openHostPage, placeFreeOrder, productUrl } from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();
const THANK_YOU = 'https://thanks.merchant.example/thank-you/';

test('RT-115 order of a product that redirects to an external page navigates the top window', { tag: ['@free', '@regression', '@embed'] }, async ({ page, request }) => {
  test.setTimeout(180_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_redirect');

  const product = productUrl(config, 'url_embed_product_redirect');

  await page.route('https://thanks.merchant.example/**', (route) => route.fulfill({
    status: 200,
    contentType: 'text/html',
    body: '<!doctype html><title>Thank you</title><h1>Thanks for your order</h1>',
  }));
  await openHostPage(page, `<div data-ppcart-checkout="${product}"></div>${await loaderTag(request, product)}`);

  const frame = await frameOf(page.locator(INLINE_FRAME));
  await placeFreeOrder(frame, config, 'rt115');

  await expect(page, 'the top window lands on the external thank-you page').toHaveURL(THANK_YOU);
  await expect(page.getByRole('heading', { name: 'Thanks for your order' })).toBeVisible();
  await expect(page.locator('iframe'), 'the thank-you page is not inside the frame').toHaveCount(0);
});
