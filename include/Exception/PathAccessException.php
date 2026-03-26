<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Exception;

final class PathAccessException extends FileLocatorException
{
    public function __construct(
        private readonly string $path,
        private readonly string $operation,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Failed to %s path "%s".', $operation, $path),
            0,
            $previous
        );
    }

    public function path(): string
    {
        return $this->path;
    }

    public function operation(): string
    {
        return $this->operation;
    }
}
