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
    test('file locator ignores symlinked files and directories by default', static function (): void {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('src/Real.php', "<?php\n");
            $filesystem->writeFile('linked-target/Linked.php', "<?php\n");
            $filesystem->symlink($filesystem->path('linked-target'), 'src/LinkedDir');
            $filesystem->symlink($filesystem->path('linked-target/Linked.php'), 'src/LinkedFile.php');

            $locator = new FileLocator(buildSymlinkConfig($filesystem, false));
            $files = iterator_to_array($locator->locate(), false);
            sort($files, SORT_STRING);

            Assert::same(
                [
                    $filesystem->path('src/Real.php'),
                ],
                $files
            );
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('file locator can follow symlinks and avoids recursive directory cycles', static function (): void {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('src/Real.php', "<?php\n");
            $filesystem->writeFile('linked-target/Linked.php', "<?php\n");
            $filesystem->symlink($filesystem->path('linked-target'), 'src/LinkedDir');
            $filesystem->symlink($filesystem->path('linked-target/Linked.php'), 'src/LinkedFile.php');
            $filesystem->symlink($filesystem->path('src'), 'linked-target/LoopBack');

            $locator = new FileLocator(buildSymlinkConfig($filesystem, true));
            $files = iterator_to_array($locator->locate(), false);
            sort($files, SORT_STRING);

            Assert::same(
                [
                    $filesystem->path('src/LinkedDir/Linked.php'),
                    $filesystem->path('src/LinkedFile.php'),
                    $filesystem->path('src/Real.php'),
                ],
                $files
            );
        } finally {
            $filesystem->cleanup();
        }
    }),
];

function buildSymlinkConfig(TemporaryFilesystem $filesystem, bool $followSymlinks): DiscoveryConfig
{
    return new DiscoveryConfig(
        projectRoot: $filesystem->rootPath,
        exclude: [],
        defaults: new DiscoveryDefaults(
            followSymlinks: $followSymlinks,
            includeHidden: false,
            onUnreadable: UnreadablePathMode::Skip,
            extensions: ['php'],
        ),
        roots: [
            new RootConfig(
                name: 'src',
                enabled: true,
                path: $filesystem->path('src'),
                recursive: true,
                extensions: ['php'],
                exclude: [],
            ),
        ],
    );
}
