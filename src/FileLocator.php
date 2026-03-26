<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator;

use Generator;
use LiquidRazor\FileLocator\Config\DiscoveryConfig;
use LiquidRazor\FileLocator\Config\RootConfig;
use LiquidRazor\FileLocator\Enum\UnreadablePathMode;
use LiquidRazor\FileLocator\Exception\PathAccessException;
use LiquidRazor\FileLocator\Filesystem\PathFilter;
use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;

final readonly class FileLocator
{
    private PathFilter $pathFilter;

    public function __construct(
        private DiscoveryConfig $config,
        ?PathFilter             $pathFilter = null,
    ) {
        $this->pathFilter = $pathFilter ?? new PathFilter($config);
    }

    /**
     * @return Generator<int, string>
     */
    public function locate(): Generator
    {
        foreach ($this->config->roots as $root) {
            yield from $this->locateRoot($root);
        }
    }

    /**
     * @return Generator<int, string>
     */
    private function locateRoot(RootConfig $root): Generator
    {
        if (!is_dir($root->path)) {
            return;
        }

        if (!$this->config->defaults->followSymlinks && is_link($root->path)) {
            return;
        }

        if (!$this->canAccessPath($root->path, 'read')) {
            return;
        }

        $visitedDirectories = [];

        if ($this->config->defaults->followSymlinks) {
            $rootRealPath = realpath($root->path);

            if ($rootRealPath === false) {
                if ($this->config->defaults->onUnreadable === UnreadablePathMode::Skip) {
                    return;
                }

                throw new PathAccessException($root->path, 'read');
            }

            $visitedDirectories[$this->normalizePath($rootRealPath)] = true;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveCallbackFilterIterator(
                    new RecursiveDirectoryIterator(
                        $root->path,
                        FilesystemIterator::SKIP_DOTS
                        | ($this->config->defaults->followSymlinks ? FilesystemIterator::FOLLOW_SYMLINKS : 0)
                    ),
                    function (SplFileInfo $fileInfo) use ($root, &$visitedDirectories): bool {
                        $path = $this->normalizePath($fileInfo->getPathname());

                        if (!$this->config->defaults->followSymlinks && $fileInfo->isLink()) {
                            return false;
                        }

                        if (!$this->canAccessPath($path, 'read')) {
                            return false;
                        }

                        if ($fileInfo->isDir()) {
                            if (!$root->recursive) {
                                return false;
                            }

                            if (!$this->pathFilter->shouldDescend($path, $root)) {
                                return false;
                            }

                            if ($this->config->defaults->followSymlinks) {
                                $realPath = realpath($fileInfo->getPathname());

                                if ($realPath === false) {
                                    if ($this->config->defaults->onUnreadable === UnreadablePathMode::Skip) {
                                        return false;
                                    }

                                    throw new PathAccessException($path, 'read');
                                }

                                $normalizedRealPath = $this->normalizePath($realPath);

                                if (isset($visitedDirectories[$normalizedRealPath])) {
                                    return false;
                                }

                                $visitedDirectories[$normalizedRealPath] = true;
                            }

                            return true;
                        }

                        return $this->pathFilter->shouldYield($path, $root);
                    }
                ),
                RecursiveIteratorIterator::LEAVES_ONLY,
                $this->config->defaults->onUnreadable === UnreadablePathMode::Skip
                    ? RecursiveIteratorIterator::CATCH_GET_CHILD
                    : 0
            );
        } catch (UnexpectedValueException $exception) {
            if ($this->config->defaults->onUnreadable === UnreadablePathMode::Skip) {
                return;
            }

            throw new PathAccessException($root->path, 'read', $exception);
        }

        try {
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }

                yield $this->normalizePath($fileInfo->getPathname());
            }
        } catch (UnexpectedValueException $exception) {
            if ($this->config->defaults->onUnreadable === UnreadablePathMode::Skip) {
                return;
            }

            throw new PathAccessException($root->path, 'read', $exception);
        }
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function canAccessPath(string $path, string $operation): bool
    {
        if (is_readable($path)) {
            return true;
        }

        if ($this->config->defaults->onUnreadable === UnreadablePathMode::Skip) {
            return false;
        }

        throw new PathAccessException($path, $operation);
    }
}
