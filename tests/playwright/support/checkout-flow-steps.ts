import { expect, type Page } from '@playwright/test';

import { RegressionConfig } from './regression-config';

const ORDER_CONFIRMATION = "Thank you. We've received your order.";

export async function fillCheckoutContact(
  page: Page,
  config: RegressionConfig,
  testKey: string,
): Promise<void> {
  await page.getByTestId('ppcart-checkout-field-first-name').fill(config.var('first_name'));
  await page.getByTestId('ppcart-checkout-field-last-name').fill(config.var('last_name'));
  await page.getByTestId('ppcart-checkout-field-email').fill(config.uniqueEmail(testKey));
  await page.getByTestId('ppcart-checkout-field-phone').fill(config.var('phone'));
  await page.getByTestId('ppcart-checkout-field-company').fill(config.var('company'));
}

export async function expectPaymentPlan(page: Page, planNumber: number): Promise<void> {
  await expect(page.getByTestId(`ppcart-payment-plan-${planNumber}`)).toBeVisible();
}

export async function selectPaymentPlan(page: Page, planNumber: number): Promise<void> {
  await expectPaymentPlan(page, planNumber);
  await page.getByTestId(`ppcart-payment-plan-${planNumber}-name`).click();
}

export async function expectSalePaymentPlan(page: Page, planNumber: number): Promise<void> {
  await expectPaymentPlan(page, planNumber);
  await expect(page.getByTestId(`ppcart-payment-plan-${planNumber}-original-price`)).toBeVisible();
  await expect(page.getByTestId(`ppcart-payment-plan-${planNumber}-price`)).toBeVisible();
}

export async function selectPayPal(page: Page): Promise<void> {
  const input = page.getByTestId('ppcart-payment-method-paypal-input');

  await expect(page.getByTestId('ppcart-payment-method-paypal-label')).toBeVisible();

  if (!(await input.isChecked())) {
    await page.getByTestId('ppcart-payment-method-paypal-label').click();
  }

  await expect(input).toBeChecked();
}

export async function clickOrderNow(page: Page): Promise<void> {
  const button = page.getByTestId('ppcart-order-submit');

  await expect(button).toBeVisible();
  await acceptTermsIfPresent(page);
  await waitForOrderNowEnabled(page);
  await expect(button).toBeEnabled();

  const startingUrl = page.url();
  const checkoutOutcome = Promise.race([
    page.waitForEvent('dialog', { timeout: 30_000 })
      .then(async (dialog) => {
        const message = dialog.message();
        await dialog.accept();
        return { type: 'error' as const, message };
      })
      .catch(() => ({ type: 'pending' as const })),
    page.waitForURL((url) => url.toString() !== startingUrl, { timeout: 30_000 })
      .then(() => ({ type: 'navigation' as const }))
      .catch(() => ({ type: 'pending' as const })),
  ]);

  await button.click();
  const outcome = await checkoutOutcome;

  if (outcome.type === 'error') {
    throw new Error(`Checkout failed: ${outcome.message}`);
  }
}

export async function fillCustomPrice(page: Page, amount: string): Promise<void> {
  const selector = '[data-testid="ppcart-custom-price-input"]';
  const input = page.locator(selector);

  await input.waitFor({ state: 'visible', timeout: 30_000 });
  await input.fill(amount);
  await page.evaluate((fieldSelector) => {
    const field = document.querySelector<HTMLInputElement>(fieldSelector);

    if (!field) {
      return;
    }

    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
    field.blur();
  }, selector);
  await page.waitForTimeout(1_000);
  await waitForPositiveCheckoutAmount(page);
}

export async function waitForPositiveCheckoutAmount(page: Page): Promise<void> {
  const deadline = Date.now() + 30_000;

  while (Date.now() < deadline) {
    const amount = await page.evaluate(() => {
      const input = document.querySelector<HTMLInputElement>('input[name=ppcart_amount]');

      return input ? Number.parseFloat(input.value) : 0;
    });

    if (amount > 0) {
      return;
    }

    await page.waitForTimeout(500);
  }

  throw new Error('Timed out waiting for checkout amount. Select a paid plan before clicking Order Now.');
}

export async function selectPaypalPayment(page: Page): Promise<void> {
  await page.locator('.pay-methods').waitFor({ state: 'visible', timeout: 30_000 });
  const paypalMethod = page.locator('.pay-methods #method-paypal');

  if (!(await paypalMethod.isChecked())) {
    await page.locator('.pay-methods label:has(#method-paypal)').click();
  }
}

/** Clicks Order Now repeatedly; only the first click waits for the button to be enabled. */
export async function clickOrderNowRepeated(page: Page, times: number): Promise<void> {
  const count = Math.max(1, times);
  const button = page.locator('#ppcart_card_button');

  await button.waitFor({ state: 'visible', timeout: 30_000 });
  await acceptTermsIfPresent(page);
  await waitForOrderNowEnabled(page);

  if (count === 1) {
    await button.click();
    return;
  }

  await button.click({ clickCount: count, delay: 50 });
}

