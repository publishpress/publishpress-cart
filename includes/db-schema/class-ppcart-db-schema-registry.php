<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Collects table schemas from Free definitions and the public filter.
 */
final class PPCart_DB_Schema_Registry
{
    /** @var PPCart_DB_Schema_Free_Definitions */
    private $free;

    /**
     * @param PPCart_DB_Schema_Free_Definitions $free Free table definitions.
     */
    public function __construct(PPCart_DB_Schema_Free_Definitions $free)
    {
        $this->free = $free;
    }

    /**
     * @return array<string, PPCart_DB_Table_Schema>
     */
    public function get_schemas()
    {
        // Free's own definitions use the live table helpers, which may resolve to a Compat-owned name.
        $schemas = $this->add_valid_schemas([], $this->free->get_schemas(), false);

        /**
         * Register additional plugin-owned table schemas (Pro affiliate tables, etc.).
         *
         * @param PPCart_DB_Table_Schema[] $schemas Table schemas keyed by table name.
         */
        $filtered = apply_filters('ppcart_db_table_schemas', array_values($schemas));

        if (is_array($filtered)) {
            $schemas = $this->add_valid_schemas($schemas, $filtered, true);
        }

        return $schemas;
    }

    /**
     * @param array<string, PPCart_DB_Table_Schema> $schemas Accepted schemas keyed by table name.
     * @param array $candidates Candidate schemas.
     * @param bool $require_owned_prefix Whether table names must use an owned Cart prefix.
     * @return array<string, PPCart_DB_Table_Schema>
     */
    private function add_valid_schemas(array $schemas, array $candidates, $require_owned_prefix)
    {
        $owned_prefixes = $require_owned_prefix ? $this->get_owned_table_prefixes() : [];

        foreach ($candidates as $schema) {
            if (! $schema instanceof PPCart_DB_Table_Schema) {
                continue;
            }

            $table = $schema->get_table_name();

            if ('' === $table || isset($schemas[ $table ])) {
                continue;
            }

            $problem = $this->find_definition_problem($schema);

            if ('' === $problem && $require_owned_prefix && ! $this->has_owned_prefix($table, $owned_prefixes)) {
                $problem = 'table name must start with an owned Cart table prefix (see ppcart_db_schema_owned_table_prefixes)';
            }

            if ('' !== $problem) {
                _doing_it_wrong(
                    'PPCart_DB_Schema_Registry::get_schemas',
                    esc_html(sprintf('Schema for table "%s" was skipped: %s', $table, $problem)),
                    '1.0.0'
                );
                continue;
            }

            $schemas[ $table ] = $schema;
        }

        return $schemas;
    }

    /**
     * Identifiers are interpolated into DDL, and inline UNIQUE / PRIMARY KEY
     * in a column fragment would add a duplicate index on every repair.
     *
     * @param PPCart_DB_Table_Schema $schema Candidate schema.
     * @return string Problem description, or '' when the schema is usable.
     */
    private function find_definition_problem(PPCart_DB_Table_Schema $schema)
    {
        if (! $this->is_identifier($schema->get_table_name())) {
            return 'invalid table name';
        }

        if (! $this->is_plugin_table_name($schema->get_table_name())) {
            return 'table name must use the site table prefix and must not be a WordPress core table';
        }

        $columns = $schema->get_columns();

        if (empty($columns)) {
            return 'no columns';
        }

        foreach ($columns as $name => $fragment) {
            if (! $this->is_identifier($name) || ! is_string($fragment) || ! $this->is_single_column_definition($fragment)) {
                return 'invalid column definition';
            }

            if (preg_match('/\b(unique|primary\s+key)\b/i', $fragment)) {
                return 'declare UNIQUE and PRIMARY KEY as indexes, not in the column fragment';
            }
        }

        foreach ($schema->get_indexes() as $name => $index) {
            if (! $this->is_identifier($name) || ! is_array($index) || empty($index['columns']) || ! is_array($index['columns'])) {
                return 'invalid index definition';
            }

            foreach ($index['columns'] as $column) {
                if (! is_string($column) || ! preg_match('/^[A-Za-z0-9_$]+(\(\d+\))?$/', $column)) {
                    return 'invalid index column';
                }
            }
        }

        return '';
    }

    /**
     * @return string[] Unprefixed table name prefixes that registered schemas may use.
     */
    private function get_owned_table_prefixes()
    {
        /**
         * Unprefixed table name prefixes that `ppcart_db_table_schemas` entries may use.
         *
         * @param string[] $prefixes Defaults to `[ 'ppcart_' ]`; each value is appended to `$wpdb->prefix`.
         */
        $prefixes = apply_filters('ppcart_db_schema_owned_table_prefixes', [ 'ppcart_' ]);

        if (! is_array($prefixes)) {
            return [ 'ppcart_' ];
        }

        return array_values(array_filter($prefixes, function ($prefix) {
            return is_string($prefix) && 1 === preg_match('/^[A-Za-z0-9_$]+_$/', $prefix);
        }));
    }

    /**
     * @param string $table Table name.
     * @param string[] $owned_prefixes Unprefixed owned prefixes.
     * @return bool
     */
    private function has_owned_prefix($table, array $owned_prefixes)
    {
        global $wpdb;

        foreach ($owned_prefixes as $prefix) {
            if (0 === strpos($table, $wpdb->prefix . $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $table Table name.
     * @return bool
     */
    private function is_plugin_table_name($table)
    {
        global $wpdb;

        if (! isset($wpdb) || ! is_object($wpdb)) {
            return false;
        }

        $prefix = (string) $wpdb->base_prefix;

        if ('' === $prefix || 0 !== strpos($table, $prefix)) {
            return false;
        }

        return ! in_array($table, $wpdb->tables('all', true), true);
    }

    /**
     * Column definitions are appended to ALTER / CREATE TABLE as raw SQL, so a
     * definition must not be able to end the clause or the statement.
     *
     * @param string $fragment Column definition, e.g. "varchar(20) NOT NULL DEFAULT 'x'".
     * @return bool
     */
    private function is_single_column_definition($fragment)
    {
        $fragment = trim($fragment);

        if (! preg_match('/^[A-Za-z]/', $fragment)) {
            return false;
        }

        // Replace complete single-quoted literals ('' and \' escapes) so a quote left over means an unterminated literal.
        $outside_literals = preg_replace("/'(?:[^'\\\\]|\\\\.|'')*'/s", 'LITERAL', $fragment);

        if (null === $outside_literals || preg_match('/[\'";`#\\\\]|--|\/\*|\*\//', $outside_literals)) {
            return false;
        }

        $depth = 0;

        foreach (str_split($outside_literals) as $char) {
            if ('(' === $char) {
                ++$depth;
            } elseif (')' === $char) {
                --$depth;
            } elseif (',' === $char && 0 === $depth) {
                return false;
            }

            if ($depth < 0) {
                return false;
            }
        }

        return 0 === $depth;
    }

    /**
     * @param mixed $name Identifier candidate.
     * @return bool
     */
    private function is_identifier($name)
    {
        return is_string($name) && 1 === preg_match('/^[A-Za-z0-9_$]{1,64}$/', $name);
    }
}
