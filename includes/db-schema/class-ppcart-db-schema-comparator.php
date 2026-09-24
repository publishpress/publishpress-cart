<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Pure schema diff (no WordPress, no database).
 */
final class PPCart_DB_Schema_Comparator
{
    /** @var PPCart_DB_Column_Type_Policy */
    private $type_policy;

    /**
     * @param PPCart_DB_Column_Type_Policy|null $type_policy Decides which type changes are safe to apply.
     */
    public function __construct(?PPCart_DB_Column_Type_Policy $type_policy = null)
    {
        $this->type_policy = $type_policy ?? new PPCart_DB_Column_Type_Policy();
    }

    /**
     * @param PPCart_DB_Table_Schema $schema Expected schema.
     * @param bool $exists Whether the table exists.
     * @param array<string, string> $live_columns Live columns.
     * @param array<string, array{columns: string[], unique: bool}> $live_indexes Live indexes.
     * @return PPCart_DB_Schema_Issue[]
     */
    public function compare(PPCart_DB_Table_Schema $schema, $exists, array $live_columns, array $live_indexes)
    {
        $table = $schema->get_table_name();

        if (! $exists) {
            return [
                new PPCart_DB_Schema_Issue(PPCart_DB_Schema_Issue::MISSING_TABLE, $table),
            ];
        }

        $issues = [];

        foreach ($schema->get_columns() as $column_name => $expected_fragment) {
            if (! isset($live_columns[ $column_name ])) {
                $issues[] = new PPCart_DB_Schema_Issue(
                    PPCart_DB_Schema_Issue::MISSING_COLUMN,
                    $table,
                    $column_name
                );
                continue;
            }

            $expected_type = $this->normalize_type($this->extract_type_fragment($expected_fragment));
            $actual_type   = $this->normalize_type($live_columns[ $column_name ]);

            if ($expected_type !== $actual_type) {
                $issues[] = new PPCart_DB_Schema_Issue(
                    $this->type_policy->is_lossless_change($actual_type, $expected_type)
                        ? PPCart_DB_Schema_Issue::COLUMN_MISMATCH
                        : PPCart_DB_Schema_Issue::COLUMN_UNSAFE_CHANGE,
                    $table,
                    $column_name,
                    $expected_type,
                    $actual_type
                );
            }
        }

        foreach ($schema->get_indexes() as $index_name => $expected_index) {
            if (! isset($live_indexes[ $index_name ])) {
                $issues[] = new PPCart_DB_Schema_Issue(
                    PPCart_DB_Schema_Issue::MISSING_INDEX,
                    $table,
                    $index_name,
                    $this->describe_index($expected_index)
                );
                continue;
            }

            if ('PRIMARY' === $index_name) {
                $expected_index['unique'] = true;
            }

            $live_index = $live_indexes[ $index_name ];

            if (! $this->indexes_match($expected_index, $live_index)) {
                $issues[] = new PPCart_DB_Schema_Issue(
                    PPCart_DB_Schema_Issue::INDEX_MISMATCH,
                    $table,
                    $index_name,
                    $this->describe_index($expected_index),
                    $this->describe_index($live_index)
                );
            }
        }

        return $issues;
    }

    /**
     * @param string $fragment Column SQL fragment from the schema definition.
     * @return string
     */
    private function extract_type_fragment($fragment)
    {
        $fragment = trim((string) $fragment);

        if ('' === $fragment) {
            return '';
        }

        $stop_tokens = [
            ' not null',
            ' null',
            ' default ',
            ' auto_increment',
            ' unique',
            ' primary key',
            ' collate ',
            ' character set ',
            ' charset ',
            ' comment ',
            ' on update ',
        ];
        $lower       = strtolower($fragment);
        $cut         = strlen($fragment);

        foreach ($stop_tokens as $token) {
            $pos = strpos($lower, $token);

            if (false !== $pos && $pos < $cut) {
                $cut = $pos;
            }
        }

        return trim(substr($fragment, 0, $cut));
    }

    /**
     * @param string $type Raw or partial SQL type.
     * @return string
     */
    public function normalize_type($type)
    {
        $normalized = strtolower(trim((string) $type));
        $normalized = preg_replace('/\b(bigint|int|mediumint|smallint|tinyint)\(\d+\)/', '$1', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim((string) $normalized);
    }

    /**
     * @param array{columns: string[], unique: bool} $index
     * @return bool
     */
    private function indexes_match(array $index, array $live_index)
    {
        $expected_unique = ! empty($index['unique']);
        $live_unique     = ! empty($live_index['unique']);

        if ($expected_unique !== $live_unique) {
            return false;
        }

        $expected_columns = array_values($index['columns']);
        $live_columns     = array_values($live_index['columns']);

        return $expected_columns === $live_columns;
    }

    /**
     * @param array{columns: string[], unique: bool} $index
     * @return string
     */
    private function describe_index(array $index)
    {
        $columns = implode(', ', $index['columns']);
        $kind    = ! empty($index['unique']) ? 'unique' : 'non-unique';

        return $kind . ' (' . $columns . ')';
    }
}
