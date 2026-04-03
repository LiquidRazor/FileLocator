<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Tests\Unit\Interpolation;

use LiquidRazor\ConfigLoader\Exception\MissingEnvironmentVariableException;
use LiquidRazor\ConfigLoader\Lib\Interpolation\EnvironmentInterpolator;
use LiquidRazor\ConfigLoader\Tests\Support\ArrayEnvironmentReader;
use PHPUnit\Framework\TestCase;

final class EnvironmentInterpolatorTest extends TestCase
{
    public function testInterpolatesVariablesAndDefaultsWithoutTouchingNonStrings(): void
    {
        $interpolator = new EnvironmentInterpolator(
            new ArrayEnvironmentReader([
                'APP_HOST' => 'example.test',
            ]),
        );

        $interpolated = $interpolator->interpolate([
            'host' => '${APP_HOST}',
            'dsn' => 'https://${APP_HOST}/api',
            'fallback' => '${APP_PORT:-8080}',
            'debug' => true,
            'ports' => [80, '${APP_PORT:-443}'],
        ]);

        self::assertSame(
            [
                'host' => 'example.test',
                'dsn' => 'https://example.test/api',
                'fallback' => '8080',
                'debug' => true,
                'ports' => [80, '443'],
            ],
            $interpolated,
        );
    }

    public function testThrowsWhenRequiredEnvironmentVariableIsMissing(): void
    {
        $interpolator = new EnvironmentInterpolator(new ArrayEnvironmentReader([]));

        $this->expectException(MissingEnvironmentVariableException::class);

        $interpolator->interpolate([
            'host' => '${APP_HOST}',
        ]);
    }
}
