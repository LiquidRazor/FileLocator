<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Config;

use LiquidRazor\ConfigLoader\Exception\ConfigException;
use LiquidRazor\ConfigLoader\Exception\InvalidConfigSyntaxException;
use LiquidRazor\ConfigLoader\Exception\MissingConfigFileException;
use LiquidRazor\ConfigLoader\Exception\UnsupportedFormatException;
use LiquidRazor\FileLocator\Config\DiscoveryConfigFactory;
use LiquidRazor\FileLocator\Enum\UnreadablePathMode;
use LiquidRazor\FileLocator\Exception\InvalidDiscoveryConfigException;
use LiquidRazor\FileLocator\Tests\Support\Assert;
use LiquidRazor\FileLocator\Tests\Support\TemporaryFilesystem;

return [
    test('discovery config factory builds config from library defaults when no project override exists', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $config = (new DiscoveryConfigFactory())->create($filesystem->rootPath);

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
                $config->exclude,
            );
            Assert::false($config->defaults->followSymlinks);
            Assert::false($config->defaults->includeHidden);
            Assert::same(UnreadablePathMode::Skip, $config->defaults->onUnreadable);
            Assert::same(['php'], $config->defaults->extensions);
            Assert::same(['include', 'lib', 'src'], array_map(
                static fn ($root) => $root->name,
                $config->roots,
            ));
            Assert::same($filesystem->rootPath . '/include', $config->roots[0]->path);
            Assert::same($filesystem->rootPath . '/lib', $config->roots[1]->path);
            Assert::same($filesystem->rootPath . '/src', $config->roots[2]->path);
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('discovery config factory loads project overrides through config loader interpolation and merge behavior', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('config/roots.yaml', <<<'YAML'
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
      path: ${DISCOVERY_CUSTOM_ROOT:-modules}
      recursive: true
YAML);

            $config = (new DiscoveryConfigFactory())->create($filesystem->rootPath);

            Assert::true($config->defaults->includeHidden);
            Assert::same(
                [
                    $filesystem->rootPath . '/storage/tmp',
                ],
                $config->exclude,
            );
            Assert::same(['include', 'src', 'modules'], array_map(
                static fn ($root) => $root->name,
                $config->roots,
            ));
            Assert::same([$filesystem->rootPath . '/src/Legacy'], $config->roots[1]->exclude);
            Assert::same($filesystem->rootPath . '/modules', $config->roots[2]->path);
            Assert::same(['php'], $config->roots[2]->extensions);
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('discovery config factory fails when multiple yaml variants match the same logical config name', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('config/roots.yaml', "discovery:\n  defaults:\n    include_hidden: true\n");
            $filesystem->writeFile('config/roots.yml', "discovery:\n  defaults:\n    include_hidden: false\n");

            $exception = Assert::throws(
                static function () use ($filesystem): void {
                    (new DiscoveryConfigFactory())->create($filesystem->rootPath);
                },
                ConfigException::class,
            );

            Assert::contains('Multiple config files match "roots"', $exception->getMessage());
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('discovery config factory fails fast on invalid project discovery schema', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('config/roots.yml', <<<'YAML'
discovery:
  defaults:
    include_hidden: maybe
YAML);

            $exception = Assert::throws(
                static function () use ($filesystem): void {
                    (new DiscoveryConfigFactory())->create($filesystem->rootPath);
                },
                InvalidDiscoveryConfigException::class,
            );

            Assert::contains('discovery.defaults.include_hidden', $exception->getMessage());
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('discovery config factory surfaces config loader syntax errors unchanged', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('config/roots.yaml', "discovery:\n\tdefaults:\n    include_hidden: true\n");

            $exception = Assert::throws(
                static function () use ($filesystem): void {
                    (new DiscoveryConfigFactory())->create($filesystem->rootPath);
                },
                InvalidConfigSyntaxException::class,
            );

            Assert::contains('roots.yaml', $exception->getMessage());
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('discovery config factory keeps yaml format deterministic and rejects json-only config files', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile(
                'config/roots.json',
                '{"discovery":{"defaults":{"include_hidden":true}}}',
            );

            $exception = Assert::throws(
                static function () use ($filesystem): void {
                    (new DiscoveryConfigFactory())->create($filesystem->rootPath);
                },
                UnsupportedFormatException::class,
            );

            Assert::contains('json', $exception->getMessage());
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('discovery config factory fails loudly when the default config file is missing', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->mkdir('empty-config-root');

            $exception = Assert::throws(
                static function () use ($filesystem): void {
                    (new DiscoveryConfigFactory(
                        defaultConfigRoot: $filesystem->path('empty-config-root'),
                    ))->create($filesystem->rootPath);
                },
                MissingConfigFileException::class,
            );

            Assert::contains('roots.yaml', $exception->getMessage());
        } finally {
            $filesystem->cleanup();
        }
    }),
];
