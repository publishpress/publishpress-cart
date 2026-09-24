import { type Page } from '@playwright/test';

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
 * Off here has to mean "the plugin decided not to send", not "WordPress sent it
 * instead", so `_ppcart_use_wp_notification` is forced off alongside the gate. Left on,
 * a mail would still arrive and the spec would fail for the wrong reason.
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

test('EM-021 New User Welcome — disabled does not send', { tag: ["@admin","@email"] }, async ({ page, request }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasEmailFixtureIds(), 'Run composer test:admin:setup');
  test.setTimeout(120_000);

  const recipient = uniqueRecipient('registration-disabled');

  // The "off" state is asserted as persisted before the trigger fires; a silently
  // failed save would otherwise make the no-send result a false pass.
  await setRegistrationEnabled(page, false);

  await mailpit.deleteAll();

  // triggerFixture throws unless the endpoint reports success, and the endpoint
  // reports whether the user was created or already existed, so "nothing arrived"
  // cannot be explained away by the trigger never having run.
  await triggerFixture(request, config, {
    trigger: 'create_user',
    order_id: config.var('admin_email_order_id'),
    email: recipient,
  });

  await mailpit.expectNoMessage({ to: recipient });
});
