<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Filesystem;

use LiquidRazor\FileLocator\Config\DiscoveryConfig;
use LiquidRazor\FileLocator\Config\RootConfig;

final readonly class PathFilter
{
    public function __construct(
        private DiscoveryConfig $config,
    ) {
    }

    public function isHidden(string $path): bool
    {
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }

            if ($segment[0] === '.') {
                return true;
            }
        }

        return false;
    }

    public function isExcluded(string $path, RootConfig $root): bool
    {
        foreach ($this->config->exclude as $prefix) {
            if ($this->matchesPrefix($path, $prefix)) {
                return true;
            }
        }

        foreach ($root->exclude as $prefix) {
            if ($this->matchesPrefix($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function matchesExtension(string $path, RootConfig $root): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === '') {
            return false;
        }

        return in_array($extension, $root->extensions, true);
    }

    public function shouldDescend(string $path, RootConfig $root): bool
    {
        if (!$this->config->defaults->includeHidden && $this->isHidden($path)) {
            return false;
        }

        return !$this->isExcluded($path, $root);
    }

    public function shouldYield(string $path, RootConfig $root): bool
    {
        if (!$this->config->defaults->includeHidden && $this->isHidden($path)) {
            return false;
        }

        if ($this->isExcluded($path, $root)) {
            return false;
        }

        return $this->matchesExtension($path, $root);
    }

    private function matchesPrefix(string $path, string $prefix): bool
    {
        $normalizedPath = rtrim(str_replace('\\', '/', $path), '/');
        $normalizedPrefix = rtrim(str_replace('\\', '/', $prefix), '/');

        if ($normalizedPath === $normalizedPrefix) {
            return true;
        }

        return str_starts_with($normalizedPath, $normalizedPrefix . '/');
    }
}
