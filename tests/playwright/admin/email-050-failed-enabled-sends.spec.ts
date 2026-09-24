import { expect } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { test } from '../support/admin-test';
import { EMAIL_TYPES, setEmailEnabled, triggerFixture, uniqueRecipient } from '../support/email-settings';
import { Mailpit } from '../support/mailpit';

const config = AdminConfig.fromEnv();
const mailpit = Mailpit.fromEnv();

test.describe.configure({ mode: 'serial' });

test('EM-050 Subscription Renewal Failed — enabled sends', { tag: ["@admin","@email"] }, async ({ page, request }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasEmailFixtureIds(), 'Run composer test:admin:setup');
  test.setTimeout(120_000);

  // Like `renewal`, `failed` builds a PPCart_Order rather than a PPCart_Subscription,
  // so this fires against the order fixture. `_ppcart_email_past_due_*` are aliases of
  // the failed options, not a separate type.
  const descriptor = EMAIL_TYPES.failed;
  const recipient = uniqueRecipient('failed-enabled');

  await setEmailEnabled(page, config, 'failed', true);

  await mailpit.deleteAll();

  await triggerFixture(request, config, {
    trigger: 'order_status',
    order_id: config.var('admin_email_order_id'),
    status: descriptor.triggerStatus,
    email: recipient,
  });

  const message = await mailpit.waitForMessage({ to: recipient });

  expect(message.Subject).not.toBe('');
});
