<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use Codeception\Test\Unit;
use PPCart_Mcp_Input;

require_once PPCART_PLUGIN_ROOT . 'includes/mcp/class-ppcart-mcp-input.php';

class InputTest extends Unit
{
    public function test_json_objects_and_lists_remain_distinct(): void
    {
        $schema = PPCart_Mcp_Input::schema([
            'enabled' => ['type' => 'boolean'],
            'ids' => ['type' => 'array', 'items' => ['type' => 'integer', 'minimum' => 1]],
        ], ['enabled', 'ids']);
        $result = PPCart_Mcp_Input::validate((object) ['enabled' => false, 'ids' => [1, 2]], $schema);
        $this->assertSame(['enabled' => false, 'ids' => [1, 2]], $result);
        foreach (['false', 0, null, [], (object) []] as $bad) {
            $this->assertTrue(is_wp_error(PPCart_Mcp_Input::validate(['enabled' => $bad, 'ids' => [1]], $schema)));
        }
        $this->assertTrue(is_wp_error(PPCart_Mcp_Input::validate(['enabled' => false, 'ids' => ['1']], $schema)));
        $this->assertTrue(is_wp_error(PPCart_Mcp_Input::validate(['enabled' => false, 'ids' => (object) []], $schema)));
        $this->assertTrue(is_wp_error(PPCart_Mcp_Input::validate(['enabled' => false, 'ids' => [1], 'unknown' => true], $schema)));
    }
    public function test_nullable_scalars_and_unicode_lengths_are_strict(): void
    {
        $schema = ['type' => ['integer', 'null'], 'minimum' => 0];
        $this->assertNull(PPCart_Mcp_Input::validate(null, $schema));
        $this->assertSame(0, PPCart_Mcp_Input::validate(0, $schema));
        $this->assertTrue(is_wp_error(PPCart_Mcp_Input::validate('0', $schema)));
        $this->assertTrue(is_wp_error(PPCart_Mcp_Input::validate(false, $schema)));
        $this->assertSame('éé', PPCart_Mcp_Input::validate('éé', ['type' => 'string', 'maxLength' => 2]));
        $this->assertTrue(is_wp_error(PPCart_Mcp_Input::validate(INF, ['type' => 'number'])));
    }

}
