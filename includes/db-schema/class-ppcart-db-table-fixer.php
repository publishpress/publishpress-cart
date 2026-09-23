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
        // Printed wpdb errors would corrupt the AJAX JSON response; errors are returned instead.
        $show_errors = $this->wpdb->hide_errors();

        try {
            return $this->apply_fixes($schema, $issues);
        } finally {
            $this->wpdb->show_errors($show_errors);
        }
    }

    /**
     * @param PPCart_DB_Table_Schema $schema Expected schema.
     * @param PPCart_DB_Schema_Issue[] $issues Issues to fix.
     * @return string[] SQL error strings.
     */
    private function apply_fixes(PPCart_DB_Table_Schema $schema, array $issues)
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
            $primary_key = $this->take_missing_primary_key_for($schema, $issue->get_name(), $missing_index);
            $errors      = array_merge($errors, $this->fix_column_issue($schema, $issue, $primary_key));
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
     * MySQL rejects an AUTO_INCREMENT column that is not a key, so a missing
     * primary key on that column must be added in the same statement.
     *
     * @param PPCart_DB_Table_Schema $schema Table schema.
     * @param string $column Column being repaired.
     * @param PPCart_DB_Schema_Issue[] $missing_index Missing index issues; the PRIMARY issue is removed when taken.
     * @return string[] Primary key columns, or an empty array.
     */
    private function take_missing_primary_key_for(PPCart_DB_Table_Schema $schema, $column, array &$missing_index)
    {
        $def     = (string) ($schema->get_columns()[ $column ] ?? '');
        $primary = $schema->get_indexes()['PRIMARY'] ?? null;

        if (false === stripos($def, 'auto_increment') || ! is_array($primary) || empty($primary['columns'])) {
            return [];
        }

        if (! in_array($column, array_map([ $this, 'strip_sub_part' ], $primary['columns']), true)) {
            return [];
        }

        foreach ($missing_index as $key => $issue) {
            if ('PRIMARY' === $issue->get_name()) {
                unset($missing_index[ $key ]);

                return $primary['columns'];
            }
        }

        return [];
    }

    /**
     * @param string $column Index column, optionally with a (Sub_part).
     * @return string
     */
    private function strip_sub_part($column)
    {
        return (string) preg_replace('/\(\d+\)$/', '', (string) $column);
    }

    /**
     * @param PPCart_DB_Table_Schema $schema Table schema.
     * @param PPCart_DB_Schema_Issue $issue Column issue.
     * @param string[] $primary_key Primary key columns to add in the same statement.
     * @return string[]
     */
    private function fix_column_issue(PPCart_DB_Table_Schema $schema, PPCart_DB_Schema_Issue $issue, array $primary_key = [])
    {
        $table  = $schema->get_table_name();
        $column = $issue->get_name();
        $def    = $schema->get_columns()[ $column ] ?? '';

        if ('' === $def) {
            return [];
        }

        $primary_clause = empty($primary_key) ? '' : ', ADD PRIMARY KEY (' . $this->format_index_columns($primary_key) . ')';

        if (PPCart_DB_Schema_Issue::MISSING_COLUMN === $issue->get_type()) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Repair missing column on a plugin-owned table.
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'ALTER TABLE %i ADD COLUMN %i ' . $def . $primary_clause,
                    $table,
                    $column
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Repair column type on a plugin-owned table.
            $this->wpdb->query(
                $this->wpdb->prepare(
                    'ALTER TABLE %i MODIFY COLUMN %i ' . $def . $primary_clause,
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
