<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Exception;

final class MissingConfigFileException extends ConfigException
{
    public static function forPath(string $path): self
    {
        return new self(sprintf('Config file does not exist: %s', $path));
    }
}
