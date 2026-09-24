import { AdminConfig } from '../support/admin-config';
import { test } from '../support/admin-test';
import { EMAIL_TYPES, setEmailEnabled, triggerFixture, uniqueRecipient } from '../support/email-settings';
import { Mailpit } from '../support/mailpit';

const config = AdminConfig.fromEnv();
const mailpit = Mailpit.fromEnv();

test.describe.configure({ mode: 'serial' });

test('EM-061 Subscription Canceled — disabled does not send', { tag: ["@admin","@email"] }, async ({ page, request }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasEmailFixtureIds(), 'Run composer test:admin:setup');
  test.setTimeout(120_000);

  const descriptor = EMAIL_TYPES.canceled;
  const recipient = uniqueRecipient('canceled-disabled');

  await setEmailEnabled(page, config, 'canceled', false);

  await mailpit.deleteAll();

  await triggerFixture(request, config, {
    trigger: 'subscription_status',
    subscription_id: config.var('admin_email_subscription_id'),
    status: descriptor.triggerStatus,
    email: recipient,
  });

  await mailpit.expectNoMessage({ to: recipient });
});
