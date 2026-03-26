<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Config;

use LiquidRazor\FileLocator\Config\DiscoveryConfigValidator;
use LiquidRazor\FileLocator\Exception\InvalidDiscoveryConfigException;
use LiquidRazor\FileLocator\Tests\Support\Assert;

return [
    test('discovery config validator normalizes valid merged config into stable scalar and path values', static function (): void {
        $validator = new DiscoveryConfigValidator();

        $validated = $validator->validate(
            [
                'discovery' => [
                    'defaults' => [
                        'follow_symlinks' => false,
                        'include_hidden' => false,
                        'on_unreadable' => 'skip',
                        'extensions' => ['.PHP', 'php'],
                    ],
                    'exclude' => [
                        'vendor',
                        'var/cache',
                        '/project/vendor',
                    ],
                    'roots' => [
                        'include' => [
                            'enabled' => true,
                            'path' => 'include',
                            'recursive' => true,
                            'extensions' => ['php'],
                            'exclude' => [],
                        ],
                        'lib' => [
                            'enabled' => false,
                        ],
                        'src' => [
                            'enabled' => true,
                            'path' => 'src/./Domain/..',
                            'recursive' => true,
                            'extensions' => ['.PHP', 'php'],
                            'exclude' => [
                                'src/Legacy',
                                '/project/src/Legacy',
                            ],
                        ],
                        'modules' => [
                            'path' => 'modules',
                            'recursive' => true,
                        ],
                    ],
                ],
            ],
            '/project'
        );

        Assert::same(
            [
                'discovery' => [
                    'defaults' => [
                        'follow_symlinks' => false,
                        'include_hidden' => false,
                        'on_unreadable' => 'skip',
                        'extensions' => ['php'],
                    ],
                    'exclude' => [
                        '/project/vendor',
                        '/project/var/cache',
                    ],
                    'roots' => [
                        'include' => [
                            'enabled' => true,
                            'path' => '/project/include',
                            'recursive' => true,
                            'extensions' => ['php'],
                            'exclude' => [],
                        ],
                        'src' => [
                            'enabled' => true,
                            'path' => '/project/src',
                            'recursive' => true,
                            'extensions' => ['php'],
                            'exclude' => [
                                '/project/src/Legacy',
                            ],
                        ],
                        'modules' => [
                            'enabled' => true,
                            'path' => '/project/modules',
                            'recursive' => true,
                            'extensions' => ['php'],
                            'exclude' => [],
                        ],
                    ],
                ],
            ],
            $validated
        );
    }),
    test('discovery config validator rejects unsupported keys at every schema level', static function (): void {
        $validator = new DiscoveryConfigValidator();

        $topLevel = Assert::throws(
            static function () use ($validator): void {
                $validator->validate(
                    [
                        'discovery' => [
                            'defaults' => [
                                'follow_symlinks' => false,
                                'include_hidden' => false,
                                'on_unreadable' => 'skip',
                                'extensions' => ['php'],
                            ],
                            'exclude' => [],
                            'roots' => [],
                        ],
                        'other' => true,
                    ],
                    '/project'
                );
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('Unsupported key "other" in "config".', $topLevel->getMessage());

        $rootKey = Assert::throws(
            static function () use ($validator): void {
                $validator->validate(
                    [
                        'discovery' => [
                            'defaults' => [
                                'follow_symlinks' => false,
                                'include_hidden' => false,
                                'on_unreadable' => 'skip',
                                'extensions' => ['php'],
                            ],
                            'exclude' => [],
                            'roots' => [
                                'src' => [
                                    'path' => 'src',
                                    'recursive' => true,
                                    'unexpected' => true,
                                ],
                            ],
                        ],
                    ],
                    '/project'
                );
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('Unsupported key "unexpected" in "discovery.roots.src".', $rootKey->getMessage());
    }),
    test('discovery config validator rejects invalid types and invalid unreadable mode', static function (): void {
        $validator = new DiscoveryConfigValidator();

        $boolException = Assert::throws(
            static function () use ($validator): void {
                $validator->validate(
                    [
                        'discovery' => [
                            'defaults' => [
                                'follow_symlinks' => 'false',
                                'include_hidden' => false,
                                'on_unreadable' => 'skip',
                                'extensions' => ['php'],
                            ],
                            'exclude' => [],
                            'roots' => [],
                        ],
                    ],
                    '/project'
                );
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('discovery.defaults.follow_symlinks', $boolException->getMessage());
        Assert::contains('boolean', $boolException->getMessage());

        $modeException = Assert::throws(
            static function () use ($validator): void {
                $validator->validate(
                    [
                        'discovery' => [
                            'defaults' => [
                                'follow_symlinks' => false,
                                'include_hidden' => false,
                                'on_unreadable' => 'ignore',
                                'extensions' => ['php'],
                            ],
                            'exclude' => [],
                            'roots' => [],
                        ],
                    ],
                    '/project'
                );
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('skip, fail', $modeException->getMessage());
    }),
    test('discovery config validator rejects non php extensions after canonicalization', static function (): void {
        $validator = new DiscoveryConfigValidator();

        $exception = Assert::throws(
            static function () use ($validator): void {
                $validator->validate(
                    [
                        'discovery' => [
                            'defaults' => [
                                'follow_symlinks' => false,
                                'include_hidden' => false,
                                'on_unreadable' => 'skip',
                                'extensions' => ['php', '.inc'],
                            ],
                            'exclude' => [],
                            'roots' => [],
                        ],
                    ],
                    '/project'
                );
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('Unsupported extension ".inc"', $exception->getMessage());
    }),
    test('discovery config validator rejects invalid root structure and missing required values for enabled roots', static function (): void {
        $validator = new DiscoveryConfigValidator();

        $nameException = Assert::throws(
            static function () use ($validator): void {
                $validator->validate(
                    [
                        'discovery' => [
                            'defaults' => [
                                'follow_symlinks' => false,
                                'include_hidden' => false,
                                'on_unreadable' => 'skip',
                                'extensions' => ['php'],
                            ],
                            'exclude' => [],
                            'roots' => [
                                '' => [
                                    'path' => 'src',
                                    'recursive' => true,
                                ],
                            ],
                        ],
                    ],
                    '/project'
                );
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('non-empty string keys', $nameException->getMessage());

        $requiredException = Assert::throws(
            static function () use ($validator): void {
                $validator->validate(
                    [
                        'discovery' => [
                            'defaults' => [
                                'follow_symlinks' => false,
                                'include_hidden' => false,
                                'on_unreadable' => 'skip',
                                'extensions' => ['php'],
                            ],
                            'exclude' => [],
                            'roots' => [
                                'src' => [
                                    'enabled' => true,
                                ],
                            ],
                        ],
                    ],
                    '/project'
                );
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('discovery.roots.src.path', $requiredException->getMessage());
    }),
    test('discovery config validator rejects invalid path values during normalization', static function (): void {
        $validator = new DiscoveryConfigValidator();

        $exception = Assert::throws(
            static function () use ($validator): void {
                $validator->validate(
                    [
                        'discovery' => [
                            'defaults' => [
                                'follow_symlinks' => false,
                                'include_hidden' => false,
                                'on_unreadable' => 'skip',
                                'extensions' => ['php'],
                            ],
                            'exclude' => ['../../etc'],
                            'roots' => [],
                        ],
                    ],
                    '/project'
                );
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('escape above the project root', $exception->getMessage());
    }),
];
