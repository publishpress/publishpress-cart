<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Debug_Log_Viewer_Group_Formatting_Trait
{
    /**
         * Compare entries oldest first.
         *
         * @param array $a First entry.
         * @param array $b Second entry.
         * @return int
         */
    private static function compare_entries_oldest_first($a, $b)
    {
        $a_sequence = absint(self::get($a, 'sequence', 0));
        $b_sequence = absint(self::get($b, 'sequence', 0));
        if ($a_sequence !== $b_sequence) {
            return $a_sequence < $b_sequence ? -1 : 1;
        }

        $a_time = absint(self::get($a, 'timestamp', 0));
        $b_time = absint(self::get($b, 'timestamp', 0));
        if ($a_time === $b_time) {
            return 0;
        }

        return $a_time < $b_time ? -1 : 1;
    }

    /**
         * Compare groups newest first.
         *
         * @param array $a First group.
         * @param array $b Second group.
         * @return int
         */
    private static function compare_groups_newest_first($a, $b)
    {
        if ($a['last_timestamp'] !== $b['last_timestamp']) {
            return $a['last_timestamp'] > $b['last_timestamp'] ? -1 : 1;
        }

        if ($a['last_sequence'] === $b['last_sequence']) {
            return 0;
        }

        return $a['last_sequence'] > $b['last_sequence'] ? -1 : 1;
    }

    /**
         * Build a coarse timestamp bucket for fallback grouping.
         *
         * @param int $timestamp Timestamp.
         * @return int
         */
    private static function timestamp_bucket($timestamp)
    {
        $timestamp = absint($timestamp);
        return $timestamp ? (int) floor($timestamp / 300) : 0;
    }

    /**
         * Format a group's latest time.
         *
         * @param array $group Group data.
         * @return string
         */
    private static function format_group_time($group)
    {
        $timestamp = absint(self::get($group, 'last_timestamp', 0));
        return $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : '-';
    }

    /**
         * Format a group's first time.
         *
         * @param array $group Group data.
         * @return string
         */
    private static function format_group_start_time($group)
    {
        $timestamp = absint(self::get($group, 'first_timestamp', 0));
        return $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : '-';
    }

    /**
         * Format an entry as clock time for timeline rows.
         *
         * @param array $entry Parsed entry.
         * @return string
         */
    private static function format_event_clock_time($entry)
    {
        $timestamp = absint(self::get($entry, 'timestamp', 0));
        return $timestamp ? gmdate('H:i:s', $timestamp) : self::format_admin_time($entry);
    }
}
