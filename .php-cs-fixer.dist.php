<?php

use PhpCsFixer\Config;

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__);

return (new Config())
    ->setRules([
        '@Symfony' => true,
        'yoda_style' => false, // Do not enforce Yoda style (add unit tests instead...)
        'ordered_imports' => true,
    ])
    ->setFinder($finder);
