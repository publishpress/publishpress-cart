<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Applies schema repairs through dbDelta and ALTER statements.
 */
final class PPCart_DB_Table_Fixer
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
     * @param PPCart_DB_Table_Schema $schema Expected schema.
     * @param PPCart_DB_Schema_Issue[] $issues Issues to fix.
     * @return string[] SQL error strings.
     */
    public function fix(PPCart_DB_Table_Schema $schema, array $issues)
    {
        $errors = [];

        foreach ($issues as $issue) {
            if (! $issue instanceof PPCart_DB_Schema_Issue) {
                continue;
            }

            if (PPCart_DB_Schema_Issue::MISSING_TABLE === $issue->get_type()) {
                $errors = array_merge($errors, $this->create_missing_table($schema));
                break;
            }
        }

        $column_issues = [];
        $missing_index = [];
        $index_rebuild = [];

        foreach ($issues as $issue) {
            if (! $issue instanceof PPCart_DB_Schema_Issue) {
                continue;
            }

            switch ($issue->get_type()) {
                case PPCart_DB_Schema_Issue::MISSING_COLUMN:
                case PPCart_DB_Schema_Issue::COLUMN_MISMATCH:
                    $column_issues[] = $issue;
                    break;
                case PPCart_DB_Schema_Issue::MISSING_INDEX:
                    $missing_index[] = $issue;
                    break;
                case PPCart_DB_Schema_Issue::INDEX_MISMATCH:
                    $index_rebuild[] = $issue;
                    break;
            }
        }

        foreach ($column_issues as $issue) {
            $errors = array_merge($errors, $this->fix_column_issue($schema, $issue));
        }

        foreach ($missing_index as $issue) {
            $errors = array_merge($errors, $this->add_missing_index($schema, $issue));
        }

        foreach ($index_rebuild as $issue) {
            $errors = array_merge($errors, $this->rebuild_index($schema, $issue));
        }

        return array_values(array_filter($errors));
    }

    /**
     * @param PPCart_DB_Table_Schema $schema Table schema.
     * @return string[]
     */
    private function create_missing_table(PPCart_DB_Table_Schema $schema)
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $this->wpdb->get_charset_collate();
        $sql               = $schema->get_create_table_sql($charset_collate);

        dbDelta($sql);

        return $this->collect_last_error();
    }

    /**
     * @param PPCart_DB_Table_Schema $schema Table schema.
     * @param PPCart_DB_Schema_Issue $issue Column issue.
     * @return string[]
     */
    private function fix_column_issue(PPCart_DB_Table_Schema $schema, PPCart_DB_Schema_Issue $issue)
    {
        $table  = $schema->get_table_name();
        $column = $issue->get_name();
        $def    = $schema->get_columns()[ $column ] ?? '';

        if ('' === $def) {
            return [];
        }

        if (PPCart_DB_Schema_Issue::MISSING_COLUMN === $issue->get_type()) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Repair missing column on a plugin-owned table.
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'ALTER TABLE %i ADD COLUMN %i ' . $def,
                    $table,
                    $column
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Repair column type on a plugin-owned table.
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'ALTER TABLE %i MODIFY COLUMN %i ' . $def,
                    $table,
                    $column
                )
            );
        }

        return $this->collect_last_error();
    }

    /**
     * @param PPCart_DB_Table_Schema $schema Table schema.
     * @param PPCart_DB_Schema_Issue $issue Missing index issue.
     * @return string[]
     */
    private function add_missing_index(PPCart_DB_Table_Schema $schema, PPCart_DB_Schema_Issue $issue)
    {
        $index_name = $issue->get_name();
        $index      = $schema->get_indexes()[ $index_name ] ?? null;

        if (! is_array($index)) {
            return [];
        }

        $table       = $schema->get_table_name();
        $column_list = $this->format_index_columns($index['columns']);

        if ('PRIMARY' === $index_name) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Add missing primary key on a plugin-owned table.
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'ALTER TABLE %i ADD PRIMARY KEY (' . $column_list . ')',
                    $table
                )
            );

            return $this->collect_last_error();
        }

        if (! empty($index['unique'])) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Add missing unique index on a plugin-owned table.
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'ALTER TABLE %i ADD UNIQUE INDEX %i (' . $column_list . ')',
                    $table,
                    $index_name
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Add missing index on a plugin-owned table.
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'ALTER TABLE %i ADD INDEX %i (' . $column_list . ')',
                    $table,
                    $index_name
                )
            );
        }

        return $this->collect_last_error();
    }

    /**
     * @param PPCart_DB_Table_Schema $schema Table schema.
     * @param PPCart_DB_Schema_Issue $issue Index mismatch issue.
     * @return string[]
     */
    private function rebuild_index(PPCart_DB_Table_Schema $schema, PPCart_DB_Schema_Issue $issue)
    {
        $index_name = $issue->get_name();
        $index      = $schema->get_indexes()[ $index_name ] ?? null;

        if (! is_array($index)) {
            return [];
        }

        $table       = $schema->get_table_name();
        $column_list = $this->format_index_columns($index['columns']);

        if ('PRIMARY' === $index_name) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Rebuild primary key on a plugin-owned table.
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'ALTER TABLE %i DROP PRIMARY KEY, ADD PRIMARY KEY (' . $column_list . ')',
                    $table
                )
            );

            return $this->collect_last_error();
        }

        if (! empty($index['unique'])) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Rebuild unique index on a plugin-owned table.
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'ALTER TABLE %i DROP INDEX %i, ADD UNIQUE INDEX %i (' . $column_list . ')',
                    $table,
                    $index_name,
                    $index_name
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Rebuild index on a plugin-owned table.
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'ALTER TABLE %i DROP INDEX %i, ADD INDEX %i (' . $column_list . ')',
                    $table,
                    $index_name,
                    $index_name
                )
            );
        }

        return $this->collect_last_error();
    }

    /**
     * @param string[] $columns
     * @return string
     */
    private function format_index_columns(array $columns)
    {
        $parts = [];

        foreach ($columns as $column) {
            if (preg_match('/^(.+)\((\d+)\)$/', $column, $matches)) {
                $parts[] = sprintf('`%s`(%s)', $matches[1], $matches[2]);
                continue;
            }

            $parts[] = sprintf('`%s`', $column);
        }

        return implode(', ', $parts);
    }

    /**
     * @return string[]
     */
    private function collect_last_error()
    {
        if ('' === (string) $this->wpdb->last_error) {
            return [];
        }

        return [ (string) $this->wpdb->last_error ];
    }
}
