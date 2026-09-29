<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Debug_Log_Viewer;

/**
 * Markup that is escaped with wp_kses() at the echo site keeps the tags and
 * attributes the admin JavaScript needs, and loses script content.
 */
class EchoSiteEscapingTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(PPCart_Debug_Log_Viewer::class)) {
            require_once PPCART_BASE_DIR . 'includes/logging/class-ppcart-debug-log-viewer.php';
        }
    }

    /**
     * @test-id IT-390
     */
    public function test_IT_390_email_shell_removes_script_from_body_and_keeps_layout(): void
    {
        $orderInfo = ppcart_get_email_preview_order_data();

        $html = ppcart_get_email_html(
            [
                'type'       => 'ppcart_test_custom',
                'order_info' => $orderInfo,
                'headline'   => 'Hello',
                'body'       => '<p>Thanks <strong>Ada</strong></p><script>alert(1)</script><table style="width: 100%;"><tr><td>Row</td></tr></table>',
            ]
        );

        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringContainsString('<strong>Ada</strong>', $html);
        $this->assertStringContainsString('<td>Row</td>', $html);
        $this->assertStringContainsString('<style type="text/css">', $html);
    }

    /**
     * @test-id IT-390
     */
    public function test_IT_390_log_viewer_badge_and_icon_are_unchanged_by_kses(): void
    {
        $badge = $this->callViewer('render_level_badge', [ 'WARNING' ]);
        $icon  = $this->callViewer('render_workflow_icon', [ 'Stripe' ]);

        $this->assertStringContainsString('ppcart-debug-log-level--warning', $badge);
        $this->assertSame($badge, wp_kses($badge, ppcart_log_viewer_allowed_html()));
        $this->assertStringContainsString('<svg viewBox="0 0 24 24"', $icon);
        // kses lowercases attribute names (viewBox -> viewbox). HTML parsers
        // map SVG attribute names back, so compare without case.
        $this->assertSame(strtolower($icon), strtolower(wp_kses($icon, ppcart_log_viewer_allowed_html())));
    }

    /**
     * @test-id IT-390
     */
    public function test_IT_390_log_viewer_panel_keeps_script_hooks_and_drops_script_tags(): void
    {
        $entry = PPCart_Debug_Log_Viewer::parse_line(
            '[05/12/2026 6:31 AM] - WARNING : Order #627 <script>alert(1)</script> failed.'
        );

        $panel = $this->callViewer(
            'render_inspector_panel',
            [
                [
                    'id'       => 'evt-1',
                    'group_id' => 'grp-1',
                    'entry'    => $entry,
                ],
                false,
                'evt-0',
                '',
            ]
        );
        $escaped = wp_kses((string) $panel, ppcart_log_viewer_allowed_html());

        $this->assertStringContainsString('data-ppcart-debug-panel="evt-1"', $escaped);
        $this->assertStringContainsString('data-ppcart-debug-group="grp-1"', $escaped);
        $this->assertStringContainsString('data-ppcart-debug-event="evt-0"', $escaped);
        $this->assertStringContainsString('data-ppcart-debug-back', $escaped);
        $this->assertMatchesRegularExpression('/<div[^>]+ hidden/', $escaped);
        $this->assertMatchesRegularExpression('/<button[^>]+ disabled/', $escaped);
        $this->assertStringNotContainsString('<script>', $escaped);
    }

    /**
     * @param string       $method Private static viewer method.
     * @param array<mixed> $args   Arguments.
     * @return mixed
     */
    private function callViewer(string $method, array $args)
    {
        $reflection = new \ReflectionMethod(PPCart_Debug_Log_Viewer::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(null, $args);
    }
}
