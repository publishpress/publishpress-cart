# Test documentation plan

> **Historical.** This is a 2026 pilot plan, not operational documentation.
> Commands, catalog config, and suite runbooks live only in
> [docs/dev/testing/README.md](README.md). Do not treat inventory tables or
> `.tests.config.yaml` examples here as current.

Plan for replacing the Google Sheets test catalog with maintainable markdown case files and a small local web UI (browse, dashboard, and edit). Domain-specific fields (e.g. tax context, payment gateway) are declared in `.tests.config.yaml`, not in shared tooling.

Supports **multiple test suites** in one repo (unit, integration, browser regression, and future suites). **All per-plugin config lives in one file:** `.tests.config.yaml` at the repository root. Shared enums ship with the tooling. Secrets and runtime targets stay in `.env`.

**Status:** Pilot — local preview and editing moved to **`scribe`** (`composer scribe`). The legacy `tests/tools/test-docs` server/UI is removed; import scripts remain under `tests/tools/test-docs/scripts/`.

> **Config filename:** this document predates `scribe init`. Everywhere it says
> `.tests.config.yaml`, the live file is now **`.scribe.config.yaml`**; the old
> name is only a loader fallback. See
> [README.md](README.md) and the
> test-config skill.

**Related:** [Regression tests runbook](regression-tests.md) covers how to run Playwright tests; [tests/README.md](../../../tests/README.md) lists Composer suites. This document covers the test catalog, coverage planning, and dashboard tooling.

---

## Pilot scope and future direction

This is a **pilot on a single repo** (PublishPress Cart). Goal: validate that test engineers prefer markdown cases + a local UI over the spreadsheet/SaaS. If it works, we invest in extraction and a centralized platform.

**Phase 2 (future, not in this plan): centralized platform.** A shared service that reads and edits test docs *and* test files across our repos — including on **different branches** when needed — giving a fleet-level view across all 10+ plugins. The per-repo, text-file-first design here is deliberate so extraction is mechanical later: the platform consumes the same `.tests.config.yaml` + markdown cases, just across many checkouts/branches. Multi-plugin aggregation and the central platform are explicitly **out of scope for the pilot** and tracked separately.

---

## Background

### Spreadsheet (current source)

Five sheets exported as HTML (126 planned test cases as of 2026-06-01):

| Sheet | Purpose |
|-------|---------|
| **Master Test Suite** | Full catalog: ID, priority, purchase type, scenario, tax context, steps, expected results, edge cases, status |
| **Summary** | Dashboard counts (126 total, all Pending; 5 Critical, 61 High, 59 Medium, 1 Low) |
| **Module Index** | 12 modules (RT-001–015 checkout, RT-016–020 plans, … RT-114–126 settings) |
| **Lists** | Priority/status enums and execution-order hints → **tooling defaults**, not repo config |
| **References** | StudioCart documentation URLs by area |

### Codebase today

| Suite | Runner | Location | IDs today |
|-------|--------|----------|-----------|
| **Unit** | Codeception | `tests/codeception/Unit/` | Class/method names (no `UT-NNN` yet) |
| **Integration** | Codeception + WPLoader | `tests/codeception/Integration/` | Class/method names (no `IT-NNN` yet) |
| **Regression** | Playwright | `tests/playwright/regression/` | `RT-001`–`RT-019` (Ghost Inspector legacy) |

| Repo | Playwright specs | Notes |
|------|------------------|-------|
| `publishpress-cart` | 19 (`RT-001`–`RT-019`) | Ported from Ghost Inspector regression suite |
| `publishpress-cart-pro` | ~52 | Broader coverage; **same IDs can mean different things** |

**ID mismatch:** The spreadsheet `RT-001` is a broad scenario (“Purchase product using Stripe”). Playwright `RT-001` is a specific flow (“one-time Stripe first price”). The spreadsheet is a **planning catalog** for browser QA; Playwright IDs are **automation labels** inherited from Ghost Inspector.

We moved from Ghost Inspector to Playwright, with tests stored closer to the code. This plan adds a catalog layer for **all suites** — planning (including unimplemented cases), coverage tracking, and a local dashboard — without returning to a spreadsheet.

---

## Design principles

