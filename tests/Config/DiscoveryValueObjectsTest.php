<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Config;

use LiquidRazor\FileLocator\Config\DiscoveryConfig;
use LiquidRazor\FileLocator\Config\DiscoveryDefaults;
use LiquidRazor\FileLocator\Config\RootConfig;
use LiquidRazor\FileLocator\Enum\UnreadablePathMode;
use LiquidRazor\FileLocator\Tests\Support\Assert;

return [
    test('unreadable path mode exposes the supported policy values', static function (): void {
        Assert::same('skip', UnreadablePathMode::Skip->value);
        Assert::same('fail', UnreadablePathMode::Fail->value);
    }),
    test('discovery defaults stores normalized default values', static function (): void {
        $defaults = new DiscoveryDefaults(
            followSymlinks: false,
            includeHidden: false,
            onUnreadable: UnreadablePathMode::Skip,
            extensions: ['php']
        );

        Assert::false($defaults->followSymlinks);
        Assert::false($defaults->includeHidden);
        Assert::same(UnreadablePathMode::Skip, $defaults->onUnreadable);
        Assert::same(['php'], $defaults->extensions);
    }),
    test('root config stores one enabled discovery root definition', static function (): void {
        $root = new RootConfig(
            name: 'src',
            enabled: true,
            path: '/project/src',
            recursive: true,
            extensions: ['php'],
            exclude: ['/project/src/Legacy']
        );

        Assert::same('src', $root->name);
        Assert::true($root->enabled);
        Assert::same('/project/src', $root->path);
        Assert::true($root->recursive);
        Assert::same(['php'], $root->extensions);
        Assert::same(['/project/src/Legacy'], $root->exclude);
    }),
    test('discovery config stores the final ordered root list and global exclusions', static function (): void {
        $defaults = new DiscoveryDefaults(
            followSymlinks: false,
            includeHidden: false,
            onUnreadable: UnreadablePathMode::Skip,
            extensions: ['php']
        );
        $includeRoot = new RootConfig(
            name: 'include',
            enabled: true,
            path: '/project/include',
            recursive: true,
            extensions: ['php'],
            exclude: []
        );
        $srcRoot = new RootConfig(
            name: 'src',
            enabled: true,
            path: '/project/src',
            recursive: true,
            extensions: ['php'],
            exclude: ['/project/src/Legacy']
        );
        $config = new DiscoveryConfig(
            projectRoot: '/project',
            exclude: ['/project/vendor', '/project/node_modules'],
            defaults: $defaults,
            roots: [$includeRoot, $srcRoot]
        );

        Assert::same('/project', $config->projectRoot);
        Assert::same(['/project/vendor', '/project/node_modules'], $config->exclude);
        Assert::same($defaults, $config->defaults);
        Assert::count(2, $config->roots);
        Assert::same($includeRoot, $config->roots[0]);
        Assert::same($srcRoot, $config->roots[1]);
    }),
    test('discovery value objects are readonly after construction', static function (): void {
        $defaults = new DiscoveryDefaults(
            followSymlinks: false,
            includeHidden: false,
            onUnreadable: UnreadablePathMode::Skip,
            extensions: ['php']
        );

        Assert::throws(
            static function () use ($defaults): void {
                $defaults->extensions = ['xml'];
            },
            \Error::class
        );
    }),
];
