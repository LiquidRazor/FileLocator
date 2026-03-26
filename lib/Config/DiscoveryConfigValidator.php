<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Config;

use LiquidRazor\FileLocator\Enum\UnreadablePathMode;
use LiquidRazor\FileLocator\Exception\InvalidDiscoveryConfigException;
use LiquidRazor\FileLocator\Filesystem\PathNormalizer;

final readonly class DiscoveryConfigValidator
{
    public function __construct(
        private PathNormalizer $pathNormalizer = new PathNormalizer(),
    ) {
    }

    /**
     * @param array<string, mixed> $config
     * @return array{
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
    public function validate(array $config, string $projectRoot): array
    {
        $this->assertAllowedKeys($config, ['discovery'], 'config');

        if (!array_key_exists('discovery', $config)) {
            throw new InvalidDiscoveryConfigException('Missing required mapping "discovery".');
        }

        $discovery = $this->assertMapping($config['discovery'], 'discovery');
        $this->assertAllowedKeys($discovery, ['defaults', 'exclude', 'roots'], 'discovery');

        $defaults = $this->validateDefaultsSection($discovery);
        $exclude = $this->normalizePathList(
            $projectRoot,
            $this->assertList($discovery['exclude'] ?? null, 'discovery.exclude')
        );
        $roots = $this->validateRootsSection(
            $projectRoot,
            $defaults['extensions'],
            $this->assertMapping($discovery['roots'] ?? null, 'discovery.roots')
        );

        return [
            'discovery' => [
                'defaults' => $defaults,
                'exclude' => $exclude,
                'roots' => $roots,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $discovery
     * @return array{
     *     follow_symlinks: bool,
     *     include_hidden: bool,
     *     on_unreadable: 'skip'|'fail',
     *     extensions: list<string>
     * }
     */
    private function validateDefaultsSection(array $discovery): array
    {
        $defaults = $this->assertMapping($discovery['defaults'] ?? null, 'discovery.defaults');
        $this->assertAllowedKeys(
            $defaults,
            ['follow_symlinks', 'include_hidden', 'on_unreadable', 'extensions'],
            'discovery.defaults'
        );

        $followSymlinks = $this->assertBool($defaults['follow_symlinks'] ?? null, 'discovery.defaults.follow_symlinks');
        $includeHidden = $this->assertBool($defaults['include_hidden'] ?? null, 'discovery.defaults.include_hidden');
        $onUnreadable = $this->assertUnreadableMode($defaults['on_unreadable'] ?? null, 'discovery.defaults.on_unreadable');
        $extensions = $this->normalizeExtensions(
            $this->assertList($defaults['extensions'] ?? null, 'discovery.defaults.extensions'),
            'discovery.defaults.extensions'
        );

        return [
            'follow_symlinks' => $followSymlinks,
            'include_hidden' => $includeHidden,
            'on_unreadable' => $onUnreadable,
            'extensions' => $extensions,
        ];
    }

    /**
     * @param array<string, mixed> $roots
     * @param list<string> $defaultExtensions
     * @return array<string, array{
     *     enabled: true,
     *     path: string,
     *     recursive: bool,
     *     extensions: list<string>,
     *     exclude: list<string>
     * }>
     */
    private function validateRootsSection(string $projectRoot, array $defaultExtensions, array $roots): array
    {
        $normalizedRoots = [];

        foreach ($roots as $name => $root) {
            if (!is_string($name) || $name === '') {
                throw new InvalidDiscoveryConfigException('discovery.roots must use non-empty string keys.');
            }

            $rootPath = sprintf('discovery.roots.%s', $name);
            $rootConfig = $this->assertMapping($root, $rootPath);
            $this->assertAllowedKeys(
                $rootConfig,
                ['enabled', 'path', 'recursive', 'extensions', 'exclude'],
                $rootPath
            );

            $enabled = array_key_exists('enabled', $rootConfig)
                ? $this->assertBool($rootConfig['enabled'], $rootPath . '.enabled')
                : true;

            $normalizedPath = null;

            if (array_key_exists('path', $rootConfig)) {
                $normalizedPath = $this->assertString($rootConfig['path'], $rootPath . '.path');
                $normalizedPath = $this->pathNormalizer->normalize($projectRoot, $normalizedPath);
            } elseif ($enabled) {
                throw new InvalidDiscoveryConfigException(sprintf('Missing required string "%s.path".', $rootPath));
            }

            if ($enabled === false) {
                if (array_key_exists('recursive', $rootConfig)) {
                    $this->assertBool($rootConfig['recursive'], $rootPath . '.recursive');
                }

                if (array_key_exists('extensions', $rootConfig)) {
                    $this->normalizeExtensions(
                        $this->assertList($rootConfig['extensions'], $rootPath . '.extensions'),
                        $rootPath . '.extensions'
                    );
                }

                if (array_key_exists('exclude', $rootConfig)) {
                    $this->normalizePathList(
                        $projectRoot,
                        $this->assertList($rootConfig['exclude'], $rootPath . '.exclude')
                    );
                }

                continue;
            }

            $recursive = $this->assertBool($rootConfig['recursive'] ?? null, $rootPath . '.recursive');
            $extensions = array_key_exists('extensions', $rootConfig)
                ? $this->normalizeExtensions(
                    $this->assertList($rootConfig['extensions'], $rootPath . '.extensions'),
                    $rootPath . '.extensions'
                )
                : $defaultExtensions;
            $exclude = array_key_exists('exclude', $rootConfig)
                ? $this->normalizePathList(
                    $projectRoot,
                    $this->assertList($rootConfig['exclude'], $rootPath . '.exclude')
                )
                : [];

            $normalizedRoots[$name] = [
                'enabled' => true,
                'path' => $normalizedPath,
                'recursive' => $recursive,
                'extensions' => $extensions,
                'exclude' => $exclude,
            ];
        }

        return $normalizedRoots;
    }

    /**
     * @param list<mixed> $extensions
     * @return list<string>
     */
    private function normalizeExtensions(array $extensions, string $path): array
    {
        $normalized = [];

        foreach ($extensions as $index => $extension) {
            if (!is_string($extension) || $extension === '') {
                throw new InvalidDiscoveryConfigException(sprintf(
                    'Expected "%s[%d]" to be a non-empty string.',
                    $path,
                    $index
                ));
            }

            $canonical = strtolower(ltrim($extension, '.'));

            if ($canonical !== 'php') {
                throw new InvalidDiscoveryConfigException(sprintf(
                    'Unsupported extension "%s" at "%s[%d]".',
                    $extension,
                    $path,
                    $index
                ));
            }

            if (in_array($canonical, $normalized, true)) {
                continue;
            }

            $normalized[] = $canonical;
        }

        return $normalized;
    }

    /**
     * @param list<mixed> $paths
     * @return list<string>
     */
    private function normalizePathList(string $projectRoot, array $paths): array
    {
        $normalized = [];

        foreach ($paths as $index => $path) {
            if (!is_string($path) || $path === '') {
                throw new InvalidDiscoveryConfigException(sprintf(
                    'Expected path list item at index %d to be a non-empty string.',
                    $index
                ));
            }

            $canonical = $this->pathNormalizer->normalize($projectRoot, $path);

            if (in_array($canonical, $normalized, true)) {
                continue;
            }

            $normalized[] = $canonical;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $mapping
     * @param list<string> $allowedKeys
     */
    private function assertAllowedKeys(array $mapping, array $allowedKeys, string $path): void
    {
        foreach ($mapping as $key => $_value) {
            if (!is_string($key) || !in_array($key, $allowedKeys, true)) {
                throw new InvalidDiscoveryConfigException(sprintf(
                    'Unsupported key "%s" in "%s".',
                    (string) $key,
                    $path
                ));
            }
        }
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

    private function assertBool(mixed $value, string $path): bool
    {
        if (!is_bool($value)) {
            throw new InvalidDiscoveryConfigException(sprintf('Expected "%s" to be a boolean.', $path));
        }

        return $value;
    }

    private function assertString(mixed $value, string $path): string
    {
        if (!is_string($value) || $value === '') {
            throw new InvalidDiscoveryConfigException(sprintf('Expected "%s" to be a non-empty string.', $path));
        }

        return $value;
    }

    /**
     * @return 'skip'|'fail'
     */
    private function assertUnreadableMode(mixed $value, string $path): string
    {
        if (!is_string($value)) {
            throw new InvalidDiscoveryConfigException(sprintf('Expected "%s" to be a string.', $path));
        }

        foreach (UnreadablePathMode::cases() as $mode) {
            if ($mode->value === $value) {
                return $value;
            }
        }

        throw new InvalidDiscoveryConfigException(sprintf(
            'Expected "%s" to be one of: skip, fail.',
            $path
        ));
    }
}
