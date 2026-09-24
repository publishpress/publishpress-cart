# Plugin rules

Shared rules for PublishPress Free/Pro WordPress plugins. Copied from the Team Handbook. Plugin-specific overlay is in the root `AGENTS.md`.

Optional research (private): https://github.com/publishpress/team-handbook/tree/development/docs/dev — if a sibling `team-handbook` checkout exists, prefer that. If unreachable, follow this file and `AGENTS.md`. Do not invent process. Do not clone the handbook.

## Free and Pro

A **Free plugin** is listed on WordPress.org. It must not contain **Pro code** (Pro-only classes, modules, license-gated features, or copies of Pro files).

A **Pro plugin** is delivered only through **Package Server** (never WordPress.org). It depends on one Free plugin and, in current packaging, vendors that Free plugin via Composer. Pro bootstraps Free from the vendor copy. Standalone Free and Pro must not both be active.

**Extension points** in Free — hooks, filters, interfaces Pro (or others) may listen to — are allowed. They are not Pro code.

Do not move a Pro feature into Free behind `class_exists`, a license check, or a stub “so the hook has a default.” That would ship to WordPress.org.

## Do not break Pro from Free

The Pro plugin is a separate repository. Changing Free internals can break Pro without touching the Pro repo.

Treat Free hooks, filters, internal APIs, and data shapes Pro already uses as a public contract. If nothing in **this** repo references a symbol, Pro still might. Do not rename, remove, or change signature/behavior of those contracts as cleanup. If the overlay in `AGENTS.md` does not list them, say so before changing them.

## Issues

The **issue home** is the Free plugin GitHub repository. Product issues, Pro work, and release issues are filed there — not in the Pro plugin repo.

From Pro commits and PRs use `Fixes publishpress/<free-repo>#N`. Bare `#N` resolves against the Pro repo and is wrong.

## PHP

Obey **this** plugin’s declared minimum in `composer.json` and the plugin header. Minima differ across plugins (for example 7.2.5 vs 7.4). Do not use newer syntax or standard-library functions without a version check and a fallback for that minimum.

## Tests

If this repo already has tests (`composer test`, unit, integration, or otherwise), run the relevant existing suite after behavior changes. Do not add a full new test harness as part of an unrelated change.

## Vendor trees

Do not search `lib/vendor/`, `vendor/`, or `node_modules/` to learn how this plugin works. Those trees are dependencies, not this plugin’s contract.