1. **Markdown as source of truth** — Case data lives in `.md` files. Scripts and the UI read and write those files directly. No database. No intermediate formats.
2. **Single repo config file** — `.tests.config.yaml` holds only what differs per plugin: suites, paths, modules, domain `fields`, references. Shared enums and new-case defaults live in the tooling.
3. **Plugin-agnostic import tooling** — `tests/tools/test-docs/` ships built-in defaults (`priority`, `status`, etc.) and import scripts. Config is read from `.scribe.config.yaml` (or legacy `.tests.config.yaml`). No hardcoded domain concepts (tax, Stripe, checkout).
4. **Extensions for domain data** — Plugin-specific case attributes (e.g. `tax_context`, `purchase_type`) are defined in `fields` in `.tests.config.yaml` and stored in case frontmatter `extensions`.
5. **Multi-suite** — One catalog holds cases for regression (`RT-*`), unit (`UT-*`), integration (`IT-*`), or any prefix defined in config.
6. **Local preview via `scribe`** — Browser UI for cases and docs via `@rambleventures/scribe-local` (`composer scribe`). Case and config edits go through `scribe` or the IDE. **`suites` are hand-edited only** (IDE, Cursor skill, or git).
7. **`.env` is not test config** — Host URLs, API keys, and local paths only.
8. **Editions (free/pro/both)** — Each case declares whether it applies to the free edition, pro edition, or both. Visibility and edit rights follow the repo's edition role (see *Editions*).
9. **Agents link cases ↔ test code; no auto-sync** — The `automation` block is maintained by agents (or by hand), since both test docs and test files are text. The tooling reads those links for coverage but never writes them automatically.

---

## Architecture

Three layers:

```
┌─────────────────────────────────────────┐
│  tests/tools/test-docs/    (generic)    │  defaults, import scripts, parsers
├─────────────────────────────────────────┤
│  .tests.config.yaml        (repo root)  │  plugin, suites, modules, fields, refs
├─────────────────────────────────────────┤
│  docs/testing/cases/                    │  test cases (MD + frontmatter)
└─────────────────────────────────────────┘
```

### What lives where

| Location | Contains |
|----------|----------|
| **`tests/tools/test-docs/schema/defaults.yaml`** | Shared across all plugins: `priority`, `status`, new-case defaults, generic execution-order hints |
| **`.tests.config.yaml`** | `plugin`, `catalog`, **`suites`** (hand-edit only), `modules`, `references`, `fields` (UI can edit the last three) |
| **`docs/testing/cases/<suite>/`** | One markdown file per catalog case, grouped by suite (`cases/regression/RT-001.md`) |
| **`.env`** | Secrets and runtime targets — **never** suite or catalog config |

`load-config.mjs` merges **tooling defaults** + **`.tests.config.yaml`**. Repo config can override defaults only when a plugin truly needs different enums (rare).

### Reuse model

| Path | Per repo? | Notes |
|------|-----------|-------|
| `tests/tools/test-docs/` | Shared (extractable) | Pilot: lives in cart; post-pilot: extract to shared package |
| `.tests.config.yaml` | **Yes** | Copy and edit when bootstrapping a new plugin |
| `docs/testing/cases/` | **Yes** | Case content |
| `docs/dev/testing/README.md` | **Yes** | Conventions; points at `.tests.config.yaml` |
| `.agents/skills/test-config/` | **Yes** | Copy with repo; suite/config editing for agents |
| `docs/dev/testing/regression-tests.md` | **Yes** | Operational runbook for browser regression |

Bootstrapping a new plugin = copy `.tests.config.yaml`, set `plugin` and **`suites`** by hand, then add `modules` / `fields` / `references` via UI or YAML. Priority/status enums come from the tooling.

---

## Tooling defaults (shared across plugins)

Shipped in `tests/tools/test-docs/schema/defaults.yaml`. Same for every plugin unless explicitly overridden in `.tests.config.yaml`.

```yaml
priorities: [critical, high, medium, low]
statuses: [pending, in-progress, blocked, passed, failed, skipped]

new_case:
  priority: medium
  status: pending

execution_order:
  - Critical path and core flows
  - High-risk pricing, security, and integration tests
  - Medium coverage and compatibility tests
  - Low-risk polish and localization
```

| Value | Why tooling, not repo config |
|-------|------------------------------|
| `priority` / `status` | Standard QA vocabulary; identical everywhere |
| `new_case.*` | Sensible defaults when creating a case in the UI |
| `execution_order` | Generic planning guidance for dashboard sorting |

**Optional override:** A repo may define `lists:` in `.tests.config.yaml` only if it needs extra statuses (e.g. `deferred`) or a different priority scale. `load-config.mjs` deep-merges over defaults. Most plugins omit this section entirely.

The old spreadsheet **Lists** sheet maps to these tooling defaults, not to per-repo config.

---

## Repo config: `.tests.config.yaml`

Single committed file at the repository root. All tooling (test-docs server, sync scripts, CI checks) reads this file.

### Example (PublishPress Cart)

