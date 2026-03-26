<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Config;

use LiquidRazor\FileLocator\Config\DiscoveryConfigFactory;
use LiquidRazor\FileLocator\Config\DiscoveryConfigMerger;
use LiquidRazor\FileLocator\Config\DiscoveryConfigValidator;
use LiquidRazor\FileLocator\Enum\UnreadablePathMode;
use LiquidRazor\FileLocator\Exception\InvalidDiscoveryConfigException;
use LiquidRazor\FileLocator\Filesystem\PathNormalizer;
use LiquidRazor\FileLocator\Tests\Support\Assert;
use LiquidRazor\FileLocator\Tests\Support\TemporaryFilesystem;

function defaultRawDiscoveryConfig(): array
{
    return [
        'discovery' => [
            'defaults' => [
                'follow_symlinks' => false,
                'include_hidden' => false,
                'on_unreadable' => 'skip',
                'extensions' => ['php'],
            ],
            'exclude' => [
                'vendor',
                'node_modules',
                '.git',
                '.idea',
                '.vscode',
                'var/cache',
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
                    'enabled' => true,
                    'path' => 'lib',
                    'recursive' => true,
                    'extensions' => ['php'],
                    'exclude' => [],
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
    ];
}

return [
    test('discovery config factory builds config from library defaults when no project override exists', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $factory = new DiscoveryConfigFactory(
                pathNormalizer: new PathNormalizer(),
                loadConfig: static function (string $path): array {
                    if (str_ends_with($path, '/resources/config/roots.yaml')) {
                        return defaultRawDiscoveryConfig();
                    }

                    throw new \RuntimeException(sprintf('Unexpected config load for "%s".', $path));
                },
                mergeConfig: static function (array $defaults, array $overrides): array {
                    return (new DiscoveryConfigMerger())->merge($defaults, $overrides);
                },
                validateConfig: static function (array $config, string $projectRoot): array {
                    return (new DiscoveryConfigValidator())->validate($config, $projectRoot);
                },
            );

            $config = $factory->create($filesystem->rootPath);

            Assert::same($filesystem->rootPath, $config->projectRoot);
            Assert::same(
                [
                    $filesystem->rootPath . '/vendor',
                    $filesystem->rootPath . '/node_modules',
                    $filesystem->rootPath . '/.git',
                    $filesystem->rootPath . '/.idea',
                    $filesystem->rootPath . '/.vscode',
                    $filesystem->rootPath . '/var/cache',
                ],
                $config->exclude
            );
            Assert::false($config->defaults->followSymlinks);
            Assert::false($config->defaults->includeHidden);
            Assert::same(UnreadablePathMode::Skip, $config->defaults->onUnreadable);
            Assert::same(['php'], $config->defaults->extensions);
            Assert::same(['include', 'lib', 'src'], array_map(
                static fn ($root) => $root->name,
                $config->roots
            ));
            Assert::same($filesystem->rootPath . '/include', $config->roots[0]->path);
            Assert::same($filesystem->rootPath . '/lib', $config->roots[1]->path);
            Assert::same($filesystem->rootPath . '/src', $config->roots[2]->path);
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('discovery config factory merges project override data and removes disabled roots', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('config/roots.yaml', <<<YAML
discovery:
  defaults:
    include_hidden: true

  exclude:
    - storage/tmp

  roots:
    lib:
      enabled: false

    src:
      exclude:
        - src/Legacy

    modules:
      path: modules
      recursive: true
YAML);

            $factory = new DiscoveryConfigFactory(
                pathNormalizer: new PathNormalizer(),
                loadConfig: static function (string $path) use ($filesystem): array {
                    if (str_ends_with($path, '/resources/config/roots.yaml')) {
                        return defaultRawDiscoveryConfig();
                    }

                    if ($path === $filesystem->rootPath . '/config/roots.yaml') {
                        return [
                            'discovery' => [
                                'defaults' => [
                                    'include_hidden' => true,
                                ],
                                'exclude' => [
                                    'storage/tmp',
                                ],
                                'roots' => [
                                    'lib' => [
                                        'enabled' => false,
                                    ],
                                    'src' => [
                                        'exclude' => [
                                            'src/Legacy',
                                        ],
                                    ],
                                    'modules' => [
                                        'path' => 'modules',
                                        'recursive' => true,
                                    ],
                                ],
                            ],
                        ];
                    }

                    throw new \RuntimeException(sprintf('Unexpected config load for "%s".', $path));
                },
                mergeConfig: static function (array $defaults, array $overrides): array {
                    return (new DiscoveryConfigMerger())->merge($defaults, $overrides);
                },
                validateConfig: static function (array $config, string $projectRoot): array {
                    return (new DiscoveryConfigValidator())->validate($config, $projectRoot);
                },
            );
            $config = $factory->create($filesystem->rootPath);

            Assert::true($config->defaults->includeHidden);
            Assert::same(
                [
                    $filesystem->rootPath . '/vendor',
                    $filesystem->rootPath . '/node_modules',
                    $filesystem->rootPath . '/.git',
                    $filesystem->rootPath . '/.idea',
                    $filesystem->rootPath . '/.vscode',
                    $filesystem->rootPath . '/var/cache',
                    $filesystem->rootPath . '/storage/tmp',
                ],
                $config->exclude
            );
            Assert::same(['include', 'src', 'modules'], array_map(
                static fn ($root) => $root->name,
                $config->roots
            ));
            Assert::same([$filesystem->rootPath . '/src/Legacy'], $config->roots[1]->exclude);
            Assert::same($filesystem->rootPath . '/modules', $config->roots[2]->path);
            Assert::same(['php'], $config->roots[2]->extensions);
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('discovery config factory checks roots yaml before roots yml', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('config/roots.yaml', <<<YAML
discovery:
  defaults:
    include_hidden: true
YAML);
            $filesystem->writeFile('config/roots.yml', <<<YAML
discovery:
  defaults:
    include_hidden: false
YAML);

            $factory = new DiscoveryConfigFactory(
                pathNormalizer: new PathNormalizer(),
                loadConfig: static function (string $path) use ($filesystem): array {
                    if (str_ends_with($path, '/resources/config/roots.yaml')) {
                        return defaultRawDiscoveryConfig();
                    }

                    if ($path === $filesystem->rootPath . '/config/roots.yaml') {
                        return [
                            'discovery' => [
                                'defaults' => [
                                    'include_hidden' => true,
                                ],
                            ],
                        ];
                    }

                    if ($path === $filesystem->rootPath . '/config/roots.yml') {
                        return [
                            'discovery' => [
                                'defaults' => [
                                    'include_hidden' => false,
                                ],
                            ],
                        ];
                    }

                    throw new \RuntimeException(sprintf('Unexpected config load for "%s".', $path));
                },
                mergeConfig: static function (array $defaults, array $overrides): array {
                    return (new DiscoveryConfigMerger())->merge($defaults, $overrides);
                },
                validateConfig: static function (array $config, string $projectRoot): array {
                    return (new DiscoveryConfigValidator())->validate($config, $projectRoot);
                },
            );
            $config = $factory->create($filesystem->rootPath);

            Assert::true($config->defaults->includeHidden);
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('discovery config factory fails fast on invalid project override config', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('config/roots.yml', <<<YAML
discovery:
  defaults:
    include_hidden: maybe
YAML);

            $factory = new DiscoveryConfigFactory(
                pathNormalizer: new PathNormalizer(),
                loadConfig: static function (string $path) use ($filesystem): array {
                    if (str_ends_with($path, '/resources/config/roots.yaml')) {
                        return defaultRawDiscoveryConfig();
                    }

                    if ($path === $filesystem->rootPath . '/config/roots.yml') {
                        return [
                            'discovery' => [
                                'defaults' => [
                                    'include_hidden' => 'maybe',
                                ],
                            ],
                        ];
                    }

                    throw new \RuntimeException(sprintf('Unexpected config load for "%s".', $path));
                },
                mergeConfig: static function (array $defaults, array $overrides): array {
                    return (new DiscoveryConfigMerger())->merge($defaults, $overrides);
                },
                validateConfig: static function (array $config, string $projectRoot): array {
                    return (new DiscoveryConfigValidator())->validate($config, $projectRoot);
                },
            );

            $exception = Assert::throws(
                static function () use ($factory, $filesystem): void {
                    $factory->create($filesystem->rootPath);
                },
                InvalidDiscoveryConfigException::class
            );

            Assert::contains('discovery.defaults.include_hidden', $exception->getMessage());
        } finally {
            $filesystem->cleanup();
        }
    }),
];
