import { expect } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { test } from '../support/admin-test';
import { EMAIL_TYPES, setEmailEnabled, triggerFixture, uniqueRecipient } from '../support/email-settings';
import { Mailpit } from '../support/mailpit';

const config = AdminConfig.fromEnv();
const mailpit = Mailpit.fromEnv();

test.describe.configure({ mode: 'serial' });

test('EM-060 Subscription Canceled — enabled sends', { tag: ["@admin","@email"] }, async ({ page, request }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasEmailFixtureIds(), 'Run composer test:admin:setup');
  test.setTimeout(120_000);

  // `canceled` is one of the five statuses ppcart_notification_send() builds a
  // PPCart_Subscription for, so this is the one free type driven off the
  // subscription fixture rather than the order.
  const descriptor = EMAIL_TYPES.canceled;
  const recipient = uniqueRecipient('canceled-enabled');

  await setEmailEnabled(page, config, 'canceled', true);

  await mailpit.deleteAll();

  await triggerFixture(request, config, {
    trigger: 'subscription_status',
    subscription_id: config.var('admin_email_subscription_id'),
    status: descriptor.triggerStatus,
    email: recipient,
  });

  const message = await mailpit.waitForMessage({ to: recipient });

  expect(message.Subject).not.toBe('');
});
