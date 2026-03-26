<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Config;

final class YamlLoaderTestHook
{
    public static ?bool $yamlExtensionLoaded = null;

    /**
     * @var null|\Closure(string): mixed
     */
    public static ?\Closure $yamlParseFileHandler = null;

    public static int $yamlParseFileCalls = 0;

    public static ?string $lastYamlParseFilePath = null;

    public static function reset(): void
    {
        self::$yamlExtensionLoaded = null;
        self::$yamlParseFileHandler = null;
        self::$yamlParseFileCalls = 0;
        self::$lastYamlParseFilePath = null;
    }
}

function extension_loaded(string $name): bool
{
    if ($name === 'yaml' && YamlLoaderTestHook::$yamlExtensionLoaded !== null) {
        return YamlLoaderTestHook::$yamlExtensionLoaded;
    }

    return \extension_loaded($name);
}

function yaml_parse_file(string $path): mixed
{
    YamlLoaderTestHook::$yamlParseFileCalls++;
    YamlLoaderTestHook::$lastYamlParseFilePath = $path;

    if (YamlLoaderTestHook::$yamlParseFileHandler !== null) {
        return (YamlLoaderTestHook::$yamlParseFileHandler)($path);
    }

    return \yaml_parse_file($path);
}

namespace LiquidRazor\FileLocator\Tests\Config;

use LiquidRazor\FileLocator\Config\YamlDiscoveryConfigLoader;
use LiquidRazor\FileLocator\Config\YamlLoaderTestHook;
use LiquidRazor\FileLocator\Exception\YamlParseException;
use LiquidRazor\FileLocator\Tests\Support\Assert;
use LiquidRazor\FileLocator\Tests\Support\TemporaryFilesystem;

return [
    test('yaml discovery config loader uses the fallback parser when the yaml extension is unavailable', static function (): void {
        YamlLoaderTestHook::reset();
        YamlLoaderTestHook::$yamlExtensionLoaded = false;

        try {
            $loader = new YamlDiscoveryConfigLoader();

            $parsed = $loader->load(LIQUIDRAZOR_FILELOCATOR_ROOT . '/resources/config/roots.yaml');

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
                ],
                $parsed
            );
            Assert::same(0, YamlLoaderTestHook::$yamlParseFileCalls);
        } finally {
            YamlLoaderTestHook::reset();
        }
    }),
    test('yaml discovery config loader fails clearly when the file does not exist', static function (): void {
        YamlLoaderTestHook::reset();
        YamlLoaderTestHook::$yamlExtensionLoaded = false;

        try {
            $loader = new YamlDiscoveryConfigLoader();
            $path = LIQUIDRAZOR_FILELOCATOR_ROOT . '/config/missing.yaml';

            $exception = Assert::throws(
                static function () use ($loader, $path): void {
                    $loader->load($path);
                },
                YamlParseException::class
            );

            Assert::same($path, $exception->path());
            Assert::contains('YAML file does not exist.', $exception->getMessage());
        } finally {
            YamlLoaderTestHook::reset();
        }
    }),
    test('yaml discovery config loader surfaces fallback parser errors', static function (): void {
        YamlLoaderTestHook::reset();
        YamlLoaderTestHook::$yamlExtensionLoaded = false;
        $filesystem = TemporaryFilesystem::create();

        try {
            $path = $filesystem->writeFile('invalid.yaml', "discovery:\n\tdefaults:\n    follow_symlinks: false\n");
            $loader = new YamlDiscoveryConfigLoader();

            $exception = Assert::throws(
                static function () use ($loader, $path): void {
                    $loader->load($path);
                },
                YamlParseException::class
            );

            Assert::same($path, $exception->path());
            Assert::contains('Tabs are not supported for indentation.', $exception->getMessage());
        } finally {
            $filesystem->cleanup();
            YamlLoaderTestHook::reset();
        }
    }),
    test('yaml discovery config loader prefers the yaml extension when available', static function (): void {
        YamlLoaderTestHook::reset();
        YamlLoaderTestHook::$yamlExtensionLoaded = true;
        YamlLoaderTestHook::$yamlParseFileHandler = static function (string $path): array {
            return [
                'loaded_from' => $path,
            ];
        };
        $filesystem = TemporaryFilesystem::create();

        try {
            $path = $filesystem->writeFile('config.yaml', "ignored: true\n");
            $loader = new YamlDiscoveryConfigLoader();

            $parsed = $loader->load($path);

            Assert::same(['loaded_from' => $path], $parsed);
            Assert::same(1, YamlLoaderTestHook::$yamlParseFileCalls);
            Assert::same($path, YamlLoaderTestHook::$lastYamlParseFilePath);
        } finally {
            $filesystem->cleanup();
            YamlLoaderTestHook::reset();
        }
    }),
    test('yaml discovery config loader fails when the yaml extension cannot parse the file', static function (): void {
        YamlLoaderTestHook::reset();
        YamlLoaderTestHook::$yamlExtensionLoaded = true;
        YamlLoaderTestHook::$yamlParseFileHandler = static function (): bool {
            return false;
        };
        $filesystem = TemporaryFilesystem::create();

        try {
            $path = $filesystem->writeFile('config.yaml', "invalid: [\n");
            $loader = new YamlDiscoveryConfigLoader();

            $exception = Assert::throws(
                static function () use ($loader, $path): void {
                    $loader->load($path);
                },
                YamlParseException::class
            );

            Assert::same($path, $exception->path());
            Assert::contains('The YAML extension failed to parse the file.', $exception->getMessage());
        } finally {
            $filesystem->cleanup();
            YamlLoaderTestHook::reset();
        }
    }),
    test('yaml discovery config loader rejects invalid top level values from the yaml extension', static function (): void {
        YamlLoaderTestHook::reset();
        YamlLoaderTestHook::$yamlExtensionLoaded = true;
        $filesystem = TemporaryFilesystem::create();

        try {
            $path = $filesystem->writeFile('config.yaml', "ignored: true\n");
            $loader = new YamlDiscoveryConfigLoader();

            YamlLoaderTestHook::$yamlParseFileHandler = static function (): string {
                return 'discovery';
            };

            $scalarException = Assert::throws(
                static function () use ($loader, $path): void {
                    $loader->load($path);
                },
                YamlParseException::class
            );

            Assert::contains('Top-level YAML value must be a mapping.', $scalarException->getMessage());

            YamlLoaderTestHook::$yamlParseFileHandler = static function (): array {
                return ['discovery'];
            };

            $listException = Assert::throws(
                static function () use ($loader, $path): void {
                    $loader->load($path);
                },
                YamlParseException::class
            );

            Assert::contains('Top-level YAML value must be a mapping.', $listException->getMessage());
        } finally {
            $filesystem->cleanup();
            YamlLoaderTestHook::reset();
        }
    }),
];
