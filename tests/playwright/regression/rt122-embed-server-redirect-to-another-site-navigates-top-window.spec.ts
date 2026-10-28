import { expect, test } from '@playwright/test';

import { loaderTag, openHostPage, productUrl } from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();
const CLOSED_PAGE = 'https://thanks.merchant.example/closed/';

test('RT-122 a server redirect to another site from an embed navigates the top window', { tag: ['@free', '@regression', '@embed'] }, async ({ page, request }) => {
  test.setTimeout(120_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_closed');

  const product = productUrl(config, 'url_embed_product_closed');

  await page.route('https://thanks.merchant.example/**', (route) => route.fulfill({
    status: 200,
    contentType: 'text/html',
    body: '<!doctype html><title>Closed</title><h1>This offer has ended</h1>',
  }));
  await openHostPage(page, `<div data-ppcart-checkout="${product}"></div>${await loaderTag(request, product)}`);

  await expect(page, 'the top window lands on the closed-cart redirect page').toHaveURL(CLOSED_PAGE);
  await expect(page.getByRole('heading', { name: 'This offer has ended' })).toBeVisible();
  await expect(page.locator('iframe'), 'the redirect page is not inside the frame').toHaveCount(0);
});
