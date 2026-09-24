# Email Settings Gate Tests

Regression/admin coverage proving the **Emails settings tab actually controls whether each
transactional email is sent**.

Origin: a purchase confirmation email was not being delivered. Two independent causes were found
(fix merged as `a559433f`):

1. `_ppcart_email_confirmation_enable` was `0`, so `ppcart_notification_send()` returned before
   `wp_mail()`.
2. `PPCart_Order::get_invoice()` required a stale relative path, fatalling **before** `wp_mail()`
   whenever `_ppcart_invoice_attach_{type}_email` was on.

Neither had test coverage. Cause 2 in particular meant the setting could be on and the email still
never arrive. These tests close that gap.

## Goal

For every email type available in the **free** plugin, assert both directions:

- **Enabled via the settings page** → the email is delivered.
- **Disabled via the settings page** → the email is **not** delivered.

The toggle must be driven **through the real settings UI** (click + Save), not by writing the option
directly. Writing the option would not prove the settings page controls anything — which is the
entire point of this work.

## Scope: free vs Pro

Authoritative source is `ppcart_pro_locked_emails()` in
`includes/helpers/pro-locks/field-and-email-locks.php`. Pro-locked emails have **no settings field**
in `admin/settings/traits/options/core-settings.php` and are out of scope for send/no-send testing.

### In scope — free (7 types)

| # | Settings title | Type key | Enable option | Admin-copy option | Trigger |
|---|---|---|---|---|---|
| 1 | Order Received Confirmation | `pending` | `_ppcart_email_pending_enable` | `_ppcart_email_pending_admin` | order status `pending` (COD) |
| 2 | Purchase Confirmation | `confirmation` | `_ppcart_email_confirmation_enable` | `_ppcart_email_confirmation_admin` | order status `paid` |
| 3 | New User Welcome | `registration` | `_ppcart_email_registration_enable` | `_ppcart_registration_email_admin` | new user created at checkout |
| 4 | Order Refunded | `refunded` | `_ppcart_email_refunded_enable` | `_ppcart_email_refunded_admin` | order status `refunded` |
| 5 | Subscription Renewal Confirmation | `renewal` | `_ppcart_email_renewal_enable` | `_ppcart_email_renewal_admin` | status `renewal` |
| 6 | Subscription Renewal Failed | `failed` | `_ppcart_email_failed_enable` | `_ppcart_email_failed_admin` | status `failed` (renewal) |
| 7 | Subscription Canceled Confirmation | `canceled` | `_ppcart_email_canceled_enable` | `_ppcart_email_canceled_admin` | status `canceled` |

### Out of scope — Pro-locked

Order Complete Notification (`completed`), Trial Ending Reminder (`trial_ending`), Upcoming Renewal
Reminder (`reminder`), Subscription Paused Confirmation (`paused`).

Assert only that these render as **locked rows** with no functional Enable control in free.

> Known gap, do **not** silently work around: `_ppcart_email_completed_enable` is referenced by
> `ppcart_email_templates()` but has no field in `core-settings.php`, so it can never be enabled from
> the UI in free. Cover it with the locked-row assertion and note it.

## Label / group

Every new spec is tagged:

```ts
{ tag: ["@admin", "@email"] }
```

- `@email` is how the suite is selected and, critically, how it is **excluded** from the
  parallel admin sweep: `composer test:admin` runs `--grep @admin --grep-invert @email`.
- `@admin` marks these as admin-UI tests and keeps `admin-chromium` (with its auth
  dependency and storage state) as the project that collects them.

**These tests must run with one worker.** Every settings tab shares a single `<form>` posting to
`options.php`, so parallel workers overwrite each other's saves; the enable-direction assertions then
fail at random while the disable-direction ones pass, because concurrent saves mostly drive state
toward *off*. This is enforced, not merely documented:

- `composer test:email` pins `--workers=1`.
- `assertSerialExecution()` in `email-settings.ts` throws immediately if `workers > 1`, so a
  misconfigured run fails on line one with the reason instead of a cascade of racy red.

