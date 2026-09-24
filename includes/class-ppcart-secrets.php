<?php

/**
 * Payment and integration secret storage hardening.
 *
 * @package PublishPress_Cart
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/secrets/traits/trait-ppcart-secrets-config.php';
require_once __DIR__ . '/secrets/traits/trait-ppcart-secrets-read.php';
require_once __DIR__ . '/secrets/traits/trait-ppcart-secrets-migration.php';
require_once __DIR__ . '/secrets/traits/trait-ppcart-secrets-render.php';
require_once __DIR__ . '/secrets/traits/trait-ppcart-secrets-ajax.php';
require_once __DIR__ . '/secrets/traits/trait-ppcart-secrets-update.php';
require_once __DIR__ . '/secrets/traits/trait-ppcart-secrets-crypto.php';

/**
 * Manages sensitive wp_options: registry, optional encryption, migration, and access control.
 */
class PPCart_Secrets
{
    use PPCart_Secrets_Config_Trait;
    use PPCart_Secrets_Read_Trait;
    use PPCart_Secrets_Migration_Trait;
    use PPCart_Secrets_Render_Trait;
    use PPCart_Secrets_Ajax_Trait;
    use PPCart_Secrets_Update_Trait;
    use PPCart_Secrets_Crypto_Trait;

    public const ENCRYPTED_PREFIX = 'ppenc1:';

    public const ENCRYPT_SECRETS_OPTION = '_ppcart_encrypt_secrets';

    public const MIGRATION_DONE_OPTION = '_ppcart_secrets_migration_done';

    public const MIGRATION_NOTICE_TRANSIENT = '_ppcart_secrets_migration_notice';

    public const MIGRATION_DISMISS_META = '_ppcart_secrets_migration_notice_dismissed';

    /**
     * Whether option hooks are registered.
     *
     * @var bool
     */
    private static $initialized = false;

    /**
     * Option names that already have a decrypt filter registered.
     *
     * @var array<string, bool>
     */
    private static $decrypt_filters_registered = [];

    /**
     * Prevents recursive migration while a sensitive option is being rewritten.
     *
     * @var array<string, bool>
     */
    private static $migrating_plaintext_options = [];

    /**
     * Allows internal plaintext-to-encrypted rewrites during lazy migration on read.
     *
     * @var bool
     */
    private static $allow_internal_secret_migration = false;

    /**
     * Memoized sensitive option names; reset by register_option_decrypt_filters().
     *
     * @var string[]|null
     */
    private static $sensitive_option_names_cache = null;

    /**
     * Whether the alloptions decrypt scan has already run this request.
     *
     * @var bool
     */
    private static $alloptions_decrypt_done = false;
}
