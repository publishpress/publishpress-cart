<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/admin',
        __DIR__ . '/includes',
        __DIR__ . '/models',
        __DIR__ . '/public',
    ])
    ->append([
        __DIR__ . '/publishpress-cart.php',
    ])
    ->exclude([
        'node_modules',
        'vendor',
    ])
    ->name('*.php')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP71Migration' => true,
        '@PHP73Migration' => true,
        '@PHP74Migration' => true,
        // PHPCBF (WordPress / mixed PHP+HTML) owns indent; these PSR12 rules loop against it.
        'indentation_type' => false,
        'statement_indentation' => false,
    ])
    ->setFinder($finder)
;
