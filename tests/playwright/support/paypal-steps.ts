import { type Page } from '@playwright/test';

import { RegressionConfig } from './regression-config';

export async function loginAndComplete(
  page: Page,
  config: RegressionConfig,
  isSubscription = false,
): Promise<void> {
  if (await isPayPalErrorPage(page)) {
    if (await isPayPalCurrencyErrorPage(page)) {
      throw new Error(
        'PayPal rejected the checkout because the Cart currency does not match the sandbox Business account. '
        + 'Brazil (BR) sandbox merchants require REGRESSION_CURRENCY=BRL in .env (then composer test:regression:setup). '
        + 'US sandbox merchants use REGRESSION_CURRENCY=USD.',
      );
    }

    throw new Error(
      'PayPal rejected the checkout (INVALID_BUSINESS_ERROR). '
      + 'Set PAYPAL_SANDBOX_EMAIL to your sandbox Business account email.',
    );
  }

  if (await isMockCheckout(page)) {
    await loginMock(page, config);
    await completeMockPayment(page, isSubscription);

    return;
  }

  assertRealPaypalRequired(config);
  assertBuyerCredentials(config);
  assertSandboxFundingCard(config);

  await loginSandbox(page, config);
  await completeSandboxPayment(page, isSubscription);
}

async function completeSandboxPayment(page: Page, isSubscription: boolean): Promise<void> {
  const paymentSelectors = isSubscription
    ? '#confirmButtonTop, [data-test-id="continueButton"], [data-testid="submit-button-initial"], #payment-submit-btn'
    : '[data-testid="submit-button-initial"], #payment-submit-btn, #confirmButtonTop, [data-test-id="continueButton"]';

  await advanceSandboxCheckout(page, paymentSelectors);
}

async function advanceSandboxCheckout(page: Page, paymentSelectors: string): Promise<void> {
  const deadline = Date.now() + 120_000;

  while (Date.now() < deadline) {
    if (await isBackOnMerchantSite(page)) {
      return;
    }

    await waitForPayPalSpinnerToFinish(page);

    if (await tryReturnToMerchantFromPayPalReceipt(page)) {
      await page.waitForTimeout(2_000);
      continue;
    }

    if (await clickFundingInstrumentContinueButton(page)) {
      await waitForPayPalSpinnerToFinish(page);
      await page.waitForTimeout(1_000);
      continue;
    }

    if (await clickSandboxCheckoutButton(page, paymentSelectors)) {
      await waitForPayPalSpinnerToFinish(page);
      await page.waitForTimeout(1_000);
      continue;
    }

    await page.waitForTimeout(1_000);
  }

  throw new Error(
    'Timed out completing PayPal sandbox checkout. Expected to return to the merchant site after approving payment.',
  );
}

async function isBackOnMerchantSite(page: Page): Promise<boolean> {
  for (const candidate of page.context().pages()) {
    if (candidate.isClosed()) {
      continue;
    }

    const url = candidate.url();

    if (!url.includes('paypal.com') && !url.includes('regression-paypal-mock') && url !== 'about:blank') {
      return true;
    }
  }

  if (page.isClosed()) {
    return false;
  }

  const url = page.url();

  return !url.includes('paypal.com') && !url.includes('regression-paypal-mock');
}

async function clickSandboxCheckoutButton(page: Page, selectorList: string): Promise<boolean> {
  return page.evaluate((selectorsCsv) => {
    const selectors = selectorsCsv.split(',').map((selector) => selector.trim());
    const labels = [
      'Continue',
      'Continuar',
      'Agree & Continue',
      'Agree and Continue',
      'Pay Now',
      'Concluir',
      'Concordar e assinar',
      'Concordar e continuar',
      'Assinar',
      'Pagar agora',
    ];

    function isClickable(el: Element | null): el is HTMLElement {
      if (!(el instanceof HTMLElement) || el.hasAttribute('disabled') || el.getAttribute('data-disabled') === 'true') {
        return false;
      }

      const style = window.getComputedStyle(el);

      return style.display !== 'none' && style.visibility !== 'hidden' && el.offsetParent !== null;
    }

    function clickEl(el: HTMLElement): boolean {
      el.scrollIntoView({ block: 'center' });
      ['mouseover', 'mousedown', 'mouseup', 'click'].forEach((eventName) => {
        el.dispatchEvent(new MouseEvent(eventName, { bubbles: true, cancelable: true, view: window }));
      });

      if (document.activeElement !== el && typeof el.focus === 'function') {
        el.focus();
      }

      return true;
    }

    for (const selector of selectors) {
      const button = document.querySelector(selector);

      if (isClickable(button)) {
        return clickEl(button);
      }
    }

    const candidates = document.querySelectorAll('button, input[type=submit], a.btn, a[role="button"]');

    for (const candidate of candidates) {
      if (!isClickable(candidate)) {
        continue;
      }

      const text = (candidate.textContent || (candidate as HTMLInputElement).value || '').replace(/\s+/g, ' ').trim();

      for (const label of labels) {
        if (text.includes(label)) {
          return clickEl(candidate);
        }
      }
    }

    return false;
  }, selectorList);
}

