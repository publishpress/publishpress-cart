<?php

/**
 * Generate and validate the documented WordPress hook surface.
 */

$root = dirname(__DIR__, 2);
$repoName = detect_repository_name($root);
$update = in_array('--update', $argv, true);
$checkPolicy = in_array('--check-policy', $argv, true);

$config = [
    'publishpress-cart' => [
        'source_paths' => ['admin', 'api', 'includes', 'models', 'public', 'publishpress-cart.php'],
        'manifest_json' => 'docs/hooks-manifest.json',
        'manifest_md' => 'docs/hooks-manifest.md',
        'title' => 'PublishPress Cart Hook Manifest',
    ],
    'publishpress-cart-pro' => [
        'source_paths' => ['admin', 'api', 'includes', 'modules', 'public', 'publishpress-cart-pro.php'],
        'manifest_json' => 'docs/hooks-manifest.json',
        'manifest_md' => 'docs/hooks-manifest.md',
        'title' => 'PublishPress Cart Pro Hook Manifest',
    ],
];

if (! isset($config[$repoName])) {
    fwrite(STDERR, "Unsupported repository: {$repoName}\n");
    exit(1);
}

$settings = $config[$repoName];

$operations = [
    'do_action' => ['type' => 'action'],
    'do_action_deprecated' => ['type' => 'action'],
    'apply_filters' => ['type' => 'filter'],
    'apply_filters_deprecated' => ['type' => 'filter'],
    'add_action' => ['type' => 'action'],
    'add_filter' => ['type' => 'filter'],
];

$coreHooks = array_fill_keys([
    'admin_bar_menu',
    'admin_enqueue_scripts',
    'admin_init',
    'admin_menu',
    'admin_notices',
    'before_delete_post',
    'current_screen',
    'delete_user',
    'edit_form_after_title',
    'init',
    'plugins_loaded',
    'post_row_actions',
    'pre_get_posts',
    'restrict_manage_posts',
    'save_post',
    'template_redirect',
    'the_content',
    'user_register',
    'wp',
    'wp_enqueue_scripts',
    'wp_footer',
    'wp_loaded',
    'wp_login',
], true);

$thirdPartyPrefixes = [
    'arm_',
    'mepr-',
    'tutor_',
];

$hooks = [];
$dynamicPatterns = [];

foreach (collect_php_files($root, $settings['source_paths']) as $file) {
    $relativeFile = relative_path($root, $file);
    $tokens = token_get_all((string) file_get_contents($file));
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];

        if (! is_array($token) || T_STRING !== $token[0] || ! isset($operations[$token[1]])) {
            continue;
        }

        $operation = $token[1];
        $open = next_non_whitespace($tokens, $i + 1);
        if ($open === null || ($tokens[$open] ?? null) !== '(') {
            continue;
        }

        $arg = first_argument_tokens($tokens, $open + 1);
        if ($arg === []) {
            continue;
        }

        $name = static_string_argument($arg);
        $dynamic = false;

        if ($name === null) {
            $name = dynamic_pattern($arg);
            $dynamic = true;
        }

        if ($name === '') {
            continue;
        }

        $record = classify_hook($name, $operation, $operations[$operation]['type'], $relativeFile, $dynamic, $coreHooks, $thirdPartyPrefixes);
        $key = $record['operation'] . '|' . $record['name'] . '|' . $record['file'];
        $hooks[$key] = $record;

        if ($dynamic) {
            $dynamicPatterns[$record['name']] = [
                'pattern' => $record['name'],
                'operation' => $record['operation'],
                'file' => $record['file'],
                'classification' => $record['classification'],
                'status' => $record['status'],
                'recommended_pattern' => $record['recommended_hook'],
            ];
        }
    }
}
add_price_formatting_compatibility_hooks($hooks, $root, $repoName, $coreHooks, $thirdPartyPrefixes);

$hooks = array_values($hooks);
usort($hooks, static function ($a, $b) {
    return [$a['name'], $a['operation'], $a['file']] <=> [$b['name'], $b['operation'], $b['file']];
});

