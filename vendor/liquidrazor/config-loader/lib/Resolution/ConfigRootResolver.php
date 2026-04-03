<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Lib\Resolution;

use LiquidRazor\ConfigLoader\Exception\InvalidConfigRootException;

final class ConfigRootResolver
{
    public function resolve(string $configRoot): string
    {
        if (!file_exists($configRoot)) {
            throw InvalidConfigRootException::becausePathDoesNotExist($configRoot);
        }

        if (!is_dir($configRoot)) {
            throw InvalidConfigRootException::becausePathIsNotDirectory($configRoot);
        }

        $resolvedPath = realpath($configRoot);

        if ($resolvedPath === false) {
            throw InvalidConfigRootException::becausePathDoesNotExist($configRoot);
        }

        return rtrim($resolvedPath, DIRECTORY_SEPARATOR);
    }
}
