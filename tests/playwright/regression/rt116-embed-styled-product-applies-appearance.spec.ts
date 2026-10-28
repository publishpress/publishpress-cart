import { expect, test } from '@playwright/test';

import { expectReceipt, frameOf, INLINE_FRAME, loaderTag, openHostPage, placeFreeOrder, productUrl } from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();
const TEXT_COLOR = 'rgb(17, 34, 51)';

test('RT-116 saved embed appearance styles the checkout and the receipt inside the frame', { tag: ['@free', '@regression', '@embed'] }, async ({ page, request }) => {
  test.setTimeout(180_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_styled');

  const product = productUrl(config, 'url_embed_product_styled');

  await openHostPage(page, `<div data-ppcart-checkout="${product}"></div>${await loaderTag(request, product)}`);

  const frame = await frameOf(page.locator(INLINE_FRAME));
  const submit = frame.getByTestId('ppcart-order-submit');
  const firstName = frame.getByTestId('ppcart-checkout-field-first-name');

  await expect(submit, 'the accent color is the submit button background').toHaveCSS('background-color', 'rgb(255, 90, 31)');
  await expect(firstName, 'the corner radius rounds the fields').toHaveCSS('border-radius', '12px');
  await expect(frame.locator('body'), 'the background color fills the frame').toHaveCSS('background-color', 'rgb(255, 251, 230)');
  await expect(frame.getByTestId('ppcart-checkout-field-group-first-name').locator('label'), 'the text color is the label color').toHaveCSS('color', TEXT_COLOR);
  await expect(firstName, 'the font family applies to the fields').toHaveCSS('font-family', /^Georgia\b/);

  await placeFreeOrder(frame, config, 'rt116');
  await expectReceipt(frame);
  await expect(frame.getByTestId('ppcart-order-confirmation-message'), 'the receipt uses the text color').toHaveCSS('color', TEXT_COLOR);
});
