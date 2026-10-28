import { expect, test } from '@playwright/test';

import { fillCheckoutContact } from '../support/checkout-flow-steps';
import { expectReceipt, frameOf, HOST_URL, INLINE_FRAME, loaderTag, openHostPage, productUrl, submitOrder } from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';
import { fillCard } from '../support/stripe-steps';

const config = RegressionConfig.fromEnv();

test('RT-117 Stripe one-time order inside the inline embed shows the receipt in the frame', { tag: ['@stripe', '@regression', '@embed'] }, async ({ page, request }) => {
  test.setTimeout(300_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_stripe');

  const product = productUrl(config, 'url_embed_product_stripe');

  await openHostPage(page, `<div data-ppcart-checkout="${product}"></div>${await loaderTag(request, product)}`);

  const frame = await frameOf(page.locator(INLINE_FRAME));
  await expect(frame.getByTestId('ppcart-order-submit'), 'the checkout form renders in the frame').toBeVisible();
  await fillCheckoutContact(frame, config, 'rt117');
  await fillCard(frame, config);
  await submitOrder(frame);
  await expectReceipt(frame);

  expect(new URL(frame.url()).searchParams.get('ppcart-embed'), 'the receipt URL keeps the embed flag').toBe('1');
  expect(page.url(), 'the host page stays where it was').toBe(HOST_URL);
});
