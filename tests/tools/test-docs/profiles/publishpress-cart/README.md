# PublishPress Cart spreadsheet import profile

Place Google Sheets HTML exports here:

- `Master Test Suite.html` — required (126 TC-* planning rows → RT-* catalog cases)
- `Module Index.html` — optional reference for module ranges

Source: StudioCart QA Master Test Suite spreadsheet.

## Replace regression catalog (RT-*)

From repo root:

```bash
composer test:docs:import-rt
```

Dry run:

```bash
composer test:docs:import-rt -- --dry-run
```

This **replaces** all `docs/testing/cases/regression/RT-*.md` files. Spreadsheet `TC-NNN` rows map to `RT-NNN` (same number). Does not merge with existing cases or preserve `automation` links.

Then check conformance:

```bash
composer scribe:doctor
```

## Legacy TC-* import (deprecated)

```bash
composer test:docs:import
node tests/tools/test-docs/scripts/import-spreadsheet.mjs --force
```

Writes `TC-*.md` IDs. Prefer `test:docs:import-rt` for regression catalog sync.

## Column map

Field mappings and module ranges: [column-map.yaml](column-map.yaml). When module ranges change, also update `modules` in `.scribe.config.yaml`.
