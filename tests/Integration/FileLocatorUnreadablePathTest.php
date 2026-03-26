<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Integration;

use LiquidRazor\FileLocator\Config\DiscoveryConfig;
use LiquidRazor\FileLocator\Config\DiscoveryDefaults;
use LiquidRazor\FileLocator\Config\RootConfig;
use LiquidRazor\FileLocator\Enum\UnreadablePathMode;
use LiquidRazor\FileLocator\Exception\PathAccessException;
use LiquidRazor\FileLocator\FileLocator;
use LiquidRazor\FileLocator\Tests\Support\Assert;
use LiquidRazor\FileLocator\Tests\Support\TemporaryFilesystem;

return [
    test('file locator skips unreadable files when configured to skip', static function (): void {
        if (shouldSkipUnreadablePermissionTest()) {
            return;
        }

        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('src/Readable.php', "<?php\n");
            $unreadableFile = $filesystem->writeFile('src/Secret.php', "<?php\n");
            $filesystem->chmod('src/Secret.php', 0000);

            if (is_readable($unreadableFile)) {
                return;
            }

            $config = buildUnreadableConfig($filesystem, UnreadablePathMode::Skip);
            $files = iterator_to_array((new FileLocator($config))->locate(), false);
            sort($files, SORT_STRING);

            Assert::same(
                [
                    $filesystem->path('src/Readable.php'),
                ],
                $files
            );
        } finally {
            $filesystem->cleanup();
        }
    }),
    test('file locator throws on unreadable files when configured to fail', static function (): void {
        if (shouldSkipUnreadablePermissionTest()) {
            return;
        }

        $filesystem = TemporaryFilesystem::create();

        try {
            $filesystem->writeFile('src/Readable.php', "<?php\n");
            $unreadableFile = $filesystem->writeFile('src/Secret.php', "<?php\n");
            $filesystem->chmod('src/Secret.php', 0000);

            if (is_readable($unreadableFile)) {
                return;
            }

            $config = buildUnreadableConfig($filesystem, UnreadablePathMode::Fail);

            $exception = Assert::throws(
                static function () use ($config): void {
                    iterator_to_array((new FileLocator($config))->locate(), false);
                },
                PathAccessException::class
            );

            Assert::same($filesystem->path('src/Secret.php'), $exception->path());
            Assert::same('read', $exception->operation());
        } finally {
            $filesystem->cleanup();
        }
    }),
];

function shouldSkipUnreadablePermissionTest(): bool
{
    if (PHP_OS_FAMILY === 'Windows') {
        return true;
    }

    return function_exists('posix_geteuid') && posix_geteuid() === 0;
}

function buildUnreadableConfig(TemporaryFilesystem $filesystem, UnreadablePathMode $mode): DiscoveryConfig
{
    return new DiscoveryConfig(
        projectRoot: $filesystem->rootPath,
        exclude: [],
        defaults: new DiscoveryDefaults(
            followSymlinks: false,
            includeHidden: false,
            onUnreadable: $mode,
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
