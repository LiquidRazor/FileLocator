<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Exception;

final class YamlParseException extends DiscoveryConfigException
{
    public function __construct(
        private readonly string $path,
        string $reason,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Failed to parse YAML file "%s": %s', $path, $reason),
            0,
            $previous
        );
    }

    public function path(): string
    {
        return $this->path;
    }
}