```yaml
version: 1

plugin:
  id: publishpress-cart
  name: PublishPress Cart
  display_name: StudioCart QA Test Suite
  edition: free                        # this repo's role: free | pro | standalone

catalog:
  cases: docs/testing/cases          # cases live under per-suite subdirs: cases/<suite>/

modules:
  - id: core-checkout
    name: Core checkout and product basics
    suites: [regression]
    test_range: RT-001–RT-015
    notes: Core checkout, validation, payment failures, logged-in/guest behavior.

  - id: taxes-vat
    name: Taxes and VAT
    suites: [regression]
    test_range: RT-041–RT-051
    notes: Tax/VAT calculation, address changes, coupon interactions.

  - id: stripe-sync
    name: Stripe sync handlers
    suites: [integration]
    test_range: IT-001–IT-020

references:
  - area: Docs index
    url: https://www.studiocart.co/docs/
    notes: Used to identify documentation sections and coverage areas.

  - area: Products / coupons
    url: https://www.studiocart.co/docs/products/coupon-management-pro-only/
    notes: Coupon fields, URL coupons, discount types, duration.

fields:
  purchase_type:
    label: Purchase type
    type: enum
    values: [one-time, subscription, free, custom-price]
    filter: true
    suites: [regression]

  tax_context:
    label: Tax context
    type: enum
    values: [none, tax-inclusive, tax-exclusive, vat]
    filter: true
    suites: [regression]

  gateway:
    label: Payment gateway
    type: enum
    values: [stripe, paypal, cod, none]
    filter: true
    suites: [regression]

suites:
  regression:
    label: Browser regression
    description: End-to-end checkout flows against a real WordPress site
    prefix: RT
    id_format: '{prefix}-{number:03d}'
    runner: playwright
    roots:
      - tests/playwright
    spec_glob: '**/*.spec.ts'
    id_pattern: 'RT-\\d{3}'
    discovery:
      title: true
      filename: true
    run:
      composer: test:regression
      npm: test:e2e

  unit:
    label: Unit tests
    description: PHP unit tests without WordPress bootstrap
    prefix: UT
    id_format: '{prefix}-{number:03d}'
    runner: codeception
    suite: Unit
    roots:
      - tests/codeception/Unit
    file_glob: '**/*Test.php'
    id_pattern: 'UT-\\d{3}'
    discovery:
      docblock: true
      group: true
    run:
      composer: test:unit

  integration:
    label: Integration tests
    description: PHP tests with WordPress + WPLoader
    prefix: IT
    id_format: '{prefix}-{number:03d}'
    runner: codeception
    suite: Integration
    roots:
      - tests/codeception/Integration
    file_glob: '**/*Test.php'
    id_pattern: 'IT-\\d{3}'
    discovery:
      docblock: true
      group: true
    run:
      composer: test:integration
```

### Top-level sections (repo config)

| Section | Purpose | Editable in UI |
|---------|---------|----------------|
| `plugin` | Plugin id, display name (for UI header) | No — hand-edit |
| `catalog` | Path to case files | No — hand-edit |
| `suites` | Prefix, runner, roots, discovery, run commands | **No — hand-edit only** |
| `modules` | Coverage groupings; optional `suites` scope and `test_range` | **Yes** — add, edit, delete |
| `references` | External doc URLs for traceability | **Yes** — add, edit, delete |
| `fields` | Extension field schema; drives filters and case forms | **Yes** — add, edit, delete |
| `lists` | *(optional)* Override tooling defaults for priority/status | No — rare; hand-edit |

Not in repo config: `priority`, `status`, and new-case defaults — those come from `tests/tools/test-docs/schema/defaults.yaml`.

### Suites (hand-edit only)

`suites` define runners, prefixes, and scan paths — structural test infrastructure. Changing them incorrectly breaks discovery and CI. **Configure only in `.tests.config.yaml` in the IDE**, not in the web UI.

The UI **reads** suites (dropdowns when creating cases, dashboard filters, suite switcher) but never writes them. Adding or removing a suite is a hand-edited change — use the **test-config** Cursor skill or edit `.tests.config.yaml` directly.

### Suite config fields

| Field | Required | Purpose |
|-------|----------|---------|
| `label` | yes | Human name in UI |
| `prefix` | yes | ID prefix (`RT`, `UT`, `IT`, …) |
| `id_format` | yes | `{prefix}` and `{number:Nd}` placeholders |
| `runner` | yes | `playwright`, `codeception`, or future runners |
| `roots` | yes | Directories to scan |
| `id_pattern` | yes | Regex to extract IDs from source |
| `discovery` | no | Per-runner ID discovery rules |
| `run` | no | Composer/npm command names |
| `inherit_free` | no | Playwright only; pro repo runs the free suite's specs too (see *Editions*) |

Adding a suite (e.g. `smoke`, prefix `SM`) is a hand-edited config change — see **Suites (hand-edit only)** above.

### Filename

Use **`.scribe.config.yaml`** at the repo root (alongside `.editorconfig`).
Document in `docs/dev/testing/README.md`. Legacy `.tests.config.yaml` is still
read as a fallback when present.

