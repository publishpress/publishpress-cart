<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;

require_once PPCART_PLUGIN_ROOT . 'includes/mcp/bootstrap.php';

class CompatibilityTest extends Unit
{
    public function test_missing_abilities_api_keeps_native_bootstrap_operational(): void
    {
        $hooks = [];
        WordPressStubContext::set('add_action', static function ($hook, $callback) use (&$hooks) {
            $hooks[$hook] = $callback;
        });
        try {
            ppcart_mcp_boot(new \stdClass(), new \stdClass());
            $this->assertCount(2, $hooks);
            $this->assertNull($hooks['wp_abilities_api_categories_init']());
            $this->assertNull($hooks['wp_abilities_api_init']());
        } finally {
            WordPressStubContext::clear();
        }
    }
}