`playwright.config.ts` reads **`PW_WORKERS` only** — it applies to every project including admin.
`ADMIN_PLAYWRIGHT_WORKERS` is *not* read by the config despite the name; do not rely on it.

Add a composer script:

```json
"test:email": "npm run test:e2e -- --grep @email"
```

## Running without Mailpit

The suite asserts on delivered mail, so it needs a Mailpit instance. Not every contributor runs the
DDEV stack, so this is handled explicitly rather than left to fail.

`gotoEmailsTab()` probes Mailpit once per worker process **before any setting is mutated** — so an
unavailable mail catcher can never leave the site half-toggled — and `PPCART_EMAIL_TESTS` decides
what happens:

| Value | Behaviour |
|---|---|
| `auto` (default) | Probe Mailpit. Reachable → run. Unreachable → **skip** with a reason naming the endpoint and how to fix it. |
| `on` | Require Mailpit. Unreachable → **fail**. CI should set this. |
| `off` | Skip unconditionally. |

Point `MAILPIT_URL` at any Mailpit-compatible endpoint (the standalone binary works; it does not have
to be DDEV). Default is `http://cart.ddev.site:8025`.

This is deliberately three-state rather than a plain on/off flag. A bare opt-out invites a green run
with zero email coverage and no signal that the coverage disappeared — the same class of false
confidence these tests exist to prevent. Skips always report as skips, never as passes, and CI
pinning `on` makes silent loss impossible.

Verified behaviour with Mailpit pointed at a dead port:

- `auto` → `18 skipped, 1 passed` in ~3s, no failures, no settings touched.
- `on` → fails with `Mailpit is not reachable at <url> … PPCART_EMAIL_TESTS=on requires it`.
- `off` → `18 skipped`.

## Where the specs live — and why

`tests/playwright/admin/validate-admin-specs.js` enforces a **strict bijection**: every file matching
`^ad\d{3}-.+\.spec\.ts` must reference an `adminTestsById.get('AD-xxx')` entry, and every case ID in
`admin-cases.ts` must have a spec file.

The `admin-cases.ts` DSL is a restricted Selenium-style command list
(`click`, `assertElementPresent`, …). **It cannot assert on delivered mail.** So these tests cannot be
case-driven.

Resolution:

- Put specs in `tests/playwright/admin/` so the `admin-chromium` project (`testMatch:
  /admin\/.*\.spec\.ts/`) picks them up.
- Name them **`email-NNN-*.spec.ts`**, which does *not* match `^ad\d{3}-`, so the validator ignores
  them and the bijection stays intact.
- Do **not** add entries to `admin-cases.ts` for these.

`composer test:admin:validate` must still pass unchanged.

## Required infrastructure

### 1. Mailpit helper — `tests/playwright/support/mailpit.ts`

DDEV Mailpit: use **`http://cart.ddev.site:8025` (HTTP)**, configurable via `MAILPIT_URL`.

> Corrected after live verification: `https://cart.ddev.site:8026` works in curl and browsers but
> **fails from Node** with `UNABLE_TO_VERIFY_LEAF_SIGNATURE` — the mkcert root is not in Node's trust
> store, and Playwright's `ignoreHTTPSErrors` does not apply because these are plain `fetch` calls,
> not browser-context requests. Do not "fix" the helper back to HTTPS.
> `http://127.0.0.1:32770` also works but the port is remapped by `ddev restart`, so it is not stable.

API surface needed:

- `deleteAll()` — `DELETE /api/v1/messages`. Call before every trigger so assertions are isolated.
- `search(query)` — `GET /api/v1/search?query=…`.
- `waitForMessage({ to, subject }, timeoutMs)` — poll until match or timeout; returns the message.
- `expectNoMessage({ to }, quietMs)` — wait out a quiet period, assert nothing arrived.

`expectNoMessage` is the load-bearing half. Give it a real quiet window (~5s) so it cannot pass just
by checking too early — a false green here would hide exactly the bug that started this.

### 2. Trigger endpoint — extend the fixtures layer

Specs cannot shell out to WP-CLI. Add a **capability-gated, fixtures-only** trigger to
`tests/ppcart-fixtures/ppcart-admin-fixtures.php` (follow `PPCart_Fixtures::assert_can_create()`), exposing:

