<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Filesystem;

use LiquidRazor\FileLocator\Exception\InvalidDiscoveryConfigException;
use LiquidRazor\FileLocator\Filesystem\PathNormalizer;
use LiquidRazor\FileLocator\Tests\Support\Assert;

return [
    test('path normalizer resolves relative paths against the project root', static function (): void {
        $normalizer = new PathNormalizer();

        Assert::same('/project/src/Service', $normalizer->normalize('/project', 'src/./Domain/../Service'));
        Assert::same('/project/var/cache', $normalizer->normalize('/project/', 'var//cache/'));
    }),
    test('path normalizer normalizes absolute paths without requiring filesystem access', static function (): void {
        $normalizer = new PathNormalizer();

        Assert::same('/project/src/Feature', $normalizer->normalize('/project', '/project/src//Feature/'));
        Assert::same('C:/project/lib', $normalizer->normalize('/project', 'C:\\project\\src\\..\\lib\\'));
    }),
    test('path normalizer normalizes lists in declaration order', static function (): void {
        $normalizer = new PathNormalizer();

        Assert::same(
            [
                '/project/vendor',
                '/project/var/cache',
                '/project/src/Legacy',
            ],
            $normalizer->normalizeAll(
                '/project',
                [
                    'vendor',
                    'var/cache/',
                    'src\\Legacy',
                ]
            )
        );
    }),
    test('path normalizer rejects non absolute project roots', static function (): void {
        $normalizer = new PathNormalizer();

        $exception = Assert::throws(
            static function () use ($normalizer): void {
                $normalizer->normalize('project', 'src');
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('project root', $exception->getMessage());
        Assert::contains('absolute path', $exception->getMessage());
    }),
    test('path normalizer rejects relative paths that escape above the project root', static function (): void {
        $normalizer = new PathNormalizer();

        $exception = Assert::throws(
            static function () use ($normalizer): void {
                $normalizer->normalize('/project', '../../etc');
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('escape above the project root', $exception->getMessage());
    }),
    test('path normalizer rejects empty or unsafe path values', static function (): void {
        $normalizer = new PathNormalizer();

        $emptyPathException = Assert::throws(
            static function () use ($normalizer): void {
                $normalizer->normalize('/project', '');
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('must not be empty', $emptyPathException->getMessage());

        $nullByteException = Assert::throws(
            static function () use ($normalizer): void {
                $normalizer->normalize('/project', "src\0hidden");
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('null bytes', $nullByteException->getMessage());
    }),
    test('path normalizer rejects absolute paths that escape above their root', static function (): void {
        $normalizer = new PathNormalizer();

        $unixException = Assert::throws(
            static function () use ($normalizer): void {
                $normalizer->normalize('/project', '/../../etc');
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('must not escape above its root', $unixException->getMessage());

        $windowsException = Assert::throws(
            static function () use ($normalizer): void {
                $normalizer->normalize('/project', 'C:/../windows');
            },
            InvalidDiscoveryConfigException::class
        );

        Assert::contains('must not escape above its root', $windowsException->getMessage());
    }),
];
