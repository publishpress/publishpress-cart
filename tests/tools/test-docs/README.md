# Test case import scripts

One-off import utilities for the markdown test catalog. Local preview and
editing use **`scribe`** (`npm run scribe` or `composer scribe`).

## Import commands

From the repository root:

```bash
composer test:docs:import-rt              # Master Test Suite HTML → RT cases
composer test:docs:import-rt -- --dry-run # preview counts only
composer test:docs:import                 # legacy spreadsheet import (TC-* IDs)
```

NPM equivalents: `npm run test:docs:import-rt`, `npm run test:docs:import`.

## Config

- **Tooling defaults:** `schema/defaults.yaml` (priority, status, new-case defaults)
- **Repo config:** `.scribe.config.yaml` or `.tests.config.yaml` at repository root
- **Override:** `TEST_DOCS_CONFIG=/path/to/.scribe.config.yaml composer test:docs:import-rt`

## Profiles

Cart-specific column maps and source spreadsheets live under
`profiles/publishpress-cart/`. See
[profiles/publishpress-cart/README.md](profiles/publishpress-cart/README.md).

## After import

Run `composer scribe:doctor` to check catalog conformance, then `composer scribe`
to review cases in the local Scribe UI.
