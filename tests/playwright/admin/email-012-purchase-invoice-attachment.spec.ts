import { expect, type Page } from '@playwright/test';

import { AdminConfig } from '../support/admin-config';
import { test } from '../support/admin-test';
import {
  EMAIL_TYPES,
  setCheckbox,
  setEmailEnabled,
  triggerFixture,
  uniqueRecipient,
} from '../support/email-settings';
import { Mailpit } from '../support/mailpit';

const config = AdminConfig.fromEnv();
const mailpit = Mailpit.fromEnv();

/** Option that makes ppcart_notification_send() call PPCart_Order::get_invoice(). */
const ATTACH_OPTION_ID = '_ppcart_invoice_attach_confirmation_email';

test.describe.configure({ mode: 'serial' });

/**
 * The attach-invoice checkboxes live on the Invoices tab, not in an email modal,
 * so they need the plain tab + bottom-save path rather than the modal helpers.
 */
async function gotoInvoiceTab(page: Page): Promise<void> {
  await page.goto(`${config.host()}/wp-admin/admin.php?page=ppcart-settings#invoice`);
  await page.waitForLoadState('domcontentloaded');

  const tabButton = page.locator('[data-testid="ppcart-admin-settings-tab-invoice"]');
  await expect(tabButton).toBeVisible();

  await tabButton.click();
  await expect(page.locator('#content_tab_invoice')).toBeVisible();
}

/**
 * Saves the shared settings form from the Invoices tab.
 *
 * Waits for the `settings-updated=true` redirect options.php issues rather than a
 * bare load state: a later goto() would otherwise be able to cancel the in-flight POST.
 */
async function saveFromInvoiceTab(page: Page): Promise<void> {
  await Promise.all([
    page.waitForURL(/settings-updated=true/),
    page.click('[data-testid="ppcart-admin-settings-save-bottom"]'),
  ]);
}

async function setInvoiceAttachment(page: Page, enabled: boolean): Promise<void> {
  await gotoInvoiceTab(page);
  await setCheckbox(page, ATTACH_OPTION_ID, enabled);
  await saveFromInvoiceTab(page);

  // Re-read from a fresh render so the assertion reflects get_option(), not the DOM
  // state we just clicked into place.
  await gotoInvoiceTab(page);
  expect(await page.locator(`#${ATTACH_OPTION_ID}`).isChecked()).toBe(enabled);
}

test('EM-012 Purchase Confirmation — sends with invoice attached', { tag: ["@admin","@email"] }, async ({ page, request }) => {
  test.skip(!config.hasCredentials(), 'Set WP_TESTS_ADMIN_USER and WP_TESTS_ADMIN_PASSWORD in .env');
  test.skip(!config.hasEmailFixtureIds(), 'Run composer test:admin:setup');
  test.setTimeout(180_000);

  const descriptor = EMAIL_TYPES.confirmation;
  const recipient = uniqueRecipient('purchase-invoice');

  await setEmailEnabled(page, config, 'confirmation', true);

  try {
    await setInvoiceAttachment(page, true);

    await mailpit.deleteAll();

    // Regression guard for a559433f: get_invoice() required a stale relative path and
    // fatalled before wp_mail(), so the enabled setting still delivered nothing.
    await triggerFixture(request, config, {
      trigger: 'order_status',
      order_id: config.var('admin_email_order_id'),
      status: descriptor.triggerStatus,
      email: recipient,
    });

    const summary = await mailpit.waitForMessage({ to: recipient });

    expect(summary.Attachments).toBeGreaterThanOrEqual(1);

    const message = await mailpit.message(summary.ID);

    expect(message.Attachments.length).toBeGreaterThanOrEqual(1);
    expect(message.Attachments[0].FileName).toMatch(/\.pdf$/i);
  } finally {
    // Other specs assert on plain confirmation mail; leave the attachment off.
    await setInvoiceAttachment(page, false);
  }
});
