<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Webhook_Logger_Writer_Trait
{
    /**
     * Record a verified Stripe event handling outcome.
     *
     * @param object|array $event   Stripe event object.
     * @param string       $status  Handling status.
     * @param string       $message Human-readable summary.
     * @param array        $context Safe scalar diagnostic context.
     * @return bool
     */
    public static function record_event($event, $status, $message = '', $context = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-writer-record-event.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Record a request-level rejection when no valid Stripe event exists yet.
     *
     * @param string $reason  Rejection reason.
     * @param array  $context Safe scalar request context.
     * @return bool
     */
    public static function record_rejected_request($reason, $context = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-writer-record-rejected-request.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Remove the current and rotated webhook log files.
     *
     * @return void
     */
    public static function clear_log_files()
    {
        $file_name = self::get_log_file_name(false);
        if (! $file_name) {
            return;
        }

        $paths = [ self::get_log_path(0, false) ];
        for ($i = 1; $i <= self::MAX_ROTATED_FILES; $i++) {
            $paths[] = self::get_log_path($i, false);
        }
        $paths[] = self::get_log_path(0, false) . '.lock';

        foreach ($paths as $path) {
            if ($path && file_exists($path)) {
                // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink,WordPress.WP.AlternativeFunctions.unlink_unlink -- Deletes plugin-owned webhook log files.
                unlink($path);
            }
        }
    }

    /**
     * Append one normalized entry.
     *
     * @param array $entry Log entry.
     * @return bool
     */
    private static function append_entry($entry)
    {
        $line = self::json_encode($entry);
        if (! $line) {
            return false;
        }

        return self::append_json_line($line);
    }

    /**
     * Append one JSON line with a lock around rotation and write.
     *
     * @param string $line JSON string.
     * @return bool
     */
    private static function append_json_line($line)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-writer-append-json-line.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Rotate the current file when appending would exceed the cap.
     *
     * @param int $append_size Bytes about to be appended.
     * @return void
     */
    private static function rotate_if_needed($append_size)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-writer-rotate-if-needed.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Ensure the log folder and basic web guards exist.
     *
     * @return bool
     */
    private static function ensure_log_directory()
    {
        $dir = self::get_log_folder_path();
        if (! is_dir($dir)) {
            if (function_exists('wp_mkdir_p')) {
                wp_mkdir_p($dir);
            } else {
                // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir,WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Creates plugin-owned log directory in standalone tests.
                mkdir($dir, 0755, true);
            }
        }

        if (! is_dir($dir) || ! is_writable($dir)) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable,WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Checks plugin-owned log directory.
            return false;
        }

        self::write_guard_file(
            $dir . '/index.html',
            ''
        );

        $legacy_php_guard = $dir . '/index.php';
        if (is_file($dir . '/index.html') && is_file($legacy_php_guard)) {
            // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink,WordPress.WP.AlternativeFunctions.unlink_unlink -- Removes leftover generated PHP log guard after the HTML guard exists.
            unlink($legacy_php_guard);
        }

        return true;
    }

    /**
     * Create a guard file when missing.
     *
     * @param string $path    File path.
     * @param string $content File content.
     * @return void
     */
    private static function write_guard_file($path, $content)
    {
        if (file_exists($path)) {
            return;
        }

        $handle = fopen($path, 'wb'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Creates plugin-owned log guard file.
        if ($handle) {
            fwrite($handle, $content); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite,WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writes plugin-owned log guard file.
            fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes plugin-owned log guard file.
        }
    }

    /**
     * Stream all webhook logs as JSON Lines.
     *
     * @return void
     */
    private static function download_log()
    {
        $file_name = self::get_log_file_name(false);
        header('Content-Type: application/x-ndjson');
        header('Content-Disposition: attachment; filename="' . ($file_name ? basename($file_name) : 'stripe-webhook.log') . '"');

        for ($i = self::MAX_ROTATED_FILES; $i >= 1; $i--) {
            self::stream_log_file(self::get_log_path($i, false));
        }
        self::stream_log_file(self::get_log_path(0, false));
    }

    /**
     * Stream a single log file.
     *
     * @param string $path File path.
     * @return void
     */
    private static function stream_log_file($path)
    {
        if ($path && is_readable($path)) {
            readfile($path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streams plugin-owned webhook log download.
        }
    }

    /**
     * Resolve the log path.
     *
     * @param int  $rotation Rotation index.
     * @param bool $create   Whether to lazy-create the file option.
     * @return string
     */
    private static function get_log_path($rotation = 0, $create = true)
    {
        $file_name = self::get_log_file_name($create);
        if (! $file_name) {
            return '';
        }

        $path = self::get_log_folder_path() . '/' . $file_name;
        return $rotation ? $path . '.' . absint($rotation) : $path;
    }

    /**
     * Resolve or lazily create the randomized log filename.
     *
     * @param bool $create Whether to create when missing.
     * @return string
     */
    private static function get_log_file_name($create = true)
    {
        $file_name = get_option(self::FILE_OPTION, '');
        if (! $file_name && $create) {
            $file_name = wp_generate_uuid4() . '-stripe-webhook.log';
            update_option(self::FILE_OPTION, $file_name, false);
        }

        return self::sanitize_file_name($file_name);
    }

    /**
     * Resolve the log folder path.
     *
     * @return string
     */
    private static function get_log_folder_path()
    {
        if (defined('PPCART_STRIPE_WEBHOOK_LOG_DIR')) {
            return rtrim(PPCART_STRIPE_WEBHOOK_LOG_DIR, '/\\');
        }

        $upload_dir = wp_upload_dir();

        return trailingslashit($upload_dir['basedir']) . 'publishpress-cart/logs';
    }

    /**
     * Get the active maximum file size.
     *
     * @return int
     */
    private static function max_file_size()
    {
        if (defined('PPCART_STRIPE_WEBHOOK_LOG_MAX_BYTES')) {
            return absint(PPCART_STRIPE_WEBHOOK_LOG_MAX_BYTES);
        }

        return 2 * 1024 * 1024;
    }

    /**
     * Read server data safely.
     *
     * @param string $key Server key.
     * @return string
     */
    private static function get_server_value($key)
    {
        if (empty($_SERVER[ $key ]) || ! is_scalar($_SERVER[ $key ])) {
            return '';
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized immediately after unslashing.
        $value = wp_unslash($_SERVER[ $key ]);
        return self::sanitize_text($value);
    }

    /**
     * Hash remote IP for rejected-request rate limiting and diagnostics.
     *
     * @return string
     */
    private static function get_remote_ip_hash()
    {
        $ip   = self::get_server_value('REMOTE_ADDR');
        $seed = self::get_remote_ip_hash_seed();

        return substr(hash('sha256', $ip . $seed), 0, 16);
    }

    /**
     * Resolve or lazily create the site-local seed used for IP fingerprints.
     *
     * @return string
     */
    private static function get_remote_ip_hash_seed()
    {
        $seed = get_option(self::IP_HASH_SEED_OPTION, '');
        if (! is_string($seed) || '' === $seed) {
            $seed = wp_generate_password(64, false, false);
            update_option(self::IP_HASH_SEED_OPTION, $seed, false);
        }

        return $seed;
    }

    /**
     * Write important errors to the existing debug log when enabled.
     *
     * @param string $message Debug message.
     * @param int    $level   Debug level.
     * @return void
     */
    private static function log_debug($message, $level = 4)
    {
        global $ppcart_debug_logger;

        if (is_object($ppcart_debug_logger) && is_callable([ $ppcart_debug_logger, 'log_debug' ])) {
            $ppcart_debug_logger->log_debug($message, $level);
        }
    }
}
