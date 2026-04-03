<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Exception;

final class InvalidConfigRootException extends ConfigException
{
    public static function becausePathDoesNotExist(string $path): self
    {
        return new self(sprintf('Config root does not exist: %s', $path));
    }

    public static function becausePathIsNotDirectory(string $path): self
    {
        return new self(sprintf('Config root is not a directory: %s', $path));
    }
}
