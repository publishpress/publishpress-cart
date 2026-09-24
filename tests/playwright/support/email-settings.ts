import { expect, test, type APIRequestContext, type Page } from '@playwright/test';

import fs from 'fs';
import os from 'os';
import path from 'path';

import { AdminConfig } from './admin-config';
import { Mailpit } from './mailpit';

/**
 * Shared helpers for the `@email` settings-gate suite.
 *
 * Two hard-won rules are encoded here. Do not work around them:
 *
 * 1. The Enable checkbox is `display: none` (admin/css/ppcart-admin.css:121), so
 *    `check()` / `uncheck()` / `setChecked()` all fail — including with `force: true`.
 *    State is changed by clicking `label[for="<optionId>"]` and read via `isChecked()`.
 *    `check()` also returns early and silently passes when the box already holds the
 *    target state, which is exactly how a gate test turns into a false green.
 *
 * 2. Every settings tab shares one `<form>` posting to `options.php`, so any save
 *    rewrites every registered option. These specs must run serially.
 */

export interface EmailTypeDescriptor {
  /** Option name, which is also the DOM id and name of the input. */
  optionId: string;
  /** Companion option that CCs the site admin. */
  adminOptionId: string;
  /** Settings-section key used in modal and button ids. */
  modalKey: string;
  /** Row title rendered in the notifications list. */
  title: string;
  /** Status passed to the fixture trigger endpoint. */
  triggerStatus: string;
  /** Which fixture object the trigger acts on. */
  target: 'order' | 'subscription' | 'user';
}

/**
 * The seven email types available in the free plugin.
 *
 * Pro-locked types (completed, trial_ending, reminder, paused) are deliberately absent —
 * they render as locked rows with no functional control. See ppcart_pro_locked_emails().
 *
 * Note `confirmation` uses the `purchase` modal key. It is the one type whose section key
 * does not match its own name.
 */
export const EMAIL_TYPES: Record<string, EmailTypeDescriptor> = {
  pending: {
    optionId: '_ppcart_email_pending_enable',
    adminOptionId: '_ppcart_email_pending_admin',
    modalKey: 'pending',
    title: 'Order Received Confirmation',
    triggerStatus: 'pending',
    target: 'order',
  },
  confirmation: {
    optionId: '_ppcart_email_confirmation_enable',
    adminOptionId: '_ppcart_email_confirmation_admin',
    modalKey: 'purchase',
    title: 'Purchase Confirmation',
    triggerStatus: 'paid',
    target: 'order',
  },
  registration: {
    optionId: '_ppcart_email_registration_enable',
    adminOptionId: '_ppcart_registration_email_admin',
    modalKey: 'registration',
    title: 'New User Welcome',
    triggerStatus: '',
    target: 'user',
  },
  refunded: {
    optionId: '_ppcart_email_refunded_enable',
    adminOptionId: '_ppcart_email_refunded_admin',
    modalKey: 'refunded',
    title: 'Order Refunded',
    triggerStatus: 'refunded',
    target: 'order',
  },
  renewal: {
    optionId: '_ppcart_email_renewal_enable',
    adminOptionId: '_ppcart_email_renewal_admin',
    modalKey: 'renewal',
    title: 'Subscription Renewal Confirmation',
    triggerStatus: 'renewal',
    target: 'order',
  },
  failed: {
    optionId: '_ppcart_email_failed_enable',
    adminOptionId: '_ppcart_email_failed_admin',
    modalKey: 'failed',
    title: 'Subscription Renewal Failed',
    triggerStatus: 'failed',
    target: 'order',
  },
  canceled: {
    optionId: '_ppcart_email_canceled_enable',
    adminOptionId: '_ppcart_email_canceled_admin',
    modalKey: 'canceled',
    title: 'Subscription Canceled Confirmation',
    triggerStatus: 'canceled',
    target: 'subscription',
  },
};

/** Titles of the four Pro-locked rows, used by the locked-row assertions. */
export const PRO_LOCKED_EMAIL_TITLES = [
  'Order Complete Notification',
  'Trial Ending Reminder',
  'Upcoming Renewal Reminder',
  'Subscription Paused Confirmation',
];

export const LOCKED_ROW_SELECTOR = '.ppcart-settings__email-notifications-row--locked';
export const FUNCTIONAL_ROW_SELECTOR =
  '.ppcart-settings__email-notifications-row:not(.ppcart-settings__email-notifications-row--locked)';