$dynamicPatterns = array_values($dynamicPatterns);
usort($dynamicPatterns, static function ($a, $b) {
    return [$a['pattern'], $a['operation']] <=> [$b['pattern'], $b['operation']];
});
$manifest = [
    'generated_at' => gmdate('Y-m-d'),
    'source' => 'static PHP token audit of ' . $repoName,
    'policy' => [
        'default_new_hook_prefix' => 'ppcart_',
        'allowed_existing_public_prefixes' => ['ppcart_', '_ppcart_'],
        'allowed_existing_internal_prefixes' => [],
        'legacy_compat_prefixes' => ['sc_', 'studiocart_', '_sc_', 'ncs_', 'nsc_'],
        'disallowed_new_prefixes_or_patterns' => ['ncs-cart-*', 'nsc_*', 'pp_cart_*', 'sc_', 'studiocart_', '_sc_'],
    ],
    'hooks' => $hooks,
    'overlaps' => overlaps_for_repo($repoName),
    'dynamic_patterns' => $dynamicPatterns,
];

$json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
$markdown = render_markdown($settings['title'], $repoName, $manifest);

$jsonPath = $root . '/' . $settings['manifest_json'];
$mdPath = $root . '/' . $settings['manifest_md'];

if ($checkPolicy) {
    check_policy($manifest);
}

if ($update) {
    if (! is_dir(dirname($jsonPath))) {
        mkdir(dirname($jsonPath), 0777, true);
    }
    file_put_contents($jsonPath, $json);
    file_put_contents($mdPath, $markdown);
    echo "Updated {$settings['manifest_json']} and {$settings['manifest_md']} with " . count($hooks) . " hook entries.\n";
    exit(0);
}

$errors = [];
if (! is_file($jsonPath) || (string) file_get_contents($jsonPath) !== $json) {
    $errors[] = $settings['manifest_json'];
}
if (! is_file($mdPath) || (string) file_get_contents($mdPath) !== $markdown) {
    $errors[] = $settings['manifest_md'];
}

if ($errors !== []) {
    fwrite(STDERR, "Hook manifest is out of date: " . implode(', ', $errors) . "\nRun: php tests/bin/hook-manifest.php --update\n");
    exit(1);
}

echo "Hook manifest matches source (" . count($hooks) . " hook entries).\n";

function detect_repository_name($root)
{
    $composerPath = $root . '/composer.json';

    if (is_file($composerPath)) {
        $composer = json_decode((string) file_get_contents($composerPath), true);

        if (is_array($composer)) {
            $pluginFolder = $composer['extra']['plugin-folder'] ?? null;
            if (is_string($pluginFolder) && $pluginFolder !== '') {
                return $pluginFolder;
            }

            $packageName = $composer['name'] ?? null;
            if (is_string($packageName) && strpos($packageName, '/') !== false) {
                return substr($packageName, strrpos($packageName, '/') + 1);
            }
        }
    }

    if (is_file($root . '/publishpress-cart-pro.php')) {
        return 'publishpress-cart-pro';
    }

    if (is_file($root . '/publishpress-cart.php')) {
        return 'publishpress-cart';
    }

    return basename($root);
}

function add_price_formatting_compatibility_hooks(array &$hooks, $root, $repoName, array $coreHooks, array $thirdPartyPrefixes)
{
    if ($repoName !== 'publishpress-cart') {
        return;
    }

    $sources = [
        $root . '/includes/compat/studiocart-compatibility-mode/hooks.php',
        $root . '/includes/compat/studiocart-compatibility-mode/maps/hooks.php',
    ];

    foreach ($sources as $file) {
        if (! is_file($file)) {
            continue;
        }

        $source = (string) file_get_contents($file);
        foreach ([ 'nsc_run_price_formatting', 'sc_run_price_formatting' ] as $hookName) {
            if (strpos($source, "'" . $hookName . "'") === false && strpos($source, '"' . $hookName . '"') === false) {
                continue;
            }

            $record = classify_hook(
                $hookName,
                'add_action',
                'action',
                'includes/compat/studiocart-compatibility-mode/hooks.php',
                false,
                $coreHooks,
                $thirdPartyPrefixes
            );
            $key = $record['operation'] . '|' . $record['name'] . '|' . $record['file'];
            $hooks[$key] = $record;
        }
    }
}

