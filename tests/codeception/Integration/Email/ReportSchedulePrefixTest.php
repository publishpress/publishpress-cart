<?php

declare(strict_types=1);

namespace Tests\Integration\Email;

use lucatume\WPBrowser\TestCase\WPTestCase;

class ReportSchedulePrefixTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();

        wp_clear_scheduled_hook('ppcart_email_schedule_hook');
        delete_option('ppcart_report_schedule');
        delete_option('ppcart_current_schedule_val');
    }

    protected function tearDown(): void
    {
        wp_clear_scheduled_hook('ppcart_email_schedule_hook');
        delete_option('ppcart_report_schedule');
        delete_option('ppcart_current_schedule_val');

        parent::tearDown();
    }

    /**
     * @test-id IT-370
     */
    public function test_IT_370_legacy_daily_option_is_rewritten_and_cron_uses_canonical_recurrence(): void
    {
        update_option('ppcart_report_schedule', 'mt_daily');
        update_option('ppcart_current_schedule_val', 'mt_daily');

        ppcart_register_report_schedule_event();

        $this->assertSame('ppcart_daily', get_option('ppcart_report_schedule'));
        $event = wp_get_scheduled_event('ppcart_email_schedule_hook');
        $this->assertIsObject($event);
        $this->assertSame('ppcart_daily', $event->schedule);
    }

    /**
     * @test-id IT-370
     */
    public function test_IT_370_none_clears_the_report_cron_hook(): void
    {
        update_option('ppcart_report_schedule', 'ppcart_daily');
        ppcart_register_report_schedule_event();
        $this->assertNotFalse(wp_next_scheduled('ppcart_email_schedule_hook'));

        update_option('ppcart_report_schedule', 'mt_none');
        ppcart_register_report_schedule_event();

        $this->assertSame('ppcart_none', get_option('ppcart_report_schedule'));
        $this->assertFalse(wp_next_scheduled('ppcart_email_schedule_hook'));
    }
}
