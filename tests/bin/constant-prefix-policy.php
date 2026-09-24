<?php

/**
 * First-party PHP outside Compatibility Mode must not define or read legacy
 * NCS_CART_* / SC_STRIPE_CONNECT_* runtime constants.
 */

$root = dirname(__DIR__, 2);

assert_no_legacy_runtime_constants_outside_compat($root);

echo "Constant prefix compatibility assertions passed.\n";

function assert_no_legacy_runtime_constants_outside_compat($root)
{
    $pattern = '/\b(?:NCS_CART_(?!LEGACY_COMPAT\b)\w+|SC_STRIPE_CONNECT_\w+|NCS_MINIMUM_(?:PHP|WP)_VERSION|NCS_STYLESHEETPATH)\b/';
    $exclude = [
        'includes/compat/',
        'tests/',
        'vendor/',
        'lib/vendor/',
        'languages/',
        'graphify-out/',
        'node_modules/',
        '.web/',
        'dev-workspace-cache/',
        'dist/',
    ];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY,
        RecursiveIteratorIterator::CATCH_GET_CHILD
    );
    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $path = $file->getPathname();
        if (! is_readable($path)) {
            continue;
        }
        $relative = str_replace($root . '/', '', $path);
        foreach ($exclude as $prefix) {
            if (strpos($relative, $prefix) === 0) {
                continue 2;
            }
        }

        $source = (string) file_get_contents($path);
        if (! preg_match_all($pattern, $source, $matches)) {
            continue;
        }

        $hits = array_values(array_unique($matches[0]));
        fail('Legacy runtime constant outside Compatibility Mode: ' . $relative . ' (' . implode(', ', $hits) . ')');
    }
}

function fail($message)
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}