async function clickFundingInstrumentContinueButton(page: Page): Promise<boolean> {
  const selector = 'button.continueButton[ng-click="continue()"]';
  const isReady = await page.evaluate((buttonSelector) => {
    const button = document.querySelector(buttonSelector);

    if (!(button instanceof HTMLElement) || button.hasAttribute('disabled') || button.getAttribute('data-disabled') === 'true') {
      return false;
    }

    const style = window.getComputedStyle(button);

    return style.display !== 'none' && style.visibility !== 'hidden' && button.offsetParent !== null;
  }, selector);

  if (!isReady) {
    return false;
  }

  try {
    await page.locator(selector).click();

    return true;
  } catch {
    return false;
  }
}

function assertBuyerCredentials(config: RegressionConfig): void {
  const username = config.var('paypal_username');

  if (username.startsWith('AT') || username.startsWith('Ac')) {
    throw new Error('PAYPAL_USERNAME must be a sandbox Personal (buyer) account email, not a REST Client ID.');
  }

  if (username.includes('@business.example.com')) {
    throw new Error(
      'PAYPAL_USERNAME must be a sandbox Personal (buyer) account email (@personal.example.com), '
      + 'not the Business merchant email used for PAYPAL_SANDBOX_EMAIL.',
    );
  }
}

function assertSandboxFundingCard(config: RegressionConfig): void {
  const cardNumber = config.optional('paypal_card_number').trim();

  if (cardNumber === '') {
    return;
  }

  const expiry = config.optional('paypal_cc_exp').trim();

  if (expiry === '') {
    throw new Error(
      'PAYPAL_CC_EXP must be set when PAYPAL_CARD_NUMBER is set. '
      + 'Use the expiry of the card on your PayPal sandbox Personal (buyer) account.',
    );
  }
}

async function loginMock(page: Page, config: RegressionConfig): Promise<void> {
  const username = config.optional('paypal_username').trim() || 'mock-buyer@example.com';
  const password = config.optional('paypal_password').trim() || 'mock-password';

  await page.locator('#email').waitFor({ state: 'visible', timeout: 30_000 });
  await page.locator('#email').fill(username);
  await page.locator('xpath=//button[contains(text(), "Next")]').click();
  await page.locator('#password').waitFor({ state: 'visible', timeout: 30_000 });
  await page.locator('#password').fill(password);
  await page.locator('xpath=//button[contains(text(), "Log In")]').click();
}

async function loginSandbox(page: Page, config: RegressionConfig): Promise<void> {
  await waitForSandboxLoginOrCheckout(page);

  if (await isSandboxCheckoutReady(page)) {
    return;
  }

  if (!(await isSandboxLoginFormReady(page))) {
    throw new Error('Timed out waiting for PayPal sandbox login or checkout page.');
  }

  await fillPaypalLoginEmail(page, config.var('paypal_username'));
  await clickPaypalNext(page);
  await waitForPayPalSpinnerToFinish(page);
  await fillPaypalLoginPassword(page, config.var('paypal_password'));
  await clickPaypalLogin(page);
  await waitForPayPalSpinnerToFinish(page);
}

function paypalEmailField(page: Page) {
  return page.locator('#email').or(page.getByRole('textbox', { name: /email|mobile/i }));
}

function paypalPasswordField(page: Page) {
  return page.locator('#password').or(page.getByRole('textbox', { name: /password/i }));
}

function paypalNextButton(page: Page) {
  return page.locator('#btnNext').or(page.getByRole('button', { name: /^Next$/i }));
}

function paypalLoginButton(page: Page) {
  return page.locator('#btnLogin').or(page.getByRole('button', { name: /log in|entrar/i }));
}

async function fillPaypalLoginEmail(page: Page, email: string): Promise<void> {
  const field = paypalEmailField(page);
  await field.waitFor({ state: 'visible', timeout: 60_000 });
  await field.fill(email);
}

async function fillPaypalLoginPassword(page: Page, password: string): Promise<void> {
  const field = paypalPasswordField(page);
  await field.waitFor({ state: 'visible', timeout: 60_000 });
  await field.fill(password);
}

async function clickPaypalNext(page: Page): Promise<void> {
  await paypalNextButton(page).click();
}

async function clickPaypalLogin(page: Page): Promise<void> {
  await paypalLoginButton(page).click();
}

async function waitForSandboxLoginOrCheckout(page: Page): Promise<void> {
  const deadline = Date.now() + 60_000;

  while (Date.now() < deadline) {
    await waitForPayPalSpinnerToFinish(page);

    if (await isSandboxCheckoutReady(page) || await isSandboxLoginFormReady(page)) {
      return;
    }

    await page.waitForTimeout(1_000);
  }
}

