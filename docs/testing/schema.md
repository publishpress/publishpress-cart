# Testing documentation schema

Twin of the agent skill reference
[`node_modules/@rambleventures/scribe/dist/agent-skills/scribe-schemas/references/testing-schema.md`](../../node_modules/@rambleventures/scribe/dist/agent-skills/scribe-schemas/references/testing-schema.md).
Keep both in sync when schema rules change. Plugin agents follow
`.agents/skills/scribe-schemas/SKILL.md`,
which points at the installed package.

Canonical format for test case markdown in plugin repos (Scribe v2).

## Schema versions

| Version | Status | Notes |
|---------|--------|-------|
| v1 | Historical | Required `id`, `plugin`, `suite` (runner enum), `priority`, `last_verified` |
| **v2** | **Current** | Required fields are non-derivable only |

## Source of truth

Each **record** is one markdown file under the repo's declared `catalog.cases`
path in `.scribe.config.yaml`. No database, no parallel formats.

**Records vs indexes:** only files under `catalog.cases` are catalog records.
Everything else under `docs/testing/` — `README.md`, plans, summaries — is
prose/index content. Scribe does not ingest index docs as test cases.

## File structure

```markdown
---
# YAML front matter (required fields below)
---

# Title (H1)

## Scenario
...

## Steps
1. ...

## Expected result
...

## Edge cases
- ...
```

Body section headings are fixed. Optional sections may be empty but headings
should remain for tooling consistency.

## Required front matter (v2)

A required field must be **non-derivable** — irreducible human judgment that
cannot be inferred from filename, repo config, or file path.

| Field | Type | Description |
|-------|------|-------------|
| `suite` | string | Repo-local suite key (e.g. `unit`, `integration`, `regression`). Must be a key declared in the repo's `suites` config. **Not** a test runner. |
| `priority` | enum | `critical` \| `high` \| `medium` \| `low` |

### Suite values

`suite` is a **repo-local vocabulary**, not a runner identifier. Each repo
declares valid keys under `suites` in `.scribe.config.yaml`. Scribe validates
case front matter against those keys only.

Example:

| Config key | Typical prefix | Runner (repo tooling only) |
|------------|----------------|----------------------------|
| `unit` | `UT-` | codeception |
| `integration` | `IT-` | codeception |
| `regression` | `RT-` | playwright |

The runner lives in per-suite config (`suites.<key>.runner`). Scribe does not
model runners on case front matter.

## Derived fields (optional overrides)

These values are computed at build time. Authors may supply them only when the
default derivation is wrong.

| Field | Derived from | Override when |
|-------|--------------|---------------|
| `id` | Filename stem (`UT-014.md` → `UT-014`) | Filename cannot match the canonical id |
| `plugin` | Repo config `plugin` | Multi-plugin repo or exceptional case |

Do not hand-type derivable metadata in front matter.

This repo’s Playwright generator (`tests/tools/test-docs`) omits derived `id`
and does not store `automation.status`. Catalog-sync reports `status_drift` only
when a stored status is present and not `implemented`. `scribe doctor` derives
`id` from the filename stem when front matter omits it — do not add stored `id`
or `automation.status` to satisfy doctor.

### Dropped in v2

| Field | Reason |
|-------|--------|
| `last_verified` | Hand-typed dates drift |
| `runner` | Belongs in `suites.<key>.runner`, not case front matter |

## Recommended front matter

| Field | Type | Description |
|-------|------|-------------|
| `module` | string | Module id from per-repo config |
| `edition` | enum | `free` \| `pro` \| `both` |
| `automation` | object | Links to test implementations — see below |
| `features` | string[] | Product feature ids from config `features:` |
| `refs` | object[] | Per-case documentation URLs |
| `tags` | string[] | Free-form labels for filtering |

## Status

`not-implemented` · `implemented`

Derived from `automation.implementations` — not stored in front matter. A case is
`implemented` when the first implementation entry has a `file` and, for
codeception / jest suites, a `method`. Playwright implementations may omit
`method` when `file` points at a `.spec.ts` path.

## Automation

```yaml
automation:
  implementations:
    - suite: unit
      file: tests/codeception/Unit/Example/ExampleTest.php
      method: test_UT_001_example_behavior
```

The `suite` key on each implementation entry refers to the **repo-local suite
key** (same vocabulary as case front matter), not a runner name.

## Extensions

Plugin-specific attributes may be declared in per-repo config and stored under
`extensions`:

```yaml
extensions:
  purchase_type: subscription
  gateway: stripe
```

## Example (v2)

```yaml
---
suite: unit
edition: free
module: debug-log-viewer
priority: medium
features:
  - debug-log-viewer
automation:
  implementations:
    - suite: unit
      file: tests/codeception/Unit/DebugLogViewer/DebugLogViewerTest.php
      method: test_UT_066_debug_log_rotates_when_max_size_is_exceeded
---

# Debug Log Rotates When Max Size Is Exceeded

## Scenario

The logger rotates the active file when it exceeds the configured max size.

## Steps

1. Write enough log lines to exceed the cap.
2. Check the filesystem for the rotated archive file.

## Expected result

The rotated archive file exists alongside the active log.

## Edge cases

- Below-cap writes should not trigger rotation.
```

Note: `id` and `plugin` are omitted — derived from filename and repo config.

## CI enforcement

Run conformance locally with `composer scribe:doctor` or `npm run scribe:doctor`.
Checks include:

- Required front matter present; `suite` matches a declared config key;
  `priority` is a valid enum
- `id` unique across records; matches filename stem when present
- Body sections present (`## Scenario`, `## Steps`, `## Expected result`,
  `## Edge cases`)
- Index docs under `docs/testing/` are not treated as records

PR CI does not currently run `scribe doctor`. This repo does not yet define
`docs.ci_strict` in `.scribe.config.yaml`. Scribe sync never refuses to publish
on conformance failures.
