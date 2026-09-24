# Testing docs

Catalog conventions and suite runbooks live in
[docs/dev/testing/README.md](../dev/testing/README.md).

Case markdown remains under `docs/testing/cases/<suite>/`. Schema:
[schema.md](schema.md). Case `features` front matter links to capability ids
in the `features:` block of [`.scribe.config.yaml`](../../.scribe.config.yaml),
not `docs/features/`.
