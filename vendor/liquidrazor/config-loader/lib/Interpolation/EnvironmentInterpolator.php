<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Lib\Interpolation;

use LiquidRazor\ConfigLoader\Contract\EnvironmentReaderInterface;
use LiquidRazor\ConfigLoader\Exception\ConfigException;
use LiquidRazor\ConfigLoader\Exception\MissingEnvironmentVariableException;

final class EnvironmentInterpolator
{
    public function __construct(
        private readonly EnvironmentReaderInterface $environmentReader,
    ) {
    }

    /**
     * @param array<mixed> $config
     * @return array<mixed>
     */
    public function interpolate(array $config): array
    {
        return $this->interpolateValue($config);
    }

    private function interpolateValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $interpolated = [];

            foreach ($value as $key => $nestedValue) {
                $interpolated[$key] = $this->interpolateValue($nestedValue);
            }

            return $interpolated;
        }

        if (!is_string($value)) {
            return $value;
        }

        $interpolated = preg_replace_callback(
            '/\$\{([A-Za-z_][A-Za-z0-9_]*)(?::-([^}]*))?\}/',
            function (array $matches): string {
                $name = $matches[1];
                $default = array_key_exists(2, $matches) ? $matches[2] : null;
                $resolved = $this->environmentReader->get($name);

                if ($resolved !== null) {
                    return $resolved;
                }

                if ($default !== null) {
                    return $default;
                }

                throw MissingEnvironmentVariableException::forName($name);
            },
            $value,
        );

        if ($interpolated === null) {
            throw new ConfigException('Interpolation failed due to an invalid replacement pattern.');
        }

        if (str_contains($interpolated, '${')) {
            throw new ConfigException(sprintf('Invalid interpolation expression: %s', $value));
        }

        return $interpolated;
    }
}
