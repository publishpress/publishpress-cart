import { expect, test } from '@playwright/test';

import { expectFrameFitsContent, frameHeights, frameOf, loaderTag, openHostPage, productUrl } from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test.use({ viewport: { width: 1280, height: 2000 } });

test('RT-119 two inline embeds on one page render and size independently', { tag: ['@free', '@regression', '@embed'] }, async ({ page, request }) => {
  test.setTimeout(180_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_free');
  skipUnlessRegressionUrl(config, 'url_embed_product_stripe');

  const free = productUrl(config, 'url_embed_product_free');
  const stripe = productUrl(config, 'url_embed_product_stripe');

  await openHostPage(
    page,
    `<div id="free" data-ppcart-checkout="${free}"></div><div id="stripe" data-ppcart-checkout="${stripe}"></div>${await loaderTag(request, free)}`,
  );

  const freeIframe = page.locator('#free iframe');
  const stripeIframe = page.locator('#stripe iframe');
  const freeFrame = await frameOf(freeIframe);
  const stripeFrame = await frameOf(stripeIframe);

  await expect(freeFrame.getByText('Free access'), 'the first embed shows the free product').toBeVisible();
  await expect(stripeFrame.getByText('One payment of $10'), 'the second embed shows the Stripe product').toBeVisible();

  const freeForm = await expectFrameFitsContent(freeIframe, 'the first iframe fits its form', (h) => h.iframe > 480);
  const stripeForm = await expectFrameFitsContent(stripeIframe, 'the second iframe fits its form', (h) => h.iframe > 480);

  const termsBox = freeFrame.locator('.checkbox-wrap .item-name').first();
  await termsBox.click({ position: { x: 5, y: 8 } });
  await termsBox.click({ position: { x: 5, y: 8 } });
  await expect(freeFrame.locator('.checkbox-wrap.invalid .error'), 'the first form shows a validation message').toBeVisible();

  await expectFrameFitsContent(freeIframe, 'the first iframe grows with its message', (h) => h.iframe > freeForm.iframe);
  expect((await frameHeights(stripeIframe)).iframe, 'the second iframe keeps its height').toBe(stripeForm.iframe);
});
