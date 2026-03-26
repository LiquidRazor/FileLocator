<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Config;

final readonly class DiscoveryConfig
{
    /**
     * @param list<string> $exclude
     * @param list<RootConfig> $roots
     */
    public function __construct(
        public string $projectRoot,
        public array $exclude,
        public DiscoveryDefaults $defaults,
        public array $roots,
    ) {
    }
}
