<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Config;

final readonly class RootConfig
{
    /**
     * @param list<string> $extensions
     * @param list<string> $exclude
     */
    public function __construct(
        public string $name,
        public bool $enabled,
        public string $path,
        public bool $recursive,
        public array $extensions,
        public array $exclude,
    ) {
    }
}
