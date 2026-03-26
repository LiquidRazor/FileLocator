<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Config;

use LiquidRazor\FileLocator\Exception\InvalidDiscoveryConfigException;

final class DiscoveryConfigMerger
{
    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public function merge(array $defaults, array $overrides): array
    {
        $merged = $defaults;
        $defaultDiscovery = $this->getMapping($defaults, 'discovery', true);
        $overrideDiscovery = $this->getMapping($overrides, 'discovery', false) ?? [];

        $merged['discovery'] = $this->mergeDiscovery($defaultDiscovery, $overrideDiscovery);

        foreach ($overrides as $key => $value) {
            if ($key === 'discovery') {
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function mergeDiscovery(array $defaults, array $overrides): array
    {
        $merged = $defaults;

        foreach ($overrides as $key => $value) {
            if ($key === 'defaults') {
                $merged[$key] = $this->mergeDefaultsSection(
                    $this->assertMapping($defaults[$key] ?? [], 'discovery.defaults'),
                    $this->assertMapping($value, 'discovery.defaults')
                );
                continue;
            }

            if ($key === 'exclude') {
                $merged[$key] = $this->mergeLists(
                    $this->assertList($defaults[$key] ?? [], 'discovery.exclude'),
                    $this->assertList($value, 'discovery.exclude')
                );
                continue;
            }

            if ($key === 'roots') {
                $merged[$key] = $this->mergeRootsSection(
                    $this->assertMapping($defaults[$key] ?? [], 'discovery.roots'),
                    $this->assertMapping($value, 'discovery.roots')
                );
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function mergeDefaultsSection(array $defaults, array $overrides): array
    {
        $merged = $defaults;

        foreach ($overrides as $key => $value) {
            if ($key === 'extensions') {
                $merged[$key] = $this->mergeLists(
                    $this->assertList($defaults[$key] ?? [], 'discovery.defaults.extensions'),
                    $this->assertList($value, 'discovery.defaults.extensions')
                );
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function mergeRootsSection(array $defaults, array $overrides): array
    {
        $merged = $defaults;

        foreach ($overrides as $name => $rootConfig) {
            if (!is_string($name) || $name === '') {
                throw new InvalidDiscoveryConfigException('discovery.roots must use non-empty string keys.');
            }

            if (!array_key_exists($name, $merged)) {
                $merged[$name] = $rootConfig;
                continue;
            }

            $merged[$name] = $this->mergeRootConfig(
                $this->assertMapping($merged[$name], sprintf('discovery.roots.%s', $name)),
                $this->assertMapping($rootConfig, sprintf('discovery.roots.%s', $name))
            );
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function mergeRootConfig(array $defaults, array $overrides): array
    {
        $merged = $defaults;

        foreach ($overrides as $key => $value) {
            if ($key === 'exclude' || $key === 'extensions') {
                $merged[$key] = $this->mergeLists(
                    $this->assertList($defaults[$key] ?? [], sprintf('root.%s', $key)),
                    $this->assertList($value, sprintf('root.%s', $key))
                );
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>|null
     */
    private function getMapping(array $config, string $key, bool $required): ?array
    {
        if (!array_key_exists($key, $config)) {
            if ($required) {
                throw new InvalidDiscoveryConfigException(sprintf('Missing required mapping "%s".', $key));
            }

            return null;
        }

        return $this->assertMapping($config[$key], $key);
    }

    /**
     * @return array<string, mixed>
     */
    private function assertMapping(mixed $value, string $path): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new InvalidDiscoveryConfigException(sprintf('Expected "%s" to be a mapping.', $path));
        }

        return $value;
    }

    /**
     * @return list<mixed>
     */
    private function assertList(mixed $value, string $path): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new InvalidDiscoveryConfigException(sprintf('Expected "%s" to be a list.', $path));
        }

        return $value;
    }

    /**
     * @param list<mixed> $defaults
     * @param list<mixed> $overrides
     * @return list<mixed>
     */
    private function mergeLists(array $defaults, array $overrides): array
    {
        $merged = [];

        foreach ([$defaults, $overrides] as $values) {
            foreach ($values as $value) {
                if (in_array($value, $merged, true)) {
                    continue;
                }

                $merged[] = $value;
            }
        }

        return $merged;
    }
}
