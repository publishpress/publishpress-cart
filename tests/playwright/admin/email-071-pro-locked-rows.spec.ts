import { expect } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { test } from '../support/admin-test';
import {
  EMAIL_TYPES,
  FUNCTIONAL_ROW_SELECTOR,
  LOCKED_ROW_SELECTOR,
  PRO_LOCKED_EMAIL_TITLES,
  gotoEmailsTab,
  openEmailModal,
} from '../support/email-settings';

const config = AdminConfig.fromEnv();

test.describe.configure({ mode: 'serial' });

test('EM-071 Pro-locked email rows have no working Enable control', { tag: ["@admin","@email"] }, async ({ page }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasEmailFixtureIds(), 'Run composer test:admin:setup');
  test.setTimeout(120_000);

  await gotoEmailsTab(page, config);

  // Four Pro rows from ppcart_pro_locked_emails(), seven free ones from the email
  // template sections. Exact counts, so a Pro row leaking a working control — or a
  // free row losing one — is caught here rather than in a send/no-send spec.
  await expect(page.locator(LOCKED_ROW_SELECTOR)).toHaveCount(4);
  await expect(page.locator(FUNCTIONAL_ROW_SELECTOR)).toHaveCount(7);
  expect(PRO_LOCKED_EMAIL_TITLES).toHaveLength(4);
  expect(Object.keys(EMAIL_TYPES)).toHaveLength(7);

  for (const title of PRO_LOCKED_EMAIL_TITLES) {
    const row = page.locator(
      `${LOCKED_ROW_SELECTOR}:has(.ppcart-settings__email-title:text-is("${title}"))`,
    );

    await expect(row, `${title} should render as a locked row`).toHaveCount(1);

    // An upgrade link stands where a free row carries its Manage button.
    const lock = row.locator('a.ppcart-settings__pro-lock');

    await expect(lock).toHaveCount(1);
    await expect(lock).toHaveAttribute('href', /publishpress\.com/);

    // Nothing in the row can change an option: no modal trigger, no input to toggle,
    // and no data-testid for a later spec to reach for by mistake.
    await expect(row.locator('button.ppcart-settings__email-manage')).toHaveCount(0);
    await expect(row.locator('[data-pp-email-modal-open]')).toHaveCount(0);
    await expect(row.locator('input')).toHaveCount(0);
    await expect(row.locator('[data-testid]')).toHaveCount(0);
  }

  // Control group: every free row keeps the Manage button the locked ones lack.
  for (const descriptor of Object.values(EMAIL_TYPES)) {
    const row = page.locator(
      `${FUNCTIONAL_ROW_SELECTOR}:has(.ppcart-settings__email-title:text-is("${descriptor.title}"))`,
    );

    await expect(row, `${descriptor.title} should render as a functional row`).toHaveCount(1);
    await expect(
      row.locator(
        `button[data-testid="ppcart-admin-email-emailtemplate-${descriptor.modalKey}-manage"]`,
      ),
    ).toHaveCount(1);
    await expect(row.locator('a.ppcart-settings__pro-lock')).toHaveCount(0);
  }

  // And one of those buttons really opens a modal holding a live Enable checkbox,
  // so "functional" is more than a class name.
  await openEmailModal(page, EMAIL_TYPES.pending.modalKey);
  await expect(page.locator(`#${EMAIL_TYPES.pending.optionId}`)).toHaveCount(1);
});
