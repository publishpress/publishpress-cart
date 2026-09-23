<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Aggregated schema check or repair results.
 */
final class PPCart_DB_Schema_Report
{
    /** @var array<string, array{label: string, issues: PPCart_DB_Schema_Issue[], fix_errors: string[]}> */
    private $tables = [];

    /**
     * @param PPCart_DB_Table_Schema $schema Table schema.
     * @param PPCart_DB_Schema_Issue[] $issues Detected issues.
     * @param string[] $fix_errors SQL errors from repair attempts.
     * @return void
     */
    public function add(PPCart_DB_Table_Schema $schema, array $issues, array $fix_errors = [])
    {
        $this->tables[ $schema->get_table_name() ] = [
            'label'      => $schema->get_label(),
            'issues'     => $issues,
            'fix_errors' => array_values(array_filter(array_map('strval', $fix_errors))),
        ];
    }

    /**
     * @param string $table_name Table name.
     * @param string[] $fix_errors SQL errors.
     * @return void
     */
    public function set_fix_errors($table_name, array $fix_errors)
    {
        $table_name = (string) $table_name;

        if (! isset($this->tables[ $table_name ])) {
            return;
        }

        $this->tables[ $table_name ]['fix_errors'] = array_values(
            array_filter(array_map('strval', $fix_errors))
        );
    }

    /**
     * @return bool
     */
    public function is_healthy()
    {
        foreach ($this->tables as $entry) {
            if (! empty($entry['issues'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function to_array()
    {
        $tables = [];

        foreach ($this->tables as $table_name => $entry) {
            $issues = [];

            foreach ($entry['issues'] as $issue) {
                if (! $issue instanceof PPCart_DB_Schema_Issue) {
                    continue;
                }

                $issues[] = [
                    'type'    => $issue->get_type(),
                    'name'    => $issue->get_name(),
                    'message' => $issue->get_message(),
                ];
            }

            $tables[ $table_name ] = [
                'label'      => $entry['label'],
                'issues'     => $issues,
                'fix_errors' => $entry['fix_errors'],
            ];
        }

        return [
            'healthy' => $this->is_healthy(),
            'tables'  => $tables,
        ];
    }
}
