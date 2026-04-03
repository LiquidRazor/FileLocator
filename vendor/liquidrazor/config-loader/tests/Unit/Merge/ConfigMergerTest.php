<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Tests\Unit\Merge;

use LiquidRazor\ConfigLoader\Lib\Merge\ConfigMerger;
use PHPUnit\Framework\TestCase;

final class ConfigMergerTest extends TestCase
{
    public function testRecursivelyMergesAssociativeArraysAndReplacesLists(): void
    {
        $merger = new ConfigMerger();

        $merged = $merger->mergeAll([
            [
                'service' => [
                    'timeout' => 10,
                    'hosts' => ['one', 'two'],
                    'options' => [
                        'retries' => 2,
                        'cache' => true,
                    ],
                ],
            ],
            [
                'service' => [
                    'timeout' => 30,
                    'hosts' => ['prod'],
                    'options' => [
                        'cache' => false,
                    ],
                ],
            ],
        ]);

        self::assertSame(
            [
                'service' => [
                    'timeout' => 30,
                    'hosts' => ['prod'],
                    'options' => [
                        'retries' => 2,
                        'cache' => false,
                    ],
                ],
            ],
            $merged,
        );
    }
}