---

## Proposed layout

```
.scribe.config.yaml           # Plugin-specific: suites, modules, fields, features

docs/testing/
  schema.md                   # Scribe v2 case schema (twin of scribe-schemas skill)
  cases/
    regression/
      RT-001.md
      ...
    unit/
      UT-001.md
      ...
    integration/
      IT-001.md
      ...

docs/dev/schemas/
  features.md                 # Feature catalog schema (twin of scribe-schemas skill)

tests/tools/test-docs/
  schema/
    defaults.yaml             # Shared: priority, status, new-case defaults
  README.md
  lib/
    load-config.mjs           # Merge defaults.yaml + repo config
    parse-cases.mjs           # Read case .md (frontmatter + sections)
    write-case.mjs            # Serialize case → .md on disk
    import-spreadsheet.mjs    # Spreadsheet / HTML import helpers
  profiles/
    publishpress-cart/
      column-map.yaml
      source/                 # Master Test Suite HTML exports
  scripts/
    import-spreadsheet.mjs
    import-rt-cases.mjs

.agents/skills/scribe-schemas/
  references/testing-schema.md
  references/features-schema.md

.agents/skills/test-config/
  SKILL.md                    # Create/edit .scribe.config.yaml; add/remove suites
```

Do **not** add `docs/testing/README.md` — `scribe doctor` treats that stem as a
prose index that would be ingested as a catalog record. Put overview prose in
`docs/dev/testing/README.md` instead. No `docs/testing/_config/`. No duplicate
YAML elsewhere in the repo.

---

## Core schema (case markdown)

Canonical Scribe **v2** field rules live in
[docs/testing/schema.md](../../testing/schema.md) (twin of the
`scribe-schemas` skill). Required non-derivable front matter is `suite` and
`priority`. `id` and `plugin` are derived from the filename and repo config.
Implementation status is derived from `automation.implementations` — do not
store `status` or `last_verified` on the case.

Feature catalog rules: [docs/dev/schemas/features.md](../schemas/features.md).

---

## Case frontmatter examples

Regression case with domain extensions (v2 — omit derived `id`):

```yaml
---
suite: regression
edition: both
module: taxes-vat
features: [taxes-vat]
priority: high
tags: [stripe, tax]
extensions:
  purchase_type: subscription
  tax_context: vat-inclusive
  gateway: stripe
automation:
  implementations: []
---

# Tax calculation with VAT on subscription

## Scenario
...

## Steps
1. ...

## Expected result
...

## Edge cases
- ...
```

Unit case (no extensions required):

```yaml
---
suite: unit
module: download
features: [secure-downloads]
priority: high
automation:
  implementations:
    - suite: unit
      file: tests/codeception/Unit/Download/DownloadPathValidationTest.php
      method: testRejectsPathTraversal
---
```

Extension keys must match `fields` in `.scribe.config.yaml`. The UI validates
and renders filters from that schema.

---

## Linking catalog ↔ test code (agent-driven)

Both the catalog (`.md`) and the test files (`.spec.ts`, `*Test.php`) are text, so an **agent** maintains the link rather than an auto-sync script. Given a case or a test file, an agent reads both sides and updates the case's `automation` block:

```yaml
automation:
  status: implemented      # none | partial | implemented
  implementations:
    - suite: regression
      file: tests/playwright/regression/rt001-....spec.ts
```

The tooling **reads** these links for coverage display in `scribe`; it never writes them. No `test:docs:sync` in the pilot.

Agents (and the optional read-only check) recognize IDs via:

| Runner | Where the ID appears |
|--------|----------------------|
| **playwright** | `test('RT-001 ...')` title; filename `rt001-*.spec.ts` |
| **codeception** | `@test-id UT-001` docblock; `@group ut-001` |

**Coverage metrics (read from frontmatter):**

| Metric | Meaning |
|--------|---------|
| Planned | Catalog cases for that suite/edition |
| Automated | Cases with `automation.status: implemented` |
| Passing | From last ingested run report (read-only) |
| Gap | Planned − automated |

**Conformance check:** run `composer scribe:doctor` to list catalog issues (duplicate ids, invalid priority, index docs ingested as records, etc.).

---

## Editions: free / pro / both

Several plugins ship as a free/pro pair. The catalog encodes this with a per-case `edition` field and a repo `edition` role.

### Case field

`edition: free | pro | both` — defaults to `both` for cases authored in a free repo, `pro` for cases authored in a pro repo.

### Repo roles (`plugin.edition`)