- `trigger_order_status(order_id, status)` → `ppcart_trigger_integrations($status, $order_id)`
- `trigger_subscription_status(sub_id, status)` → same for subscriptions
- `create_user_for_order(order_id, email)` → `ppcart_create_user(...)` for the registration email

Constraints:

- Registered **only** when fixtures are loaded — never in a normal site boot.
- Requires `manage_options` + nonce.
- Returns JSON `{ ok, triggered, status }`.

This deliberately tests the notification gate, not the checkout flow — checkout is already covered by
`rt001`–`rt019`.

### 3. Fixture data

Reuse `PPCart_Admin_Fixtures` conventions. Needs one order (one-time) and one subscription, with a
customer at a **unique per-test** address (`@example.invalid`) so Mailpit assertions can't collide
across parallel workers.

## Test catalog

Two specs per email type, plus shared UI coverage. IDs continue from the current highest (`AD-211`),
but are **not** `AD-` prefixed — the `EM-` series is its own suite (`email`) declared in
[`.scribe.config.yaml`](../../../.scribe.config.yaml).

**One catalog case per test lives in [`docs/testing/cases/email/`](../../testing/cases/email/)**
(`EM-001.md` … `EM-071.md`), covering EM-001/002/003 pending, EM-010/011/012 purchase confirmation,
EM-020/021 new user welcome, EM-030/031 refunded, EM-040/041 renewal, EM-050/051 renewal failed,
EM-060/061 canceled, EM-070 toggle persistence, and EM-071 Pro-locked rows. Those files are the
per-test source of truth for steps and expected results — do not restate them here. Verify the
catalog matches the specs with `composer test:docs:sync`.

### Per-test shape

1. Log in as admin (reuse `admin-auth.setup.ts` storage state).
2. Navigate to `…/admin.php?page=ppcart-settings` → Emails tab.
3. Set the Enable checkbox to the target state, **Save**, assert it persisted.
4. `mailpit.deleteAll()`.
5. Fire the trigger endpoint.
6. Assert delivered / not delivered.

Always assert step 3 before step 5. If the save silently failed, a "not delivered" result would be a
false pass.

## Gotchas found during research — do not rediscover these

- `ppcart_notification_send()` maps status `paid` → type `confirmation`. Every other status passes
  through unchanged. Do not assume `paid` reads `_ppcart_email_paid_enable`.
- It builds a `PPCart_Subscription` only for statuses in
  `['completed','active','paused','canceled','past_due']`. `renewal` and `failed` build a
  `PPCart_Order`. Fixture data must satisfy whichever object is constructed.
