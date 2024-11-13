<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        'single_line_throw' => false,
        'single_line_comment_spacing' => false,
        'declare_strict_types' => true,
        'blank_line_before_statement' => [
            'statements' => [
                'return',
            ],
        ],
        'phpdoc_to_comment' => [
            'ignored_tags' => [
                'var', /** @var */
                'see',
            ],
        ],
        'phpdoc_align' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile('.php-cs-fixer.cache')
    ->setLineEnding("\n")
    ->setRiskyAllowed(true)
;
