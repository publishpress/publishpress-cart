import { AdminConfig } from '../support/admin-config';
import { executeAdminTest } from '../support/admin-executor';
import { test } from '../support/admin-test';
import { adminTestsById } from '../support/admin-cases';

const config = AdminConfig.fromEnv();
const testCase = adminTestsById.get('AD-011');

if (!testCase) {
  throw new Error('Missing admin test definition: AD-011');
}

test('AD-011 Extensions Page', { tag: ["@admin","@admin-core"] }, async ({ page }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.setTimeout(90_000);

  await executeAdminTest(page, testCase, config);
});
