<?php

namespace unit\DbSchema;

use Codeception\Test\Unit;
use PPCart_DB_Schema_Comparator;
use PPCart_DB_Schema_Issue;
use PPCart_DB_Table_Schema;
use UnitTester;

class ComparatorTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /** @var PPCart_DB_Schema_Comparator */
    private $comparator;

    protected function _before(): void
    {
        require_once PPCART_PLUGIN_ROOT . 'includes/db-schema/class-ppcart-db-table-schema.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/db-schema/class-ppcart-db-schema-issue.php';
        require_once PPCART_PLUGIN_ROOT . 'includes/db-schema/class-ppcart-db-schema-comparator.php';

        $this->comparator = new PPCart_DB_Schema_Comparator();
    }

    public function test_UT_359_normalize_type_treats_integer_display_width_as_equivalent(): void
    {
        $this->assertSame(
            $this->comparator->normalize_type('bigint unsigned'),
            $this->comparator->normalize_type('bigint(20) unsigned')
        );
    }

    public function test_UT_359_missing_column_is_reported(): void
    {
        $schema = $this->sampleSchema(
            [
                'id' => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
            ],
            [
                'PRIMARY' => [
                    'columns' => [ 'id' ],
                    'unique'  => true,
                ],
            ]
        );

        $issues = $this->comparator->compare(
            $schema,
            true,
            [],
            [
                'PRIMARY' => [
                    'columns' => [ 'id' ],
                    'unique'  => true,
                ],
            ]
        );

        $this->assertCount(1, $issues);
        $this->assertSame(PPCart_DB_Schema_Issue::MISSING_COLUMN, $issues[0]->get_type());
        $this->assertSame('id', $issues[0]->get_name());
    }

    public function test_UT_359_column_type_mismatch_is_reported(): void
    {
        $schema = $this->sampleSchema(
            [
                'amount' => 'varchar(255) NOT NULL',
            ],
            []
        );

        $issues = $this->comparator->compare(
            $schema,
            true,
            [ 'amount' => 'text' ],
            []
        );

        $this->assertCount(1, $issues);
        $this->assertSame(PPCart_DB_Schema_Issue::COLUMN_MISMATCH, $issues[0]->get_type());
    }

    public function test_UT_359_index_column_order_mismatch_is_reported(): void
    {
        $schema = $this->sampleSchema([], [
            'composite' => [
                'columns' => [ 'a', 'b' ],
                'unique'  => false,
            ],
        ]);

        $issues = $this->comparator->compare(
            $schema,
            true,
            [],
            [
                'composite' => [
                    'columns' => [ 'b', 'a' ],
                    'unique'  => false,
                ],
            ]
        );

        $this->assertCount(1, $issues);
        $this->assertSame(PPCart_DB_Schema_Issue::INDEX_MISMATCH, $issues[0]->get_type());
    }

    public function test_UT_359_missing_subpart_is_reported_as_index_mismatch(): void
    {
        $schema = $this->sampleSchema([], [
            'meta_key' => [
                'columns' => [ 'meta_key(191)' ],
                'unique'  => false,
            ],
        ]);

        $issues = $this->comparator->compare(
            $schema,
            true,
            [],
            [
                'meta_key' => [
                    'columns' => [ 'meta_key' ],
                    'unique'  => false,
                ],
            ]
        );

        $this->assertCount(1, $issues);
        $this->assertSame(PPCart_DB_Schema_Issue::INDEX_MISMATCH, $issues[0]->get_type());
    }

    public function test_UT_359_unique_flag_mismatch_is_reported(): void
    {
        $schema = $this->sampleSchema([], [
            'order_key' => [
                'columns' => [ 'order_key' ],
                'unique'  => true,
            ],
        ]);

        $issues = $this->comparator->compare(
            $schema,
            true,
            [],
            [
                'order_key' => [
                    'columns' => [ 'order_key' ],
                    'unique'  => false,
                ],
            ]
        );

        $this->assertCount(1, $issues);
        $this->assertSame(PPCart_DB_Schema_Issue::INDEX_MISMATCH, $issues[0]->get_type());
    }

    public function test_UT_359_primary_key_without_unique_flag_matches_live_primary(): void
    {
        $schema = $this->sampleSchema(
            [ 'id' => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT' ],
            [ 'PRIMARY' => [ 'columns' => [ 'id' ] ] ]
        );

        $issues = $this->comparator->compare(
            $schema,
            true,
            [ 'id' => 'bigint unsigned' ],
            [
                'PRIMARY' => [
                    'columns' => [ 'id' ],
                    'unique'  => true,
                ],
            ]
        );

        $this->assertSame([], $issues);
    }

    /**
     * @param array<string, string> $columns
     * @param array<string, array{columns: string[], unique: bool}> $indexes
     * @return PPCart_DB_Table_Schema
     */
    private function sampleSchema(array $columns, array $indexes)
    {
        return new PPCart_DB_Table_Schema('wp_ppcart_sample', $columns, $indexes, 'Sample');
    }
}
