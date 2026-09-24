# Feature catalog schema

Twin of the agent skill reference
[`node_modules/@rambleventures/scribe/dist/agent-skills/scribe-schemas/references/features-schema.md`](../../../node_modules/@rambleventures/scribe/dist/agent-skills/scribe-schemas/references/features-schema.md).
Keep both in sync when schema rules change. Plugin agents follow
`.agents/skills/scribe-schemas/SKILL.md`,
which points at the installed package.

Canonical definition for **product features** in plugin repos.

Features are short, machine-readable records — not long-form documentation. Put
user-facing detail in `docs/public/` and engineering detail in `docs/dev/`.

## Where features live

| Source | Role |
|--------|------|
| `features:` block in `.scribe.config.yaml` | **Canonical** product capability catalog |
| `docs/README.md` | Optional index prose only |

**Do not** create `docs/features/<id>.md` when the repo config defines a
`features:` block. `scribe doctor` reports those files as `feature_md_orphan`.

**Features** = product capabilities customers care about.
**Modules** = engineering groupings for tests (`modules:` in config, `module:` on
cases). Never create `docs/modules/`.

## YAML shape

Map keyed by feature id (same pattern as `suites:`):

```yaml
features:
  core-checkout:
    name: Core checkout
    status: active
    description: One-line summary for tables and search
    source_areas:
      - public/checkout/
    edition: both   # optional: free | pro | both
```

### Required fields (per feature)

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Short human title |
| `status` | enum | `active` \| `deprecated` \| `planned` |

The map key is the feature **id** (stable slug). Do not duplicate `id:` inside
the entry.

### Recommended fields

| Field | Type | Description |
|-------|------|-------------|
| `description` | string | One line — not a markdown essay |
| `source_areas` | string[] | Repo-relative code paths for drift checks (`docs.validate_source_areas`) |
| `edition` | enum | `free` \| `pro` \| `both` when edition-specific |

## Linking from other docs

Test cases and dev/public docs link features via front matter:

```yaml
features:
  - core-checkout
  - subscriptions
```

When the repo config declares `features:`, `scribe doctor` validates that each
referenced id exists in the map.

Do **not** list covering tests inside the feature definition — coverage is
computed from links on cases and docs.
