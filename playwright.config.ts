import { existsSync } from 'fs';
import { cpus } from 'os';

import { defineConfig, devices } from '@playwright/test';

import './tests/playwright/support/nerd-console-glyphs.cjs';
import { loadDotEnv } from './tests/playwright/support/load-dotenv';
import { ADMIN_AUTH_STATE } from './tests/playwright/support/paths';

loadDotEnv();

const baseURL = (
  process.env.PUBLISHPRESS_CART_HOST ??
  process.env.WP_TESTS_URL ??
  'http://tests.local'
).replace(/\/$/, '');
const chromiumExecutableCandidates = [
  process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH,
  '/usr/bin/chromium',
  '/usr/bin/chromium-browser',
].filter(Boolean) as string[];
const chromiumExecutablePath = chromiumExecutableCandidates.find((path) => existsSync(path));
const chromiumArgs = process.env.PLAYWRIGHT_MAP_LOCALHOST_TO_HOST_DOCKER_INTERNAL === '1'
  ? ['--host-resolver-rules=MAP localhost host.docker.internal']
  : [];
const chromiumLaunchOptions = {
  ...(chromiumExecutablePath ? { executablePath: chromiumExecutablePath } : {}),
  ...(chromiumArgs.length > 0 ? { args: chromiumArgs } : {}),
};
const mockedGatewaysEnabled = ['1', 'true', 'yes', 'on'].includes(
  (process.env.REGRESSION_MOCKED_GATEWAYS ?? '0').toLowerCase(),
);
const mockedGatewayHeaders = mockedGatewaysEnabled
  ? { extraHTTPHeaders: { 'X-PPCART-Regression-Mocked': '1' } }
  : {};

const hasChromiumLaunchOptions = Object.keys(chromiumLaunchOptions).length > 0;
const chromiumUse = {
  ...devices['Desktop Chrome'],
  ...(hasChromiumLaunchOptions ? { launchOptions: chromiumLaunchOptions } : {}),
  ...mockedGatewayHeaders,
};

function resolveWorkers(): number {
  const raw = process.env.PW_WORKERS;

  if (raw !== undefined && raw !== '') {
    const parsed = Number.parseInt(raw, 10);

    if (Number.isFinite(parsed) && parsed > 0) {
      return parsed;
    }
  }

  const cpuCount = Math.max(1, cpus().length);

  // Checkout flows are independent (unique emails per test) and mostly wait on
  // network/payment sandboxes, so run more than Playwright's CPU/2 default.
  // Keep CI modest for shared PayPal sandbox rate limits.
  if (process.env.CI) {
    return Math.min(2, cpuCount);
  }

  return Math.min(4, Math.max(2, Math.ceil(cpuCount / 2)));
}

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: './tests/playwright',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: resolveWorkers(),
  reporter: process.env.CI
    ? [['list'], ['html', { open: 'never' }]]
    : [['list'], ['html', { open: 'on-failure' }]],
  timeout: 60_000,
  expect: {
    timeout: 30_000,
  },
  use: {
    baseURL,
    ignoreHTTPSErrors: true,
    headless: process.env.PW_HEADED === '1' ? false : true,
    trace: process.env.CI ? 'on-first-retry' : (process.env.PW_TRACE === '1' ? 'on' : 'off'),
    screenshot: process.env.PW_SCREENSHOT === 'on' ? 'on' : 'only-on-failure',
    video: process.env.PW_VIDEO === '1' ? 'on' : 'off',
    viewport: { width: 1366, height: 900 },
    ...mockedGatewayHeaders,
  },
  projects: [
    {
      name: 'admin-setup',
      testMatch: /setup\/admin-auth\.setup\.ts/,
      use: chromiumUse,
    },
    {
      name: 'regression-chromium',
      testMatch: /regression\/.*\.spec\.ts/,
      use: chromiumUse,
    },
    {
      name: 'harness-chromium',
      testMatch: /unit\/.*\.spec\.ts/,
      use: chromiumUse,
    },
    {
      name: 'admin-chromium',
      testMatch: /admin\/.*\.spec\.ts/,
      dependencies: ['admin-setup'],
      use: {
        ...chromiumUse,
        storageState: ADMIN_AUTH_STATE,
      },
    },
  ],
});
