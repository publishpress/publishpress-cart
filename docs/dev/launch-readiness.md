# PublishPress Cart — Launch Readiness Criteria

Defines how we know the product is safe, reliable, and maintainable enough to launch under the PublishPress brand.

**Scope:** Free plugin (`publishpress-cart`) + Pro plugin (`publishpress-cart-pro`), tested together.

---

## 1. Testing & Code Quality

- [ ] Comprehensive automated end-to-end (e2e) tests implemented and passing.
- [ ] Pass Q&A review (modular code, avoid god files/anti-patterns). [Criteria still in progress]
  - [ ] Security score > 4
  - [ ] Maintainability score > 4
  - [ ] Code quality score > 4
- [x] Legacy unit and integration test suites run and pass consistently in local development environments.
- [x] Ensure `composer lint:php` completes with no errors or warnings.
- [x] Ensure `composer lint:phpcs` runs without any errors or warnings.
- [x] All PHP files have been auto-formatted to PSR-12 coding standards (retaining original class and method names). This ensures consistency and moves away from legacy WordPress core code style.

## 2. Security & Payments

- [x] All known security issues resolved; no unresolved advisories (free & Pro fully audited).
- [ ] Payment and webhook workflows reviewed and verified (Stripe, PayPal).

## 3. CI/CD & GitHub Workflows

- [x] Ensure all essential GitHub workflows are present in the Free plugin, using the Future project’s development branch as a reference.
  - [x] `code-standards.yml` (present as `.github/workflows/code-standards.yml`, reusable workflow `code-standards.yml`).
  - [x] `dependabot-triage.yml`.
  - [x] `deploy-free-assets.yml`.
  - [x] `deploy-free.yml`.
- [x] Ensure all essential GitHub workflows are present in the Pro plugin, using the Future Pro project’s development branch as a reference.
  - [x] `code-standards.yml`.
  - [x] `dependabot-triage.yml`.
  - [x] `deploy-pro.yml`.
- [x] Dev-workspace is fully implemented and seamlessly integrated into the development workflow.
- [ ] All active, unresolved Dependabot issues in production and CI packages are addressed.
- [x] Standardized repository branch structure and implemented security policies.
- [x] Ensure that GitHub issue templates in `.github/ISSUE_TEMPLATE` include the release checklists. Use Future repository as a reference.

## 4. Free & Pro Architecture

- [ ] Integrate Free and Pro cleanly: eliminate code duplication, ensuring all Pro-specific logic resides exclusively in Pro. Treat Free as a library consumed by Pro, with no Pro logic present in the Free plugin.
- [ ] Both Free and Pro plugins can be activated simultaneously on a site without errors or warnings, with the Free plugin taking precedence if both are active.
- [x] Different versions of the plugin can be installed at the same time. The system should softly handle this (using our shared library), ensuring it is not loaded twice.
- [ ] Free dependencies are also listed as dependencies in the pro plugin and properly loaded by it, with the free plugin always taking precedence.
- [x] Consistent use of our shared libraries.
  - [x] Ensure "publishpress/psr-container" is included only if the plugin requires it.
  - [x] "publishpress/pimple-pimple".
  - [x] "publishpress/wordpress-version-notices".
  - [x] "publishpress/wordpress-reviews".
  - [x] "publishpress/instance-protection".
- [x] If using Composer's autoloader, ensure Authoritative class maps are enabled for optimal performance and reliability.

## 5. Build & Packaging

- [x] Ensure only development dependencies are in the root `vendor` directory; production dependencies must be inside `lib/vendor`.
- [x] Ensure the ZIP package contains no development, debug, log, or other dev-related configuration files. Leverage exclusion rules in `.distignore`, `.gitattributes`, `.gitignore`, `.rsync-filters-dev-sync`, `.rsync-filters-pre-build`, and `.rsync-filters-post-build` to keep builds clean.

## 6. Release, Changelog & Versioning

- [x] Ensure `CHANGELOG.md` accurately reflects all recent changes.
- [x] Changelog uses the required standardized format and structure, with the changelog now maintained in a dedicated file rather than in `readme.txt`.
- [ ] Changelog deployment is fully integrated with the Package Server (handled automatically via GitHub and Package Server; no further action required).
- [ ] EDD registry updated for seamless Package Server and Changelog deployments.
- [ ] Confirm that this launch increases the major version number.
- [ ] Ensure all WordPress assets in the `.wordpress-org` directory are up to date in the root of the repository.
- [ ] Ensure the `readme.txt` file is current and up to date in the repository.

## 7. Migration & Compatibility

- [ ] Verify seamless upgrade from latest pre-PublishPress releases, ensuring all data and configuration settings remain compatible or are migrated without loss.
- [ ] Studiocart migration path documented and tested.
- [x] Minimum WordPress version set to 6.7 and validated in the main plugin file.
- [x] Minimum PHP version is set to 7.4 and enforced in the main plugin file (aligned with WordPress core recommendations; lower from current 8.0 minimum where applicable).
- [x] Main plugin file loads gracefully on unsupported PHP versions, with no fatal errors or exceptions.
- [ ] Delete security related docs and transfer to a new repository, migrating issues, milestones (required to remove any security related notes)

## 8. Translations & i18n

- [x] Translation workflow is fully implemented according to our new Weblate-based translation strategy.
- [x] Ensure `Loco.xml` is present in both Free and Pro plugins, as well as in any shared libraries that provide translations.

## 9. Product, UX & Branding

- [ ] Clean, in-context upsells for all Pro features.
- [ ] Several good and clearly advertised Pro features.
- [x] The free plugin clearly identifies itself as "Free" in its name.
- [ ] All Cart admin screens use one design system (no legacy Studiocart islands on launch paths).

## 10. Integrations

- [ ] Integration with the Hub plugin is complete (managed entirely via the Hub and EDD; no additional actions required here).
