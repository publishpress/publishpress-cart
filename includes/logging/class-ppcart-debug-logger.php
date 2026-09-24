<?php

if (! defined('ABSPATH')) {
    exit;
}

/*
 * Logs debug data to a debug file in the "logs" folder. Example usage below:
 *
 * global $ppcart_debug_logger;
 * $ppcart_debug_logger->log_debug("Some debug message");
 *
 * OR
 *
 * PPCart_Debug_Logger::log_debug_st("Some debug message");
 */

class PPCart_Debug_Logger
{
    protected static $instance;
    protected static $current_flow_id;
    protected $log_folder_path;
    protected $default_log_file = 'log.txt';
    protected $overwrite        = false;
    public const MAX_ROTATED_FILES     = 3;
    public const DEFAULT_MAX_BYTES     = 2097152;
    public $debug_enabled          = false;
    public $debug_status           = [ 'SUCCESS', 'STATUS', 'NOTICE', 'WARNING', 'FAILURE', 'CRITICAL' ];
    public $section_break_marker   = "\n----------------------------------------------------------\n\n";
    public $log_reset_marker       = "-------- Log File Reset --------\n";

    public function __construct()
    {
        include __DIR__ . '/templates/ppcart-debug-logger---construct.php';
    }

    public function enable_debug_log_setting($options)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-logger-enable-debug-log-setting.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function view_log_request()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-view-log-request.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public static function get_instance()
    {
        return empty(self::$instance) ? new self() : self::$instance;
    }

    public function init_default_log_file()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-init-default-log-file.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_debug_timestamp()
    {
        return '[' . gmdate('m/d/Y g:i A') . '] - ';
    }

    public function get_debug_status($level)
    {
        $size = count($this->debug_status);
        if ($level >= $size) {
            return 'UNKNOWN';
        } else {
            return $this->debug_status[ $level ];
        }
    }

    public function view_log($file_name = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-view-log.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function stream_log_file($path)
    {
        if ($path && is_readable($path)) {
            readfile($path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streams plugin-owned debug log download.
        }
    }

    public function get_section_break($section_break)
    {
        if ($section_break) {
            return $this->section_break_marker;
        }
        return '';
    }

    public function reset_log_file($file_name = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-reset-log-file.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function append_to_file($content, $file_name)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-append-to-file.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function ensure_log_directory()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-ensure-log-directory.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function write_guard_file($path, $content)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-write-guard-file.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function rotate_if_needed($file_name, $append_size)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-rotate-if-needed.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function clear_rotated_files($file_name)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-clear-rotated-files.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function max_file_size()
    {
        $max_bytes = self::resolve_max_bytes();
        if (null !== $max_bytes) {
            return max(1, absint($max_bytes));
        }

        return self::DEFAULT_MAX_BYTES;
    }

    public function get_log_file_path($file_name)
    {
        return trailingslashit($this->log_folder_path) . basename((string) $file_name);
    }

    public function get_rotated_file_path($file_name, $rotation)
    {
        return $this->get_log_file_path($file_name) . '.' . absint($rotation);
    }

    public function log_debug($message, $level = 1, $section_break = false, $file_name = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-log-debug.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function log_event($event, $message, $context = [], $level = 1, $section_break = false, $file_name = '')
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-log-event.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_current_flow_id()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-logger-get-current-flow-id.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function redact_context($context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-redact-context.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public static function log_debug_st($message, $level = 1, $section_break = false, $file_name = '')
    {
        if (! get_option('_ppcart_enable_debug')) {//Debugging is disabled
            return;
        }
        $instance = self::get_instance();
        $instance->debug_enabled = true;
        $instance->log_debug($message, $level, $section_break, $file_name);
    }

    /**
     * Get compact log data for the settings Debug screen.
     */
    public static function get_admin_log_data()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-debug-logger-get-admin-log-data.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Resolve configured debug log directory.
     *
     * @return string|null
     */
    public static function resolve_log_dir()
    {
        if (defined('PPCART_DEBUG_LOG_DIR')) {
            return rtrim(PPCART_DEBUG_LOG_DIR, '/\\');
        }

        $upload_dir = wp_upload_dir();

        return trailingslashit($upload_dir['basedir']) . 'publishpress-cart/logs';
    }

    /**
     * Resolve configured debug log max bytes.
     *
     * @return int|null
     */
    private static function resolve_max_bytes()
    {
        if (defined('PPCART_DEBUG_LOG_MAX_BYTES')) {
            return PPCART_DEBUG_LOG_MAX_BYTES;
        }

        return null;
    }
}
