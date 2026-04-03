<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Lib\Parsing;

use Closure;
use ErrorException;
use LiquidRazor\ConfigLoader\Contract\ConfigParserInterface;
use LiquidRazor\ConfigLoader\Exception\InvalidConfigSyntaxException;
use Throwable;

final class YamlConfigParser implements ConfigParserInterface
{
    private readonly ?Closure $extensionParser;

    public function __construct(
        ?callable $extensionParser = null,
        private readonly bool $allowNativeExtension = true,
        private readonly InternalYamlParser $fallbackParser = new InternalYamlParser(),
    ) {
        $this->extensionParser = $extensionParser === null ? null : Closure::fromCallable($extensionParser);
    }

    public function parse(string $contents, string $source): array
    {
        if ($this->extensionParser !== null || ($this->allowNativeExtension && function_exists('yaml_parse'))) {
            return $this->parseWithExtension($contents, $source);
        }

        return $this->fallbackParser->parse($contents, $source);
    }

    private function parseWithExtension(string $contents, string $source): array
    {
        $parser = $this->extensionParser;

        if ($parser === null) {
            $parser = static fn (string $yaml): mixed => yaml_parse($yaml);
        }

        try {
            set_error_handler(
                static function (int $severity, string $message): never {
                    throw new ErrorException($message, 0, $severity);
                },
            );

            $parsed = $parser($contents);
        } catch (Throwable $throwable) {
            throw InvalidConfigSyntaxException::forSource($source, $throwable->getMessage(), $throwable);
        } finally {
            restore_error_handler();
        }

        if (!is_array($parsed)) {
            throw InvalidConfigSyntaxException::forSource($source, 'Root value must decode to an array.');
        }

        return $parsed;
    }
}