- The registration email is **not** sent by `ppcart_notification_send()`. It goes through
  `ppcart_create_user()` → `ppcart_new_user_notification()`, and is additionally gated by
  `_ppcart_use_wp_notification` (which routes to core's `wp_new_user_notification` instead). Force
  that option off so the plugin's own email is under test.
- `PPCart_Public::do_after_integration_functions()` also consults per-product meta
  `disable_{type}_email` (`purchase` for paid, `pending`, `welcome`). Fixture products must leave
  these unset or tests will fail for the wrong reason.
- `_ppcart_email_past_due_*` are **aliases** of `_ppcart_email_failed_*`
  (`ppcart_email_template_alias_options()`). Do not treat them as an eighth type.
- Enable flags have **no registered default** and are never seeded — a fresh site has every email
  off. Tests must set state explicitly and never rely on an install default.
- `renewal` and `failed` act on the **order** fixture, not the subscription fixture. The `target`
  field in `EMAIL_TYPES` encodes this correctly — trust it over prose.
- **EM-050 passes for a non-obvious reason.** `ppcart_trigger_integrations()` contains
  `case 'failed' && $event_type == 'renewal':`. The case expression evaluates to a *boolean*, not the
  string `'failed'`, so for a plain order it becomes `case false:` and never matches — no
  `ppcart_renewal_failed` action fires. The email still sends only because
  `ppcart_run_after_integrations` fires unconditionally at the end of the function and
  `do_after_integration_functions()` calls `ppcart_notification_send()`. Verified with
  `php -r`: non-renewal falls to `default`, renewal matches. A future fix to that switch arm would
  therefore **not** break EM-050. Same defect on the adjacent `'uncollectible' && …` arm, and
  `case 'trialing':` is duplicated (lines 94 and 109 — the second is unreachable). Production bugs,
  out of scope for this task, but do not "fix" the tests to accommodate them.

## Definition of done

- [ ] `composer test:admin:validate` passes (bijection intact).
- [ ] `composer test:email` runs the new group green.
- [ ] `composer test:admin` still green — no regressions.
- [ ] Every one of the 7 free types has a passing send **and** no-send test.
- [ ] EM-012 fails if `get_invoice()` is reverted to the broken path (verify by reverting locally).
- [ ] `docs/dev/testing/README.md` links this document.
- [ ] `composer test:docs:sync` reports no `email` suite errors — 18 specs, 18 catalog cases under
      `docs/testing/cases/email/`.
- [ ] No production code changed — tests and fixtures only.

## Verification requirement

The no-send assertions are the ones most likely to be **false green**. Before declaring done, prove
at least one no-send test actually fails when the gate is removed: temporarily force
`ppcart_notification_send()` past its enable check and confirm the matching EM-0x1 test goes red.
Report the result. A no-send test that cannot fail is worthless.

---

# Appendix A — Verified selectors and mechanics

Everything below was confirmed against the live DDEV site and traced to source. Do not re-derive it,
and do not "improve" on it without re-verifying — several of these are counter-intuitive.

## A.1 The Enable checkbox is `display:none` — `.check()` does not work

`admin/css/ppcart-admin.css:121` sets `.ppcart-admin-page .ckbx-style input { display: none; }` and
nothing restores it. Verified empirically:

- `locator.check()` → TimeoutError (not visible)
- `locator.check({ force: true })` → **also fails**: "Element is not visible"
- `label[for="<option_id>"]` **is** visible (40×22) and clicking it toggles reliably

**Rule: read state with `input.isChecked()`, change state by clicking `label[for="<option_id>"]`.**
Never use `check()` / `uncheck()` / `setChecked()`, with or without `force`.

Worse: `.check()` **returns early and silently passes** when the box is already in the target state,
so it can look like it works. Do not let that into the suite.

Helper to write once and reuse:

```ts
async function setEnable(page: Page, optionId: string, desired: boolean) {
  const input = page.locator(`#${optionId}`);
  if ((await input.isChecked()) !== desired) {
    await page.click(`label[for="${optionId}"]`);
  }
  expect(await input.isChecked()).toBe(desired);
}
```

## A.2 Selector table (verified in rendered HTML)

`ppcart_testid()` (`includes/functions.php:389`) lowercases, replaces `[^a-z0-9]+` with `-`, and trims
`-`. So `_ppcart_email_pending_enable` → `ppcart-admin-field-ppcart-email-pending-enable`.

| Option id (also the DOM `id` and `name`) | `data-testid` | Modal | Manage button testid |
|---|---|---|---|
| `_ppcart_email_pending_enable` | `ppcart-admin-field-ppcart-email-pending-enable` | `#ppcart-settings-email-modal-emailtemplate_pending` | `ppcart-admin-email-emailtemplate-pending-manage` |
| `_ppcart_email_confirmation_enable` | `ppcart-admin-field-ppcart-email-confirmation-enable` | `#ppcart-settings-email-modal-emailtemplate_purchase` | `ppcart-admin-email-emailtemplate-purchase-manage` |
| `_ppcart_email_registration_enable` | `ppcart-admin-field-ppcart-email-registration-enable` | `#ppcart-settings-email-modal-emailtemplate_registration` | `ppcart-admin-email-emailtemplate-registration-manage` |
| `_ppcart_email_refunded_enable` | `ppcart-admin-field-ppcart-email-refunded-enable` | `#ppcart-settings-email-modal-emailtemplate_refunded` | `ppcart-admin-email-emailtemplate-refunded-manage` |
| `_ppcart_email_renewal_enable` | `ppcart-admin-field-ppcart-email-renewal-enable` | `#ppcart-settings-email-modal-emailtemplate_renewal` | `ppcart-admin-email-emailtemplate-renewal-manage` |
| `_ppcart_email_failed_enable` | `ppcart-admin-field-ppcart-email-failed-enable` | `#ppcart-settings-email-modal-emailtemplate_failed` | `ppcart-admin-email-emailtemplate-failed-manage` |
| `_ppcart_email_canceled_enable` | `ppcart-admin-field-ppcart-email-canceled-enable` | `#ppcart-settings-email-modal-emailtemplate_canceled` | `ppcart-admin-email-emailtemplate-canceled-manage` |

