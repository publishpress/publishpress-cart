<?php

declare(strict_types=1);

namespace Tests\Support\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;
use WP\MCP\Domain\Tools\McpTool;

/**
 * Execute domain tests through a separately installed released MCP Adapter.
 */
abstract class McpTestCase extends WPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $adapter = getenv('MCP_ADAPTER_TEST_BOOTSTRAP');
        if ($adapter && ! class_exists(McpTool::class)) {
            require_once $adapter;
        }
        if (! class_exists(McpTool::class)) {
            $this->markTestSkipped('Set MCP_ADAPTER_TEST_BOOTSTRAP to the released MCP Adapter entry file.');
        }
    }

    protected function execute(string $name, $input)
    {
        $ability = wp_get_ability('publishpress-cart/' . $name);
        $this->assertNotNull($ability);
        $tool = McpTool::fromAbility($ability);
        $this->assertNotWPError($tool);
        return $tool->execute($input);
    }
}
