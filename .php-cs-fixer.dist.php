<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['var', 'assets'])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'phpdoc_to_comment' => [
            'ignored_tags' => ['var'],
        ],
    ])
    ->setFinder($finder)
;
