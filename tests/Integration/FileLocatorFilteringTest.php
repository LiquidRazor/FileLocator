<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Integration;

use LiquidRazor\FileLocator\Config\DiscoveryConfig;
use LiquidRazor\FileLocator\Config\DiscoveryDefaults;
use LiquidRazor\FileLocator\Config\RootConfig;
use LiquidRazor\FileLocator\Enum\UnreadablePathMode;
use LiquidRazor\FileLocator\FileLocator;
use LiquidRazor\FileLocator\Tests\Support\Assert;
use LiquidRazor\FileLocator\Tests\Support\TemporaryFilesystem;

return [
    test('file locator skips missing roots and obeys hidden and exclusion filtering during traversal', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('src/Service.php', "<?php\n");
            $filesystem->writeFile('src/.hidden/Hidden.php', "<?php\n");
            $filesystem->writeFile('src/Excluded/Skip.php', "<?php\n");
            $filesystem->writeFile('src/Legacy/Old.php', "<?php\n");
            $filesystem->writeFile('src/Guide.txt', "text\n");

            $config = new DiscoveryConfig(
                projectRoot: $filesystem->rootPath,
                exclude: [
                    $filesystem->path('src/Excluded'),
                ],
                defaults: new DiscoveryDefaults(
                    followSymlinks: false,
                    includeHidden: false,
                    onUnreadable: UnreadablePathMode::Skip,
                    extensions: ['php'],
                ),
                roots: [
                    new RootConfig(
                        name: 'missing',
                        enabled: true,
                        path: $filesystem->path('missing'),
                        recursive: true,
                        extensions: ['php'],
                        exclude: [],
                    ),
                    new RootConfig(
                        name: 'src',
                        enabled: true,
                        path: $filesystem->path('src'),
                        recursive: true,
                        extensions: ['php'],
                        exclude: [
                            $filesystem->path('src/Legacy'),
                        ],
                    ),
                ],
            );

            $files = iterator_to_array((new FileLocator($config))->locate(), false);
            sort($files, SORT_STRING);

            Assert::same(
                [
                    $filesystem->path('src/Service.php'),
                ],
                $files
            );
        } finally {
            $filesystem->cleanup();
        }
    }),
];