export async function assertOrderNowSubmitting(page: Page, timeoutMs = 5_000): Promise<void> {
  const button = page.locator('#ppcart_card_button');

  await expect(button).toHaveClass(/running/, { timeout: timeoutMs });
  await expect(button).toBeDisabled();
  await expect(button.click({ trial: true, timeout: 1_000 })).rejects.toThrow();
}

export async function assertOrderNowRecoverableAfterStripeError(page: Page, timeoutMs = 30_000): Promise<void> {
  const button = page.locator('#ppcart_card_button');

  await expect(button).not.toHaveClass(/running/, { timeout: timeoutMs });
  await expect(button).toBeEnabled({ timeout: timeoutMs });
  await expect(page).not.toHaveURL(/[?&]ppcart-order=\d+/);
}

export async function clickOrderNowExpectingStripeDecline(page: Page): Promise<string> {
  const button = page.locator('#ppcart_card_button');

  await button.waitFor({ state: 'visible', timeout: 30_000 });
  await acceptTermsIfPresent(page);
  await waitForOrderNowEnabled(page);

  const dialogPromise = page.waitForEvent('dialog', { timeout: 60_000 });
  await button.click();
  const dialog = await dialogPromise;
  const message = dialog.message();

  await dialog.accept();
  await page.waitForTimeout(500);

  return message;
}

export async function submitStripeCheckoutExpectingDecline(page: Page): Promise<void> {
  const message = await clickOrderNowExpectingStripeDecline(page);

  expect(message.toLowerCase()).toMatch(/declin/);
  await assertOrderNowRecoverableAfterStripeError(page);
}

/** Clicks Order Now even when disabled to surface client-side validation errors. */
export async function clickOrderNowForValidation(page: Page): Promise<void> {
  const button = page.locator('#ppcart_card_button');
  await button.waitFor({ state: 'visible', timeout: 30_000 });

  page.once('dialog', (dialog) => {
    void dialog.accept();
  });

  await page.evaluate(() => {
    const $ = (window as Window & { jQuery?: JQueryStatic }).jQuery;

    if (!$) {
      throw new Error('jQuery is not available on the checkout page.');
    }

    const $button = $('#ppcart_card_button');

    if ($button.length === 0) {
      throw new Error('Order Now button (#ppcart_card_button) was not found.');
    }

    $button.prop('disabled', false).trigger('click');
  });

  await page.waitForTimeout(500);
}

export async function clickTermsCheckbox(page: Page): Promise<void> {
  const label = page.locator('.checkbox-wrap label .item-name').first();
  await label.waitFor({ state: 'visible', timeout: 30_000 });
  await label.click();
}

async function waitForOrderNowEnabled(page: Page, timeoutMs = 30_000): Promise<void> {
  const deadline = Date.now() + timeoutMs;

  while (Date.now() < deadline) {
    const enabled = await page.evaluate(() => {
      const button = document.querySelector<HTMLButtonElement>('#ppcart_card_button');

      if (!button) {
        return true;
      }

      return !button.disabled && !button.classList.contains('running');
    });

    if (enabled) {
      return;
    }

    await page.waitForTimeout(500);
  }

  throw new Error('Timed out waiting for Order Now to become enabled.');
}

export async function assertOrderReceived(page: Page): Promise<void> {
  const raw = process.env.REGRESSION_ORDER_CONFIRMATION_TIMEOUT;
  const timeoutSeconds = !raw || raw.trim() === '' ? 180 : Math.max(180, Number.parseInt(raw, 10));
  const timeout = timeoutSeconds * 1_000;
  const confirmation = page.getByTestId('ppcart-order-confirmation-message');
  const closed = page.getByTestId('ppcart-closed-message');

  if (await closed.isVisible().catch(() => false)) {
    throw new Error(
      'Checkout showed a closed-product message instead of an order confirmation. '
      + 'The payment return likely dropped the order-access token or never finished.',
    );
  }

  await expect.poll(async () => {
    if (await closed.isVisible().catch(() => false)) {
      throw new Error(
        'Checkout showed a closed-product message instead of an order confirmation. '
        + 'The payment return likely dropped the order-access token or never finished.',
      );
    }

    return page.url();
  }, { timeout }).toMatch(/[?&]ppcart-order=\d+/);

  await confirmation.waitFor({ state: 'visible', timeout });
  await expect(confirmation).toBeVisible();
}

export async function assertBodyContains(page: Page, expected: string): Promise<void> {
  const body = page.locator('body');
  await body.waitFor({ state: 'visible', timeout: 30_000 });
  await expect(body).toContainText(expected, { ignoreCase: true });
}

export async function assertElementContainsText(
  page: Page,
  selector: string,
  expected: string,
  timeoutMs = 30_000,
): Promise<void> {
  const locator = page.locator(selector);
  await locator.waitFor({ state: 'visible', timeout: timeoutMs });
  await expect(locator).toContainText(expected, { ignoreCase: true });
}

export async function assertElementNotPresent(page: Page, selector: string): Promise<void> {
  await expect(page.locator(selector)).toHaveCount(0);
}

