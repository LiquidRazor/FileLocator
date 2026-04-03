<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Config;

use LiquidRazor\FileLocator\Exception\DiscoveryConfigException;
use LiquidRazor\FileLocator\Exception\FileLocatorException;
use LiquidRazor\FileLocator\Exception\InvalidDiscoveryConfigException;
use LiquidRazor\FileLocator\Exception\PathAccessException;
use LiquidRazor\FileLocator\Tests\Support\Assert;

return [
    test('config exceptions inherit from the repository base exception', static function (): void {
        $exception = new DiscoveryConfigException('Invalid config.');

        Assert::true($exception instanceof FileLocatorException);
        Assert::same('Invalid config.', $exception->getMessage());
    }),
    test('invalid discovery config exception is a specialized config exception', static function (): void {
        $exception = new InvalidDiscoveryConfigException('Unsupported discovery schema.');

        Assert::true($exception instanceof DiscoveryConfigException);
        Assert::same('Unsupported discovery schema.', $exception->getMessage());
    }),
    test('path access exception preserves the path and attempted operation', static function (): void {
        $previous = new \RuntimeException('Permission denied.');
        $exception = new PathAccessException('/project/src/Secret', 'read', $previous);

        Assert::true($exception instanceof FileLocatorException);
        Assert::same('/project/src/Secret', $exception->path());
        Assert::same('read', $exception->operation());
        Assert::contains('/project/src/Secret', $exception->getMessage());
        Assert::contains('read', $exception->getMessage());
        Assert::same($previous, $exception->getPrevious());
    }),
];
