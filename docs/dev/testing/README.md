# Testing docs

Test catalog, suite runbooks, and tooling conventions. Catalog config lives in
[`.scribe.config.yaml`](../../../.scribe.config.yaml) at the repo root (suites,
modules, fields, references). Shared enums ship with `tests/tools/test-docs/`.
The legacy `.tests.config.yaml` filename is no longer present.

Case markdown follows Scribe v2 — see
[docs/testing/schema.md](../../testing/schema.md). Feature catalog fields are
in [docs/dev/schemas/features.md](../schemas/features.md). Architecture and
rollout history: [test-documentation-plan.md](test-documentation-plan.md).
Browser suite setup: [regression-tests.md](regression-tests.md),
[smoke-tests.md](smoke-tests.md), and [admin-tests.md](admin-tests.md).
Email settings-gate coverage (`@email` group):
[email-settings-gate-tests.md](email-settings-gate-tests.md).
Proposed isolated parallel runner (not implemented):
[isolated-e2e-site-pool.md](isolated-e2e-site-pool.md).

## Source of truth

**Markdown case files** under `docs/testing/cases/<suite>/` are the catalog
source of truth. Use **`scribe`** for local preview and editing
(`composer scribe`). Import scripts under `tests/tools/test-docs/scripts/`
bulk-create cases from spreadsheets.

Each case is one file named by full ID (e.g. `cases/regression/RT-001.md`)
with YAML front matter and body sections (Scenario, Steps, Expected result,
Edge cases). Required non-derivable fields are `suite` and `priority`; `id`
and `plugin` are derived unless overridden.

`module` groups tests by code area (see `modules` in `.scribe.config.yaml`).
`features` links cases to sellable capability ids in the `features:` block of
`.scribe.config.yaml` — see the [feature index](../../README.md).

## What to edit where

| Target | Edit via | Notes |
|--------|----------|-------|
| `plugin`, `catalog`, `suites` | **IDE / git** | Hand-edited in `.scribe.config.yaml`; web UI is read-only |
| `modules`, `references`, `fields` | IDE / git | Hand-edited in `.scribe.config.yaml` |
| Case content | `scribe` or `docs/testing/cases/<suite>/*.md` | |
| `automation` links | **IDE / agent** | Read-only in UI; link cases to spec or PHP test files |

Suite prefixes and scan paths (`RT-`, `UT-`, `IT-`) live in
[`.scribe.config.yaml`](../../../.scribe.config.yaml). Use the
test-config Cursor skill when
adding or removing suites. Author new cases with the
write-test-case skill.

## Catalog layout

```
.scribe.config.yaml         # repo-root catalog config

docs/testing/
  schema.md                 # Scribe v2 case schema (twin of agent skill)
  cases/<suite>/            # one markdown case per ID
```

| Suite key | Prefix | Runner | Test code |
|-----------|--------|--------|-----------|
| `regression` | `RT` | Playwright | `tests/playwright/regression/` |
| `admin` | `AD` | Playwright | `tests/playwright/admin/ad*.spec.ts` |
| `email` | `EM` | Playwright | `tests/playwright/admin/email-*.spec.ts` |
| `smoke` | `ST` | Playwright | `tests/playwright/smoke/` |
| `unit` | `UT` | Codeception | `tests/codeception/Unit/` |
| `integration` | `IT` | Codeception + WPLoader | `tests/codeception/Integration/` |

Cases for the `regression`, `admin`, and `smoke` Playwright suites are
**generated from their test code**; see
test-catalog-sync.
Unit, integration, and `email` cases are hand-written — the email specs are not
driven by `admin-cases.ts`, so there is nothing to generate from.

## Running tests

| Suite | Composer |
|-------|----------|
| Regression | `composer test:regression` (setup: `composer test:regression:setup`) |
| Admin | `composer test:admin` (setup: `composer test:admin:setup`) |
| Smoke | `composer test:smoke` (setup: `composer test:regression:setup`) |
| Unit | `composer test:unit` |
| Integration | `composer test:integration` |
| All | `composer test:all` (unit + integration + regression + admin; not smoke) |
| Email | `composer test:email` (setup: `composer test:admin:setup`) |

See [regression-tests.md](regression-tests.md), [smoke-tests.md](smoke-tests.md), and
[tests/README.md](../../../tests/README.md) for environment variables and
setup. GitHub Actions: unit + integration on pull_request / workflow_dispatch
(`.github/workflows/unit-tests.yml`, `integration-tests.yml`). Playwright mocked
regression (`test-regression-mocked.yml`) and admin (`test-admin.yml`) run on
PRs to `development`. Real-gateway regression (`test-regression.yml`) is
workflow_dispatch only. CI copies `.env.example`; `composer install` writes
bind-mount paths — see [tests/README.md](../../../tests/README.md#github-actions).

## Import and local preview

```bash
composer test:docs:sync         # catalog <-> test code sync check
composer test:docs:import-rt    # bulk import RT cases from Master Test Suite
composer test:docs:import       # legacy spreadsheet import
composer scribe                 # local catalog UI (replaces legacy test-docs server)
composer scribe:doctor          # catalog conformance check
composer test:jev:regression    # Jev oracle-quality dry-run (not a test runner)
```

`composer test:jev:regression -- --live` calls Jev 1.13 (`TYPESAFE_API_KEY`).
Hand-written `should_catch` claims in
`tests/tools/jev-regression-effectiveness/pack.mjs` — not generated RT
catalog markdown. Score is mean `catches_distinguishing.noul`.

`scribe:doctor` checks documentation conformance only. `test:docs:sync`
verifies every test has a case and every case links real code — run both.

Override config path:
`TEST_DOCS_CONFIG=/path/to/.scribe.config.yaml composer test:docs:import-rt`

## Authoring new cases

1. Assign the next ID for the suite prefix (see existing files under
   `cases/<suite>/`).
2. Create or update `docs/testing/cases/<suite>/<ID>.md` (catalog case).
3. Write or update test code; set the case `automation` block to link the
   implementation.
4. Run the suite and confirm pass/skip.
5. Run `composer test:docs:sync` and `composer scribe:doctor`.

Use write-test-case for
v2 front matter and body shape, and
test-catalog-sync when
auditing or repairing drift.
