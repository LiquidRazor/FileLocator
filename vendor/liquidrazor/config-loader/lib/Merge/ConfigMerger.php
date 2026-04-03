<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Lib\Merge;

final class ConfigMerger
{
    /**
     * @param list<array<mixed>> $layers
     * @return array<mixed>
     */
    public function mergeAll(array $layers): array
    {
        $merged = [];

        foreach ($layers as $layer) {
            $merged = $this->merge($merged, $layer);
        }

        return $merged;
    }

    /**
     * @param array<mixed> $base
     * @param array<mixed> $override
     * @return array<mixed>
     */
    public function merge(array $base, array $override): array
    {
        if (array_is_list($base) || array_is_list($override)) {
            return $override;
        }

        $merged = $base;

        foreach ($override as $key => $value) {
            $baseValue = $merged[$key] ?? null;

            if (!array_key_exists($key, $merged)) {
                $merged[$key] = $value;
                continue;
            }

            if (is_array($baseValue) && is_array($value) && !array_is_list($baseValue) && !array_is_list($value)) {
                $merged[$key] = $this->merge($baseValue, $value);
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }
}
