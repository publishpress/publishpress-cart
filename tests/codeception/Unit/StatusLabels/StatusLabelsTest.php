<?php

namespace unit\StatusLabels;

use Codeception\Test\Unit;
use PPCart_Status_Labels;
use Tests\Support\WordPressStubContext;
use UnitTester;

class StatusLabelsTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::setState('post_statuses', array());

        WordPressStubContext::set(
            'get_post_status_object',
            function ($status) {
                $statuses = WordPressStubContext::getState('post_statuses', array());

                return isset($statuses[$status]) ? $statuses[$status] : null;
            }
        );

        if (! class_exists(PPCart_Status_Labels::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-status-labels.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_UT_163_registered_post_status_labels_are_used_before_fallback(): void
    {
        WordPressStubContext::setState(
            'post_statuses',
            array(
                'paid' => (object) array(
                    'label' => 'Paid translated',
                ),
            )
        );

        $this->assertSame('Paid translated', PPCart_Status_Labels::get('paid'));
    }

    public function test_UT_164_known_fallback_labels_are_available_when_wordpress_has_no_status_object(): void
    {
        $this->assertSame('Past Due', PPCart_Status_Labels::get('past_due'));
    }

    public function test_UT_165_legacy_cancelled_spelling_remains_available_as_early_fallback(): void
    {
        $this->assertSame('Cancelled', PPCart_Status_Labels::get('cancelled'));
    }

    public function test_UT_166_unknown_statuses_are_converted_into_readable_fallback_labels(): void
    {
        $this->assertSame('Custom Status Value', PPCart_Status_Labels::get('custom-status_value'));
    }

    public function test_UT_328_edit_select_value_maps_pending_payment_and_initiated_to_pending(): void
    {
        $this->assertSame('pending', PPCart_Status_Labels::edit_select_value('pending-payment'));
        $this->assertSame('pending', PPCart_Status_Labels::edit_select_value('initiated'));
    }

    public function test_UT_328_edit_select_value_leaves_other_statuses_unchanged(): void
    {
        $this->assertSame('paid', PPCart_Status_Labels::edit_select_value('paid'));
        $this->assertSame('pending', PPCart_Status_Labels::edit_select_value('pending'));
        $this->assertSame('', PPCart_Status_Labels::edit_select_value(''));
    }

    public function test_UT_354_logical_statuses_map_to_prefixed_registered_slugs(): void
    {
        $this->assertSame('ppcart_paid', PPCart_Status_Labels::registered_slug('paid'));
        $this->assertSame('ppcart_pending', PPCart_Status_Labels::registered_slug('pending-payment'));
        $this->assertSame('ppcart_paid', PPCart_Status_Labels::registered_slug('ppcart_paid'));
        $this->assertSame('paid', PPCart_Status_Labels::logical_slug('ppcart_paid'));
        $this->assertSame('pending-payment', PPCart_Status_Labels::logical_slug('ppcart_pending'));
        $this->assertSame([ 'ppcart_paid', 'paid' ], PPCart_Status_Labels::query_slugs('paid'));
        $this->assertSame([ 'ppcart_pending', 'pending-payment' ], PPCart_Status_Labels::query_slugs('pending-payment'));
    }

    public function test_UT_354_registered_slugs_fit_the_post_status_column(): void
    {
        foreach (PPCart_Status_Labels::registered_map() as $logical => $registered) {
            $this->assertLessThanOrEqual(20, strlen($registered), $logical . ' => ' . $registered);
        }
    }

    public function test_UT_354_get_uses_prefixed_registered_status_object(): void
    {
        WordPressStubContext::setState(
            'post_statuses',
            array(
                'ppcart_paid' => (object) array(
                    'label' => 'Paid translated',
                ),
            )
        );

        $this->assertSame('Paid translated', PPCart_Status_Labels::get('paid'));
        $this->assertSame('Paid translated', PPCart_Status_Labels::get('ppcart_paid'));
    }

    public function test_UT_354_edit_select_value_canonicalizes_prefixed_slugs(): void
    {
        $this->assertSame('paid', PPCart_Status_Labels::edit_select_value('ppcart_paid'));
        $this->assertSame('pending', PPCart_Status_Labels::edit_select_value('ppcart_pending'));
    }

    public function test_UT_354_insert_filter_prefixes_canonical_order_status(): void
    {
        if (! class_exists(\PPCart_Post_Status_Sync::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-post-status-sync.php';
        }

        $mapped = \PPCart_Post_Status_Sync::map_insert_post_data(
            [
                'post_type'   => 'ppcart_order',
                'post_status' => 'paid',
            ]
        );
        $this->assertSame('ppcart_paid', $mapped['post_status']);

        $leftover = \PPCart_Post_Status_Sync::map_insert_post_data(
            [
                'post_type'   => 'sc_order',
                'post_status' => 'paid',
            ]
        );
        $this->assertSame('paid', $leftover['post_status']);

        $publish = \PPCart_Post_Status_Sync::map_insert_post_data(
            [
                'post_type'   => 'ppcart_order',
                'post_status' => 'publish',
            ]
        );
        $this->assertSame('publish', $publish['post_status']);
    }

    public function test_UT_354_query_expansion_includes_legacy_and_prefixed_slugs(): void
    {
        if (! class_exists(\PPCart_Post_Status_Sync::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-post-status-sync.php';
        }

        $this->assertSame(
            [ 'ppcart_paid', 'paid' ],
            \PPCart_Post_Status_Sync::expand_query_status_value('paid')
        );
        $this->assertSame('any', \PPCart_Post_Status_Sync::expand_query_status_value('any'));
    }
}