export function emailType(key: string): EmailTypeDescriptor {
  const descriptor = EMAIL_TYPES[key];

  if (!descriptor) {
    throw new Error(`Unknown email type: ${key}`);
  }

  return descriptor;
}

/**
 * Opens the Emails tab.
 *
 * The `#emails` hash matters: getInitialTabId() reads it before falling back to the
 * `ppcartSettingsActiveTab` localStorage key, so omitting it makes the active tab depend
 * on whatever ran previously.
 */
export function assertSerialExecution(): void {
  const workers = test.info().config.workers;

  if (workers > 1) {
    throw new Error(
      `The @email suite requires --workers=1 (got ${workers}). Every settings tab shares one ` +
        `<form> posting to options.php, so parallel workers overwrite each other's saves and the ` +
        `enable-direction assertions fail at random. Run "composer test:email", which pins it. ` +
        `Note playwright.config.ts reads PW_WORKERS only — ADMIN_PLAYWRIGHT_WORKERS is not used.`,
    );
  }
}

const RUN_LOCK_PATH = path.join(os.tmpdir(), 'ppcart-email-suite.lock');
let runLockHeld = false;

function processAlive(pid: number): boolean {
  try {
    // Signal 0 checks existence without touching the process.
    process.kill(pid, 0);
    return true;
  } catch (error) {
    // EPERM means the process EXISTS but belongs to another user - still alive.
    // Only ESRCH means "no such process". Treating EPERM as dead would steal a
    // live lock and reintroduce exactly the corruption this guards against.
    return (error as NodeJS.ErrnoException)?.code === 'EPERM';
  }
}

function releaseRunLock(): void {
  if (!runLockHeld) {
    return;
  }

  runLockHeld = false;

  try {
    fs.unlinkSync(RUN_LOCK_PATH);
  } catch {
    // Already gone - nothing to do.
  }
}

/**
 * Takes a cross-process lock for the duration of the run.
 *
 * --workers=1 only serialises workers inside ONE Playwright process. Two separate runs
 * (two terminals, a stray background run, two CI jobs) still share one WordPress site and
 * one Mailpit mailbox: deleteAll() in one wipes the other's expected mail, and settings
 * saves interleave. That produces failures that look random but are not, so refuse to
 * start rather than corrupt both runs.
 */
export function acquireExclusiveRunLock(): void {
  if (runLockHeld) {
    return;
  }

  for (let attempt = 0; attempt < 2; attempt += 1) {
    try {
      const fd = fs.openSync(RUN_LOCK_PATH, 'wx');
      fs.writeSync(fd, JSON.stringify({ pid: process.pid, started: new Date().toISOString() }));
      fs.closeSync(fd);

      runLockHeld = true;
      process.once('exit', releaseRunLock);
      process.once('SIGINT', releaseRunLock);
      process.once('SIGTERM', releaseRunLock);

      return;
    } catch {
      let holder: { pid?: number; started?: string } = {};

      try {
        holder = JSON.parse(fs.readFileSync(RUN_LOCK_PATH, 'utf8')) as typeof holder;
      } catch {
        holder = {};
      }

      // Reclaim a lock left behind by a killed run.
      if (!holder.pid || !processAlive(holder.pid)) {
        try {
          fs.unlinkSync(RUN_LOCK_PATH);
        } catch {
          // Someone else reclaimed it first; the retry will fail loudly below.
        }

        continue;
      }

      throw new Error(
        `Another @email run is already in progress (pid ${holder.pid}, started ${holder.started}). ` +
          `These tests mutate shared WordPress settings and share one Mailpit mailbox, so two ` +
          `concurrent runs corrupt each other — mail gets deleted mid-assertion and saves ` +
          `interleave. Wait for it to finish, or remove ${RUN_LOCK_PATH} if that run is dead.`,
      );
    }
  }
}

export type EmailSuiteMode = 'auto' | 'on' | 'off';

/**
 * How the @email suite behaves when Mailpit is absent, via PPCART_EMAIL_TESTS:
 *
 * - `auto` (default) - probe Mailpit; run if reachable, otherwise SKIP with a reason.
 *   Right default for contributors who do not use the DDEV stack.
 * - `on`  - require Mailpit; FAIL if unreachable. CI should set this, so the suite can
 *   never quietly stop covering anything.
 * - `off` - skip unconditionally.
 *
 * Deliberately not a plain on/off switch: a bare opt-out invites a green run with zero
 * email coverage and no signal that it vanished. Skips are always reported as skips.
 */
