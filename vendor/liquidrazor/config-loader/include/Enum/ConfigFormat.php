<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Enum;

enum ConfigFormat: string
{
    case YAML = 'yaml';
    case JSON = 'json';

    /**
     * @return list<string>
     */
    public function extensions(): array
    {
        return match ($this) {
            self::YAML => ['yaml', 'yml'],
            self::JSON => ['json'],
        };
    }

    public function defaultExtension(): string
    {
        return $this->extensions()[0];
    }
}
