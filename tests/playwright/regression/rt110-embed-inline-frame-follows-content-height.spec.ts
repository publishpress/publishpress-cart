import { expect, test } from '@playwright/test';

import {
  expectFrameFitsContent,
  expectReceipt,
  frameOf,
  INLINE_FRAME,
  loaderTag,
  openHostPage,
  placeFreeOrder,
  productUrl,
} from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test('RT-110 inline embed renders the checkout in a cross-origin frame that follows its content height', { tag: ['@free', '@regression', '@embed'] }, async ({ page, request }) => {
  test.setTimeout(180_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_free');

  const product = productUrl(config, 'url_embed_product_free');

  await openHostPage(page, `<div data-ppcart-checkout="${product}"></div>${await loaderTag(request, product)}`);

  const iframe = page.locator(INLINE_FRAME);
  await expect(iframe, 'the loader inserts one iframe').toHaveCount(1);
  await expect(iframe, 'the iframe may use the Payment Request API').toHaveAttribute('allow', /\bpayment\b/);
  await expect(iframe).toHaveAttribute('title', 'Checkout');

  const frame = await frameOf(iframe);
  await expect(frame.getByTestId('ppcart-order-submit'), 'the checkout form renders in the frame').toBeVisible();
  expect(new URL(frame.url()).origin, 'the frame is on the WordPress origin, not the host page origin').toBe(new URL(config.host()).origin);
  expect(new URL(page.url()).origin).toBe('https://merchant.example');

  const form = await expectFrameFitsContent(iframe, 'the iframe is as tall as the checkout form', (h) => h.iframe > 480);

  const termsBox = frame.locator('.checkbox-wrap .item-name').first();
  await termsBox.click({ position: { x: 5, y: 8 } });
  await expect(frame.locator('#ppcart_accept_terms'), 'the buyer ticks the terms box').toBeChecked();
  await termsBox.click({ position: { x: 5, y: 8 } });
  await expect(frame.locator('#ppcart_accept_terms'), 'and unticks it').not.toBeChecked();

  await expect(frame.locator('.checkbox-wrap.invalid .error'), 'unticking the required terms box shows a message under it').toBeVisible();
  const grown = await expectFrameFitsContent(iframe, 'the iframe grows with the validation message', (h) => h.iframe > form.iframe);

  await placeFreeOrder(frame, config, 'rt110');
  await expectReceipt(frame);
  await expectFrameFitsContent(iframe, 'the iframe shrinks to the receipt', (h) => h.iframe < grown.iframe);
});