async function isSandboxCheckoutReady(page: Page): Promise<boolean> {
  return page.evaluate(() => {
    function visible(selector: string): boolean {
      const element = document.querySelector(selector);

      if (!(element instanceof HTMLElement)) {
        return false;
      }

      const style = window.getComputedStyle(element);

      return style.display !== 'none' && style.visibility !== 'hidden' && element.offsetParent !== null;
    }

    return visible('[data-testid="submit-button-initial"]')
      || visible('#payment-submit-btn')
      || visible('#confirmButtonTop')
      || visible('[data-test-id="continueButton"]')
      || visible('button.continueButton[ng-click="continue()"]')
      || visible('#subscription-step')
      || visible('[data-testid="header-profile-container"]');
  }).catch(() => false);
}

async function isSandboxLoginFormReady(page: Page): Promise<boolean> {
  if (page.isClosed()) {
    return false;
  }

  const emailVisible = await paypalEmailField(page).isVisible().catch(() => false);
  const nextVisible = await paypalNextButton(page).isVisible().catch(() => false);

  return emailVisible && nextVisible;
}

async function completeMockPayment(page: Page, isSubscription: boolean): Promise<void> {
  if (isSubscription) {
    await page.locator('#subscription-step').waitFor({ state: 'visible', timeout: 30_000 });
    await assertElementContainsText(page, 'h4.noBottom', 'Choose a way to pay');
    await page.locator('xpath=//button[contains(text(), "Continue")]').click();
    await page.locator('[data-test-id="continueButton"]').click();

    return;
  }

  await page.locator('[data-testid="submit-button-initial"]').waitFor({ state: 'visible', timeout: 30_000 });
  await page.locator('[data-testid="submit-button-initial"]').click();
}

async function waitForPayPalSpinnerToFinish(page: Page): Promise<void> {
  const deadline = Date.now() + 60_000;

  while (Date.now() < deadline) {
    if (page.isClosed()) {
      return;
    }

    const busy = await page.evaluate(() => {
      const spinner = document.querySelector('.transitioning.spinnerWithLockIcon');

      if (!(spinner instanceof HTMLElement)) {
        return false;
      }

      if (spinner.classList.contains('hide')) {
        return false;
      }

      const style = window.getComputedStyle(spinner);

      return style.display !== 'none' && style.visibility !== 'hidden' && spinner.offsetParent !== null;
    }).catch(() => false);

    if (!busy) {
      return;
    }

    await page.waitForTimeout(1_000);
  }

  throw new Error('Timed out waiting for PayPal loading spinner to finish.');
}

async function assertElementContainsText(page: Page, selector: string, expected: string): Promise<void> {
  const locator = page.locator(selector);
  await locator.waitFor({ state: 'visible', timeout: 30_000 });
  const message = (await locator.innerText()).trim();

  if (!message.includes(expected)) {
    throw new Error(`Expected ${expected} in ${selector}, got ${message}`);
  }
}

async function tryReturnToMerchantFromPayPalReceipt(page: Page): Promise<boolean> {
  return page.evaluate(() => {
    if (!document.querySelector('#subscriptionsDonePage, #receipt-paid-text, .xo-done, .subscription-container.xo-done')) {
      return false;
    }

    function findReturnUrl(node: unknown): string | null {
      if (!node || typeof node !== 'object') {
        return null;
      }

      if (Array.isArray(node)) {
        for (const item of node) {
          const found = findReturnUrl(item);

          if (found) {
            return found;
          }
        }

        return null;
      }

      const record = node as Record<string, unknown>;

      if (typeof record.return_url === 'string' && record.return_url.includes('ppcart-pp')) {
        return record.return_url;
      }

      for (const value of Object.values(record)) {
        const found = findReturnUrl(value);

        if (found) {
          return found;
        }
      }

      return null;
    }

    const sources = [
      (window as Window & { PRELOADED_DATA?: unknown }).PRELOADED_DATA,
      (window as Window & { __INITIAL_STATE__?: unknown }).__INITIAL_STATE__,
    ];

    for (const source of sources) {
      const returnUrl = findReturnUrl(source);

      if (returnUrl) {
        window.location.href = returnUrl;

        return true;
      }
    }

    return false;
  }).catch(() => false);
}

function assertRealPaypalRequired(config: RegressionConfig): void {
  if (config.mockedGateways() || !config.requiresRealPaypal()) {
    throw new Error(
      'Expected the local PayPal mock page (/regression-paypal-mock/), but the browser reached a different URL. '
      + 'WordPress is likely still in real PayPal mode. '
      + 'Run `composer test:regression:mocked` (enables mocks automatically) or `composer test:regression:setup:mocked`.',
    );
  }
}

async function isMockCheckout(page: Page): Promise<boolean> {
  return page.url().includes('regression-paypal-mock');
}

async function isPayPalErrorPage(page: Page): Promise<boolean> {
  if (page.isClosed()) {
    return false;
  }

  const title = await page.title().catch(() => '');
  const body = await page.locator('body').innerText().catch(() => '');

  return title.includes('Please try again')
    || body.includes("Something doesn't look right")
    || body.includes('Something doesn’t look right')
    || await isPayPalCurrencyErrorPage(page);
}

async function isPayPalCurrencyErrorPage(page: Page): Promise<boolean> {
  const body = await page.locator('body').innerText().catch(() => '');

  return body.includes('does not accept payments in your currency')
    || body.includes('não aceita pagamentos em sua moeda');
}
