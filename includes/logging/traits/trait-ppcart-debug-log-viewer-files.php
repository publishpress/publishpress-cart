<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Debug_Log_Viewer_Files_Trait
{
    /**
     * Get ordered log paths from oldest rotated file to current file.
     *
     * @param string $file_name Optional file name.
     * @return array
     */
    private static function get_log_paths($file_name = '')
    {
        $paths = [];
        for ($i = self::MAX_ROTATED_FILES; $i >= 1; $i--) {
            $paths[] = self::get_log_path($i, $file_name);
        }
        $paths[] = self::get_log_path(0, $file_name);

        return $paths;
    }

    /**
     * Get one log file path.
     *
     * @param int    $rotation  Rotation index.
     * @param string $file_name Optional file name.
     * @return string
     */
    private static function get_log_path($rotation = 0, $file_name = '')
    {
        $file_name = self::sanitize_file_name($file_name);
        if ('' === $file_name) {
            $file_name = self::sanitize_file_name((string) get_option('_ppcart_log_file', 'log.txt'));
        }

        $path = trailingslashit(self::get_log_folder_path()) . $file_name;
        if ($rotation > 0) {
            $path .= '.' . absint($rotation);
        }

        return $path;
    }

    /**
     * Get debug log folder path.
     *
     * @return string
     */
    private static function get_log_folder_path()
    {
        if (defined('PPCART_DEBUG_LOG_DIR')) {
            return rtrim(PPCART_DEBUG_LOG_DIR, '/\\');
        }

        $upload_dir = wp_upload_dir();

        return trailingslashit($upload_dir['basedir']) . 'publishpress-cart/logs';
    }

    /**
     * Sanitize a plugin-owned log file name.
     *
     * @param string $file_name File name.
     * @return string
     */
    private static function sanitize_file_name($file_name)
    {
        $file_name = basename((string) $file_name);
        return preg_replace('/[^A-Za-z0-9._-]/', '', $file_name);
    }
}
