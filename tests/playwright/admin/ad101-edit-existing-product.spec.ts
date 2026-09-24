import { AdminConfig } from '../support/admin-config';
import { executeAdminTest } from '../support/admin-executor';
import { test } from '../support/admin-test';
import { adminTestsById } from '../support/admin-cases';

const config = AdminConfig.fromEnv();
const testCase = adminTestsById.get('AD-101');

if (!testCase) {
  throw new Error('Missing admin test definition: AD-101');
}

test('AD-101 Edit Existing Product', { tag: ["@admin","@admin-fixture"] }, async ({ page }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasFixtureIds(), 'Set ADMIN_* fixture variables in .env before running fixture-dependent admin specs');
  test.setTimeout(90_000);

  await executeAdminTest(page, testCase, config);
});
