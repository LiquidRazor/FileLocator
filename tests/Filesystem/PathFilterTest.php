<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Filesystem;

use LiquidRazor\FileLocator\Config\DiscoveryConfig;
use LiquidRazor\FileLocator\Config\DiscoveryDefaults;
use LiquidRazor\FileLocator\Config\RootConfig;
use LiquidRazor\FileLocator\Enum\UnreadablePathMode;
use LiquidRazor\FileLocator\Filesystem\PathFilter;
use LiquidRazor\FileLocator\Tests\Support\Assert;

return [
    test('path filter detects hidden paths by dot-prefixed segments', static function (): void {
        $filter = new PathFilter(buildDiscoveryConfig(includeHidden: false));
        $root = buildRootConfig();

        Assert::true($filter->isHidden('/project/.git'));
        Assert::true($filter->isHidden('/project/src/.hidden/File.php'));
        Assert::false($filter->isHidden('/project/src/Visible/File.php'));
        Assert::false($filter->shouldDescend('/project/.git', $root));
        Assert::false($filter->shouldYield('/project/src/.hidden/File.php', $root));
    }),
    test('path filter allows hidden paths when configured', static function (): void {
        $filter = new PathFilter(buildDiscoveryConfig(includeHidden: true));
        $root = buildRootConfig();

        Assert::true($filter->isHidden('/project/src/.hidden/File.php'));
        Assert::true($filter->shouldDescend('/project/.hidden', $root));
        Assert::true($filter->shouldYield('/project/src/.hidden/File.php', $root));
    }),
    test('path filter applies global and root specific exclusions with exact and descendant matches', static function (): void {
        $filter = new PathFilter(buildDiscoveryConfig(
            includeHidden: false,
            globalExclude: ['/project/vendor', '/project/var/cache']
        ));
        $root = buildRootConfig(exclude: ['/project/src/Legacy']);

        Assert::true($filter->isExcluded('/project/vendor', $root));
        Assert::true($filter->isExcluded('/project/vendor/bin/tool.php', $root));
        Assert::true($filter->isExcluded('/project/src/Legacy/Old.php', $root));
        Assert::false($filter->isExcluded('/project/vendorized/Tool.php', $root));
        Assert::false($filter->shouldDescend('/project/src/Legacy', $root));
        Assert::false($filter->shouldYield('/project/var/cache/result.php', $root));
    }),
    test('path filter matches allowed extensions case insensitively for file yields', static function (): void {
        $filter = new PathFilter(buildDiscoveryConfig());
        $root = buildRootConfig(extensions: ['php']);

        Assert::true($filter->matchesExtension('/project/src/File.php', $root));
        Assert::true($filter->matchesExtension('/project/src/File.PHP', $root));
        Assert::false($filter->matchesExtension('/project/src/File.txt', $root));
        Assert::false($filter->matchesExtension('/project/src/File', $root));
        Assert::true($filter->shouldYield('/project/src/File.PHP', $root));
        Assert::false($filter->shouldYield('/project/src/File.txt', $root));
    }),
    test('path filter keeps directory pruning and file skipping rules consistent', static function (): void {
        $filter = new PathFilter(buildDiscoveryConfig(globalExclude: ['/project/src/Excluded']));
        $root = buildRootConfig(exclude: ['/project/src/Legacy']);

        Assert::true($filter->shouldDescend('/project/src', $root));
        Assert::false($filter->shouldDescend('/project/src/Excluded', $root));
        Assert::false($filter->shouldDescend('/project/src/Legacy', $root));
        Assert::true($filter->shouldYield('/project/src/Service.php', $root));
        Assert::false($filter->shouldYield('/project/src/.hidden/Service.php', $root));
        Assert::false($filter->shouldYield('/project/src/Excluded/Service.php', $root));
        Assert::false($filter->shouldYield('/project/src/Legacy/Service.php', $root));
    }),
];

function buildDiscoveryConfig(bool $includeHidden = false, array $globalExclude = []): DiscoveryConfig
{
    return new DiscoveryConfig(
        projectRoot: '/project',
        exclude: $globalExclude,
        defaults: new DiscoveryDefaults(
            followSymlinks: false,
            includeHidden: $includeHidden,
            onUnreadable: UnreadablePathMode::Skip,
            extensions: ['php'],
        ),
        roots: [],
    );
}

function buildRootConfig(array $extensions = ['php'], array $exclude = []): RootConfig
{
    return new RootConfig(
        name: 'src',
        enabled: true,
        path: '/project/src',
        recursive: true,
        extensions: $extensions,
        exclude: $exclude,
    );
}
