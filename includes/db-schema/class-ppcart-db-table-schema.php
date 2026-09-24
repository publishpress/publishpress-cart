<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Expected shape of a plugin-owned custom table (no database I/O).
 */
final class PPCart_DB_Table_Schema
{
    /** @var string */
    private $table_name;

    /** @var array<string, string> */
    private $columns;

    /** @var array<string, array{columns: string[], unique: bool}> */
    private $indexes;

    /** @var string */
    private $label;

    /**
     * @param string $table_name Resolved table name including prefix.
     * @param array<string, string> $columns Column name => SQL type fragment.
     * @param array<string, array{columns: string[], unique: bool}> $indexes Index name => definition.
     * @param string $label Admin label.
     */
    public function __construct($table_name, array $columns, array $indexes, $label = '')
    {
        $this->table_name = (string) $table_name;
        $this->columns    = $columns;
        $this->indexes    = $indexes;
        $this->label      = (string) $label;
    }

    /**
     * @return string
     */
    public function get_table_name()
    {
        return $this->table_name;
    }

    /**
     * @return string
     */
    public function get_label()
    {
        return $this->label;
    }

    /**
     * @return array<string, string>
     */
    public function get_columns()
    {
        return $this->columns;
    }

    /**
     * @return array<string, array{columns: string[], unique: bool}>
     */
    public function get_indexes()
    {
        return $this->indexes;
    }

    /**
     * @param string $charset_collate Charset collate clause from wpdb.
     * @return string
     */
    public function get_create_table_sql($charset_collate)
    {
        $lines = [];

        foreach ($this->columns as $name => $definition) {
            $lines[] = sprintf('`%s` %s', $name, $definition);
        }

        foreach ($this->indexes as $index_name => $index) {
            $column_list = $this->format_index_column_list($index['columns']);

            if ('PRIMARY' === $index_name) {
                $lines[] = 'PRIMARY KEY (' . $column_list . ')';
                continue;
            }

            if (! empty($index['unique'])) {
                $lines[] = sprintf('UNIQUE KEY `%s` (%s)', $index_name, $column_list);
                continue;
            }

            $lines[] = sprintf('KEY `%s` (%s)', $index_name, $column_list);
        }

        return sprintf(
            "CREATE TABLE %s (\n\t\t\t%s\n\t\t  ) %s;",
            $this->table_name,
            implode(",\n\t\t\t", $lines),
            $charset_collate
        );
    }

    /**
     * @param string[] $columns
     * @return string
     */
    private function format_index_column_list(array $columns)
    {
        $parts = [];

        foreach ($columns as $column) {
            $parts[] = $this->format_index_column($column);
        }

        return implode(', ', $parts);
    }

    /**
     * @param string $column
     * @return string
     */
    private function format_index_column($column)
    {
        if (preg_match('/^(.+)\((\d+)\)$/', $column, $matches)) {
            return sprintf('`%s`(%s)', $matches[1], $matches[2]);
        }

        return sprintf('`%s`', $column);
    }
}
