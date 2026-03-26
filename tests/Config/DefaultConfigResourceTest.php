<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Config;

use LiquidRazor\FileLocator\Tests\Support\Assert;

return [
    test('default config resource exists at the canonical path', static function (): void {
        Assert::fileExists(LIQUIDRAZOR_FILELOCATOR_ROOT . '/resources/config/roots.yaml');
    }),
    test('default config resource matches the documented canonical yaml', static function (): void {
        $expected = <<<'YAML'
discovery:
  defaults:
    follow_symlinks: false
    include_hidden: false
    on_unreadable: skip
    extensions: [php]

  exclude:
    - vendor
    - node_modules
    - .git
    - .idea
    - .vscode
    - var/cache

  roots:
    include:
      enabled: true
      path: include
      recursive: true
      extensions: [php]
      exclude: []

    lib:
      enabled: true
      path: lib
      recursive: true
      extensions: [php]
      exclude: []

    src:
      enabled: true
      path: src
      recursive: true
      extensions: [php]
      exclude: []
YAML;
        $expected .= "\n";

        $actual = file_get_contents(LIQUIDRAZOR_FILELOCATOR_ROOT . '/resources/config/roots.yaml');

        if ($actual === false) {
            Assert::fail('Failed to read the default config resource.');
        }

        Assert::same($expected, $actual);
    }),
];
