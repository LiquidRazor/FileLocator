<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Exception;

final class MissingJsonExtensionException extends ConfigException
{
    public static function requiredForJsonFormat(): self
    {
        return new self(
            'JSON configuration requires the PHP ext-json extension. Either install ext-json or switch the ConfigLoader format to YAML.',
        );
    }
}
