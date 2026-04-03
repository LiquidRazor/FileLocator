<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Tests\Support;

use LiquidRazor\ConfigLoader\Contract\EnvironmentReaderInterface;

final readonly class ArrayEnvironmentReader implements EnvironmentReaderInterface
{
    /**
     * @param array<string, string> $values
     */
    public function __construct(
        private array $values,
    ) {
    }

    public function get(string $name): ?string
    {
        return $this->values[$name] ?? null;
    }
}
