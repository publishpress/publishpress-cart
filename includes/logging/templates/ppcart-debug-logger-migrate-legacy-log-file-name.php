<?php

if (! defined('ABSPATH')) {
    exit;
}

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Debug_Logger::migrate_legacy_log_file_name().

// Earlier versions stored the debug log as "{uuid}-log.txt". Web servers serve
// .txt files as text/plain, while many hosts and WAFs already deny *.log.
$file_name = basename((string) $file_name);

if ('.txt' !== strtolower(substr($file_name, -4))) {
    return $file_name;
}

$stem          = substr($file_name, 0, -4);
$new_file_name = ('-log' === substr($stem, -4) ? substr($stem, 0, -4) . '-debug' : $stem) . '.log';

$renames = [ $this->get_log_file_path($file_name) => $this->get_log_file_path($new_file_name) ];
for ($i = 1; $i <= self::MAX_ROTATED_FILES; $i++) {
    $renames[ $this->get_rotated_file_path($file_name, $i) ] = $this->get_rotated_file_path($new_file_name, $i);
}

foreach ($renames as $from => $to) {
    if (! file_exists($from)) {
        continue;
    }

    if (file_exists($to)) {
        // Keep the old file name when the target exists, so no log content is lost.
        return $file_name;
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Renames the plugin-owned debug log file.
    if (! @rename($from, $to)) {
        // Retry on a later request, and keep writing to the old file until then.
        return $file_name;
    }
}

$lock_path = $this->get_log_file_path($file_name) . '.lock';
if (file_exists($lock_path)) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink,WordPress.WP.AlternativeFunctions.unlink_unlink -- Removes the lock file of the renamed plugin-owned debug log.
    @unlink($lock_path);
}

update_option('_ppcart_log_file', $new_file_name);

return $new_file_name;