function collect_php_files($root, array $sourcePaths)
{
    $files = [];
    foreach ($sourcePaths as $sourcePath) {
        $path = $root . '/' . $sourcePath;
        if (is_file($path) && substr($path, -4) === '.php') {
            $files[] = $path;
            continue;
        }
        if (! is_dir($path)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $pathName = $file->getPathname();
            if ($file->getExtension() === 'php' && ! excluded_path($pathName)) {
                $files[] = $pathName;
            }
        }
    }
    sort($files, SORT_STRING);
    return $files;
}

function excluded_path($path)
{
    foreach (['/vendor/', '/lib/vendor/', '/node_modules/', '/dist/'] as $excluded) {
        if (strpos($path, $excluded) !== false) {
            return true;
        }
    }
    return false;
}

function next_non_whitespace(array $tokens, $index)
{
    $count = count($tokens);
    for ($i = $index; $i < $count; $i++) {
        if (is_array($tokens[$i]) && T_WHITESPACE === $tokens[$i][0]) {
            continue;
        }
        return $i;
    }
    return null;
}

function first_argument_tokens(array $tokens, $index)
{
    $arg = [];
    $depth = 0;
    $count = count($tokens);

    for ($i = $index; $i < $count; $i++) {
        $token = $tokens[$i];
        if ($token === '(' || $token === '[' || $token === '{') {
            $depth++;
        } elseif ($token === ')' || $token === ']' || $token === '}') {
            if ($token === ')' && $depth === 0) {
                break;
            }
            if ($depth > 0) {
                $depth--;
            }
        } elseif ($token === ',' && $depth === 0) {
            break;
        }
        $arg[] = $token;
    }

    return trim_tokens($arg);
}

function trim_tokens(array $tokens)
{
    while ($tokens !== [] && is_array($tokens[0]) && T_WHITESPACE === $tokens[0][0]) {
        array_shift($tokens);
    }
    while ($tokens !== [] && is_array($tokens[count($tokens) - 1]) && T_WHITESPACE === $tokens[count($tokens) - 1][0]) {
        array_pop($tokens);
    }
    return $tokens;
}

function static_string_argument(array $tokens)
{
    if (count($tokens) !== 1 || ! is_array($tokens[0]) || T_CONSTANT_ENCAPSED_STRING !== $tokens[0][0]) {
        return null;
    }
    return stripcslashes(substr($tokens[0][1], 1, -1));
}

function dynamic_pattern(array $tokens)
{
    $parts = [];
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if ($token === '.' || $token === '"' || $token === '{' || $token === '}' || (is_array($token) && T_WHITESPACE === $token[0])) {
            continue;
        }
        if (is_array($token) && defined('T_CURLY_OPEN') && T_CURLY_OPEN === $token[0]) {
            continue;
        }
        if (is_array($token) && defined('T_DOLLAR_OPEN_CURLY_BRACES') && T_DOLLAR_OPEN_CURLY_BRACES === $token[0]) {
            continue;
        }
        if (is_array($token) && T_CONSTANT_ENCAPSED_STRING === $token[0]) {
            $parts[] = stripcslashes(substr($token[1], 1, -1));
            continue;
        }
        if (is_array($token) && T_VARIABLE === $token[0]) {
            $expr = $token[1];
            $j = $i + 1;
            while ($j + 1 < $count && $tokens[$j] === '->' && is_array($tokens[$j + 1])) {
                $expr .= '->' . $tokens[$j + 1][1];
                $j += 2;
            }
            if ($j < $count && $tokens[$j] === '[') {
                $expr .= '[...]';
                while ($j < $count && $tokens[$j] !== ']') {
                    $j++;
                }
            }
            $parts[] = '{' . $expr . '}';
            $i = max($i, $j - 1);
            continue;
        }
        if (is_array($token) && T_ENCAPSED_AND_WHITESPACE === $token[0]) {
            $parts[] = $token[1];
            continue;
        }
        if (is_array($token)) {
            $parts[] = '{' . $token[1] . '}';
        } else {
            $parts[] = $token;
        }
    }

    return preg_replace('/\s+/', '', implode('', $parts));
}

