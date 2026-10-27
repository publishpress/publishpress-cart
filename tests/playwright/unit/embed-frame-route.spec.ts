import path from 'path';

import { expect, test, type Page } from '@playwright/test';

const FRAME_SCRIPT = path.resolve(process.cwd(), 'public/js/ppcart-embed-frame.js');
const FRAME_ORIGIN = 'https://shop.example';

type Decision = { action: 'frame' | 'top'; url: string } | null;

async function route(page: Page, url: string, origin: string = FRAME_ORIGIN): Promise<Decision> {
  return page.evaluate(
    ([target, frameOrigin]) => (window as unknown as { PPCartEmbedFrame: { route: (u: string, o: string) => Decision } })
      .PPCartEmbedFrame.route(target, frameOrigin),
    [url, origin],
  );
}

test.beforeEach(async ({ page }) => {
  await page.setContent('<p>frame bridge harness</p>');
  await page.addScriptTag({ path: FRAME_SCRIPT });
});

test('route keeps a same-origin URL in the frame and adds the embed flag', async ({ page }) => {
  expect(await route(page, 'https://shop.example/products/foo/')).toEqual({
    action: 'frame',
    url: 'https://shop.example/products/foo/?ppcart-embed=1',
  });
  expect(await route(page, 'https://shop.example/thanks/?ppcart-order=12&token=abc#top')).toEqual({
    action: 'frame',
    url: 'https://shop.example/thanks/?ppcart-order=12&token=abc&ppcart-embed=1#top',
  });
});

test('route leaves an existing embed flag alone', async ({ page }) => {
  expect(await route(page, 'https://shop.example/products/foo/?ppcart-embed=1')).toEqual({
    action: 'frame',
    url: 'https://shop.example/products/foo/?ppcart-embed=1',
  });
  expect(await route(page, 'https://shop.example/products/foo/?ppcart-embed=popup')).toEqual({
    action: 'frame',
    url: 'https://shop.example/products/foo/?ppcart-embed=popup',
  });
});

test('route sends any other origin to the top window unchanged', async ({ page }) => {
  expect(await route(page, 'https://pay.example/session/x?a=1&b=2')).toEqual({
    action: 'top',
    url: 'https://pay.example/session/x?a=1&b=2',
  });
  expect(await route(page, 'http://shop.example/products/foo/')).toEqual({
    action: 'top',
    url: 'http://shop.example/products/foo/',
  });
  expect(await route(page, 'https://shop.example:8443/products/foo/')).toEqual({
    action: 'top',
    url: 'https://shop.example:8443/products/foo/',
  });
});

test('route ignores anything that is not an http(s) URL', async ({ page }) => {
  expect(await route(page, 'javascript:alert(1)')).toBeNull();
  expect(await route(page, 'mailto:buyer@example.com')).toBeNull();
  expect(await route(page, 'not a url')).toBeNull();
  expect(await route(page, '')).toBeNull();
});

test('the bridge stays inert on a page without the embed flag', async ({ page }) => {
  const navigateType = await page.evaluate(() => typeof (window as unknown as { ppcartNavigate?: unknown }).ppcartNavigate);

  expect(navigateType).toBe('undefined');
});
