<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Post_Status_Sync;

class PostStatusPrefixTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /**
     * @var int[]
     */
    private $createdPostIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdPostIds as $postId) {
            wp_delete_post($postId, true);
        }
        $this->createdPostIds = [];

        parent::tearDown();
    }

    public function test_IT_372_inserting_a_paid_order_stores_prefixed_post_status(): void
    {
        $orderId = $this->insertOrder('paid');

        $this->assertSame('ppcart_paid', get_post_status($orderId));
        $this->assertSame('paid', ppcart_get_post_meta($orderId, 'status', true));
    }

    public function test_IT_372_queries_for_paid_still_find_legacy_post_status_rows(): void
    {
        global $wpdb;

        $prefixedId = $this->insertOrder('paid');
        $legacyId   = $this->insertOrder('paid');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Test plants a pre-prefix row the query filter must still find.
        $wpdb->update(
            $wpdb->posts,
            [ 'post_status' => 'paid' ],
            [ 'ID' => $legacyId ],
            [ '%s' ],
            [ '%d' ]
        );
        clean_post_cache($legacyId);

        $found = get_posts(
            [
                'post_type'      => ppcart_live_post_type('order'),
                'post_status'    => 'paid',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]
        );

        $this->assertContains($prefixedId, $found);
        $this->assertContains($legacyId, $found);
    }

    public function test_IT_372_upgrade_migrates_legacy_canonical_post_status(): void
    {
        global $wpdb;

        $orderId = $this->insertOrder('paid');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Test plants a pre-prefix canonical row for upgrade migrate.
        $wpdb->update(
            $wpdb->posts,
            [ 'post_status' => 'paid' ],
            [ 'ID' => $orderId ],
            [ '%s' ],
            [ '%d' ]
        );
        clean_post_cache($orderId);
        $this->assertSame('paid', get_post_status($orderId));

        PPCart_Post_Status_Sync::migrate_stored_statuses();
        clean_post_cache($orderId);

        $this->assertSame('ppcart_paid', get_post_status($orderId));
        $this->assertSame('paid', ppcart_get_post_meta($orderId, 'status', true));
    }

    /**
     * @param string $status Logical status.
     * @return int
     */
    private function insertOrder(string $status): int
    {
        $orderId = wp_insert_post(
            [
                'post_type'   => ppcart_live_post_type('order'),
                'post_status' => $status,
                'post_title'  => 'Prefixed status order',
            ]
        );
        $this->assertIsInt($orderId);
        $this->assertGreaterThan(0, $orderId);
        $this->createdPostIds[] = $orderId;
        ppcart_update_post_meta($orderId, 'status', $status);

        return $orderId;
    }
}
