<?php

namespace {
    if (! function_exists('is_serialized')) {
        // Minimal copy of the WordPress core check for serialized strings (strict mode).
        function is_serialized($data, $strict = true)
        {
            if (! is_string($data)) {
                return false;
            }

            $data = trim($data);
            if ('N;' === $data) {
                return true;
            }

            return (bool) preg_match('/^(?:[aOs]:\\d+:|[bid]:[^;]*;$)/s', $data)
                && in_array(substr($data, -1), [ ';', '}' ], true);
        }
    }
}

namespace unit\Checkout {

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;
use UnitTester;

/**
 * Behavior checks for code that no longer uses extract() or unrestricted unserialize().
 */
class ScannerFunctionHardeningTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::set('apply_filters', static function ($tag, $value) {
            return $value;
        });
        WordPressStubContext::set('did_action', static function () {
            return 0;
        });
        foreach ([ 'add_shortcode', 'add_action', 'add_filter' ] as $registrar) {
            WordPressStubContext::set($registrar, static function () {
                return true;
            });
        }

        if (! function_exists('ppcart_unserialize_plain_object')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_unserialize_plain_object_allows_only_stdclass(): void
    {
        $plan = (object) [ 'price' => 10, 'nested' => (object) [ 'a' => [ 1, 2 ] ] ];

        $this->assertEquals($plan, ppcart_unserialize_plain_object(serialize($plan)));
        $this->assertNull(ppcart_unserialize_plain_object(serialize(new \ArrayObject([ 1 ]))));
        $this->assertNull(ppcart_unserialize_plain_object(serialize((object) [ 'inner' => new \ArrayObject([ 1 ]) ])));
        $this->assertNull(ppcart_unserialize_plain_object(serialize([ 'not' => 'an object' ])));
        $this->assertNull(ppcart_unserialize_plain_object('not serialized'));
        $this->assertNull(ppcart_unserialize_plain_object(''));
        $this->assertNull(ppcart_unserialize_plain_object([ 'array' ]));
    }
}
}
