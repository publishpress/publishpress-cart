import { expect, type Page } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { test } from '../support/admin-test';
import {
  EMAIL_TYPES,
  expectRowState,
  gotoEmailsTab,
  openEmailModal,
  saveEmailSettings,
  setCheckbox,
  triggerFixture,
  uniqueRecipient,
} from '../support/email-settings';
import { Mailpit } from '../support/mailpit';

const config = AdminConfig.fromEnv();
const mailpit = Mailpit.fromEnv();

/** When on, core's wp_new_user_notification() sends and the plugin template never runs. */
const WP_NOTIFICATION_OPTION_ID = '_ppcart_use_wp_notification';

test.describe.configure({ mode: 'serial' });

/**
 * Registration is the one free type `setEmailEnabled()` cannot drive on its own.
 *
 * `ppcart_create_user()` consults `_ppcart_email_registration_enable` and then hands off
 * to core's wp_new_user_notification() whenever `_ppcart_use_wp_notification` is truthy,
 * so both boxes have to be set in the same modal before the single shared save.
 */
async function setRegistrationEnabled(page: Page, enabled: boolean): Promise<void> {
  const descriptor = EMAIL_TYPES.registration;

  await gotoEmailsTab(page, config);
  await openEmailModal(page, descriptor.modalKey);
  await setCheckbox(page, descriptor.optionId, enabled);
  await setCheckbox(page, WP_NOTIFICATION_OPTION_ID, false);
  await saveEmailSettings(page, descriptor.modalKey);
  await expectRowState(page, descriptor, enabled);
}

test('EM-020 New User Welcome — enabled sends', { tag: ["@admin","@email"] }, async ({ page, request }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasEmailFixtureIds(), 'Run composer test:admin:setup');
  test.setTimeout(120_000);

  // This type never reaches ppcart_notification_send(); the trigger is
  // ppcart_create_user() -> ppcart_new_user_notification(), hence `create_user`.
  const recipient = uniqueRecipient('registration-enabled');

  await setRegistrationEnabled(page, true);

  await mailpit.deleteAll();

  // Each run creates a real WordPress user, so the address must be unique or
  // ppcart_create_user() takes the "user already exists" branch and sends nothing.
  await triggerFixture(request, config, {
    trigger: 'create_user',
    order_id: config.var('admin_email_order_id'),
    email: recipient,
  });

  const message = await mailpit.waitForMessage({ to: recipient });

  expect(message.Subject).not.toBe('');
});
