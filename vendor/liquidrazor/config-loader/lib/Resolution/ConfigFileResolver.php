<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Lib\Resolution;

use LiquidRazor\ConfigLoader\Enum\ConfigFormat;
use LiquidRazor\ConfigLoader\Exception\ConfigException;
use LiquidRazor\ConfigLoader\Exception\InvalidConfigNameException;
use LiquidRazor\ConfigLoader\Exception\MissingConfigFileException;
use LiquidRazor\ConfigLoader\Exception\UnsupportedFormatException;

final class ConfigFileResolver
{
    private const KNOWN_EXTENSIONS = ['yaml', 'yml', 'json'];

    public function __construct(
        private readonly string $configRoot,
        private readonly ConfigFormat $format,
    ) {
    }

    public function resolve(string $logicalName): string
    {
        $normalizedName = $this->normalizeLogicalName($logicalName);

        return $this->resolveVariant($normalizedName);
    }

    /**
     * @param list<string> $layers
     * @return list<string>
     */
    public function resolveLayered(string $logicalName, array $layers): array
    {
        $normalizedName = $this->normalizeLogicalName($logicalName);
        $paths = [$this->resolveVariant($normalizedName)];

        foreach ($layers as $layer) {
            $normalizedLayer = $this->normalizeLayer($layer);
            $paths[] = $this->resolveVariant(sprintf('%s.%s', $normalizedName, $normalizedLayer));
        }

        return $paths;
    }

    private function resolveVariant(string $baseName): string
    {
        $allowedMatches = [];

        foreach ($this->format->extensions() as $extension) {
            $candidate = $this->buildPath($baseName, $extension);

            if (is_file($candidate)) {
                $allowedMatches[$extension] = $candidate;
            }
        }

        if (count($allowedMatches) > 1) {
            throw new ConfigException(
                sprintf(
                    'Multiple config files match "%s" for format %s: %s',
                    $baseName,
                    $this->format->value,
                    implode(', ', array_values($allowedMatches)),
                ),
            );
        }

        if ($allowedMatches !== []) {
            return array_values($allowedMatches)[0];
        }

        foreach (self::KNOWN_EXTENSIONS as $extension) {
            if (in_array($extension, $this->format->extensions(), true)) {
                continue;
            }

            $candidate = $this->buildPath($baseName, $extension);

            if (is_file($candidate)) {
                throw UnsupportedFormatException::forExtension($extension);
            }
        }

        throw MissingConfigFileException::forPath($this->buildPath($baseName, $this->format->defaultExtension()));
    }

    private function normalizeLogicalName(string $logicalName): string
    {
        $normalized = str_replace('\\', '/', trim($logicalName));

        if ($normalized === '') {
            throw InvalidConfigNameException::becauseEmpty();
        }

        if (str_starts_with($normalized, '/')) {
            throw InvalidConfigNameException::becauseTraversal($logicalName);
        }

        $segments = explode('/', $normalized);

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw InvalidConfigNameException::becauseTraversal($logicalName);
            }
        }

        return implode('/', $segments);
    }

    private function normalizeLayer(string $layer): string
    {
        $normalized = trim($layer);

        if ($normalized === '') {
            throw InvalidConfigNameException::becauseEmpty();
        }

        if (str_contains($normalized, '/') || str_contains($normalized, '\\') || $normalized === '.' || $normalized === '..') {
            throw InvalidConfigNameException::becauseTraversal($layer);
        }

        return $normalized;
    }

    private function buildPath(string $baseName, string $extension): string
    {
        return sprintf(
            '%s%s%s.%s',
            $this->configRoot,
            DIRECTORY_SEPARATOR,
            str_replace('/', DIRECTORY_SEPARATOR, $baseName),
            $extension,
        );
    }
}
