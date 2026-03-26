<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Support;

use Throwable;

final class Assert
{
    public static function same(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected === $actual) {
            return;
        }

        self::fail($message !== '' ? $message : self::buildComparisonMessage($expected, $actual));
    }

    public static function true(bool $condition, string $message = ''): void
    {
        self::same(true, $condition, $message !== '' ? $message : 'Expected condition to be true.');
    }

    public static function false(bool $condition, string $message = ''): void
    {
        self::same(false, $condition, $message !== '' ? $message : 'Expected condition to be false.');
    }

    public static function count(int $expectedCount, \Countable|array $values, string $message = ''): void
    {
        self::same($expectedCount, count($values), $message !== '' ? $message : 'Unexpected element count.');
    }

    public static function contains(string $needle, string $haystack, string $message = ''): void
    {
        if (str_contains($haystack, $needle)) {
            return;
        }

        self::fail($message !== '' ? $message : sprintf('Failed asserting that "%s" contains "%s".', $haystack, $needle));
    }

    public static function throws(callable $callback, string $expectedException, string $message = ''): Throwable
    {
        try {
            $callback();
        } catch (Throwable $throwable) {
            if ($throwable instanceof $expectedException) {
                return $throwable;
            }

            self::fail($message !== '' ? $message : sprintf(
                'Expected exception of type "%s", got "%s".',
                $expectedException,
                $throwable::class
            ));
        }

        self::fail($message !== '' ? $message : sprintf(
            'Expected exception of type "%s", but nothing was thrown.',
            $expectedException
        ));
    }

    public static function fileExists(string $path, string $message = ''): void
    {
        self::true(is_file($path), $message !== '' ? $message : sprintf('Expected file to exist: %s', $path));
    }

    public static function directoryExists(string $path, string $message = ''): void
    {
        self::true(is_dir($path), $message !== '' ? $message : sprintf('Expected directory to exist: %s', $path));
    }

    public static function fail(string $message): never
    {
        throw new AssertionFailed($message);
    }

    private static function buildComparisonMessage(mixed $expected, mixed $actual): string
    {
        return sprintf(
            "Failed asserting that two values are identical.\nExpected: %s\nActual: %s",
            var_export($expected, true),
            var_export($actual, true)
        );
    }
}

final class AssertionFailed extends \RuntimeException
{
}
