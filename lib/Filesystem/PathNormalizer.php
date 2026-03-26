<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Filesystem;

use LiquidRazor\FileLocator\Exception\InvalidDiscoveryConfigException;

final class PathNormalizer
{
    public function normalize(string $projectRoot, string $path): string
    {
        $normalizedProjectRoot = $this->normalizeAbsolutePath($projectRoot, 'project root');

        if ($this->isAbsolutePath($path)) {
            return $this->normalizeAbsolutePath($path, 'path');
        }

        $relativePath = $this->normalizeRelativePath($path);

        if ($relativePath === '') {
            throw new InvalidDiscoveryConfigException('Path must not be empty.');
        }

        return $this->joinNormalizedPath($normalizedProjectRoot, $relativePath);
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    public function normalizeAll(string $projectRoot, array $paths): array
    {
        $normalizedPaths = [];

        foreach ($paths as $path) {
            $normalizedPaths[] = $this->normalize($projectRoot, $path);
        }

        return $normalizedPaths;
    }

    private function normalizeAbsolutePath(string $path, string $label): string
    {
        $path = $this->sanitizePath($path, $label);

        if (!$this->isAbsolutePath($path)) {
            throw new InvalidDiscoveryConfigException(sprintf('The %s must be an absolute path.', $label));
        }

        [$prefix, $segments] = $this->splitAbsolutePath($path, $label);
        $normalizedSegments = $this->normalizeSegments($segments, $label);

        return $this->buildAbsolutePath($prefix, $normalizedSegments);
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = $this->sanitizePath($path, 'path');

        if ($path === '.' || $path === './') {
            return '';
        }

        $segments = explode('/', $path);
        $normalizedSegments = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if ($normalizedSegments === []) {
                    throw new InvalidDiscoveryConfigException('Path must not escape above the project root.');
                }

                array_pop($normalizedSegments);
                continue;
            }

            $normalizedSegments[] = $segment;
        }

        return implode('/', $normalizedSegments);
    }

    private function sanitizePath(string $path, string $label): string
    {
        if ($path === '') {
            throw new InvalidDiscoveryConfigException(sprintf('The %s must not be empty.', $label));
        }

        if (str_contains($path, "\0")) {
            throw new InvalidDiscoveryConfigException(sprintf('The %s must not contain null bytes.', $label));
        }

        return str_replace('\\', '/', $path);
    }

    private function isAbsolutePath(string $path): bool
    {
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:\//', $path) === 1
            || preg_match('#^//[^/]+/[^/]+#', $path) === 1;
    }

    /**
     * @return array{0:string,1:list<string>}
     */
    private function splitAbsolutePath(string $path, string $label): array
    {
        if (preg_match('#^//([^/]+)/([^/]+)(?:/(.*))?$#', $path, $matches) === 1) {
            $prefix = sprintf('//%s/%s', $matches[1], $matches[2]);
            $remainder = $matches[3] ?? '';

            return [$prefix, $remainder === '' ? [] : explode('/', $remainder)];
        }

        if (preg_match('/^([A-Za-z]:)\/?(.*)$/', $path, $matches) === 1) {
            $prefix = $matches[1] . '/';
            $remainder = $matches[2];

            return [$prefix, $remainder === '' ? [] : explode('/', $remainder)];
        }

        if (str_starts_with($path, '/')) {
            $remainder = ltrim($path, '/');

            return ['/', $remainder === '' ? [] : explode('/', $remainder)];
        }

        throw new InvalidDiscoveryConfigException(sprintf('The %s must be an absolute path.', $label));
    }

    /**
     * @param list<string> $segments
     * @return list<string>
     */
    private function normalizeSegments(array $segments, string $label): array
    {
        $normalizedSegments = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if ($normalizedSegments === []) {
                    throw new InvalidDiscoveryConfigException(sprintf('The %s must not escape above its root.', $label));
                }

                array_pop($normalizedSegments);
                continue;
            }

            $normalizedSegments[] = $segment;
        }

        return $normalizedSegments;
    }

    private function buildAbsolutePath(string $prefix, array $segments): string
    {
        if ($segments === []) {
            return $prefix === '/' ? '/' : rtrim($prefix, '/');
        }

        if ($prefix === '/') {
            return '/' . implode('/', $segments);
        }

        return rtrim($prefix, '/') . '/' . implode('/', $segments);
    }

    private function joinNormalizedPath(string $projectRoot, string $relativePath): string
    {
        if ($projectRoot === '/') {
            return '/' . $relativePath;
        }

        return $projectRoot . '/' . $relativePath;
    }
}
