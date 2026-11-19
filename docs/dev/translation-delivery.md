# Translation Delivery

Cart registers `publishpress-cart`, its GitHub repository and `PPCART_VERSION` with Hub. Hub downloads the site locale for that installed version, not the latest Cart release. The translation library loads the catalogs from `wp-content/uploads/publishpress/language-packs/`, preserving Loco override paths. Deactivating Hub leaves installed catalogs in place.

The stable release workflow calls the shared `language-pack.yml@v3` after the plugin build. It uploads committed PO, MO and JSON catalogs as `publishpress-cart-locales.zip` on the same release. The updated shared workflow then emits an edited-release event so Package Server can import the catalogs after the plugin ZIP has already been stored.

On the store's Pro product, set Free Version GitHub Repository to `publishpress/publishpress-cart`. The updated EDD/Simba registry uses that mapping to approve Free catalog imports without authorizing Free plugin ZIP distribution. The matching Package Server GitHub App needs access to the Free repository.

## Staging Checks

Use a disposable site with Hub's translation URL filter pointing to the staging Package Server. Check PHP and JavaScript strings in a non-English site language, repeated checks, a plugin version change, Loco overrides and Hub deactivation. Check the Hub notice on Cart screens with Hub inactive; other plugins' domains and screens must be unaffected.

Test standalone Free before Pro. Pro bundles its own Free copy and does not run beside an active standalone Free plugin. A Pro test build must include this updated Free implementation. A normal Free release also deploys to WordPress.org, so do not publish a Cart release solely to populate staging.
