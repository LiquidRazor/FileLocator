<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Config;

use LiquidRazor\FileLocator\Config\DiscoveryConfigMerger;
use LiquidRazor\FileLocator\Exception\InvalidDiscoveryConfigException;
use LiquidRazor\FileLocator\Tests\Support\Assert;

return [
    test('discovery config merger overrides scalars and merges top level lists deterministically', static function (): void {
        $merger = new DiscoveryConfigMerger();

        $merged = $merger->merge(
            [
                'discovery' => [
                    'defaults' => [
                        'follow_symlinks' => false,
                        'include_hidden' => false,
                        'on_unreadable' => 'skip',
                        'extensions' => ['php'],
                    ],
                    'exclude' => ['vendor', '.git'],
                    'roots' => [],
                    'custom_default' => 'default-value',
                ],
            ],
            [
                'discovery' => [
                    'defaults' => [
                        'include_hidden' => true,
                        'extensions' => ['php', 'inc'],
                    ],
                    'exclude' => ['.git', 'var/cache'],
                    'custom_default' => 'override-value',
                    'custom_override' => 'project-only',
                ],
                'other' => [
                    'preserved' => true,
                ],
            ]
        );

        Assert::same(
            [
                'discovery' => [
                    'defaults' => [
                        'follow_symlinks' => false,
                        'include_hidden' => true,
                        'on_unreadable' => 'skip',
                        'extensions' => ['php', 'inc'],
                    ],
                    'exclude' => ['vendor', '.git', 'var/cache'],
                    'roots' => [],
                    'custom_default' => 'override-value',
                    'custom_override' => 'project-only',
                ],
                'other' => [
                    'preserved' => true,
                ],
            ],
            $merged
        );
    }),
    test('discovery config merger merges roots by key and preserves declaration order', static function (): void {
        $merger = new DiscoveryConfigMerger();

        $merged = $merger->merge(
            [
                'discovery' => [
                    'defaults' => [],
                    'exclude' => [],
                    'roots' => [
                        'include' => [
                            'enabled' => true,
                            'path' => 'include',
                            'recursive' => true,
                            'extensions' => ['php'],
                            'exclude' => [],
                        ],
                        'lib' => [
                            'enabled' => true,
                            'path' => 'lib',
                            'recursive' => true,
                            'extensions' => ['php'],
                            'exclude' => ['lib/Generated'],
                        ],
                    ],
                ],
            ],
            [
                'discovery' => [
                    'roots' => [
                        'lib' => [
                            'recursive' => false,
                            'extensions' => ['php', 'inc'],
                            'exclude' => ['lib/Generated', 'lib/Legacy'],
                            'custom' => 'preserved',
                        ],
                        'src' => [
                            'enabled' => true,
                            'path' => 'src',
                            'recursive' => true,
                            'extensions' => ['php'],
                            'exclude' => [],
                        ],
                    ],
                ],
            ]
        );

        Assert::same(
            [
                'include',
                'lib',
                'src',
            ],
            array_keys($merged['discovery']['roots'])
        );
        Assert::same(
            [
                'enabled' => true,
                'path' => 'lib',
                'recursive' => false,
                'extensions' => ['php', 'inc'],
                'exclude' => ['lib/Generated', 'lib/Legacy'],
                'custom' => 'preserved',
            ],
            $merged['discovery']['roots']['lib']
        );
        Assert::same(
            [
                'enabled' => true,
                'path' => 'src',
                'recursive' => true,
                'extensions' => ['php'],
                'exclude' => [],
            ],
            $merged['discovery']['roots']['src']
        );
    }),
    test('discovery config merger keeps disabled roots in the merged raw config', static function (): void {
        $merger = new DiscoveryConfigMerger();

        $merged = $merger->merge(
            [
                'discovery' => [
                    'defaults' => [],
                    'exclude' => [],
                    'roots' => [
                        'lib' => [
                            'enabled' => true,
                            'path' => 'lib',
                            'recursive' => true,
                            'extensions' => ['php'],
                            'exclude' => [],
                        ],
                    ],
                ],
            ],
            [
                'discovery' => [
                    'roots' => [
                        'lib' => [
                            'enabled' => false,
                            'exclude' => ['lib/Legacy'],
                        ],
                    ],
                ],
            ]
        );

        Assert::same(
            [
                'enabled' => false,
                'path' => 'lib',
                'recursive' => true,
                'extensions' => ['php'],
                'exclude' => ['lib/Legacy'],
            ],
            $merged['discovery']['roots']['lib']
        );
    }),
    test('discovery config merger rejects malformed merge sections', static function (): void {
        $merger = new DiscoveryConfigMerger();

        $missingDiscovery = Assert::throws(
            static function () use ($merger): void {
                $merger->merge([], []);
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('Missing required mapping "discovery".', $missingDiscovery->getMessage());

        $invalidExclude = Assert::throws(
            static function () use ($merger): void {
                $merger->merge(
                    [
                        'discovery' => [
                            'defaults' => [],
                            'exclude' => [],
                            'roots' => [],
                        ],
                    ],
                    [
                        'discovery' => [
                            'exclude' => [
                                'vendor' => true,
                            ],
                        ],
                    ]
                );
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('Expected "discovery.exclude" to be a list.', $invalidExclude->getMessage());
    }),
];
