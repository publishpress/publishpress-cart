import { AdminConfig } from '../support/admin-config';
import { executeAdminTest } from '../support/admin-executor';
import { test } from '../support/admin-test';
import { adminTestsById } from '../support/admin-cases';

const config = AdminConfig.fromEnv();
const testCase = adminTestsById.get('AD-103');

if (!testCase) {
  throw new Error('Missing admin test definition: AD-103');
}

test('AD-103 Edit Existing Subscription', { tag: ["@admin","@admin-fixture"] }, async ({ page }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasFixtureIds(), 'Set ADMIN_* fixture variables in .env before running fixture-dependent admin specs');
  test.setTimeout(90_000);

  await executeAdminTest(page, testCase, config);
});
