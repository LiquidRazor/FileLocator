<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Integration;

use LiquidRazor\FileLocator\Config\DiscoveryConfigFactory;
use LiquidRazor\FileLocator\FileLocator;
use LiquidRazor\FileLocator\Tests\Support\Assert;
use LiquidRazor\FileLocator\Tests\Support\TemporaryFilesystem;

return [
    test('end to end discovery uses config roots yaml to merge defaults disable a root and add a custom root', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('include/Contract.php', "<?php\n");
            $filesystem->writeFile('lib/Disabled.php', "<?php\n");
            $filesystem->writeFile('src/Application.php', "<?php\n");
            $filesystem->writeFile('src/Legacy/Old.php', "<?php\n");
            $filesystem->writeFile('modules/Feature.php', "<?php\n");
            $filesystem->writeFile('modules/Nested/NestedFeature.php', "<?php\n");
            $filesystem->writeFile('storage/tmp/Cache.php', "<?php\n");
            $filesystem->writeFile('config/roots.yaml', <<<YAML
discovery:
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

            $factory = new DiscoveryConfigFactory();
            $config = $factory->create($filesystem->rootPath);
            $files = iterator_to_array((new FileLocator($config))->locate(), false);
            sort($files, SORT_STRING);

            Assert::same(
                ['include', 'src', 'modules'],
                array_map(static fn ($root) => $root->name, $config->roots)
            );
            Assert::same(
                [
                    $filesystem->path('include/Contract.php'),
                    $filesystem->path('modules/Feature.php'),
                    $filesystem->path('modules/Nested/NestedFeature.php'),
                    $filesystem->path('src/Application.php'),
                ],
                $files
            );
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('end to end discovery uses config roots yml when roots yaml is absent', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('include/Disabled.php', "<?php\n");
            $filesystem->writeFile('src/Application.php', "<?php\n");
            $filesystem->writeFile('packages/Feature.php', "<?php\n");
            $filesystem->writeFile('packages/Nested/Ignored.php', "<?php\n");
            $filesystem->writeFile('config/roots.yml', <<<YAML
discovery:
  roots:
    include:
      enabled: false

    packages:
      path: packages
      recursive: false
YAML);

            $factory = new DiscoveryConfigFactory();
            $config = $factory->create($filesystem->rootPath);
            $files = iterator_to_array((new FileLocator($config))->locate(), false);
            sort($files, SORT_STRING);

            Assert::same(
                ['lib', 'src', 'packages'],
                array_map(static fn ($root) => $root->name, $config->roots)
            );
            Assert::same(
                [
                    $filesystem->path('packages/Feature.php'),
                    $filesystem->path('src/Application.php'),
                ],
                $files
            );
        } finally {
            $filesystem->cleanup();
        }
    }),
];
