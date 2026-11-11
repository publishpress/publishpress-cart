<?php

/**
 * Bounded CSV decoding for native import adapters.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Csv
{
    /** @since 1.1.0 @param string $csv UTF-8 CSV. @return array|WP_Error */
    public static function parse($csv)
    {
        if (! is_string($csv) || strlen($csv) > 100000 || false !== strpos($csv, "\0")) {
            return PPCart_Mcp_Input::error();
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Bounded in-memory CSV stream, never a user-supplied path.
        $handle = fopen('php://temp', 'w+');
        if (! $handle) {
            return PPCart_Mcp_Input::error();
        }
        try {
            // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite,WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- In-memory CSV parser.
            fwrite($handle, $csv);
            rewind($handle);
            $header = fgetcsv($handle, 0, ',', '"', '');
            if (! is_array($header) || count($header) !== count(array_unique($header))) {
                return PPCart_Mcp_Input::error();
            }
            $rows = [];
            while (false !== ($row = fgetcsv($handle, 0, ',', '"', ''))) {
                if (count($row) !== count($header) || count($rows) >= 100) {
                    return PPCart_Mcp_Input::error();
                }
                $rows[] = array_combine($header, $row);
            }
            return ['header' => $header, 'rows' => $rows];
        } finally {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- In-memory CSV stream.
            fclose($handle);
        }
    }

    /** @since 1.1.0 @param array $rows Scalar CSV cells. @return string */
    public static function encode(array $rows)
    {
        require_once dirname(__DIR__) . '/helpers/ppcart-csv.php';
        return implode('', array_map('ppcart_csv_row', $rows));
    }
}
