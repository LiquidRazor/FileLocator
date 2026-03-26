<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Config;

use LiquidRazor\FileLocator\Config\Internal\MinimalYamlParser;
use LiquidRazor\FileLocator\Exception\YamlParseException;
use LiquidRazor\FileLocator\Tests\Support\Assert;
use LiquidRazor\FileLocator\Tests\Support\TemporaryFilesystem;

return [
    test('minimal yaml parser parses the canonical default discovery resource', static function (): void {
        $parser = new MinimalYamlParser();

        $parsed = $parser->parseFile(LIQUIDRAZOR_FILELOCATOR_ROOT . '/resources/config/roots.yaml');

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
    }),
    test('minimal yaml parser supports quoted strings and inline scalar lists', static function (): void {
        $parser = new MinimalYamlParser();

        $parsed = $parser->parse(<<<'YAML'
discovery:
  defaults:
    on_unreadable: "skip"
    extensions: ['php', "inc"]
YAML, 'inline.yaml');

        Assert::same(
            [
                'discovery' => [
                    'defaults' => [
                        'on_unreadable' => 'skip',
                        'extensions' => ['php', 'inc'],
                    ],
                ],
            ],
            $parsed
        );
    }),
    test('minimal yaml parser rejects tab indentation', static function (): void {
        $parser = new MinimalYamlParser();

        $exception = Assert::throws(
            static function () use ($parser): void {
                $parser->parse("discovery:\n\tdefaults:\n    follow_symlinks: false\n", 'tabs.yaml');
            },
            YamlParseException::class
        );

        Assert::contains('tabs.yaml', $exception->getMessage());
        Assert::contains('Tabs are not supported for indentation.', $exception->getMessage());
    }),
    test('minimal yaml parser rejects anchors aliases and merge keys', static function (): void {
        $parser = new MinimalYamlParser();

        $anchorException = Assert::throws(
            static function () use ($parser): void {
                $parser->parse("defaults: &defaults\n  enabled: true\ncopy: *defaults\n", 'anchors.yaml');
            },
            YamlParseException::class
        );

        Assert::contains('Anchors and aliases are not supported.', $anchorException->getMessage());

        $mergeException = Assert::throws(
            static function () use ($parser): void {
                $parser->parse("root:\n  <<: true\n", 'merge.yaml');
            },
            YamlParseException::class
        );

        Assert::contains('YAML merge keys are not supported.', $mergeException->getMessage());
    }),
    test('minimal yaml parser rejects unsupported multiline scalars and inline mappings', static function (): void {
        $parser = new MinimalYamlParser();

        $multilineException = Assert::throws(
            static function () use ($parser): void {
                $parser->parse("discovery:\n  defaults:\n    path: |\n      src\n", 'multiline.yaml');
            },
            YamlParseException::class
        );

        Assert::contains('Multiline scalars are not supported.', $multilineException->getMessage());

        $mappingException = Assert::throws(
            static function () use ($parser): void {
                $parser->parse("discovery:\n  defaults: { follow_symlinks: false }\n", 'inline-map.yaml');
            },
            YamlParseException::class
        );

        Assert::contains('Inline mappings are not supported.', $mappingException->getMessage());
    }),
    test('minimal yaml parser rejects nested list structures', static function (): void {
        $parser = new MinimalYamlParser();
        $filesystem = TemporaryFilesystem::create();

        try {
            $path = $filesystem->writeFile('invalid.yaml', <<<YAML
discovery:
  exclude:
    - vendor
      nested: true
YAML);

            $exception = Assert::throws(
                static function () use ($parser, $path): void {
                    $parser->parseFile($path);
                },
                YamlParseException::class
            );

            Assert::contains('Nested list items are not supported.', $exception->getMessage());
        } finally {
            $filesystem->cleanup();
        }
    }),
];
