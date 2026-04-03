<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Exception;

use Throwable;

final class InvalidConfigSyntaxException extends ConfigException
{
    public static function forSource(string $source, string $reason, ?Throwable $previous = null): self
    {
        return new self(sprintf('Invalid config syntax in %s: %s', $source, $reason), 0, $previous);
    }
}