export function emailSuiteMode(): EmailSuiteMode {
  const raw = (process.env.PPCART_EMAIL_TESTS ?? 'auto').trim().toLowerCase();

  if (raw === 'off' || raw === '0' || raw === 'false') {
    return 'off';
  }

  if (raw === 'on' || raw === '1' || raw === 'true') {
    return 'on';
  }

  return 'auto';
}

let reachabilityProbe: Promise<boolean> | undefined;

/** Probes Mailpit at most once per worker process. */
function mailpitReachable(): Promise<boolean> {
  if (!reachabilityProbe) {
    reachabilityProbe = Mailpit.fromEnv().isReachable();
  }

  return reachabilityProbe;
}

/**
 * Decides whether this suite can run, BEFORE anything mutates site state.
 *
 * Called from gotoEmailsTab(), which every spec reaches before its first save, so an
 * unavailable Mailpit can never leave settings half-toggled.
 */
export async function ensureEmailSuiteRunnable(): Promise<void> {
  const mode = emailSuiteMode();

  if (mode === 'off') {
    test.skip(true, 'PPCART_EMAIL_TESTS=off - email settings-gate suite disabled.');
    return;
  }

  if (await mailpitReachable()) {
    return;
  }

  const endpoint = Mailpit.fromEnv().url();
  const detail =
    `Mailpit is not reachable at ${endpoint}. The @email suite asserts on delivered mail, ` +
    `so it needs a Mailpit instance. Point MAILPIT_URL at yours, or run the DDEV stack ` +
    `(ddev describe shows the Mailpit port).`;

  if (mode === 'on') {
    throw new Error(`${detail} PPCART_EMAIL_TESTS=on requires it to be available.`);
  }

  test.skip(true, `${detail} Set PPCART_EMAIL_TESTS=on to make this a failure instead.`);
}

export async function gotoEmailsTab(page: Page, config: AdminConfig): Promise<void> {
  // Fail fast and legibly rather than as a cascade of racy red further down.
  assertSerialExecution();
  await ensureEmailSuiteRunnable();
  acquireExclusiveRunLock();

  await page.goto(`${config.host()}/wp-admin/admin.php?page=ppcart-settings#emails`);
  await page.waitForLoadState('domcontentloaded');

  const tabButton = page.locator('[data-testid="ppcart-admin-settings-tab-emails"]');
  await expect(tabButton).toBeVisible();

  // Idempotent: harmless when the hash already selected the tab.
  await tabButton.click();
  await expect(page.locator('#content_tab_emails')).toBeVisible();
}

/** Opens a row's Manage modal and waits for it to finish animating open. */
export async function openEmailModal(page: Page, modalKey: string): Promise<void> {
  await page.click(`[data-testid="ppcart-admin-email-emailtemplate-${modalKey}-manage"]`);
  await expect(page.locator(`#ppcart-settings-email-modal-emailtemplate_${modalKey}`)).toHaveClass(
    /is-open/,
  );
}

/**
 * Sets a checkbox to `desired` by clicking its label, then asserts the new state.
 *
 * Never use check()/uncheck()/setChecked() here — see the note at the top of this file.
 */
export async function setCheckbox(page: Page, optionId: string, desired: boolean): Promise<void> {
  const input = page.locator(`#${optionId}`);
  await expect(input).toHaveCount(1);

  if ((await input.isChecked()) !== desired) {
    await page.click(`label[for="${optionId}"]`);
  }

  expect(await input.isChecked()).toBe(desired);
}

/**
 * Saves via the in-modal submit button and waits for the reload.
 *
 * The bottom save button reports visible while a modal is open but sits under the
 * backdrop, so clicking it can be intercepted.
 */
export async function saveEmailSettings(page: Page, modalKey: string): Promise<void> {
  await Promise.all([
    // Wait for the Settings API redirect, not waitForLoadState('load') — that resolves
    // immediately against the already-loaded page and does not wait for the options.php
    // round trip, leaving a following goto() able to cancel the in-flight POST.
    page.waitForURL(/settings-updated=true/),
    page.click(`[data-testid="ppcart-admin-email-emailtemplate-${modalKey}-save"]`),
  ]);

  await expect(page.locator('#content_tab_emails')).toBeVisible();
}

/**
 * Asserts the persisted state from the server-rendered row status dot, which reflects
 * get_option() without needing the modal reopened.
 */
