import { expect, test } from '@playwright/test';

import {
  expectReceipt,
  frameOf,
  HOST_URL,
  INLINE_FRAME,
  loaderTag,
  openHostPage,
  placeFreeOrder,
  productUrl,
} from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test('RT-111 free order inside the inline embed shows the receipt in the frame', { tag: ['@free', '@regression', '@embed'] }, async ({ page, request }) => {
  test.setTimeout(180_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_free');

  const product = productUrl(config, 'url_embed_product_free');

  await openHostPage(page, `<div data-ppcart-checkout="${product}"></div>${await loaderTag(request, product)}`);

  const frame = await frameOf(page.locator(INLINE_FRAME));
  await placeFreeOrder(frame, config, 'rt111');
  await expectReceipt(frame);

  const receiptUrl = new URL(frame.url());
  expect(receiptUrl.searchParams.get('ppcart-embed'), 'the receipt URL keeps the embed flag').toBe('1');
  expect(receiptUrl.searchParams.get('ppcart-order'), 'the receipt URL names the order').toMatch(/^\d+$/);
  expect(`${receiptUrl.origin}${receiptUrl.pathname}`, 'the receipt is the product page').toBe(product);
  await expect(frame.locator('body'), 'the receipt renders in the embed shell').toHaveClass(/\bppcart-embed\b/);
  expect(page.url(), 'the host page stays where it was').toBe(HOST_URL);
});
