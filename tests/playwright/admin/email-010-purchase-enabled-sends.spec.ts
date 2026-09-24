import { expect } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { test } from '../support/admin-test';
import { EMAIL_TYPES, setEmailEnabled, triggerFixture, uniqueRecipient } from '../support/email-settings';
import { Mailpit } from '../support/mailpit';

const config = AdminConfig.fromEnv();
const mailpit = Mailpit.fromEnv();

test.describe.configure({ mode: 'serial' });

test('EM-010 Purchase Confirmation — enabled sends', { tag: ["@admin","@email"] }, async ({ page, request }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasEmailFixtureIds(), 'Run composer test:admin:setup');
  test.setTimeout(120_000);

  // ppcart_notification_send() maps status `paid` onto type `confirmation`; there is
  // no `_ppcart_email_paid_enable`. The descriptor keeps the two apart.
  const descriptor = EMAIL_TYPES.confirmation;
  const recipient = uniqueRecipient('purchase-enabled');

  await setEmailEnabled(page, config, 'confirmation', true);

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