```yaml
# free repo
plugin:
  id: publishpress-cart
  edition: free

# pro repo
plugin:
  id: publishpress-cart-pro
  edition: pro
  free_source:
    # The free plugin is a Composer dependency (lib/composer.json). Its package
    # ships tests + docs (stripped from release builds), so they land under the
    # vendored copy on `composer install` — no clone, no branch detection.
    composer_package: publishpress/publishpress-cart   # → lib/vendor/publishpress/publishpress-cart
    # path: ../publishpress-cart   # optional: use a working clone (e.g. to test a different free branch)
  free_overrides:                     # pro-local; does NOT edit free files
    - id: RT-021
      edition: free                   # treat this free case as free-only (out of pro scope)
```

### Visibility and edit rules

| Repo | Sees | Can edit |
|------|------|----------|
| **Free** (`edition: free`) | Own cases (`free` / `both`) | Own cases |
| **Pro** (`edition: pro`) | Own pro cases **+** free cases from the resolved `free_source` | Own pro cases only; free cases are **read-only** |

- All free-repo cases default to `both`, so they count toward both editions.
- Pro can exclude a free case from pro scope via `free_overrides` (mark it `free`). This is stored **in the pro repo**, never by editing free files.
- Effective pro catalog = pro cases + free `both`-cases − `free_overrides` marked `free`.

### Resolving the free source (Composer-vendored, recommended)

The free plugin is already installed by Composer at `lib/vendor/publishpress/publishpress-cart`. We make its package **ship the test docs + Playwright specs**, so pro gets them on `composer install` — no separate clone, no branch detection, always consistent with the pinned dependency (`publishpress/publishpress-cart: dev-development` today).

