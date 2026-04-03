<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Exception;

final class MissingEnvironmentVariableException extends ConfigException
{
    public static function forName(string $name): self
    {
        return new self(sprintf('Missing environment variable: %s', $name));
    }
}
