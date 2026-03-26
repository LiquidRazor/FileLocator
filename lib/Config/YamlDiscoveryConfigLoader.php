<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Config;

use LiquidRazor\FileLocator\Config\Internal\MinimalYamlParser;
use LiquidRazor\FileLocator\Exception\YamlParseException;

final class YamlDiscoveryConfigLoader
{
    /**
     * @return array<string, mixed>
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new YamlParseException($path, 'YAML file does not exist.');
        }

        if (extension_loaded('yaml')) {
            return $this->loadWithExtension($path);
        }

        return $this->loadWithFallbackParser($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadWithExtension(string $path): array
    {
        $parsed = yaml_parse_file($path);

        if ($parsed === false) {
            throw new YamlParseException($path, 'The YAML extension failed to parse the file.');
        }

        return $this->assertTopLevelMapping($parsed, $path);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadWithFallbackParser(string $path): array
    {
        $parsed = (new MinimalYamlParser())->parseFile($path);

        return $this->assertTopLevelMapping($parsed, $path);
    }

    /**
     * @return array<string, mixed>
     */
    private function assertTopLevelMapping(mixed $parsed, string $path): array
    {
        if (!is_array($parsed) || array_is_list($parsed)) {
            throw new YamlParseException($path, 'Top-level YAML value must be a mapping.');
        }

        return $parsed;
    }
}
