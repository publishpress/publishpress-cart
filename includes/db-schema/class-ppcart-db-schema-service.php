<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Coordinates schema checks and repairs.
 */
final class PPCart_DB_Schema_Service
{
    /** @var PPCart_DB_Schema_Registry */
    private $registry;

    /** @var PPCart_DB_Table_Inspector */
    private $inspector;

    /** @var PPCart_DB_Schema_Comparator */
    private $comparator;

    /** @var PPCart_DB_Table_Fixer */
    private $fixer;

    /**
     * @param PPCart_DB_Schema_Registry $registry Schema registry.
     * @param PPCart_DB_Table_Inspector $inspector Live inspector.
     * @param PPCart_DB_Schema_Comparator $comparator Diff engine.
     * @param PPCart_DB_Table_Fixer $fixer Repair engine.
     */
    public function __construct(
        PPCart_DB_Schema_Registry $registry,
        PPCart_DB_Table_Inspector $inspector,
        PPCart_DB_Schema_Comparator $comparator,
        PPCart_DB_Table_Fixer $fixer
    ) {
        $this->registry   = $registry;
        $this->inspector  = $inspector;
        $this->comparator = $comparator;
        $this->fixer      = $fixer;
    }

    /**
     * @return PPCart_DB_Schema_Report
     */
    public function check_all()
    {
        $report = new PPCart_DB_Schema_Report();

        foreach ($this->registry->get_schemas() as $schema) {
            $issues = $this->collect_issues($schema);
            $report->add($schema, $issues);
        }

        return $report;
    }

    /**
     * @return PPCart_DB_Schema_Report
     */
    public function repair_all()
    {
        $fix_errors_by_table = [];

        foreach ($this->registry->get_schemas() as $schema) {
            $issues = $this->collect_issues($schema);

            if (empty($issues)) {
                continue;
            }

            $fix_errors_by_table[ $schema->get_table_name() ] = $this->fixer->fix($schema, $issues);
        }

        if (function_exists('ppcart_flush_live_table_cache')) {
            ppcart_flush_live_table_cache();
        }

        if (function_exists('ppcart_register_live_meta_table')) {
            ppcart_register_live_meta_table();
        }

        $report = $this->check_all();

        foreach ($fix_errors_by_table as $table => $errors) {
            $report->set_fix_errors($table, $errors);
        }

        /**
         * Fires after a maintenance schema repair run completes.
         *
         * @param array<string, mixed> $report Report array from PPCart_DB_Schema_Report::to_array().
         */
        do_action('ppcart_db_schema_repaired', $report->to_array());

        return $report;
    }

    /**
     * @param PPCart_DB_Table_Schema $schema Table schema.
     * @return PPCart_DB_Schema_Issue[]
     */
    private function collect_issues(PPCart_DB_Table_Schema $schema)
    {
        $table  = $schema->get_table_name();
        $exists = $this->inspector->table_exists($table);

        return $this->comparator->compare(
            $schema,
            $exists,
            $this->inspector->get_columns($table),
            $this->inspector->get_indexes($table)
        );
    }
}
