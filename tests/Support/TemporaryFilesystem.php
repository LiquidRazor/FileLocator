<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Tests\Support;

use RuntimeException;

final readonly class TemporaryFilesystem
{
    public function __construct(
        public string $rootPath,
    ) {
    }

    public static function create(string $prefix = 'liquidrazor-filelocator-'): self
    {
        $baseDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $rootPath = $baseDirectory . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(8));

        if (!mkdir($rootPath, 0777, true) && !is_dir($rootPath)) {
            throw new RuntimeException(sprintf('Unable to create temporary directory "%s".', $rootPath));
        }

        return new self($rootPath);
    }

    public function path(string $relativePath = ''): string
    {
        if ($relativePath === '') {
            return $this->rootPath;
        }

        return $this->rootPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    }

    public function mkdir(string $relativePath, int $mode = 0777): string
    {
        $path = $this->path($relativePath);

        if (!mkdir($path, $mode, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Unable to create directory "%s".', $path));
        }

        return $path;
    }

    public function writeFile(string $relativePath, string $contents = ''): string
    {
        $path = $this->path($relativePath);
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write file "%s".', $path));
        }

        return $path;
    }

    public function symlink(string $target, string $link): string
    {
        $linkPath = $this->path($link);
        $directory = dirname($linkPath);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
        }

        if (!symlink($target, $linkPath)) {
            throw new RuntimeException(sprintf('Unable to create symlink "%s".', $linkPath));
        }

        return $linkPath;
    }

    public function chmod(string $relativePath, int $mode): string
    {
        $path = $this->path($relativePath);

        if (!chmod($path, $mode)) {
            throw new RuntimeException(sprintf('Unable to change permissions for "%s".', $path));
        }

        return $path;
    }

    public function cleanup(): void
    {
        if (!file_exists($this->rootPath) && !is_link($this->rootPath)) {
            return;
        }

        $this->delete($this->rootPath);
    }

    private function delete(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            if (!unlink($path)) {
                throw new RuntimeException(sprintf('Unable to delete "%s".', $path));
            }

            return;
        }

        $entries = scandir($path);

        if ($entries === false) {
            throw new RuntimeException(sprintf('Unable to read directory "%s".', $path));
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $this->delete($path . DIRECTORY_SEPARATOR . $entry);
        }

        if (!rmdir($path)) {
            throw new RuntimeException(sprintf('Unable to remove directory "%s".', $path));
        }
    }
}
