import { expect } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { test } from '../support/admin-test';
import {
  EMAIL_TYPES,
  expectRowState,
  gotoEmailsTab,
  openEmailModal,
  setEmailEnabled,
} from '../support/email-settings';

const config = AdminConfig.fromEnv();

test.describe.configure({ mode: 'serial' });

test('EM-070 Email enable toggles persist across a reload', { tag: ["@admin","@email"] }, async ({ page }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasEmailFixtureIds(), 'Run composer test:admin:setup');
  test.setTimeout(600_000);

  // Both directions matter. The enable flags have no registered default, so a save
  // that dropped the value entirely would still leave a fresh page rendering "off" —
  // which only the on -> reload -> on leg can catch.
  for (const [typeKey, descriptor] of Object.entries(EMAIL_TYPES)) {
    for (const enabled of [true, false]) {
      await setEmailEnabled(page, config, typeKey, enabled);

      // Fresh request, so the next two assertions read what get_option() returns
      // rather than the DOM this test just clicked into shape.
      await gotoEmailsTab(page, config);
      await expectRowState(page, descriptor, enabled);

      // The row dot and the checkbox are rendered from the same option but by
      // different code paths, so check the control itself as well.
      await openEmailModal(page, descriptor.modalKey);

      expect(
        await page.locator(`#${descriptor.optionId}`).isChecked(),
        `${descriptor.title} (${descriptor.optionId}) did not survive the reload as ${enabled}`,
      ).toBe(enabled);
    }
  }
});
