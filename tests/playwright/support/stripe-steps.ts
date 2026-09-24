import { type Page } from '@playwright/test';

import { RegressionConfig } from './regression-config';

const STRIPE_CARD_IFRAME = 'iframe[src*="elements-inner-card"], iframe[src*="elements-inner-payment"]';
const STRIPE_MOCK_ROOT = '[data-testid="ppcart-regression-stripe-mock"]';

export async function fillCard(
  page: Page,
  config: RegressionConfig,
  options?: { cardNumber?: string },
): Promise<void> {
  const cardNumber = options?.cardNumber ?? config.var('stripe_card_number');
  assertStripeTestCard(cardNumber);

  if (config.mockedGateways() || (await page.locator(STRIPE_MOCK_ROOT).count()) > 0) {
    await fillMockCard(page, config, cardNumber);

    return;
  }

  await page.locator(STRIPE_CARD_IFRAME).first().waitFor({ state: 'visible', timeout: 30_000 });
  const frame = page.frameLocator(STRIPE_CARD_IFRAME).first();

  await typeStripeInput(frame, 'cardnumber', cardNumber);
  await typeStripeInput(frame, 'exp-date', RegressionConfig.normalizeCardExpiry(config.var('stripe_cc_exp')));
  await typeStripeInput(frame, 'cvc', config.var('cvc'));
  await fillPostalIfPresent(frame, config.var('postal'));
}

async function fillMockCard(page: Page, config: RegressionConfig, cardNumber: string): Promise<void> {
  const root = page.locator(STRIPE_MOCK_ROOT).first();
  const realIframe = page.locator(STRIPE_CARD_IFRAME).first();

  try {
    await root.waitFor({ state: 'visible', timeout: 30_000 });
  } catch (error) {
    const hasRealStripe = (await realIframe.count()) > 0;

    throw new Error(
      'Expected the local Stripe mock card fields, but they never appeared. '
      + (hasRealStripe
        ? 'WordPress is still serving real Stripe.js. Run `composer test:regression:mocked` (it enables mocks) or `composer test:regression:setup:mocked`.'
        : 'Stripe card UI did not mount. Confirm Stripe is enabled and fixtures are set up.')
      + (error instanceof Error ? ` Original error: ${error.message}` : ''),
    );
  }

  await typeLocalInput(page, 'ppcart-regression-stripe-cardnumber', cardNumber);
  await typeLocalInput(
    page,
    'ppcart-regression-stripe-exp',
    RegressionConfig.normalizeCardExpiry(config.var('stripe_cc_exp')),
  );
  await typeLocalInput(page, 'ppcart-regression-stripe-cvc', config.var('cvc'));

  const postal = page.getByTestId('ppcart-regression-stripe-postal');

  if ((await postal.count()) > 0 && (await postal.isVisible())) {
    await typeLocalInput(page, 'ppcart-regression-stripe-postal', config.var('postal'));
  }
}

async function typeLocalInput(page: Page, testId: string, value: string): Promise<void> {
  const input = page.getByTestId(testId);

  await input.waitFor({ state: 'visible', timeout: 10_000 });
  await input.click();
  await input.fill('');

  for (const char of value) {
    await input.press(char);
  }

  await new Promise((resolve) => setTimeout(resolve, 300));
}

async function typeStripeInput(
  frame: ReturnType<Page['frameLocator']>,
  name: string,
  value: string,
): Promise<void> {
  const input = frame.locator(`input[name="${name}"]`);

  await input.waitFor({ state: 'visible', timeout: 10_000 });
  await input.click();

  for (const char of value) {
    await input.press(char);
  }

  await new Promise((resolve) => setTimeout(resolve, 300));
}

async function fillPostalIfPresent(
  frame: ReturnType<Page['frameLocator']>,
  postal: string,
): Promise<void> {
  const postalInput = frame.locator('input[name="postal"]');

  if (await postalInput.count() === 0) {
    return;
  }

  const hasPostal = await postalInput.evaluate((input) => {
    if (!(input instanceof HTMLInputElement)) {
      return false;
    }

    const style = window.getComputedStyle(input);

    return style.display !== 'none' && style.visibility !== 'hidden' && input.offsetParent !== null;
  }).catch(() => false);

  if (hasPostal) {
    await typeStripeInput(frame, 'postal', postal);
  }
}


function assertStripeTestCard(cardNumber: string): void {
  const digits = cardNumber.replace(/\D/g, '');

  if (/^(4242|400000|555555|378282|601111)/.test(digits)) {
    return;
  }

  throw new Error(
    'STRIPE_CARD_NUMBER_SUCCESS must be a Stripe test card (e.g. 4242424242424242). '
    + 'See https://stripe.com/docs/testing',
  );
}
