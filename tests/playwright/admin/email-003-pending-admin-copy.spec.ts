import { expect } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { test } from '../support/admin-test';
import {
  EMAIL_TYPES,
  resolveAdminNotificationRecipient,
  setEmailEnabled,
  triggerFixture,
  uniqueRecipient,
} from '../support/email-settings';
import { Mailpit } from '../support/mailpit';

const config = AdminConfig.fromEnv();
const mailpit = Mailpit.fromEnv();

test.describe.configure({ mode: 'serial' });

test('EM-003 Pending — admin copy', { tag: ["@admin","@email"] }, async ({ page, request }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasEmailFixtureIds(), 'Run composer test:admin:setup');
  test.setTimeout(120_000);

  const descriptor = EMAIL_TYPES.pending;
  const recipient = uniqueRecipient('pending-admin-copy');

  await setEmailEnabled(page, config, 'pending', true, { admin: true });

  const adminRecipient = await resolveAdminNotificationRecipient(page, config);

  await mailpit.deleteAll();

  await triggerFixture(request, config, {
    trigger: 'order_status',
    order_id: config.var('admin_email_order_id'),
    status: descriptor.triggerStatus,
    email: recipient,
  });

  const customerMessage = await mailpit.waitForMessage({ to: recipient });
  const adminMessage = await mailpit.waitForMessage({ to: adminRecipient });

  // The admin copy is the same composed email, just re-addressed.
  expect(adminMessage.Subject).toBe(customerMessage.Subject);
  expect(adminMessage.ID).not.toBe(customerMessage.ID);

  // Leave the admin copy off so later suites are not surprised by a second message.
  await setEmailEnabled(page, config, 'pending', true, { admin: false });
});
