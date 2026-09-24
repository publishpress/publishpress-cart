<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The file download specific functionality of the plugin.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PublishPressCart
 * @subpackage PublishPressCart/files
 */

require_once __DIR__ . '/traits/trait-ppcart-files-settings.php';
require_once __DIR__ . '/traits/trait-ppcart-files-admin.php';
require_once __DIR__ . '/traits/trait-ppcart-files-repository.php';
require_once __DIR__ . '/traits/trait-ppcart-files-frontend.php';
require_once __DIR__ . '/traits/trait-ppcart-files-order.php';
require_once __DIR__ . '/traits/trait-ppcart-files-storage.php';

class PPCart_Files
{
    use PPCart_Files_Settings_Trait;
    use PPCart_Files_Admin_Trait;
    use PPCart_Files_Repository_Trait;
    use PPCart_Files_Frontend_Trait;
    use PPCart_Files_Order_Trait;
    use PPCart_Files_Storage_Trait;

    public const DOWNLOAD_QUERY_VAR = 'ppcart-download';
    public const DOWNLOAD_REWRITE_TAG = '%ppcart-download%';
    public const DOWNLOAD_KEY_PATTERN = '([a-z0-9-]+)';
    public const DEFAULT_DOWNLOAD_SLUG = 'file';

    /**
     * The downloads table name.
     *
     * @since 1.0.0
     * @access private
     * @var string    $table_name    The downloads table name.
     */
    private static $table_name = 'ppcart_downloads';

    /**
     * The downloads page slug.
     *
     * @since 1.0.0
     * @access private
     * @var string    $slug    The downloads page slug.
     */
    private $slug = self::DEFAULT_DOWNLOAD_SLUG;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string    $plugin_name       The name of this plugin.
     * @param string    $version    The version of this plugin.
     */
    public function __construct()
    {

        $this->initialize();
    }

    public function init()
    {
    }

    public function table_name()
    {
        return function_exists('ppcart_live_table_suffix')
            ? ppcart_live_table_suffix('downloads')
            : self::$table_name;
    }

    /**
     * Prefixed live downloads table name.
     *
     * @return string
     */
    public static function live_table()
    {
        return ppcart_live_table('downloads');
    }

    /**
     * Return the filtered secure-download URL slug.
     *
     * @return string
     */
    public static function get_download_slug()
    {
        return (string) apply_filters('ppcart_download_slug', self::DEFAULT_DOWNLOAD_SLUG);
    }

    /**
     * Return the secure-download rewrite regex.
     *
     * @param string|null $slug Filtered download slug.
     * @return string
     */
    public static function get_download_rewrite_regex($slug = null)
    {
        if (null === $slug) {
            $slug = self::get_download_slug();
        }

        return preg_quote((string) $slug, '#') . '/' . self::DOWNLOAD_KEY_PATTERN . '[/]?$';
    }

    public function initialize()
    {
        add_action('ppcart_activate', [$this, 'setup_download_table']);
        add_action('ppcart_activate', [$this, 'setup_directory']);
        add_action('ppcart_upgrade', [$this, 'setup_download_table']);
        add_action('ppcart_upgrade', [$this, 'setup_directory']);
        add_action('ppcart_order_created', [$this, 'attach_downloads_to_order'], 5);
        add_action('ppcart_order_refunded', [$this, 'process_refund'], 5, 3);
        add_filter('ppcart_account_tabs', [$this, 'account_tabs'], 1);
        add_action('ppcart_tab_content_tab-files', [$this, 'file_tab_content'], 1);
        add_action('wp', [$this, 'download_file'], 1);
        add_action('edit_form_advanced', [$this, 'product_form_callback']);
        $ppcart_order_types = function_exists('ppcart_query_post_types') ? ppcart_query_post_types('order') : [ 'ppcart_order' ];
        foreach ($ppcart_order_types as $ppcart_order_type) {
            add_action('save_post_' . $ppcart_order_type, [$this, 'update_order_downloads'], 99, 2);
        }
        add_action('admin_init', [$this, 'maybe_revoke_access']);
        add_action('admin_notices', [$this, 'revoke_notice']);
        add_action('init', [$this, 'download_page_rewrites'], 1, 0);
        add_action('ppcart_email_after_order_table', [$this, 'email_download_links']);
        add_action('ppcart_receipt_after_order_details', [$this, 'receipt_download_links']);
        add_filter('_ppcart_option_list', [$this, 'login_to_download_setting']);
        add_filter('upload_dir', [ $this, 'upload_dir' ]);
        add_filter('_ppcart_option_list', [$this, 'download_slug_setting']);
        add_action('add_option_ppcart_download_slug', [$this, 'flush_permalinks'], 10, 2);
        add_action('update_option_ppcart_download_slug', [$this, 'flush_permalinks'], 10, 2);

        add_shortcode('ppcart_order_downloads', [$this, 'downloads_shortcode']);

        add_filter('ppcart_product_setting_tabs', [$this, 'files_tab']);
        add_filter('ppcart_product_field_groups', [$this, 'file_group']);
        add_filter("ppcart_product_setting_tab_files_fields", [$this, 'file_fields']);
    }
}
