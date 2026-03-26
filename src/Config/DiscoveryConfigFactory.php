<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Config;

use Closure;
use LiquidRazor\FileLocator\Enum\UnreadablePathMode;
use LiquidRazor\FileLocator\Filesystem\PathNormalizer;

final readonly class DiscoveryConfigFactory
{
    /**
     * @var Closure(string): array<string, mixed>
     */
    private Closure $loadConfig;

    /**
     * @var Closure(array<string, mixed>, array<string, mixed>): array<string, mixed>
     */
    private Closure $mergeConfig;

    /**
     * @var Closure(array<string, mixed>, string): array{
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
     * }
     */
    private Closure $validateConfig;

    public function __construct(
        private PathNormalizer $pathNormalizer = new PathNormalizer(),
        ?Closure               $loadConfig = null,
        ?Closure               $mergeConfig = null,
        ?Closure               $validateConfig = null,
    ) {
        $this->loadConfig = $loadConfig ?? static function (string $path): array {
            return (new YamlDiscoveryConfigLoader())->load($path);
        };
        $this->mergeConfig = $mergeConfig ?? static function (array $defaults, array $overrides): array {
            return (new DiscoveryConfigMerger())->merge($defaults, $overrides);
        };
        $this->validateConfig = $validateConfig ?? static function (array $config, string $projectRoot): array {
            return (new DiscoveryConfigValidator())->validate($config, $projectRoot);
        };
    }

    public function create(string $projectRoot): DiscoveryConfig
    {
        $normalizedProjectRoot = $this->pathNormalizer->normalize($projectRoot, $projectRoot);

        $defaults = ($this->loadConfig)($this->defaultConfigPath());
        $overrides = $this->loadProjectOverrides($normalizedProjectRoot);
        $merged = ($this->mergeConfig)($defaults, $overrides);
        $validated = ($this->validateConfig)($merged, $normalizedProjectRoot);

        return $this->buildConfig($validated, $normalizedProjectRoot);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadProjectOverrides(string $projectRoot): array
    {
        foreach ([
            $projectRoot . '/config/roots.yaml',
            $projectRoot . '/config/roots.yml',
        ] as $path) {
            if (!is_file($path)) {
                continue;
            }

            return ($this->loadConfig)($path);
        }

        return [];
    }

    private function defaultConfigPath(): string
    {
        return dirname(__DIR__, 2) . '/resources/config/roots.yaml';
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
}
