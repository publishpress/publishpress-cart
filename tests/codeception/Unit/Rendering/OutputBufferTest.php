<?php

namespace Tests\Unit\Rendering;

use PHPUnit\Framework\TestCase;

/** Regression coverage for scoped production rendering buffers (issue #929). */
class OutputBufferTest extends TestCase
{
    protected function setUp(): void
    {
        if (! defined('ABSPATH')) {
            define('ABSPATH', __DIR__);
        }
        require_once dirname(__DIR__, 4) . '/includes/helpers/ppcart-output-buffer.php';
    }

    public function testCapturesOutputAndClosesOnEarlyReturn(): void
    {
        $level = ob_get_level();
        $output = ppcart_capture_output(static function () {
            echo 'rendered';
            return 'ignored';
        });
        self::assertSame('rendered', $output);
        self::assertSame($level, ob_get_level());
    }

    public function testNestedCapturesPreserveOrder(): void
    {
        $level = ob_get_level();
        $output = ppcart_capture_output(static function () {
            echo 'before:';
            echo ppcart_capture_output(static function () { echo 'inner'; });
            echo ':after';
        });
        self::assertSame('before:inner:after', $output);
        self::assertSame($level, ob_get_level());
    }

    public function testUnclosedNestedBuffersPreserveOutputAndRestoreLevel(): void
    {
        $level = ob_get_level();
        $output = ppcart_capture_output(static function () {
            echo 'before:';
            ob_start(static function ($output) { return strtoupper($output); });
            echo 'nested';
        });
        self::assertSame('before:NESTED', $output);
        self::assertSame($level, ob_get_level());
    }

    public function testFlushesStayCapturedAndCleanedContentIsDiscarded(): void
    {
        $level = ob_get_level();
        $output = ppcart_capture_output(static function () {
            echo 'flushed:';
            ob_flush();
            echo 'discarded';
            ob_clean();
            echo 'tail';
        });
        self::assertSame('flushed:tail', $output);
        self::assertSame($level, ob_get_level());
    }

    public function testCallbackCanEndAndFlushCaptureWithoutLeakingOutput(): void
    {
        $level = ob_get_level();
        $output = ppcart_capture_output(static function () {
            echo 'finished';
            ob_end_flush();
        });
        self::assertSame('finished', $output);
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
                ppcart_capture_output(static function () use ($failure) {
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
            ppcart_capture_output(static function () use ($failure) { throw $failure; });
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
            $output = ppcart_capture_output(static function () {
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
                ppcart_capture_output(static function () {
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
            ppcart_capture_output(static function () use ($failure) {
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
