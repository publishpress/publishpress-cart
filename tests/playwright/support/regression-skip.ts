import { test, type APIRequestContext } from '@playwright/test';

import { RegressionConfig } from './regression-config';

const SANDBOX_XCLICK = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_xclick';
const SANDBOX_PROBE_MS = 5_000;

let sandboxProbe: Promise<boolean> | undefined;

export function skipUnlessRegressionUrl(config: RegressionConfig, urlKey: string): void {
  if (!config.isUrlConfigured(urlKey)) {
    test.skip(true, `Set ${urlKey.toUpperCase()} in .env (run composer test:regression:setup)`);
  }
}

/**
 * Clear shared-site PDT fixtures left by RT-022 (or a crashed run).
 * Purchase flows must not inherit PDT-required + fail-outcome from another worker.
 */
export async function resetPayPalPdtFixture(
  request: APIRequestContext,
  config: RegressionConfig,
): Promise<void> {
  await request.post(`${config.host()}/wp-json/ppcart-fixtures/v1/paypal-pdt-token`, {
    data: { enabled: '0' },
  });
}

export async function skipUnlessPaypal(
  config: RegressionConfig,
  request: APIRequestContext,
): Promise<void> {
  if (!config.requiresRealPaypal()) {
    return;
  }

  if (!config.hasPaypalCredentials()) {
    test.skip(true, 'Set PAYPAL_USERNAME and PAYPAL_PASSWORD in .env');
  }

  if (!await paypalSandboxResponds(request)) {
    throw new Error(
      'PayPal sandbox did not complete GET ' + SANDBOX_XCLICK + ' within 5s. '
      + 'The browser never reaches PayPal from this network. '
      + 'Use `composer test:regression:mocked` or retry when sandbox.paypal.com loads in Chromium.',
    );
  }
}

function paypalSandboxResponds(request: APIRequestContext): Promise<boolean> {
  if (!sandboxProbe) {
    sandboxProbe = (async () => {
      try {
        const response = await request.get(SANDBOX_XCLICK, {
          timeout: SANDBOX_PROBE_MS,
          failOnStatusCode: false,
        });

        return response.status() > 0;
      } catch {
        return false;
      }
    })();
  }

  return sandboxProbe;
}
