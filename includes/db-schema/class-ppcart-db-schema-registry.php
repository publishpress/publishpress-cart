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

    /** @var array<string, PPCart_DB_Table_Schema>|null */
    private $schemas_cache;

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
        if (null !== $this->schemas_cache) {
            return $this->schemas_cache;
        }

        $schemas = [];

        foreach ($this->free->get_schemas() as $schema) {
            if (! $schema instanceof PPCart_DB_Table_Schema) {
                continue;
            }

            $table = $schema->get_table_name();

            if ('' === $table || isset($schemas[ $table ])) {
                continue;
            }

            $schemas[ $table ] = $schema;
        }

        /**
         * Register additional plugin-owned table schemas (Pro affiliate tables, etc.).
         *
         * @param PPCart_DB_Table_Schema[] $schemas Table schemas keyed by table name.
         */
        $filtered = apply_filters('ppcart_db_table_schemas', array_values($schemas));

        if (is_array($filtered)) {
            foreach ($filtered as $schema) {
                if (! $schema instanceof PPCart_DB_Table_Schema) {
                    continue;
                }

                $table = $schema->get_table_name();

                if ('' === $table || isset($schemas[ $table ])) {
                    continue;
                }

                $schemas[ $table ] = $schema;
            }
        }

        $this->schemas_cache = $schemas;

        return $this->schemas_cache;
    }
}
