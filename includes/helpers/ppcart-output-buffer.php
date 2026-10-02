<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Capture a renderer and close its buffer in the same call, including on errors.
 *
 * The handler tracks ownership even if a callback closes our buffer and opens
 * a replacement at the same level. Flushes stay captured. Removable buffers
 * left by callbacks are flushed into ours before closing only our own buffer.
 *
 * @param callable $render Renderer whose return value is ignored.
 * @return string Captured output.
 */
function ppcart_capture_output(callable $render)
{
    $level   = ob_get_level();
    $active  = true;
    $output  = '';
    $failure = null;

    ob_start(static function ($buffer, $phase) use (&$active, &$output) {
        if (0 === ($phase & PHP_OUTPUT_HANDLER_CLEAN)) {
            $output .= $buffer;
        }
        if (0 !== ($phase & PHP_OUTPUT_HANDLER_FINAL)) {
            $active = false;
        }
        return '';
    });

    try {
        $render();
    } catch (Throwable $error) {
        $failure = $error;
    } finally {
        // Unwind nested output without discarding it or touching caller buffers.
        while ($active && ob_get_level() > $level + 1) {
            $nested_level = ob_get_level();
            $status = ob_get_status();
            if (0 === ($status['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)) {
                // PHP cannot remove a buffer created without the removable flag.
                $failure = $failure ?? new RuntimeException('A renderer left a non-removable output buffer open.');
                break;
            }
            try {
                ob_end_flush();
            } catch (Throwable $error) {
                // A nested handler must not skip closing ours or mask the cause.
                $failure = $failure ?? $error;
                if (ob_get_level() >= $nested_level) {
                    break;
                }
            }
        }
        if ($active && ob_get_level() === $level + 1) {
            $output .= (string) ob_get_clean();
        }
    }

    if (null !== $failure) {
        throw $failure;
    }
    return $output;
}
