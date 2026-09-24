<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Debug_Log_Viewer_Reader_Trait
{
    /**
     * Read, parse, filter, and paginate debug log entries.
     *
     * @param array $args Read and filter args.
     * @return array
     */
    public static function read_entries($args = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-reader-read-entries.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Read all matching entries up to a hard cap.
     *
     * @param array $args Read and filter args.
     * @return array
     */
    public static function read_matching_entries($args = [])
    {
        $args          = is_array($args) ? $args : [];
        $args['limit'] = isset($args['limit']) ? absint($args['limit']) : 1000;
        $args['offset'] = 0;

        return self::read_entries($args);
    }

    /**
     * Parse one raw debug log line.
     *
     * @param string $line Raw line.
     * @return array|false
     */
    public static function parse_line($line)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-reader-parse-line.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Decide if one parsed entry matches filters.
     *
     * @param array $entry Parsed entry.
     * @param array $args  Filter args.
     * @return bool
     */
    private static function entry_matches($entry, $args)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-reader-entry-matches.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Extract a structured context suffix from a debug message.
     *
     * @param string $message Debug message.
     * @return array
     */
    private static function extract_structured_context($message)
    {
        if (! preg_match('/\scontext=(\{.*\})\s*$/', $message, $matches)) {
            return [];
        }

        $decoded = json_decode($matches[1], true);
        if (! is_array($decoded)) {
            return [];
        }

        $decoded['clean_message'] = trim(substr($message, 0, - strlen($matches[0])));
        return $decoded;
    }

    /**
     * Extract a JSON object/array embedded in a message.
     *
     * @param string $message Debug message.
     * @return array
     */
    private static function extract_json_payload($message)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-reader-extract-json-payload.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Parse legacy UTC timestamp.
     *
     * @param string $time_string Raw time.
     * @return int
     */
    private static function parse_timestamp($time_string)
    {
        try {
            $date = DateTime::createFromFormat('m/d/Y g:i A', $time_string, new DateTimeZone('UTC'));
            if ($date) {
                return $date->getTimestamp();
            }
        } catch (Exception $e) {
            return 0;
        }

        $timestamp = strtotime($time_string . ' UTC');
        return $timestamp ? $timestamp : 0;
    }

    /**
     * Detect local record IDs from message and context.
     *
     * @param string $message Debug message.
     * @param array  $context Parsed context.
     * @return array
     */
    private static function tail_lines($path, $max_bytes)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-reader-tail-lines.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
