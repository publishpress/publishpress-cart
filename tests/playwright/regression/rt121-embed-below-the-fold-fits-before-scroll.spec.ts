import { expect, test } from '@playwright/test';

import { expectFrameFitsContent, frameOf, loaderTag, openHostPage, productUrl } from '../support/embed-host';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test.use({ viewport: { width: 1280, height: 720 } });

test('RT-121 an inline embed below the fold fits its content before it is scrolled into view', { tag: ['@free', '@regression', '@embed'] }, async ({ page, request }) => {
  test.setTimeout(120_000);
  skipUnlessRegressionUrl(config, 'url_embed_product_free');

  const free = productUrl(config, 'url_embed_product_free');

  await openHostPage(
    page,
    `<div style="height:1600px">Sales copy above the checkout</div><div id="below" data-ppcart-checkout="${free}"></div>${await loaderTag(request, free)}`,
  );

  const iframe = page.locator('#below iframe');
  const frame = await frameOf(iframe);

  await expect(frame.getByText('Free access'), 'the embed below the fold loads its form').toBeVisible();

  const offScreen = await iframe.evaluate((node) => node.getBoundingClientRect().top > window.innerHeight);
  expect(offScreen, 'the embed is still below the fold').toBe(true);

  await expectFrameFitsContent(iframe, 'the off-screen iframe already fits its form', (h) => h.iframe > 480);
  expect(await page.evaluate(() => window.scrollY), 'the page was never scrolled').toBe(0);
});
