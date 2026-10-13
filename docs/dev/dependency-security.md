# npm dependency security

## Dependabot alerts 17, 18, and 21 (2026-10-13)

These alerts concern the repository's Node.js development toolchain. The release
exclusions in .distignore exclude node_modules, package.json, and package-lock.json
from the WordPress plugin package. This does not remove risk when running build
or documentation tools on untrusted repository content.

| Alert | Dependency | Resolution |
| --- | --- | --- |
| 18 | postcss-selector-parser < 7.1.6 | Override every transitive copy to 7.1.6, retaining @wordpress/scripts 35.x. |
| 17 | braces <= 3.0.3 | No patched upstream release is available. Keep the alert open. |
| 21 | sprintf-js <= 1.1.3 | No patched upstream release is available. Keep the alert open. |

### Remaining exposure

braces arrives through WordPress build tools and processes glob patterns. Cart
does not directly import the npm package or expose it through a WordPress request
handler. Repository-controlled build patterns remain trusted inputs; running
tooling against untrusted changes still requires review.

sprintf-js arrives through the documentation and WordPress build toolchains,
including gray-matter's YAML tooling. Cart's test documentation tools read
repository files. The checkout block uses @wordpress/i18n's sprintf API; that
call is not an import of the npm sprintf-js package.

These observations are an exposure assessment, not a patch or proof that all
transitive execution paths are safe. Do not mark alerts 17 and 21 fixed merely
because they are development dependencies. Recheck upstream releases and
advisories before the next dependency update. No dependency is renamed or
replaced with an unreviewed fork to suppress detection.

### References

- https://github.com/advisories/GHSA-vfj7-8cjw-p6xm
- https://github.com/advisories/GHSA-hp3w-g68c-fv3c
- https://github.com/postcss/postcss-selector-parser/releases/tag/v7.1.6

### Validation

Regenerate the lockfile with npm install --package-lock-only --ignore-scripts,
then run npm ci --ignore-scripts and npm run build:gutenberg. Confirm all
postcss-selector-parser lockfile entries resolve to 7.1.6. Review npm audit for
the two unresolved advisories rather than expecting a clean audit.

Validation of this update: npm ci --ignore-scripts passed, the Gutenberg production
build passed, all selector-parser consumers resolve to 7.1.6, and a 400 KB flat
selector plus cssnano's calc/selector minification passed. npm audit reports no
selector-parser finding; braces and sprintf-js remain reported. The local Node
22.12.0 installation produces engine warnings for existing WordPress tooling.

The documentation catalog sync check reports 43 errors and 21 warnings. This
update changes no test definitions or catalog cases; catalog synchronization
requires separate investigation.
