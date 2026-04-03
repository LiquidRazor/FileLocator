<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Exception;

final class UnsupportedFormatException extends ConfigException
{
    public static function forExtension(string $extension): self
    {
        return new self(sprintf('Unsupported config format extension: %s', $extension));
    }
}
