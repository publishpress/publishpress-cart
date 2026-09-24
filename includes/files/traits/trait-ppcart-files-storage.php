<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Files_Storage_Trait
{
    public function setup_directory()
    {

        // Install files and folders for uploading files and block direct access.
        $upload_dir = wp_upload_dir();

        $htaccess_content = '# Deny direct access to all files in this directory.
                # Files must be served through the PublishPress Cart secure download handler.
                <IfModule mod_authz_core.c>
                        Require all denied
                    </IfModule>
    
                    # For Apache 2.3 and older
                    <IfModule !mod_authz_core.c>
                        Order Deny,Allow
                        Deny from all
                    </IfModule>
                ';

        $files = [
            [
                'base'    => $upload_dir['basedir'] . '/ppcart-uploads',
                'file'    => '.htaccess',
                'content' => $htaccess_content,
            ],
            [
                'base'    => $upload_dir['basedir'] . '/ppcart-uploads',
                'file'    => 'index.html',
                'content' => '',
            ],
        ];

        foreach ($files as $file) {
            $file_path = trailingslashit($file['base']) . $file['file'];

            // Always refresh .htaccess so upgrades correct legacy permissive rules.
            $should_write_file = '.htaccess' === $file['file'] || ! file_exists($file_path);

            if (wp_mkdir_p($file['base']) && $should_write_file) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Writes plugin-managed upload guard files under uploads directory.
                if ($file_handle = @fopen($file_path, 'w')) {
                    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite,WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writes plugin-managed upload guard files under uploads directory.
                    fwrite($file_handle, $file['content']);
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes plugin-owned log or guard file handle.
                    fclose($file_handle);
                }
            }
        }
    }

    /**
         * Allowed filesystem roots for local download paths.
         *
         * @since 1.0.0
         *
         * @return string[]
         */
    public static function get_allowed_download_roots()
    {
        $upload_dir = wp_upload_dir();

        if (! empty($upload_dir['error'])) {
            return apply_filters('ppcart_download_allowed_roots', []);
        }

        $roots = [
            $upload_dir['basedir'] . '/ppcart-uploads',
        ];

        $roots = array_values(array_filter(array_map('realpath', $roots)));

        return apply_filters('ppcart_download_allowed_roots', $roots);
    }

    /**
         * Resolve a product file path to a readable local file under allowed roots.
         *
         * @since 1.0.0
         *
         * @param string $path Product file URL or path.
         *
         * @return string|false Canonical local file path, or false when disallowed.
         */
    public static function resolve_local_download_path($path)
    {
        $local_path = self::local_path_from_download_reference($path);

        if (false === $local_path) {
            return false;
        }

        $resolved = realpath($local_path);

        if (false === $resolved || ! is_file($resolved)) {
            return false;
        }

        foreach (self::get_allowed_download_roots() as $root) {
            if (self::path_is_within_root($resolved, $root)) {
                return $resolved;
            }
        }

        return false;
    }

    /**
         * Convert a stored download reference to a local filesystem path.
         *
         * @since 1.0.0
         *
         * @param string $path Product file URL or path.
         *
         * @return string|false
         */
    private static function local_path_from_download_reference($path)
    {
        $path = trim((string) $path);

        if ('' === $path || false !== strpos($path, "\0")) {
            return false;
        }

        if (preg_match('#^https?://#i', $path)) {
            return self::upload_url_to_path($path);
        }

        if ('/' === $path[0] || preg_match('#^[A-Za-z]:[/\\\\]#', $path)) {
            return $path;
        }

        return path_join(ABSPATH, $path);
    }

    /**
         * Map a local uploads URL to its filesystem path.
         *
         * @since 1.0.0
         *
         * @param string $url Upload URL.
         *
         * @return string|false
         */
    private static function upload_url_to_path($url)
    {
        $upload_dir = wp_upload_dir();

        if (! empty($upload_dir['error'])) {
            return false;
        }

        $url_path = wp_parse_url(set_url_scheme($url), PHP_URL_PATH);
        $base_path = wp_parse_url(set_url_scheme($upload_dir['baseurl']), PHP_URL_PATH);

        if (! $url_path || ! $base_path) {
            return false;
        }

        $base_path = untrailingslashit($base_path);
        $url_path = untrailingslashit($url_path);

        if (0 !== stripos($url_path, $base_path)) {
            return false;
        }

        $relative = substr($url_path, strlen($base_path));

        return $upload_dir['basedir'] . $relative;
    }

    /**
         * Check whether a path stays within an allowed root directory.
         *
         * @since 1.0.0
         *
         * @param string $path Resolved file path.
         * @param string $root Allowed root directory.
         *
         * @return bool
         */
    private static function path_is_within_root($path, $root)
    {
        $path = wp_normalize_path($path);
        $root = wp_normalize_path($root);

        if ('' === $path || '' === $root) {
            return false;
        }

        return 0 === strpos(trailingslashit($path), trailingslashit($root));
    }

    public function get_table_name()
    {
        return ppcart_live_table_suffix('downloads');
    }

    public function setup_download_table()
    {
        global $wpdb;

        $ppcart_downloads_table = self::live_table();
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE IF NOT EXISTS $ppcart_downloads_table (
    			download_id bigint(20) NOT NULL AUTO_INCREMENT,
    			file_id varchar(20) NOT NULL,
    			order_id bigint(20) NOT NULL,
                order_key varchar(64) UNIQUE NOT NULL,
    			product_id bigint(20) NOT NULL,
    			access_granted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    			download_expires datetime,
    			downloads_remaining varchar(20),
    			downloads varchar(2000),
    			PRIMARY KEY (download_id)
    		  ) $charset_collate;";
        dbDelta($sql);

        // Ensure existing installs upgrade order_key width for secure 64-char tokens.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time schema inspection during activation/upgrade of a plugin-owned table.
        $column = $wpdb->get_row($wpdb->prepare('SHOW COLUMNS FROM %i LIKE %s', $ppcart_downloads_table, 'order_key'));
        if (isset($column->Type) && false !== strpos(strtolower($column->Type), 'varchar(20)')) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- One-time schema migration for backward compatibility on a plugin-owned table.
            $wpdb->query($wpdb->prepare('ALTER TABLE %i MODIFY order_key varchar(64) UNIQUE NOT NULL', $ppcart_downloads_table));
        }
        ppcart_flush_live_table_cache();
    }
}
