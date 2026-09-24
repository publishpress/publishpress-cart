import { expect } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { test } from '../support/admin-test';
import { EMAIL_TYPES, setEmailEnabled, triggerFixture, uniqueRecipient } from '../support/email-settings';
import { Mailpit } from '../support/mailpit';

const config = AdminConfig.fromEnv();
const mailpit = Mailpit.fromEnv();

// Every settings tab shares one <form> posting to options.php, so a parallel save
// from another spec would rewrite the option under test mid-run.
test.describe.configure({ mode: 'serial' });

test('EM-001 Pending — enabled sends', { tag: ["@admin","@email"] }, async ({ page, request }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasEmailFixtureIds(), 'Run composer test:admin:setup');
  test.setTimeout(120_000);

  const descriptor = EMAIL_TYPES.pending;
  const recipient = uniqueRecipient('pending-enabled');

  // Toggling through the real UI is the point: it proves the settings page, not
  // just the option, controls delivery.
  await setEmailEnabled(page, config, 'pending', true);

  // Emptied only after the save persisted, so the mailbox holds nothing but the
  // mail this trigger produces.
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
