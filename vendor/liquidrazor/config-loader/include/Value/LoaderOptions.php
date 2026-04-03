<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Value;

use LiquidRazor\ConfigLoader\Enum\ConfigFormat;

final readonly class LoaderOptions
{
    public function __construct(
        public string $configRoot,
        public ConfigFormat $format = ConfigFormat::YAML,
    ) {
    }
}