**Trap:** `confirmation` lives in the **`purchase`** modal (`emailtemplate_purchase`). Every other type
matches its own name.

## A.3 Reaching the Emails tab

Tabs are client-side buttons, no page reload. Use the hash URL:

```
https://cart.ddev.site/wp-admin/admin.php?page=ppcart-settings#emails
```

`getInitialTabId()` (`admin/js/ppcart-settings.js:122`) reads the hash **before** the `localStorage`
key `ppcartSettingsActiveTab`. Without the hash the active tab is whatever the previous test left
behind — a real flake source. Assert `#content_tab_emails` is active before proceeding.

Ignore `&ppcart_payment_subtab=enable` — it is noise injected by the payment panel's
`history.replaceState`, with no effect on the Emails tab. Keep it out of test URLs.

## A.4 It is a modal, not an accordion

Rows live in `.ppcart-settings__email-notifications-list`; "Manage" opens a modal rendered with the
`hidden` attribute. Sequence: click the Manage testid → wait for
`#ppcart-settings-email-modal-<key>.is-open` → interact.

## A.5 Saving

Plain WordPress Settings API POST to `options.php` (`admin/partials/settings-page/view.php:27`), full
page reload — **not** AJAX. Prefer the in-modal save button:

```
[data-testid="ppcart-admin-email-emailtemplate-<key>-save"]
```

`[data-testid="ppcart-admin-settings-save-bottom"]` reports visible while a modal is open but sits
under the backdrop, so clicks can be intercepted. Await the navigation — do not just click. After
the reload the modal is **closed**, so re-open Manage for any post-save checkbox assertion.

## A.6 Asserting persistence

Options are written as string `"1"` / `"0"` — never absent — because `options.php` writes every
registered option in the group and `sanitize_checkbox()` coerces. So a persisted `"0"` is
distinguishable from never-configured.

Cheapest UI assertion, no modal needed — the row status dot is rendered server-side from
`get_option()`:

```
.ppcart-settings__email-notifications-row:has(.ppcart-settings__email-title:text-is("<Title>"))
  .ppcart-settings__email-status.is-enabled
```

CLI cross-check: `ddev wp option get _ppcart_email_<type>_enable`.

## A.7 Pro-locked rows (EM-071)

Locked: `.ppcart-settings__email-notifications-row--locked` (4 rows).
Functional: `.ppcart-settings__email-notifications-row:not(...--locked)` (7 rows).

More robust than the class: a locked row's action cell has `a.ppcart-settings__pro-lock` pointing at
publishpress.com and has **no** `button.ppcart-settings__email-manage`, no `data-pp-email-modal-open`,
no inputs, no `data-testid`.

## A.8 Isolation requirements — read before writing any spec

**All settings tabs share a single `<form>`.** Saving the Emails tab rewrites every registered option
across payment, integrations, invoice, secrets, etc. Consequences:

- Snapshot and restore the `_ppcart_email_*` options around the suite.
- Do **not** run these in parallel with other settings tests. Keep `ADMIN_PLAYWRIGHT_WORKERS=1`.
- Give each test a unique `@example.invalid` recipient so Mailpit assertions cannot collide.

Avoid the Discard button — it triggers `window.confirm`, and a browser dialog will freeze the run.

Auth: reuse the storage state from `tests/playwright/setup/admin-auth.setup.ts`.