**Why this is safe:** the free plugin's release zip already excludes `tests/` and `docs/` via `.distignore` **and** `.rsync-filters-pre-build`. The `export-ignore` entries in the free plugin's `.gitattributes` are therefore the *only* reason those folders are missing from the **Composer dist** (Composer's `dist` install uses a `git archive`, which honors `export-ignore`).

**One-time change in the free plugin** — remove these lines from `.gitattributes` so the Composer dist carries them (release builds stay clean via the filters above):

```diff
- /docs export-ignore
- tests export-ignore
```

**Strip on pro deploy** — add the vendored test docs to pro's release filters so they never ship in the pro zip:

```
# pro .distignore  AND  pro .rsync-filters-pre-build
/lib/vendor/publishpress/publishpress-cart/tests
/lib/vendor/publishpress/publishpress-cart/docs
```

This mirrors the existing pattern where pro already cleans the vendored free plugin (`post-update-cmd: rm -rf vendor/publishpress/publishpress-cart/lib/vendor`).

**Resolution:** free source = `free_source.path` if set (a working clone, e.g. to test a different free branch); otherwise the vendored copy `lib/vendor/{composer_package}`. The same location serves both needs — the UI reads free **cases** from `{free}/docs/testing/cases`, and Playwright runs free **specs** from `{free}/tests/playwright`.

> **Leaner alternative:** ship only `tests/playwright` + `docs/testing` instead of all of `tests`/`docs`. Git `export-ignore` cannot re-include a child of an ignored directory, so do this by ignoring siblings (e.g. `tests/codeception export-ignore`, `tests/legacy export-ignore`) rather than negation.

### Required build/release changes (editions) — must be documented

These repo changes are prerequisites for the pro repo to consume free specs/cases. They affect packaging, so they **must also be recorded in each repo's build/release docs** (e.g. `README-build.md`) — not only here — so a future release change does not silently re-break them.

**Free plugin (`publishpress-cart`):**

- [ ] Remove `/docs export-ignore` and `tests export-ignore` from `.gitattributes` (lets the Composer dist carry them).
- [ ] Confirm the release zip still excludes `tests/` and `docs/` via `.distignore` **and** `.rsync-filters-pre-build` (they do today). Add a release-build check/test so this stays true.
- [ ] Note in `README-build.md`: "tests/docs are intentionally shipped in the Composer dist but stripped from the release zip."

**Pro plugin (`publishpress-cart-pro`):**

- [ ] Add to `.distignore` **and** `.rsync-filters-pre-build`:
  - `/lib/vendor/publishpress/publishpress-cart/tests`
  - `/lib/vendor/publishpress/publishpress-cart/docs`
- [ ] Confirm the shipped pro zip contains no vendored `tests/`/`docs/` (release-build check).
- [ ] Note the dependency in `README-build.md`: pro relies on the free package shipping tests/docs via Composer.

**Verification:** after the `.gitattributes` change, `composer update publishpress/publishpress-cart` in pro should populate `lib/vendor/publishpress/publishpress-cart/tests/playwright` and `.../docs/testing`. A built pro zip should contain neither.

### Running free Playwright specs from the pro repo

Pro is a superset of free, so free **browser** flows should still pass on a pro install. Only **Playwright** suites are shared cross-repo (not unit/integration). Any pro suite with `runner: playwright` can opt in with `inherit_free: true`; the pro `playwright.config.ts` then adds a project whose `testDir` is the resolved free source (vendored copy by default):

```ts
const freeDir = process.env.TEST_DOCS_FREE_DIR; // resolved free_source: path | lib/vendor/<package>
projects: [
  { name: 'pro-regression', testDir: './tests/playwright' },
  ...(freeDir ? [{ name: 'free-regression', testDir: `${freeDir}/tests/playwright` }] : []),
]
```

Free spec files keep their relative `../support/...` imports (they resolve inside the vendored copy) and run against the pro site/fixtures via the shared `.env`.

**To design before building (let's discuss):**

- **ID/title collisions** — free `RT-001` and pro `RT-001` differ. Use distinct project names (above) and/or tags so reports don't merge them.
- **Fixtures/env parity** — pro setup must create the checkout pages/products the free specs expect (or reuse the free `setup-site` flow).
- **Dependency availability** — free specs must import only `@playwright/test` + their own support files (already the case); anything else must exist in pro's `node_modules`.
- **Selective runs** — likely want `--project=free-regression` to run only inherited free flows.
- **Freshness** — the vendored specs reflect the last `composer update`; re-run it to pull newer free flows.

---

## Local web UI (removed — use `scribe`)

> **Superseded.** The pilot Node server and React UI under `tests/tools/test-docs/` were removed. Use **`composer scribe`** for local preview and editing. Bulk RT import remains via `composer test:docs:import-rt`.

The section below describes the removed pilot UI for historical reference.

### What the UI can edit

| Target | Storage | Operations |
|--------|---------|------------|
| **Cases** | `docs/testing/cases/<suite>/*.md` | Create, edit |
| **Modules** | `.tests.config.yaml` → `modules` | Add, edit, delete |
| **References** | `.tests.config.yaml` → `references` | Add, edit, delete |
| **Fields** | `.tests.config.yaml` → `fields` | Add, edit, delete |
| **Suites** | `.tests.config.yaml` → `suites` | **Read-only** — hand-edit in IDE |
| **Plugin / catalog paths** | `.tests.config.yaml` | **Read-only** — hand-edit in IDE |
| **Automation links** | Case frontmatter | **Read-only** in UI — agents maintain this (no auto-sync) |

### Views

| View | Editable |
|------|----------|
| Dashboard | No |
| Test list | Optional inline status/priority |
| Test detail | Link to case editor |
| Case editor | **Yes** — create / update cases |
| Modules manager | **Yes** — CRUD modules |
| References manager | **Yes** — CRUD doc links |
| Fields manager | **Yes** — CRUD extension field definitions |
| Coverage gap / orphans | No |

### Case editor

- **Frontmatter:** `suite` (from read-only `suites`), `module`, `priority`, `status`, `tags`, `extensions.*`, `refs`, `notes`
- **Body:** Scenario, Steps, Expected result, Edge cases
- **Read-only:** `id` (set at create), `automation` (sync-owned)

On save, `write-case.mjs` rewrites `{catalog.cases}/{id}.md`.

### Modules, references, and fields managers

Simple CRUD screens backed by the API below. On save, `write-config.mjs`:

1. Loads `.tests.config.yaml`
2. Replaces only the target section (`modules`, `references`, or `fields`)
3. Writes back preserving `plugin`, `catalog`, `suites`, comments, and key order

**Round-trip safety (required):** `write-config.mjs` MUST use a comment-preserving YAML round-trip (e.g. the `eemeli/yaml` `Document` API), not parse-then-stringify. A test must assert that a UI write to `modules` / `references` / `fields` leaves `suites` and all comments intact. Hand-authored suite config and inline notes are infrastructure — corrupting them is the main risk of the single-file design.

**Delete guards:**

- **Module:** warn or block if cases still reference that `module` id
- **Field:** warn if cases use that key under `extensions`; do not strip existing case data automatically
- **Reference:** safe to delete (informational only unless linked from case `refs`)

### API (v1)

| Method | Path | Purpose |
|--------|------|---------|
| `GET` | `/api/config` | Merged config (defaults + full `.tests.config.yaml`) |
| `GET` | `/api/cases` | List/filter cases |
| `GET` | `/api/cases/:id` | One case |
| `POST` | `/api/cases` | Create case |
| `PUT` | `/api/cases/:id` | Update case |
| `GET` | `/api/modules` | List modules |
| `POST` | `/api/modules` | Add module |
| `PUT` | `/api/modules/:id` | Update module |
| `DELETE` | `/api/modules/:id` | Delete module |
| `GET` | `/api/references` | List references |
| `POST` | `/api/references` | Add reference |
| `PUT` | `/api/references/:index` | Update reference (or use stable id) |
| `DELETE` | `/api/references/:index` | Delete reference |
| `GET` | `/api/fields` | List field schema |
| `POST` | `/api/fields` | Add field |
| `PUT` | `/api/fields/:key` | Update field |
| `DELETE` | `/api/fields/:key` | Delete field |
| `GET` | `/api/check` | Optional read-only orphan/coverage report (no writes) |
| `GET` | `/api/summary` | Dashboard stats |

`PUT`/`DELETE` on `/api/suites` — **not implemented** (returns 405 or route absent).

Validation: `priority` / `status` against tooling defaults; `module` / `extensions` against current `modules` / `fields`; new field keys must be unique slugs.

### Two ways to maintain data

| Approach | When to use |
|----------|-------------|
| **Web UI** | Cases, modules, references, fields day-to-day |
| **Edit files directly** | `suites`, `plugin`, `catalog`; bulk case edits; AI-assisted changes |

Refresh the UI after external file edits (or use a file watcher).

### Commands

Run import and preview through **Composer**:

```bash
composer test:docs:import-rt    # Master Test Suite HTML → RT cases
composer test:docs:import       # legacy spreadsheet import
composer scribe                 # local catalog UI (replaces legacy test-docs server)
composer scribe:doctor          # catalog conformance check
```

Add to `composer.json` `scripts`:

```json
"scripts": {
  "test:docs:import": "npm run test:docs:import",
  "test:docs:import-rt": "npm run test:docs:import-rt",
  "scribe": "npm run scribe",
  "scribe:doctor": "npm run scribe:doctor"
}
```

Matching `package.json` `scripts`:

```json
"scripts": {
  "test:docs:import": "node tests/tools/test-docs/scripts/import-spreadsheet.mjs",
  "test:docs:import-rt": "node tests/tools/test-docs/scripts/import-rt-cases.mjs",
  "scribe": "scribe dev",
  "scribe:doctor": "scribe doctor"
}
```

Optional config path override:

```bash
TEST_DOCS_CONFIG=/path/to/.scribe.config.yaml composer test:docs:import-rt
```

Default: `.scribe.config.yaml` or `.tests.config.yaml` in the repository root.

---

## Test ID strategy

### Suite prefixes (from config)

| Suite | Default prefix | Example |
|-------|----------------|---------|
| regression | `RT` | `RT-001` |
| unit | `UT` | `UT-001` |
| integration | `IT` | `IT-001` |

### Spreadsheet vs Playwright (`RT-*`)

| Option | Pros | Cons |
|--------|------|------|
| **A. Spreadsheet IDs canonical** | Matches planning doc | Requires remapping Playwright over time |
| **B. Two namespaces** | No rename churn | Mapping table needed |
| **C. New catalog IDs** | Zero Playwright change | Import must renumber |

**Recommendation:** **A long-term, B short-term** for regression. Unit/integration adopt `UT-*` / `IT-*` from the start.

### PHP test IDs

```php
/**
 * @test-id UT-014
 * @group download
 */
class DownloadPathValidationTest extends Unit
```

---

## Run reports (deferred)

Pilot scope is **catalog only** — case specs, coverage planning, and `automation` links. Ingesting CI run JSON into the repo (pass/fail per case) is deferred until we need run status in the catalog UI.

---

## Spreadsheet import

Importer profile maps spreadsheet columns to case frontmatter and `extensions` keys defined in `.tests.config.yaml`:

`tests/tools/test-docs/profiles/publishpress-cart/column-map.yaml`

Targets suite `regression` and prefix from `suites.regression.prefix`.

---

## Phased implementation

### Phase 0 — Conventions

- Finalize `.tests.config.yaml` schema
- Add **`.agents/skills/test-config/SKILL.md`** for suite/config edits (IDE + agent)
- Agree regression ID strategy; adopt `UT-*` / `IT-*` for new cases
- Document in `docs/dev/testing/README.md` (hand-edit vs UI edit)

### Phase 1 — Config + migration

- Add `.tests.config.yaml` (suites, modules, fields, references — no `lists`)
- Add `tests/tools/test-docs/schema/defaults.yaml`
- `parse-cases.mjs` / `write-case.mjs` (round-trip markdown)
- Import spreadsheet → `docs/testing/cases/regression/RT-*.md`
- Manual pass on RT-001–019 mapping

### Phase 2 — Optional checks

- `check-orphans.mjs` — optional read-only coverage/orphan report (no writes)
- Agents maintain `automation` links in case frontmatter (no auto-sync)

### Phase 3 — Local UI (read + write)

- Dashboard, test list, detail view, suite switcher (suites read-only)
- Case editor: create/update → `.md`; `edition` selector
- **Editions:** filter by edition; in a pro repo, read free cases from `free_source` (read-only) and apply `free_overrides`
- **Modules, references, fields managers** → comment-safe partial `.tests.config.yaml` update
- `write-config.mjs` with delete guards
- `automation` read-only in case editor (agents own it)

> Editions also require the build/release changes in *Required build/release changes (editions)* and updates to each repo's `README-build.md`. These are pro-repo prerequisites — do them before wiring `inherit_free` Playwright runs.

### Phase 4 — CI

- Validate `.tests.config.yaml` (JSON Schema optional)
- Optional orphan checks; PR coverage comment

### Future (post-pilot)

If the pilot succeeds:

- **Extraction:** move `tests/tools/test-docs/` to a shared package/engine; repos keep `.tests.config.yaml` + cases.
- **Central platform (roadmap Phase 2):** a service that reads/edits test docs *and* test files across repos and branches, with a fleet view across all 10+ plugins. See *Pilot scope and future direction*.

**Worktree:** `feature/test-docs` off `development`.

**Out of scope for the pilot:** auto-sync of `automation` links (agents do this); suite / plugin / catalog path editing in the UI; spreadsheet-style grid bulk edit; collaborative/multi-user editing; multi-plugin aggregation and the central platform.

---

## Add to new plugin (checklist)

1. Copy `.tests.config.yaml`; set `plugin`, `catalog`, and **`suites`** — follow test-config skill
2. Add `modules`, `fields`, `references` via UI or YAML
3. Add cases under `docs/testing/cases/`
4. Optionally add `@test-id` docblocks to PHP tests
5. `composer scribe`

Copy the skill with the repo: `.agents/skills/test-config/` (portable across plugins).

---

## What not to do (yet)

- Nested config under `docs/testing/_config/` or elsewhere
- Duplicate suite or field definitions in multiple files
- Store suite prefixes or paths in `.env`
- JSONL or other parallel data formats (markdown is the only case store)
- SQLite or embedded database
- Suite editor in the web UI (`suites` — use test-config skill instead)
- Spreadsheet-style grid bulk edit in the UI
- Hardcoding domain fields in `tests/tools/test-docs/` source

---

## Open decisions

1. **Config filename:** `.tests.config.yaml` vs `tests.config.yaml`?
2. **Regression ID strategy:** A, B, or C?
3. **Unit/integration IDs:** Planning-only first, or `@test-id` on all new PHP tests?
4. ~~**Case files:** Flat `cases/RT-001.md` vs `cases/regression/RT-001.md`?~~ **Decided:** per-suite subdirs — `cases/<suite>/RT-001.md`.
5. ~~**Run reports:** Commit per-suite `latest.json` or local-only?~~ **Decided:** deferred — catalog only for pilot.
6. **Config validation:** JSON Schema for `.tests.config.yaml` in CI?
7. **Free Playwright in pro:** project-per-edition vs tag-based separation, and how to namespace colliding IDs (see *Editions → Running free Playwright specs from the pro repo*).
8. **`free_overrides` location:** inline in pro `.tests.config.yaml` (as shown) vs a separate pro-side overrides file?
9. **Free package payload:** ship all of `tests`/`docs` in the free Composer dist (simplest) vs only `tests/playwright` + `docs/testing` (leaner); confirm the free release builder excludes them (it does today via `.distignore` + rsync filters).

---

## Cursor skill: test config

Agents and developers use **`.agents/skills/test-config/SKILL.md`** when creating or editing `.tests.config.yaml`, especially:

- Bootstrapping test-docs on a new plugin
- **Adding or removing test suites** (prefix, runner, roots, composer wiring)
- Changing `plugin` or `catalog` paths
- Bulk-editing `modules`, `fields`, or `references` in YAML

The skill includes Playwright and Codeception suite templates, remove-suite checklists, and links to references/schema.md.

**Trigger terms:** `.tests.config.yaml`, test config, add test suite, remove suite, test-docs bootstrap, ID prefix, catalog config.

Suites are **not** editable in the web UI — the skill is the supported agent path for suite changes.

---

## Relationship to existing docs

| Document | Role |
|----------|------|
| `.tests.config.yaml` | `suites` hand-edit; `modules`, `references`, `fields` UI or hand-edit |
| `.agents/skills/test-config/SKILL.md` | Agent/dev guide: config + add/remove suites |
| `tests/tools/test-docs/schema/defaults.yaml` | Shared: priority, status, new-case defaults |
| `docs/dev/testing/regression-tests.md` | Operational: env, setup, browser regression |
| `tests/README.md` | Composer suite commands |
| `docs/dev/testing/test-documentation-plan.md` | This plan |
| `docs/dev/testing/README.md` | How to edit cases; points at `.tests.config.yaml` |
| `tests/tools/test-docs/README.md` | Generic tool usage |
