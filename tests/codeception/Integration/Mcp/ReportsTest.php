<?php

declare(strict_types=1);

namespace Tests\Integration\Mcp;

use Tests\Support\Integration\McpTestCase;

require_once dirname(__DIR__, 2) . '/Support/Integration/McpTestCase.php';

class ReportsTest extends McpTestCase
{
    public function test_native_monthly_summary_and_denied_report_capability(): void
    {
        $result = $this->execute('get-monthly-overview', []);
        $this->assertNotWPError($result);
        $native = new \PPCart_Dashboard_Data();
        $this->assertSame($native->get_summary(), $result['summary']);
        $cap = ppcart_live_cap('manager_option');
        $deny = static function ($caps) use ($cap) {
            $caps[$cap] = false;
            return $caps;
        };
        add_filter('user_has_cap', $deny);
        try {
            $this->assertWPError($this->execute('get-monthly-overview', []));
        } finally {
            remove_filter('user_has_cap', $deny);
        }
    }
}
