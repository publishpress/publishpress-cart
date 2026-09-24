import { mkdirSync } from 'fs';
import path from 'path';

import { expect, test } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { ADMIN_AUTH_STATE } from '../support/paths';

test('authenticate WordPress administrator', async ({ page }) => {
  const config = AdminConfig.fromEnv();
  test.setTimeout(120_000);
  mkdirSync(path.dirname(ADMIN_AUTH_STATE), { recursive: true });

  if (!config.hasCredentials()) {
    await page.context().storageState({ path: ADMIN_AUTH_STATE });
    return;
  }

  await page.goto(`${config.host()}/wp-login.php`, {
    waitUntil: 'networkidle',
  });
  await page.locator('#user_login').fill(config.optional('wp_admin_username'));
  await page.locator('#user_pass').fill(config.optional('wp_admin_password'));
  await Promise.all([
    page.waitForURL((url) => !url.pathname.endsWith('/wp-login.php'), {
      timeout: 90_000,
    }).catch(() => null),
    page.locator('#wp-submit').click(),
  ]);

  if (/\/wp-login\.php(?:[?#]|$)/.test(page.url())) {
    const loginError = await page
      .locator('#login_error, .message')
      .first()
      .textContent()
      .catch(() => null);

    throw new Error(
      loginError?.trim() || 'WordPress admin login did not complete.'
    );
  }

  await expect(page.locator('#wpadminbar')).toBeVisible({ timeout: 30_000 });

  // Keep product metabox tabs visible for admin workflows even if a prior
  // session hid the box in Screen Options.
  await page.goto(`${config.host()}/wp-admin/post-new.php?post_type=${config.var('admin_product_post_type')}`, {
    waitUntil: 'domcontentloaded',
  });
  const screenOptionsToggle = page.locator('#show-settings-link, button:has-text("Screen Options")').first();
  if (await screenOptionsToggle.count()) {
    await screenOptionsToggle.click().catch(() => null);
  }
  const productMetaboxVisibilityToggle = page.locator('#ppcart-product-settings-hide').first();
  if (await productMetaboxVisibilityToggle.count()) {
    if (!(await productMetaboxVisibilityToggle.isChecked().catch(() => false))) {
      await productMetaboxVisibilityToggle.check().catch(() => null);
    }
  }

  await page.context().storageState({ path: ADMIN_AUTH_STATE });
});
