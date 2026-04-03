<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Lib\Environment;

use LiquidRazor\ConfigLoader\Contract\EnvironmentReaderInterface;

final class PhpEnvironmentReader implements EnvironmentReaderInterface
{
    public function get(string $name): ?string
    {
        $value = getenv($name);

        if (is_string($value)) {
            return $value;
        }

        $environmentValue = $_ENV[$name] ?? $_SERVER[$name] ?? null;

        return is_string($environmentValue) ? $environmentValue : null;
    }
}
