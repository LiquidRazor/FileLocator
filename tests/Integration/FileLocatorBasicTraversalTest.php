<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Integration;

use Generator;
use LiquidRazor\FileLocator\Config\DiscoveryConfig;
use LiquidRazor\FileLocator\Config\DiscoveryDefaults;
use LiquidRazor\FileLocator\Config\RootConfig;
use LiquidRazor\FileLocator\Enum\UnreadablePathMode;
use LiquidRazor\FileLocator\FileLocator;
use LiquidRazor\FileLocator\Tests\Support\Assert;
use LiquidRazor\FileLocator\Tests\Support\TemporaryFilesystem;

return [
    test('file locator returns a lazy generator for traversal', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('src/Service.php', "<?php\n");

            $locator = new FileLocator(buildDiscoveryConfig($filesystem, [
                new RootConfig(
                    name: 'src',
                    enabled: true,
                    path: $filesystem->path('src'),
                    recursive: true,
                    extensions: ['php'],
                    exclude: [],
                ),
            ]));

            Assert::true($locator->locate() instanceof Generator);
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('file locator traverses recursive and non recursive roots lazily and yields only matching php files', static function (): void {
        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('src/Service.php', "<?php\n");
            $filesystem->writeFile('src/Nested/Repository.php', "<?php\n");
            $filesystem->writeFile('src/Nested/readme.txt', "text\n");
            $filesystem->writeFile('modules/Module.php', "<?php\n");
            $filesystem->writeFile('modules/Nested/Ignored.php', "<?php\n");
            $filesystem->writeFile('modules/Nested/ignored.txt', "text\n");

            $locator = new FileLocator(buildDiscoveryConfig($filesystem, [
                new RootConfig(
                    name: 'src',
                    enabled: true,
                    path: $filesystem->path('src'),
                    recursive: true,
                    extensions: ['php'],
                    exclude: [],
                ),
                new RootConfig(
                    name: 'modules',
                    enabled: true,
                    path: $filesystem->path('modules'),
                    recursive: false,
                    extensions: ['php'],
                    exclude: [],
                ),
            ]));

            $files = iterator_to_array($locator->locate(), false);
            sort($files, SORT_STRING);

            Assert::same(
                [
                    $filesystem->path('modules/Module.php'),
                    $filesystem->path('src/Nested/Repository.php'),
                    $filesystem->path('src/Service.php'),
                ],
                $files
            );
        } finally {
            $filesystem->cleanup();
        }
    }),
];

/**
 * @param list<RootConfig> $roots
 */
function buildDiscoveryConfig(TemporaryFilesystem $filesystem, array $roots): DiscoveryConfig
{
    return new DiscoveryConfig(
        projectRoot: $filesystem->rootPath,
        exclude: [],
        defaults: new DiscoveryDefaults(
            followSymlinks: false,
            includeHidden: false,
            onUnreadable: UnreadablePathMode::Skip,
            extensions: ['php'],
        ),
        roots: $roots,
    );
}