export async function fillTaxableAddress(
  page: Page,
  address: { line1: string; city: string; postalCode: string; state?: string; country?: string },
): Promise<void> {
  const state = address.state ?? 'CA';
  const country = address.country ?? 'US';

  await page.locator('#address1').waitFor({ state: 'visible', timeout: 30_000 });
  await page.locator('#address1').fill(address.line1);
  await page.locator('#city').fill(address.city);
  await page.locator('#zip').fill(address.postalCode);

  await page.evaluate(
    ({ countryCode, stateCode }) => {
      const $ = (window as Window & { jQuery?: JQueryStatic }).jQuery;

      if (!$) {
        throw new Error('jQuery is not available on the checkout page.');
      }

      const $country = $('#country');

      if ($country.length > 0) {
        if ($country[0]?.selectize) {
          $country[0].selectize.setValue(countryCode);
        } else {
          $country.val(countryCode);
        }
      }

      const $state = $('#state');

      if ($state.length > 0) {
        if ($state[0]?.selectize) {
          $state[0].selectize.setValue(stateCode);
        } else {
          $state.val(stateCode);
        }
      }
    },
    { countryCode: country, stateCode: state },
  );

  await triggerCheckoutAmountUpdate(page);
}

export async function triggerCheckoutAmountUpdate(page: Page): Promise<void> {
  await page.evaluate(async () => {
    const $ = (window as Window & { jQuery?: JQueryStatic }).jQuery;
    const ppcart = (window as Window & { ppcart?: { ajax?: string } }).ppcart;

    if (!$) {
      throw new Error('jQuery is not available on the checkout page.');
    }

    if (!ppcart?.ajax) {
      throw new Error('ppcart.ajax is not available on the checkout page.');
    }

    const wrap = $('.ppcart-form-wrap').first();
    const wrapId = wrap.attr('id');

    if (!wrapId) {
      throw new Error('Checkout form wrapper was not found.');
    }

    const form = document.getElementById(wrapId)?.getElementsByTagName('form')[0];

    if (!form) {
      throw new Error('Checkout form was not found.');
    }

    const paramObj = new FormData(form);
    paramObj.set('action', 'ppcart_update_cart_amount');

    const readFieldValue = (name: string): string => {
      const $field = $(`[name="${name}"]`, `#${wrapId}`);

      if ($field.length === 0) {
        return '';
      }

      const selectize = ($field[0] as { selectize?: { getValue: () => string } }).selectize;

      if (selectize) {
        return selectize.getValue() ?? '';
      }

      return String($field.val() ?? '');
    };

    for (const field of ['country', 'state', 'city', 'zip', 'address1', 'address2'] as const) {
      const value = readFieldValue(field);

      if (value !== '') {
        paramObj.set(field, value);
      }
    }

    const response = await fetch(ppcart.ajax, {
      method: 'POST',
      body: paramObj,
    });

    if (!response.ok) {
      throw new Error('Checkout amount update request failed.');
    }

    const resp = await response.json() as {
      total?: number;
      order_summary_items?: Array<{ name: string; subtotal: string; type: string; quantity?: number }>;
      total_html?: string;
      empty?: string;
    };

    const container = wrap.closest('#ppcart-form-container').find('.summary-items');
    const totalContainer = wrap.closest('#ppcart-form-container').find('.total');

    container.empty();
    totalContainer.empty();
    $(`input[name="ppcart_amount"]`, `#${wrapId}`).val(resp.total ?? 0);

    if (!resp.order_summary_items || resp.order_summary_items.length === 0) {
      container.append(`<div class="item"><span class="ppcart-label">${resp.empty ?? ''}</span></div>`);
      return;
    }

    for (const item of resp.order_summary_items) {
      let name = item.name;

      if (Number(item.quantity) > 1) {
        name += ` x ${item.quantity}`;
      }

      container.append(
        `<div class="item ${item.type}"><span class="ppcart-label">${name}</span><span class="price">${item.subtotal}</span></div>`,
      );
    }

    if (resp.total_html) {
      totalContainer.append(resp.total_html);
    }
  });
}

export async function assertManualTaxApplied(page: Page, expectedTotal = 10.85): Promise<void> {
  const totalLocator = page.locator('.total .price, .total-rhs .price').first();

  await totalLocator.waitFor({ state: 'visible', timeout: 30_000 });

  const amount = await page.evaluate(() => {
    const input = document.querySelector<HTMLInputElement>('input[name=ppcart_amount]');

    return input ? Number.parseFloat(input.value) : 0;
  });

  expect(amount).toBeCloseTo(expectedTotal, 2);
  await expect(totalLocator).toContainText(String(expectedTotal));
}

export async function acceptTermsIfPresent(page: Page): Promise<void> {
  const checkbox = page.locator('#ppcart_accept_terms');

  if (await checkbox.count() === 0 || !(await checkbox.isVisible().catch(() => false))) {
    return;
  }

  if (await checkbox.isChecked().catch(() => false)) {
    return;
  }

  await page.evaluate(() => {
    const checkbox = document.querySelector<HTMLInputElement>('#ppcart_accept_terms');

    if (!checkbox) {
      return;
    }

    checkbox.checked = true;
    checkbox.dispatchEvent(new Event('input', { bubbles: true }));
    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
  });
}
