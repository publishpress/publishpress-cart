<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class for general reusable functions.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart_Helper
 * @author PublishPress <help@publishpress.com>
 */

class PPCart_Helper extends PPCart_Order_Helper
{
    /**
     * The single instance of the class.
     *
     * @var PPCart_Helper
     * @since 1.0.0
     */

    protected static $_instance = null;

    /**
     * PPCart_Helper Instance
     */

    public static function instance()
    {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Prepare Exception data to be logged
     */

    public function logException($exception, $line, $file)
    {

        $log['type'] = get_class($exception) ;
        $log['line'] = $line;
        $log['file'] = stripslashes(dirname($file) . '/' . basename($file) . '.php');
        $log['message'] = $exception->getError()->message;

        $this->ppcartLogger($log);
        if (wp_doing_ajax()) {
            $this->sendErrorResponse();
        }
    }


    /**
     * Save Exception Logs
     */

    public function ppcartLogger($message, $file = 'debug')
    {
        if (is_array($message)) {
            $message = wp_json_encode($message);
        }

        $log_dir = $this->get_exception_log_directory();
        if ('' === $log_dir) {
            return false;
        }

        if (! wp_mkdir_p($log_dir)) {
            return false;
        }

        $file_slug = basename((string) $file);
        $file_slug = preg_replace('/[^A-Za-z0-9._-]/', '', $file_slug);
        if (! is_string($file_slug) || '' === $file_slug || '.' === $file_slug || '..' === $file_slug) {
            $file_slug = 'debug';
        }

        $path = trailingslashit($log_dir) . $file_slug . '.log';
        if (! is_writable($log_dir) || (file_exists($path) && ! is_writable($path))) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable,WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Checks uploads exception log path before fopen.
            return false;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Exception log is written under wp-content uploads, not the plugin directory.
        $handle = fopen($path, 'a');
        if (! $handle) {
            return false;
        }

        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite,WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Exception log is written under wp-content uploads, not the plugin directory.
        $bytes = fwrite($handle, current_time('mysql') . '::' . $message . "\n");
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes the uploads exception log handle.
        fclose($handle);

        return $bytes;
    }

    /**
     * Resolve the directory for helper exception logs.
     *
     * @return string Empty when uploads cannot be resolved.
     */
    private function get_exception_log_directory()
    {
        if (defined('PPCART_DEBUG_LOG_DIR')) {
            return rtrim(PPCART_DEBUG_LOG_DIR, '/\\');
        }

        $upload_dir = wp_upload_dir();
        if (! is_array($upload_dir) || ! empty($upload_dir['error']) || empty($upload_dir['basedir'])) {
            return '';
        }

        return trailingslashit($upload_dir['basedir']) . 'publishpress-cart/logs';
    }

    /**
     * Send response when there is any exception
     */

    public function sendErrorResponse($message = '', $data = [])
    {

        if (empty($message)) {
            $message = 'Please try again later or contact support.';
        }

        wp_send_json(['error' => $message,'data' => $data]);
    }

    /**
     * Include template part
     */

    public function renderTemplate($part, $args = [])
    {
        $relative = $part . '.php';
        $template = ppcart_locate_theme_template($relative);
        if ('' === $template) {
            $template = PPCART_BASE_DIR . 'public/templates/' . $relative;
        }

        include($template);
    }
}