function classify_hook($name, $operation, $type, $file, $dynamic, array $coreHooks, array $thirdPartyPrefixes)
{
    $classification = 'public';
    $status = 'canonical';
    $recommended = $name;
    $notes = '';
    $is_compat_package = strpos($file, 'includes/compat/studiocart-compatibility-mode/') === 0;

    if (isset($coreHooks[$name]) || strpos($name, 'wp_ajax_') === 0 || strpos($name, 'wp_ajax_nopriv_') === 0 || strpos($name, 'manage_') === 0 || strpos($name, 'load-') === 0) {
        $classification = 'external';
        $status = 'wordpress_core';
        $recommended = null;
        $notes = 'WordPress core hook.';
    }

    foreach ($thirdPartyPrefixes as $prefix) {
        if (strpos($name, $prefix) === 0) {
            $classification = 'external';
            $status = 'third_party';
            $recommended = null;
            $notes = 'Third-party integration hook.';
        }
    }

    if ($classification !== 'external') {
        if (strpos($name, '_ppcart_') === 0 || strpos($name, 'ppcart_') === 0) {
            $status = 'canonical';
            $notes = 'Canonical hook prefix.';
        } elseif ($is_compat_package && (strpos($name, 'studiocart_') === 0 || strpos($name, '_sc_') === 0 || strpos($name, 'sc_') === 0 || strpos($name, 'ncs_') === 0 || strpos($name, 'nsc_') === 0)) {
            $status = 'legacy_compat';
            $notes = 'StudioCart Compatibility Mode bridge hook.';
        } elseif (strpos($name, 'studiocart_') === 0) {
            $status = 'legacy_public';
            $recommended = 'ppcart_' . substr($name, 11);
            $notes = 'Legacy public hook. Use ppcart_* in first-party code; bridge in Compatibility Mode.';
        } elseif (strpos($name, '_sc_') === 0) {
            $status = 'legacy_public';
            $recommended = '_ppcart_' . substr($name, 4);
            $notes = 'Legacy settings registration hook. Use _ppcart_* in first-party code.';
        } elseif (strpos($name, 'sc_') === 0) {
            $status = 'legacy_public';
            $recommended = 'ppcart_' . substr($name, 3);
            $notes = 'Legacy public hook. Use ppcart_* in first-party code; bridge in Compatibility Mode.';
        } elseif (strpos($name, 'nsc_') === 0) {
            $classification = 'deprecated';
            $status = 'deprecated';
            $recommended = 'ppcart_' . substr($name, 4);
            $notes = 'Legacy typo/outlier. Cron drain only; do not add new hooks.';
        } elseif (strpos($name, 'ncs-cart-') !== false) {
            $classification = 'deprecated';
            $status = 'deprecated';
            $recommended = 'ppcart_' . str_replace('-', '_', preg_replace('/^.*ncs-cart-/', '', $name));
            $notes = 'Legacy admin UI hook generated from plugin_name. Do not use for new hooks.';
        } elseif (strpos($name, 'pp_cart_') === 0) {
            $classification = 'deprecated';
            $status = 'removed';
            $recommended = preg_replace('/^pp_cart_/', 'ppcart_', $name);
            $notes = 'Removed rebrand hook family. Do not reintroduce.';
        } elseif ($dynamic && strpos($name, '{$this->post_type}_') === 0) {
            $classification = 'deprecated';
            $status = 'deprecated';
            $recommended = 'ppcart_product_' . substr($name, strlen('{$this->post_type}_'));
            $notes = 'Legacy dynamic metabox pattern. Use the ppcart_product_* canonical pattern.';
        } elseif ($dynamic && strpos($name, '{$this->plugin_name}') !== false) {
            $classification = 'deprecated';
            $status = 'deprecated';
            $recommended = str_replace('{$this->plugin_name}', 'ppcart', $name);
            $notes = 'Legacy dynamic admin UI pattern generated from plugin_name.';
        } elseif (strpos($name, 'ncs_') === 0) {
            $classification = 'internal';
            $status = 'legacy_compat';
            $recommended = 'ppcart_' . substr($name, 4);
            $notes = 'Legacy internal hook. Bridge in Compatibility Mode or cron drain.';
        } else {
            $classification = 'internal';
            $status = $dynamic ? 'dynamic' : 'unclassified';
            $notes = 'Review before documenting as public API.';
        }
    }

    if (strpos($operation, '_deprecated') !== false) {
        $classification = 'deprecated';
        $status = 'deprecated';
    }

    return [
        'name' => $name,
        'type' => $type,
        'operation' => $operation,
        'classification' => $classification,
        'status' => $status,
        'file' => $file,
        'recommended_hook' => $recommended,
        'notes' => $notes,
    ];
}

