<?php

namespace Tests\Unit\Rendering;

use Codeception\Test\Unit;
use Tests\Support\WordPressStubContext;

/** Regression coverage for scoped production rendering buffers (issue #929). */
class OutputBufferTest extends Unit
{
    protected function _before(): void
    {
        WordPressStubContext::clear();
        require_once PPCART_PLUGIN_ROOT . 'includes/integrations/gutenberg/lib/account-renderer/trait-ppcart-account-renderer-tab-content.php';
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    private function capture(callable $render): string
    {
        WordPressStubContext::set('do_action', static function ($hook) use ($render) {
            self::assertSame('ppcart_tab_content_tab-files', $hook);
            $render();
        });
        $renderer = new class {
            use \PPCart_Account_Renderer_Tab_Content;
        };
        return $renderer->render_downloads_content();
    }

    public function testCapturesOutputAndClosesOnEarlyReturn(): void
    {
        $level = ob_get_level();
        $output = $this->capture(static function () {
            echo 'rendered';
            return 'ignored';
        });
        self::assertSame('rendered', $output);
        self::assertSame($level, ob_get_level());
    }

    public function testNestedCapturesPreserveOrder(): void
    {
        $level = ob_get_level();
        $output = $this->capture(function () {
            echo 'before:';
            echo $this->capture(static function () { echo 'inner'; });
            echo ':after';
        });
        self::assertSame('before:inner:after', $output);
        self::assertSame($level, ob_get_level());
    }

    public function testUnclosedNestedBuffersPreserveOutputAndRestoreLevel(): void
    {
        $level = ob_get_level();
        $output = $this->capture(static function () {
            echo 'before:';
            ob_start(static function ($output) { return strtoupper($output); });
            echo 'nested';
        });
        self::assertSame('before:NESTED', $output);
        self::assertSame($level, ob_get_level());
    }

    public function testExplicitFlushRetainsNativeBehavior(): void
    {
        $level = ob_get_level();
        ob_start();
        try {
                $output = $this->capture(static function () {
                    echo 'flushed:';
                    ob_flush();
                    echo 'discarded';
                    ob_clean();
                    echo 'tail';
                });
                self::assertSame('tail', $output);
                self::assertSame('flushed:', ob_get_contents());
        } finally {
            ob_end_clean();
        }
        self::assertSame($level, ob_get_level());
    }

    public function testCallbackCanFlushAndCloseWithoutClosingCallerBuffer(): void
    {
        $level = ob_get_level();
        ob_start();
        try {
                $output = $this->capture(static function () {
                    echo 'finished';
                    ob_end_flush();
                });
                self::assertSame('', $output);
                self::assertSame('finished', ob_get_contents());
        } finally {
            ob_end_clean();
        }
        self::assertSame($level, ob_get_level());
    }

    public function testExceptionsRestoreCallerBufferAndPropagate(): void
    {
        ob_start();
        echo 'caller';
        $level = ob_get_level();
        $failure = new \RuntimeException('render failed');
        try {
                try {
                    $this->capture(static function () use ($failure) {
                        echo 'discard';
                        ob_start();
                        echo 'nested discard';
                        throw $failure;
                    });
                    self::fail('Expected the renderer exception.');
                } catch (\RuntimeException $caught) {
                    self::assertSame($failure, $caught);
                }
                self::assertSame($level, ob_get_level());
                self::assertSame('caller', ob_get_contents());
        } finally {
            ob_end_clean();
        }
    }

    public function testErrorsAlsoCloseCapture(): void
    {
        $level = ob_get_level();
        $failure = new \Error('render failed');
        try {
                $this->capture(static function () use ($failure) { throw $failure; });
                self::fail('Expected the renderer error.');
        } catch (\Error $caught) {
            self::assertSame($failure, $caught);
        }
        self::assertSame($level, ob_get_level());
    }

    public function testReplacementAtSameLevelIsNotClosedOrRead(): void
    {
        $level = ob_get_level();
        try {
            $output = $this->capture(static function () {
                echo 'discard';
                ob_end_clean();
                ob_start();
                echo 'replacement';
            });
            self::assertSame('', $output);
            self::assertSame($level + 1, ob_get_level());
            self::assertSame('replacement', ob_get_contents());
        } finally {
            ob_end_clean();
        }
    }

    public function testClosedCaptureDoesNotCloseCallerBufferOnException(): void
    {
        ob_start();
        echo 'caller';
        $level = ob_get_level();
        try {
                try {
                    $this->capture(static function () {
                        ob_end_clean();
                        throw new \RuntimeException('closed');
                    });
                } catch (\RuntimeException $caught) {
                    self::assertSame('closed', $caught->getMessage());
                }
                self::assertSame($level, ob_get_level());
                self::assertSame('caller', ob_get_contents());
        } finally {
            ob_end_clean();
        }
    }

    public function testNestedHandlerFailureDoesNotLeakCaptureOrMaskRenderFailure(): void
    {
        $level = ob_get_level();
        $failure = new \RuntimeException('render failed');
        try {
            $this->capture(static function () use ($failure) {
                ob_start();
                ob_start(static function () { throw new \RuntimeException('handler failed'); });
                echo 'discard';
                throw $failure;
            });
            self::fail('Expected the renderer exception.');
        } catch (\RuntimeException $caught) {
            self::assertSame($failure, $caught);
        } finally {
            $actual_level = ob_get_level();
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
        self::assertSame($level, $actual_level);
    }
}
