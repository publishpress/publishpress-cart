<?php

namespace unit\SqlLists;

use Codeception\Test\Unit;
use PPCart_Status_Labels;

class QueryPostStatusesTest extends Unit
{
    protected function _before(): void
    {
        if (! class_exists(PPCart_Status_Labels::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-status-labels.php';
        }
    }

    /**
     * @test-id UT-360
     */
    public function test_UT_360_logical_status_returns_registered_and_logical_slugs(): void
    {
        $this->assertSame([ 'ppcart_paid', 'paid' ], ppcart_query_post_statuses('paid'));
        $this->assertSame([ 'ppcart_pending', 'pending-payment' ], ppcart_query_post_statuses('pending-payment'));
    }

    /**
     * @test-id UT-360
     */
    public function test_UT_360_status_list_is_merged_without_duplicates(): void
    {
        $this->assertSame(
            [ 'ppcart_paid', 'paid', 'ppcart_active', 'active' ],
            ppcart_query_post_statuses([ 'paid', 'active', 'paid' ])
        );
    }

    /**
     * @test-id UT-360
     */
    public function test_UT_360_unmapped_status_is_returned_as_given(): void
    {
        $this->assertSame([ 'publish' ], ppcart_query_post_statuses('publish'));
        $this->assertSame([], ppcart_query_post_statuses([]));
    }

    /**
     * @test-id UT-360
     */
    public function test_UT_360_sql_in_helper_quotes_the_same_values(): void
    {
        $previous = $GLOBALS['wpdb'] ?? null;
        unset($GLOBALS['wpdb']);

        try {
            $this->assertSame("'ppcart_paid','paid'", ppcart_sql_in_post_statuses('paid'));
            $this->assertSame("''", ppcart_sql_in_post_statuses([]));
        } finally {
            if (null !== $previous) {
                $GLOBALS['wpdb'] = $previous;
            }
        }
    }
}
