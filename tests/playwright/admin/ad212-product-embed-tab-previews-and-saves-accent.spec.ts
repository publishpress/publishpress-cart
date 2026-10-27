import { expect, type Page } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { executeAdminTest } from '../support/admin-executor';
import { test } from '../support/admin-test';
import { adminTestsById } from '../support/admin-cases';

const config = AdminConfig.fromEnv();
const testCase = adminTestsById.get('AD-212');

if (!testCase) {
  throw new Error('Missing admin test definition: AD-212');
}

const ACCENT = '#ff5a1f';

async function openEmbedTab(page: Page): Promise<void> {
  await page.locator('[data-testid="ppcart-admin-product-tab-embed"]').click();
  await expect(page.locator('#ppcart-tab-embed')).toBeVisible();
}

function editedPostId(page: Page): string | null {
  return new URL(page.url()).searchParams.get('post');
}

// The admin fixtures are read-only, so this test edits a product it creates and trashes it at the end.
test('AD-212 Product Embed Tab Previews And Saves The Accent Color', { tag: ["@admin","@admin-workflow"] }, async ({ page, context }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.setTimeout(150_000);

  await context.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: config.host() });

  let productId: string | null = null;

  try {
    await executeAdminTest(page, testCase, config);
    productId = editedPostId(page);

    await expect(
      page.frameLocator('[data-testid="ppcart-admin-embed-preview"]').locator('#ppcart_card_button'),
      'the preview applies the accent before the product is saved'
    ).toHaveCSS('background-color', 'rgb(255, 90, 31)');

    await Promise.all([
      page.waitForURL(/[?&]message=\d+/),
      page.locator('#publish').click(),
    ]);
    await page.reload();
    await openEmbedTab(page);

    await expect(page.locator('#_ppcart_embed_accent'), 'the accent persists after update and reload').toHaveValue(ACCENT);

    const permalink = await page.locator('#sample-permalink a').getAttribute('href');
    expect(permalink, 'the edit screen shows the product permalink').toMatch(/^https?:\/\//);

    const inlineCode = page.locator('[data-ppcart-embed-code="inline"]');
    await expect(inlineCode).toContainText(`data-ppcart-checkout="${permalink}"`);
    await expect(page.locator('[data-ppcart-embed-code="popup"]')).toContainText(`background:${ACCENT}`);

    await page.locator('[data-testid="ppcart-admin-embed-copy-inline"]').click();
    await expect(page.locator('[data-testid="ppcart-admin-embed-copy-status-inline"]')).toHaveText('Copied');
    expect(await page.evaluate(() => navigator.clipboard.readText()), 'the inline code is on the clipboard')
      .toBe(await inlineCode.textContent());
  } finally {
    if (productId) {
      await page.goto(config.resolve(`{{publishpress_cart_host}}/wp-admin/post.php?post=${productId}&action=edit`));
      await Promise.all([
        page.waitForURL(/[?&]trashed=1/),
        page.locator('#delete-action a.submitdelete').click(),
      ]);
    }
  }
});
