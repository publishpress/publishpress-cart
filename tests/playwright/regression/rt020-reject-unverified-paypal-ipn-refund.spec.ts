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
import { skipUnlessRegressionUrl } from '../support/regression-skip';

const config = RegressionConfig.fromEnv();

test('RT-020 rejects an unverified PayPal IPN refund', { tag: ['@paypal', '@regression'] }, async ({ page, request }) => {
  test.setTimeout(90_000);
  skipUnlessRegressionUrl(config, 'url_one_time_paypal');
  test.skip(!config.mockedGateways(), 'This security regression requires the local mocked PayPal IPN response.');

  await page.goto(config.pagePath('url_one_time_paypal'));
  await fillCheckoutContact(page, config, 'rt020');
  await selectPayPal(page);
  await clickOrderNow(page);

  const paypalPage = await resolvePayPalPage(page);
  await loginAndComplete(paypalPage, config, false);

  const merchantPage = await resolveMerchantPage(page, config.host());
  await assertOrderReceived(merchantPage);

  const orderId = new URL(merchantPage.url()).searchParams.get('ppcart-order');
  expect(orderId).not.toBeNull();

  const transactionId = `rt020-parent-${orderId}-${Date.now()}`;
  const refundId = `rt020-refund-${orderId}-${Date.now()}`;
  const fixtureUrl = `${config.host()}/wp-json/ppcart-fixtures/v1/paypal-ipn-order/${orderId}`;

  const prepare = await request.post(fixtureUrl, { data: { transaction_id: transactionId } });
  expect(prepare.ok()).toBeTruthy();

  const ipn = await request.post(`${config.host()}/ppcart-webhook/paypal`, {
    form: {
      payer_email: 'buyer@example.test',
      custom: JSON.stringify({ order_id: Number(orderId) }),
      txn_id: refundId,
      parent_txn_id: transactionId,
      payment_status: 'Refunded',
      payment_gross: '-10.00',
    },
  });
  expect(ipn.ok()).toBeTruthy();

  const result = await request.get(fixtureUrl);
  expect(result.ok()).toBeTruthy();
  await expect(result.json()).resolves.toEqual({ refund_log: [] });
});
