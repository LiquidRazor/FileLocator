<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Exception;

final class InvalidConfigNameException extends ConfigException
{
    public static function becauseEmpty(): self
    {
        return new self('Config logical name must not be empty.');
    }

    public static function becauseTraversal(string $name): self
    {
        return new self(sprintf('Config logical name contains path traversal: %s', $name));
    }
}
