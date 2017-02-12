<?php

namespace Anagit;

return [
    'routes' => [
        [
            'name' => 'run',
            'route' => '',
            'description' => 'Run git repository analyze.',
            'short_description' => 'Run git repository analyze.',
            'defaults' => [
                'path' => realpath(getcwd()),
            ],
            'handler' => Command\RunAnalyze::class,
        ],
        [
            'name' => 'clear-cache',
            'route' => '',
            'description' => 'Clear all cached content.',
            'short_description' => 'Clear the static cache.',
            'defaults' => [
                'path' => realpath(getcwd()),
            ],
            'handler' => Command\ClearCache::class,
        ],
    ]
];