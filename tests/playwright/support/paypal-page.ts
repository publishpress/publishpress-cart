import type { Page } from '@playwright/test';

export async function resolvePayPalPage(page: Page): Promise<Page> {
  const deadline = Date.now() + 60_000;

  while (Date.now() < deadline) {
    const paypalPage = findPayPalPage(page);

    if (paypalPage) {
      await paypalPage.waitForLoadState('domcontentloaded').catch(() => undefined);

      return paypalPage;
    }

    await page.waitForTimeout(500);
  }

  const urls = page.context().pages()
    .filter((candidate) => !candidate.isClosed())
    .map((candidate) => candidate.url())
    .join(', ');

  throw new Error(
    'Timed out waiting for PayPal sandbox redirect after clicking Order Now. Still at: '
    + (urls || page.url())
    + '. If this is still the merchant checkout page, sandbox.paypal.com GET requests may be hanging; try `composer test:regression:mocked`.',
  );
}

export async function resolveMerchantPage(page: Page, merchantHost: string): Promise<Page> {
  const deadline = Date.now() + 120_000;
  const host = merchantHost.replace(/\/$/, '');

  while (Date.now() < deadline) {
    const merchantPage = page.context().pages().find((candidate) => {
      if (candidate.isClosed()) {
        return false;
      }

      const url = candidate.url();

      return url.startsWith(host) && !url.includes('paypal.com') && !url.includes('regression-paypal-mock');
    });

    if (merchantPage) {
      return merchantPage;
    }

    if (!page.isClosed()) {
      const url = page.url();

      if (url.startsWith(host) && !url.includes('paypal.com') && !url.includes('regression-paypal-mock')) {
        return page;
      }
    }

    await page.waitForTimeout(1_000);
  }

  throw new Error('Timed out waiting to return to the merchant site after PayPal checkout.');
}

function isPayPalCheckoutUrl(url: string): boolean {
  return url.includes('paypal.com') || url.includes('regression-paypal-mock');
}

function findPayPalPage(page: Page): Page | null {
  for (const candidate of page.context().pages()) {
    if (!candidate.isClosed() && isPayPalCheckoutUrl(candidate.url())) {
      return candidate;
    }
  }

  if (!page.isClosed() && isPayPalCheckoutUrl(page.url())) {
    return page;
  }

  return null;
}
