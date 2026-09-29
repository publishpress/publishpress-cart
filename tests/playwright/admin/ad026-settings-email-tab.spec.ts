import { expect } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { executeAdminTest } from '../support/admin-executor';
import { test } from '../support/admin-test';
import { adminTestsById } from '../support/admin-cases';

const config = AdminConfig.fromEnv();
const testCase = adminTestsById.get('AD-026');

if (!testCase) {
  throw new Error('Missing admin test definition: AD-026');
}

test('AD-026 Settings Email Tab', { tag: ["@admin","@admin-core"] }, async ({ page }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.setTimeout(90_000);

  await executeAdminTest(page, testCase, config);

  const previewType = page.getByTestId('ppcart-admin-email-preview-type');
  const previewLink = page.getByTestId('ppcart-admin-email-preview-open');

  await expect(previewType).toBeVisible();
  await expect(previewLink).toBeVisible();

  await expect(previewLink).toHaveAttribute('href', /type=%5Bconfirmation%5D/i);

  await previewType.selectOption('refunded');
  await expect(previewLink).toHaveAttribute('href', /type=%5Brefunded%5D/i);
});
