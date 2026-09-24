<?php

/**
 * Rename Cart extension hook strings to ppcart_* / _ppcart_* in first-party PHP.
 *
 * Usage: php tests/bin/rename-hook-prefixes.php [--dry-run]
 */

$root = dirname(__DIR__, 2);
$dry_run = in_array('--dry-run', $argv, true);

$hook_pattern = '(?:do_action(?:_deprecated)?|apply_filters(?:_deprecated)?|add_action|add_filter|remove_action|remove_filter|has_action|has_filter|did_action|wp_schedule_event|wp_schedule_single_event|wp_next_scheduled|wp_clear_scheduled_hook)\s*\(\s*(["\'])';

$exclude_dirs = [
    'includes/compat/studiocart-compatibility-mode',
    'vendor',
    'lib/vendor',
    'node_modules',
    'tests',
];

$frozen = [
    'save_post_sc_',
    'manage_sc_',
    'bulk_actions-edit-sc_',
    'admin_action_sc_',
    'admin_post_sc_',
    'add_option_',
    'update_option_',
    'delete_option_',
    'option_',
    'default_option_',
];

$files_changed = 0;
$replacements = 0;

foreach (collect_php_files($root, $exclude_dirs) as $file) {
    $source = (string) file_get_contents($file);
    $updated = preg_replace_callback(
        '/' . $hook_pattern . '([^"\']+)\1/s',
        static function (array $matches) use ($frozen, &$replacements) {
            $quote = $matches[1];
            $hook = $matches[2];
            $mapped = map_hook_name($hook, $frozen);

            if ($mapped === $hook) {
                return $matches[0];
            }

            ++$replacements;

            return str_replace($hook, $mapped, $matches[0]);
        },
        $source,
        -1,
        $file_count
    );

  // Remove studiocart_checkout_complete hook calls from first-party code.
    $updated = preg_replace(
        '/^[ \t]*(?:do_action|apply_filters)\(\s*[\'"]studiocart_checkout_complete[\'"][^\n;]*;\s*\n?/m',
        '',
        $updated,
        -1,
        $removed
    );

    if ($source !== $updated) {
        ++$files_changed;

        if (! $dry_run) {
            file_put_contents($file, $updated);
        }

        $relative = str_replace($root . '/', '', $file);
        echo ($dry_run ? '[dry-run] ' : '') . "updated {$relative}\n";
    }
}

echo ($dry_run ? 'Would change' : 'Changed') . " {$files_changed} files, {$replacements} hook renames.\n";

function collect_php_files($root, array $exclude_dirs)
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $path = $file->getPathname();
        $relative = str_replace($root . '/', '', $path);
        $skip = false;

        foreach ($exclude_dirs as $exclude) {
            if (0 === strpos($relative, $exclude)) {
                $skip = true;
                break;
            }
        }

        if (! $skip) {
            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

function map_hook_name($hook, array $frozen)
{
    foreach ($frozen as $prefix) {
        if (0 === strpos($hook, $prefix)) {
            return $hook;
        }
    }

    if (0 === strpos($hook, '_sc_')) {
        return '_ppcart_' . substr($hook, 4);
    }

    if (0 === strpos($hook, 'sc_')) {
        return 'ppcart_' . substr($hook, 3);
    }

    if (0 === strpos($hook, 'ncs_')) {
        return 'ppcart_' . substr($hook, 4);
    }

    if (0 === strpos($hook, 'nsc_')) {
        return 'ppcart_' . substr($hook, 4);
    }

    if (0 === strpos($hook, 'studiocart_')) {
        return 'ppcart_' . substr($hook, 11);
    }

    return $hook;
}
