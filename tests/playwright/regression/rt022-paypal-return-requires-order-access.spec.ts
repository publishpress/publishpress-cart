import { expect, test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  fillCheckoutContact,
  selectPayPal,
} from '../support/checkout-flow-steps';
import { loginAndComplete } from '../support/paypal-steps';
import { resolveMerchantPage, resolvePayPalPage } from '../support/paypal-page';
import { RegressionConfig } from '../support/regression-config';
import { resetPayPalPdtFixture, skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

async function readCheckoutComplete(
  request: import('@playwright/test').APIRequestContext,
  orderId: string,
): Promise<{ fired: number; status: string }> {
  const response = await request.get(
    `${config.host()}/wp-json/ppcart-fixtures/v1/checkout-complete/${orderId}`,
  );
  expect(response.ok()).toBeTruthy();

  return response.json();
}

function decodePayPalReturnUrl(mockPageUrl: string): string {
  const mockUrl = new URL(mockPageUrl);
  const encoded = mockUrl.searchParams.get('return');

  expect(encoded).not.toBeNull();

  return decodeURIComponent(encoded!);
}

test.describe('RT-022 PayPal return requires order access and honours PDT', () => {
  test.beforeEach(async ({ request }) => {
    await resetPayPalPdtFixture(request, config);
  });

  test.afterEach(async ({ request }) => {
    await resetPayPalPdtFixture(request, config);
  });

  test('RT-022 PayPal return requires order access and honours PDT', { tag: ['@paypal', '@regression', '@security'] }, async ({ page, request }) => {
  test.setTimeout(120_000);
  skipUnlessRegressionUrl(config, 'url_one_time_paypal');
  test.skip(!config.mockedGateways(), 'This security regression requires mocked PayPal checkout.');

  await page.goto(config.pagePath('url_one_time_paypal'));
  await fillCheckoutContact(page, config, 'rt022');
  await selectPayPal(page);
  await clickOrderNow(page);

  const paypalPage = await resolvePayPalPage(page);
  const returnUrl = decodePayPalReturnUrl(paypalPage.url());
  const returnParams = new URL(returnUrl);

  expect(returnParams.searchParams.get('ppcart-access')).not.toBeNull();
  expect(returnParams.searchParams.has('token')).toBeFalsy();

  const stripped = new URL(returnUrl);
  stripped.searchParams.delete('ppcart-access');
  await request.get(stripped.toString());

  const orderIdFromReturn = returnParams.searchParams.get('ppcart-order');
  expect(orderIdFromReturn).not.toBeNull();
  const beforeComplete = await readCheckoutComplete(request, orderIdFromReturn!);
  expect(beforeComplete.fired).toBe(0);

  await loginAndComplete(paypalPage, config, false);

  const merchantPage = await resolveMerchantPage(page, config.host());
  await assertOrderReceived(merchantPage);

  const orderId = new URL(merchantPage.url()).searchParams.get('ppcart-order');
  expect(orderId).not.toBeNull();

  const afterPurchase = await readCheckoutComplete(request, orderId!);
  expect(afterPurchase.fired).toBe(1);

  await request.get(returnUrl);
  const afterReplay = await readCheckoutComplete(request, orderId!);
  expect(afterReplay.fired).toBe(1);

  await request.post(`${config.host()}/wp-json/ppcart-fixtures/v1/paypal-pdt-token`, {
    data: { enabled: '1' },
  });

  const pendingPdt = await request.post(`${config.host()}/wp-json/ppcart-fixtures/v1/pending-order`, {
    data: {},
  });
  expect(pendingPdt.ok()).toBeTruthy();
  const pending = await pendingPdt.json();

  const productId = returnParams.searchParams.get('ppcart-pid') ?? '';
  const failUrl = new URL(returnUrl);
  failUrl.searchParams.set('ppcart-order', String(pending.order_id));
  if (productId) {
    failUrl.searchParams.set('ppcart-pid', productId);
  }
  failUrl.searchParams.set('ppcart-access', pending.access);
  failUrl.searchParams.set('tx', 'bogus-tx');

  await request.get(failUrl.toString());
  const pdtFail = await readCheckoutComplete(request, String(pending.order_id));
  expect(pdtFail.fired).toBe(0);
  expect(pdtFail.status).not.toBe('paid');

  const pendingNoTx = await request.post(`${config.host()}/wp-json/ppcart-fixtures/v1/pending-order`, {
    data: {},
  });
  expect(pendingNoTx.ok()).toBeTruthy();
  const pending2 = await pendingNoTx.json();

  const noTxUrl = new URL(returnUrl);
  noTxUrl.searchParams.set('ppcart-order', String(pending2.order_id));
  if (productId) {
    noTxUrl.searchParams.set('ppcart-pid', productId);
  }
  noTxUrl.searchParams.set('ppcart-access', pending2.access);
  noTxUrl.searchParams.delete('tx');

  await request.get(noTxUrl.toString());
  const noTxState = await readCheckoutComplete(request, String(pending2.order_id));
  expect(noTxState.fired).toBe(0);

  await request.post(`${config.host()}/wp-json/ppcart-fixtures/v1/paypal-pdt-token`, {
    data: { enabled: '0' },
  });
  });
});
