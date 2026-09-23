<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Reads live MySQL table metadata through wpdb.
 */
final class PPCart_DB_Table_Inspector
{
    /** @var wpdb */
    private $wpdb;

    /**
     * @param wpdb $wpdb Database object.
     */
    public function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * @param string $table Table name.
     * @return bool
     */
    public function table_exists($table)
    {
        $table = (string) $table;

        if ('' === $table) {
            return false;
        }

        $wpdb = $this->wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema inspection on a plugin-owned table.
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));

        return $found === $table;
    }

    /**
     * @param string $table Table name.
     * @return array<string, string> Column name => raw Type.
     */
    public function get_columns($table)
    {
        $table = (string) $table;

        if ('' === $table || ! $this->table_exists($table)) {
            return [];
        }

        $wpdb = $this->wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema inspection on a plugin-owned table.
        $rows = $wpdb->get_results($wpdb->prepare('SHOW COLUMNS FROM %i', $table), ARRAY_A);

        if (! is_array($rows)) {
            return [];
        }

        $columns = [];

        foreach ($rows as $row) {
            if (! isset($row['Field'], $row['Type'])) {
                continue;
            }

            $columns[ (string) $row['Field'] ] = (string) $row['Type'];
        }

        return $columns;
    }

    /**
     * @param string $table Table name.
     * @return array<string, array{columns: string[], unique: bool}>
     */
    public function get_indexes($table)
    {
        $table = (string) $table;

        if ('' === $table || ! $this->table_exists($table)) {
            return [];
        }

        $wpdb = $this->wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema inspection on a plugin-owned table.
        $rows = $wpdb->get_results($wpdb->prepare('SHOW INDEX FROM %i', $table), ARRAY_A);

        if (! is_array($rows)) {
            return [];
        }

        $indexes = [];

        foreach ($rows as $row) {
            if (! isset($row['Key_name'], $row['Column_name'], $row['Non_unique'])) {
                continue;
            }

            $key_name = (string) $row['Key_name'];

            if (! isset($indexes[ $key_name ])) {
                $indexes[ $key_name ] = [
                    'columns' => [],
                    'unique'  => '0' === (string) $row['Non_unique'],
                ];
            }

            $column = (string) $row['Column_name'];

            if (! empty($row['Sub_part'])) {
                $column .= '(' . (string) $row['Sub_part'] . ')';
            }

            $indexes[ $key_name ]['columns'][] = $column;
        }

        return $indexes;
    }
}
