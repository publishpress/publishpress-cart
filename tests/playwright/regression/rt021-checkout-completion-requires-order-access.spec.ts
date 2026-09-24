import { expect, test } from '@playwright/test';

import {
  assertOrderReceived,
  clickOrderNow,
  fillCheckoutContact,
} from '../support/checkout-flow-steps';
import { RegressionConfig } from '../support/regression-config';
import { skipUnlessRegressionUrl } from '../support/regression-skip';
import { fillCard } from '../support/stripe-steps';

const config = RegressionConfig.fromEnv();

function parseFormBody(body: string): Record<string, string> {
  const form: Record<string, string> = {};
  const params = new URLSearchParams(body);

  params.forEach((value, key) => {
    form[key] = value;
  });

  return form;
}



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

async function scrapeCheckoutNonce(page: import('@playwright/test').Page): Promise<string> {
  const nonce = page.locator('input[name="ppcart-nonce"]');
  await expect(nonce).toHaveCount(1);

  return nonce.inputValue();
}

async function checkoutFormAction(page: import('@playwright/test').Page): Promise<string> {
  const form = page.getByTestId('ppcart-checkout-form');
  const action = await form.getAttribute('action');

  return action && action.length > 0 ? action : `${config.host()}${config.pagePath('url_one_time_stripe')}`;
}

test('RT-021 checkout completion requires order access', { tag: ['@stripe', '@regression', '@security'] }, async ({ page, request }) => {
  test.setTimeout(300_000);
  skipUnlessRegressionUrl(config, 'url_one_time_stripe');
  test.skip(!config.mockedGateways(), 'This security regression requires mocked Stripe checkout.');

  await page.goto(config.pagePath('url_one_time_stripe'));
  await fillCheckoutContact(page, config, 'rt021');
  await fillCard(page, config);

  const completionRequest = page.waitForRequest(
    (req) => req.method() === 'POST' && (req.postData() ?? '').includes('ppcart_process_payment'),
    { timeout: 120_000 },
  );

  await clickOrderNow(page);
  const captured = await completionRequest;
  await assertOrderReceived(page);

  const orderId = new URL(page.url()).searchParams.get('ppcart-order');
  expect(orderId).not.toBeNull();

  const afterPurchase = await readCheckoutComplete(request, orderId!);
  expect(afterPurchase.fired).toBe(1);

  const postUrl = captured.url();
  const replay = await request.post(postUrl, { form: parseFormBody(captured.postData() ?? '') });
  expect(replay.ok()).toBeTruthy();
  const afterReplay = await readCheckoutComplete(request, orderId!);
  expect(afterReplay.fired).toBe(1);

  const pendingResponse = await request.post(`${config.host()}/wp-json/ppcart-fixtures/v1/pending-order`, {
    data: {},
  });
  expect(pendingResponse.ok()).toBeTruthy();
  const pending = await pendingResponse.json();
  expect(pending.order_id).toBeGreaterThan(0);

  await page.goto(config.pagePath('url_one_time_stripe'));
  const nonce = await scrapeCheckoutNonce(page);
  const formAction = await checkoutFormAction(page);

  const attackWithoutAccess = await request.post(formAction, {
    form: {
      ppcart_process_payment: '1',
      ppcart_order_id: String(pending.order_id),
      'ppcart-nonce': nonce,
    },
  });
  expect(attackWithoutAccess.ok()).toBeTruthy();
  const blocked = await readCheckoutComplete(request, String(pending.order_id));
  expect(blocked.fired).toBe(0);

  const legit = await request.post(formAction, {
    form: {
      ppcart_process_payment: '1',
      ppcart_order_id: String(pending.order_id),
      'ppcart-nonce': nonce,
      'ppcart-access': pending.access,
    },
  });
  expect(legit.ok()).toBeTruthy();
  const allowed = await readCheckoutComplete(request, String(pending.order_id));
  expect(allowed.fired).toBe(1);

  const pendingStep = await request.post(`${config.host()}/wp-json/ppcart-fixtures/v1/pending-order`, {
    data: {},
  });
  expect(pendingStep.ok()).toBeTruthy();
  const pending2 = await pendingStep.json();

  const stepUrl = new URL(formAction);
  stepUrl.searchParams.set('ppcart-order', String(pending2.order_id));
  stepUrl.searchParams.set('step', '2');
  stepUrl.searchParams.set('ppcart-access', pending2.access);

  await request.get(stepUrl.toString());
  const afterFirstStep = await readCheckoutComplete(request, String(pending2.order_id));
  expect(afterFirstStep.fired).toBe(1);

  await request.get(stepUrl.toString());
  const afterSecondStep = await readCheckoutComplete(request, String(pending2.order_id));
  expect(afterSecondStep.fired).toBe(1);
});