export async function expectRowState(
  page: Page,
  descriptor: EmailTypeDescriptor,
  enabled: boolean,
): Promise<void> {
  const state = enabled ? 'is-enabled' : 'is-disabled';
  const row = page.locator(
    `.ppcart-settings__email-notifications-row:has(.ppcart-settings__email-title:text-is("${descriptor.title}"))`,
  );

  await expect(row.locator(`.ppcart-settings__email-status.${state}`)).toHaveCount(1);
}

/**
 * Full round trip: open the tab, toggle through the real UI, save, and confirm it stuck.
 *
 * Persistence is asserted before any trigger fires. A silently failed save would
 * otherwise make a "nothing was delivered" result look like a pass.
 */
export async function setEmailEnabled(
  page: Page,
  config: AdminConfig,
  typeKey: string,
  enabled: boolean,
  options: { admin?: boolean } = {},
): Promise<EmailTypeDescriptor> {
  const descriptor = emailType(typeKey);

  await gotoEmailsTab(page, config);
  await openEmailModal(page, descriptor.modalKey);
  await setCheckbox(page, descriptor.optionId, enabled);

  if (options.admin !== undefined) {
    await setCheckbox(page, descriptor.adminOptionId, options.admin);
  }

  await saveEmailSettings(page, descriptor.modalKey);
  await expectRowState(page, descriptor, enabled);

  return descriptor;
}

/**
 * Address `ppcart_notification_send()` uses for the admin copy.
 *
 * Empty `ppcart_admin_email` falls back to WordPress `admin_email`. Do not write
 * the Cart option — it is shared with the reports scheduler.
 */
export async function resolveAdminNotificationRecipient(
  page: Page,
  config: AdminConfig,
): Promise<string> {
  const field = page.locator('#ppcart_admin_email');
  await expect(field).toHaveCount(1);

  const fromOption = (await field.inputValue()).split(',')[0].trim();
  if (fromOption !== '') {
    return fromOption;
  }

  await page.goto(`${config.host()}/wp-admin/options-general.php`);
  const fallback = (await page.locator('#new_admin_email').inputValue()).trim();
  expect(fallback).not.toBe('');

  return fallback;
}

const NONCE_ACTION = 'ppcart_admin_fixtures_trigger';

interface FixtureNonceResponse {
  ok: boolean;
  action: string;
  nonce: string;
}

export interface FixtureTriggerResponse {
  ok: boolean;
  triggered: string;
  status: string;
  error?: string;
}

/** Fetches a fresh nonce for the fixtures trigger endpoint. */
export async function getFixtureNonce(
  request: APIRequestContext,
  config: AdminConfig,
): Promise<string> {
  const response = await request.post(`${config.host()}/wp-admin/admin-ajax.php`, {
    form: { action: 'ppcart_admin_fixtures_nonce' },
  });

  expect(response.ok()).toBeTruthy();

  const body = (await response.json()) as FixtureNonceResponse;
  expect(body.ok).toBe(true);
  expect(body.action).toBe(NONCE_ACTION);

  return body.nonce;
}

export interface TriggerParams {
  trigger: 'order_status' | 'subscription_status' | 'create_user';
  order_id?: string | number;
  subscription_id?: string | number;
  status?: string;
  email?: string;
  first_name?: string;
  last_name?: string;
}

/**
 * Fires a fixtures trigger. Asserts the endpoint reported success, so a spec cannot
 * mistake "the trigger never ran" for "the email was correctly suppressed".
 */
export async function triggerFixture(
  request: APIRequestContext,
  config: AdminConfig,
  params: TriggerParams,
): Promise<FixtureTriggerResponse> {
  const nonce = await getFixtureNonce(request, config);

  const form: Record<string, string> = {
    action: 'ppcart_admin_fixtures_trigger',
    nonce,
  };

  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== '') {
      form[key] = String(value);
    }
  }

  const response = await request.post(`${config.host()}/wp-admin/admin-ajax.php`, { form });
  const body = (await response.json()) as FixtureTriggerResponse;

  if (!body.ok) {
    throw new Error(`Fixture trigger failed: ${body.error ?? 'unknown error'}`);
  }

  return body;
}

let recipientCounter = 0;

/**
 * Builds a recipient unique to this worker and call, so concurrent runs cannot match
 * each other's mail. Uses the reserved .invalid TLD so nothing can leave the host.
 */
export function uniqueRecipient(label: string): string {
  recipientCounter += 1;

  const worker = process.env.TEST_PARALLEL_INDEX ?? '0';
  const stamp = `${Date.now().toString(36)}${recipientCounter}`;

  return `ppcart-${label}-${worker}-${stamp}@example.invalid`;
}
