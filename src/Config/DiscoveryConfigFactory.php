<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Config;

use LiquidRazor\ConfigLoader\ConfigLoader;
use LiquidRazor\ConfigLoader\Enum\ConfigFormat;
use LiquidRazor\ConfigLoader\Exception\InvalidConfigRootException;
use LiquidRazor\ConfigLoader\Exception\MissingConfigFileException;
use LiquidRazor\ConfigLoader\Lib\Merge\ConfigMerger;
use LiquidRazor\ConfigLoader\Value\LoaderOptions;
use LiquidRazor\FileLocator\Enum\UnreadablePathMode;
use LiquidRazor\FileLocator\Filesystem\PathNormalizer;

final readonly class DiscoveryConfigFactory
{
    private string $defaultConfigRoot;

    public function __construct(
        private PathNormalizer $pathNormalizer = new PathNormalizer(),
        private DiscoveryConfigValidator $validator = new DiscoveryConfigValidator(),
        private ConfigMerger $configMerger = new ConfigMerger(),
        ?string $defaultConfigRoot = null,
    ) {
        $this->defaultConfigRoot = $defaultConfigRoot ?? dirname(__DIR__, 2) . '/resources/config';
    }

    public function create(string $projectRoot): DiscoveryConfig
    {
        $normalizedProjectRoot = $this->pathNormalizer->normalize($projectRoot, $projectRoot);

        $defaults = $this->loadConfig($this->defaultConfigRoot, required: true);
        $overrides = $this->loadConfig($normalizedProjectRoot . '/config', required: false);
        $layers = [$defaults];

        if ($overrides !== []) {
            $layers[] = $overrides;
        }

        $validated = $this->validator->validate(
            $this->configMerger->mergeAll($layers),
            $normalizedProjectRoot,
        );

        return $this->buildConfig($validated, $normalizedProjectRoot);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfig(string $configRoot, bool $required): array
    {
        try {
            return $this->createLoader($configRoot)->load('roots');
        } catch (InvalidConfigRootException | MissingConfigFileException $e) {
            if ($required) {
                throw $e;
            }

            return [];
        }
    }

    /**
     * @param array{
     *     discovery: array{
     *         defaults: array{
     *             follow_symlinks: bool,
     *             include_hidden: bool,
     *             on_unreadable: 'skip'|'fail',
     *             extensions: list<string>
     *         },
     *         exclude: list<string>,
     *         roots: array<string, array{
     *             enabled: true,
     *             path: string,
     *             recursive: bool,
     *             extensions: list<string>,
     *             exclude: list<string>
     *         }>
     *     }
     * } $validated
     */
    private function buildConfig(array $validated, string $projectRoot): DiscoveryConfig
    {
        $defaultsData = $validated['discovery']['defaults'];
        $defaults = new DiscoveryDefaults(
            followSymlinks: $defaultsData['follow_symlinks'],
            includeHidden: $defaultsData['include_hidden'],
            onUnreadable: UnreadablePathMode::from($defaultsData['on_unreadable']),
            extensions: $defaultsData['extensions'],
        );

        $roots = [];

        foreach ($validated['discovery']['roots'] as $name => $rootData) {
            $roots[] = new RootConfig(
                name: $name,
                enabled: $rootData['enabled'],
                path: $rootData['path'],
                recursive: $rootData['recursive'],
                extensions: $rootData['extensions'],
                exclude: $rootData['exclude'],
            );
        }

        return new DiscoveryConfig(
            projectRoot: $projectRoot,
            exclude: $validated['discovery']['exclude'],
            defaults: $defaults,
            roots: $roots,
        );
    }

    private function createLoader(string $configRoot): ConfigLoader
    {
        return new ConfigLoader(
            new LoaderOptions(
                configRoot: $configRoot,
                format: ConfigFormat::YAML,
            ),
        );
    }
}
