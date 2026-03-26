<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Config;

use LiquidRazor\FileLocator\Enum\UnreadablePathMode;

final readonly class DiscoveryDefaults
{
    /**
     * @param list<string> $extensions
     */
    public function __construct(
        public bool $followSymlinks,
        public bool $includeHidden,
        public UnreadablePathMode $onUnreadable,
        public array $extensions,
    ) {
    }
}
