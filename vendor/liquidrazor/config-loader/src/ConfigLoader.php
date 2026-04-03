<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader;

use LiquidRazor\ConfigLoader\Contract\ConfigParserInterface;
use LiquidRazor\ConfigLoader\Contract\EnvironmentReaderInterface;
use LiquidRazor\ConfigLoader\Enum\ConfigFormat;
use LiquidRazor\ConfigLoader\Exception\ConfigException;
use LiquidRazor\ConfigLoader\Lib\Environment\PhpEnvironmentReader;
use LiquidRazor\ConfigLoader\Lib\Interpolation\EnvironmentInterpolator;
use LiquidRazor\ConfigLoader\Lib\Merge\ConfigMerger;
use LiquidRazor\ConfigLoader\Lib\Parsing\JsonConfigParser;
use LiquidRazor\ConfigLoader\Lib\Parsing\YamlConfigParser;
use LiquidRazor\ConfigLoader\Lib\Resolution\ConfigFileResolver;
use LiquidRazor\ConfigLoader\Lib\Resolution\ConfigRootResolver;
use LiquidRazor\ConfigLoader\Value\LoaderOptions;

final class ConfigLoader
{
    private readonly ConfigFileResolver $fileResolver;

    private readonly ConfigParserInterface $parser;

    private readonly ConfigMerger $merger;

    private readonly EnvironmentInterpolator $interpolator;

    public function __construct(
        private readonly LoaderOptions $options,
        ?EnvironmentReaderInterface $environmentReader = null,
    ) {
        $resolvedRoot = (new ConfigRootResolver())->resolve($this->options->configRoot);

        $this->fileResolver = new ConfigFileResolver($resolvedRoot, $this->options->format);
        $this->parser = $this->createParser($this->options->format);
        $this->merger = new ConfigMerger();
        $this->interpolator = new EnvironmentInterpolator($environmentReader ?? new PhpEnvironmentReader());
    }

    /**
     * @return array<mixed>
     */
    public function load(string $logicalName): array
    {
        return $this->loadResolvedPaths([$this->fileResolver->resolve($logicalName)]);
    }

    /**
     * @param list<string> $layers
     * @return array<mixed>
     */
    public function loadLayered(string $logicalName, array $layers): array
    {
        return $this->loadResolvedPaths($this->fileResolver->resolveLayered($logicalName, $layers));
    }

    /**
     * @param list<string> $resolvedPaths
     * @return array<mixed>
     */
    private function loadResolvedPaths(array $resolvedPaths): array
    {
        $parsedLayers = [];

        foreach ($resolvedPaths as $path) {
            $parsedLayers[] = $this->parser->parse($this->readFile($path), $path);
        }

        $merged = $this->merger->mergeAll($parsedLayers);

        return $this->interpolator->interpolate($merged);
    }

    private function createParser(ConfigFormat $format): ConfigParserInterface
    {
        return match ($format) {
            ConfigFormat::YAML => new YamlConfigParser(),
            ConfigFormat::JSON => new JsonConfigParser(),
        };
    }

    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new ConfigException(sprintf('Unable to read config file: %s', $path));
        }

        return $contents;
    }
}
