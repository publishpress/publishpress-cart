import { expect, test, type Page } from '@playwright/test';

import { loaderTag, openHostPage, POPUP_FRAME, productUrl } from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

async function openPopup(page: Page): Promise<void> {
  await page.getByRole('button', { name: 'Buy now' }).click();
  await expect(page.getByRole('dialog', { name: 'Checkout' }), 'the button opens the checkout dialog').toBeVisible();
  await expect(page.frameLocator(POPUP_FRAME).getByTestId('ppcart-order-submit'), 'the checkout form renders in the popup').toBeVisible();
}

async function expectClosed(page: Page, how: string): Promise<void> {
  await expect(page.getByRole('dialog'), `${how} closes the dialog`).toHaveCount(0);
  await expect(page.locator('iframe'), `${how} removes the popup iframe`).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Buy now' }), `${how} returns focus to the button`).toBeFocused();
  expect(await page.evaluate(() => document.documentElement.style.overflow), `${how} unlocks the page scroll`).toBe('');
}

test('RT-112 popup button opens the checkout in a dialog that closes with Esc or the backdrop', { tag: ['@free', '@regression', '@embed'] }, async ({ page, request }) => {
  test.setTimeout(180_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_free');

  const product = productUrl(config, 'url_embed_product_free');

  await openHostPage(
    page,
    `<p><button type="button" data-ppcart-popup="${product}">Buy now</button></p>`
      + `<div style="height:2000px">Long page</div>${await loaderTag(request, product)}`,
  );

  await openPopup(page);

  const dialog = page.getByRole('dialog', { name: 'Checkout' });
  await expect(dialog).toHaveAttribute('aria-modal', 'true');

  const close = page.getByRole('button', { name: 'Close checkout' });
  await expect(close, 'the close button takes focus when the popup opens').toBeFocused();

  const closeBox = await close.boundingBox();
  const frameBox = await page.locator(POPUP_FRAME).boundingBox();
  expect(closeBox && frameBox && closeBox.y + closeBox.height <= frameBox.y, `the close button sits above the form: ${JSON.stringify({ closeBox, frameBox })}`).toBe(true);
  await expect(page.locator(POPUP_FRAME), 'the popup frame may use the Payment Request API').toHaveAttribute('allow', /\bpayment\b/);
  expect(await page.evaluate(() => document.documentElement.style.overflow), 'the page behind the popup does not scroll').toBe('hidden');

  await page.keyboard.press('Escape');
  await expectClosed(page, 'Esc on the host page');

  await openPopup(page);
  const firstName = page.frameLocator(POPUP_FRAME).getByTestId('ppcart-checkout-field-first-name');
  await firstName.click();
  await expect(firstName, 'focus is in a field inside the frame').toBeFocused();
  await page.keyboard.press('Escape');
  await expectClosed(page, 'Esc inside the frame');

  await openPopup(page);
  await page.mouse.click(5, 5);
  await expectClosed(page, 'a click on the backdrop');
});
