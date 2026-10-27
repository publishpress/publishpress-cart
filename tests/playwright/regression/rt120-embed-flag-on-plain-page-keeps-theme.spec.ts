import { expect, test } from '@playwright/test';

import { productUrl, withEmbedFlag } from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test('RT-120 embed flag on a page without checkout content keeps the theme template', { tag: ['@free', '@regression', '@embed'] }, async ({ page }) => {
  test.setTimeout(120_000);
  skipUnlessRegressionUrl(config, 'url_embed_plain_page');
  skipUnlessRegressionUrl(config, 'url_embed_product_free');

  const product = await page.goto(withEmbedFlag(productUrl(config, 'url_embed_product_free')));
  expect(product?.headers()['content-security-policy'], 'a product with the flag may be framed by any site').toBe('frame-ancestors *');
  await expect(page.locator('body'), 'a product with the flag gets the embed shell').toHaveClass(/\bppcart-embed\b/);
  await expect(page.locator('main.ppcart-embed__main')).toHaveCount(1);

  const plain = await page.goto(withEmbedFlag(productUrl(config, 'url_embed_plain_page')));
  expect(plain?.status()).toBe(200);
  expect(plain?.headers()['content-security-policy'] ?? '', 'a plain page keeps its own framing policy').not.toContain('frame-ancestors');
  await expect(page.getByText('A page without checkout content.'), 'the page content renders').toBeVisible();
  await expect(page.locator('body'), 'a plain page with the flag keeps the theme template').not.toHaveClass(/\bppcart-embed\b/);
  await expect(page.locator('main.ppcart-embed__main'), 'a plain page with the flag is not the embed shell').toHaveCount(0);
});