function overlaps_for_repo($repoName)
{
    if ($repoName !== 'publishpress-cart') {
        return [];
    }

    return [
        [
            'canonical' => 'ppcart_after_order_paid',
            'legacy' => 'studiocart_checkout_complete',
            'type' => 'action',
            'migration' => 'Use ppcart_after_order_paid for payment-complete workflows. studiocart_checkout_complete bridges in publishpress-cart-compat when Compatibility Mode is on.',
        ],
    ];
}

function render_markdown($title, $repoName, array $manifest)
{
    $lines = [];
    $lines[] = '# ' . $title;
    $lines[] = '';
    $lines[] = 'Generated on ' . $manifest['generated_at'] . ' from a static PHP token audit of `' . $repoName . '`.';
    $lines[] = '';
    $lines[] = '## Prefix Policy';
    $lines[] = '';
    $lines[] = 'The default prefix for new public extension hooks is `ppcart_`. StudioCart-era hook names (`sc_*`, `studiocart_*`, `_sc_*`, `ncs_*`, `nsc_*`) live only in StudioCart Compatibility Mode. Canonical AJAX routes are `wp_ajax_ppcart_*`; Compatibility Mode dual-registers shipped `wp_ajax_sc_*` / `wp_ajax_ncs_*` names. Frozen CPT-derived and `admin_action_sc_*` hooks stay unchanged.';
    $lines[] = '';
    $lines[] = '## Hooks';
    $lines[] = '';
    $lines[] = '| Hook | Type | Operation | Classification | Status | File | Recommendation |';
    $lines[] = '| --- | --- | --- | --- | --- | --- | --- |';
    foreach ($manifest['hooks'] as $hook) {
        $recommendation = $hook['recommended_hook'] ?: '';
        $lines[] = '| `' . md_escape($hook['name']) . '` | ' . ucfirst($hook['type']) . ' | `' . $hook['operation'] . '` | ' . ucfirst($hook['classification']) . ' | ' . $hook['status'] . ' | `' . $hook['file'] . '` | ' . md_escape($recommendation) . ' |';
    }
    $lines[] = '';
    $lines[] = '## Overlapping And Legacy Hooks';
    $lines[] = '';
    if ($manifest['overlaps'] === []) {
        $lines[] = 'No overlapping hook pairs are documented for this repository.';
    } else {
        $lines[] = '| Canonical | Legacy | Type | Migration |';
        $lines[] = '| --- | --- | --- | --- |';
        foreach ($manifest['overlaps'] as $overlap) {
            $lines[] = '| `' . md_escape($overlap['canonical']) . '` | `' . md_escape($overlap['legacy']) . '` | ' . ucfirst($overlap['type']) . ' | ' . md_escape($overlap['migration']) . ' |';
        }
    }
    $lines[] = '';
    $lines[] = '## Dynamic Patterns';
    $lines[] = '';
    if ($manifest['dynamic_patterns'] === []) {
        $lines[] = 'No dynamic custom hook patterns are present in this repository.';
    } else {
        $lines[] = '| Pattern | Operation | Classification | Status | File | Recommendation |';
        $lines[] = '| --- | --- | --- | --- | --- | --- |';
        foreach ($manifest['dynamic_patterns'] as $pattern) {
            $lines[] = '| `' . md_escape($pattern['pattern']) . '` | `' . $pattern['operation'] . '` | ' . ucfirst($pattern['classification']) . ' | ' . $pattern['status'] . ' | `' . $pattern['file'] . '` | `' . md_escape((string) $pattern['recommended_pattern']) . '` |';
        }
    }
    $lines[] = '';

    return implode("\n", $lines);
}

function md_escape($value)
{
    return str_replace('|', '\\|', (string) $value);
}

function check_policy(array $manifest)
{
    $errors = [];
    foreach ($manifest['hooks'] as $hook) {
        if ($hook['classification'] === 'deprecated' && $hook['status'] === 'removed') {
            $errors[] = $hook['file'] . ': removed hook family used by ' . $hook['name'];
        }
    }

    if ($errors !== []) {
        fwrite(STDERR, implode("\n", $errors) . "\n");
        exit(1);
    }
}

function relative_path($root, $path)
{
    return ltrim(str_replace('\\', '/', substr($path, strlen($root))), '/');
}
