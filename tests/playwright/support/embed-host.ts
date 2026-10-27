import { expect, type APIRequestContext, type Frame, type Locator, type Page } from '@playwright/test';

import { acceptTermsIfPresent, fillCheckoutContact } from './checkout-flow-steps';
import { RegressionConfig } from './regression-config';

export const HOST_ORIGIN = 'https://merchant.example';
export const HOST_URL = `${HOST_ORIGIN}/shop/`;
export const INLINE_FRAME = '[data-ppcart-checkout] iframe';
export const POPUP_FRAME = '.ppcart-embed-dialog iframe';

/** The product permalink a merchant pastes into a snippet. */
export function productUrl(config: RegressionConfig, urlKey: string): string {
  return `${config.host()}${config.pagePath(urlKey)}`;
}

export function withEmbedFlag(url: string, value = '1'): string {
  const parsed = new URL(url);

  parsed.searchParams.set('ppcart-embed', value);

  return parsed.toString();
}

/**
 * The loader `<script>` tag. Its URL comes from the frame script the embed shell
 * prints, so the spec does not depend on the plugin folder name.
 */
export async function loaderTag(request: APIRequestContext, product: string): Promise<string> {
  const html = await (await request.get(withEmbedFlag(product))).text();
  const match = /["'](https?:\/\/[^"']+?)\/public\/js\/ppcart-embed-frame\.js/.exec(html);

  if (!match) {
    throw new Error(`The embed shell of ${product} loads no ppcart-embed-frame.js, so the loader URL is unknown.`);
  }

  return `<script src="${match[1]}/public/js/ppcart-embed.js" async></script>`;
}

/** Serves `snippet` as the body of a page on the merchant origin and opens it. */
export async function openHostPage(page: Page, snippet: string, url = HOST_URL): Promise<void> {
  const body = '<!doctype html><html><head><meta charset="utf-8">'
    + '<meta name="viewport" content="width=device-width, initial-scale=1"><title>Merchant</title></head>'
    + `<body style="margin:0;padding:16px;font-family:sans-serif"><h1>Merchant page</h1>${snippet}</body></html>`;

  await page.route(`${HOST_ORIGIN}/**`, (route) => route.fulfill({ status: 200, contentType: 'text/html', body }));
  await page.goto(url);
}

/** The Frame inside an iframe element; it stays the same object across navigations. */
export async function frameOf(iframe: Locator): Promise<Frame> {
  const frame = await (await iframe.elementHandle())?.contentFrame();

  if (!frame) {
    throw new Error('The iframe has no content frame.');
  }

  return frame;
}

export type FrameHeights = { iframe: number; content: number };

/** Height of the iframe element on the host page, and of the document inside it. */
export async function frameHeights(iframe: Locator): Promise<FrameHeights> {
  const element = await iframe.evaluate((node) => node.getBoundingClientRect().height);
  const content = await iframe.contentFrame().locator('html').evaluate((node) => node.getBoundingClientRect().height);

  return { iframe: element, content };
}

/**
 * Waits until two samples in a row agree, the iframe is as tall as its document
 * (within 4px), and `accept` holds, then returns the heights. The iframe must be
 * on screen: Chrome pauses rendering of an off-screen cross-origin frame, so its
 * height catches up only when it scrolls into view.
 */
export async function expectFrameFitsContent(
  iframe: Locator,
  message: string,
  accept: (heights: FrameHeights) => boolean = () => true,
): Promise<FrameHeights> {
  let heights: FrameHeights = { iframe: 0, content: 0 };
  let previous = '';

  await expect.poll(async () => {
    heights = await frameHeights(iframe);
    const sample = JSON.stringify(heights);
    const settled = sample === previous;

    previous = sample;

    return settled && Math.abs(heights.iframe - heights.content) <= 4 && accept(heights) ? 'fits' : sample;
  }, { message }).toBe('fits');

  return heights;
}

export async function placeFreeOrder(frame: Frame, config: RegressionConfig, testKey: string): Promise<void> {
  const button = frame.getByTestId('ppcart-order-submit');

  await expect(button, 'the checkout form renders in the frame').toBeVisible();
  await fillCheckoutContact(frame, config, testKey);
  await submitOrder(frame);
}

export async function submitOrder(frame: Frame): Promise<void> {
  const button = frame.getByTestId('ppcart-order-submit');

  await acceptTermsIfPresent(frame);
  await expect(button, 'Order Now is enabled once the form is complete').toBeEnabled();
  await expect(button).not.toHaveClass(/running/);
  await button.click();
}

export async function expectReceipt(frame: Frame): Promise<void> {
  await frame.waitForURL(/[?&]ppcart-order=\d+/, { timeout: 60_000 });
  await expect(frame.getByTestId('ppcart-order-confirmation-message'), 'the receipt shows in the frame').toBeVisible();
}
