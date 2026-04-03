<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Lib\Parsing;

use JsonException;
use LiquidRazor\ConfigLoader\Contract\ConfigParserInterface;
use LiquidRazor\ConfigLoader\Exception\InvalidConfigSyntaxException;
use LiquidRazor\ConfigLoader\Exception\MissingJsonExtensionException;

final class JsonConfigParser implements ConfigParserInterface
{
    public function __construct(
        ?callable $extensionLoader = null,
    ) {
        $isLoaded = $extensionLoader === null
            ? extension_loaded('json')
            : (bool) $extensionLoader('json');

        if (!$isLoaded) {
            throw MissingJsonExtensionException::requiredForJsonFormat();
        }
    }

    public function parse(string $contents, string $source): array
    {
        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidConfigSyntaxException::forSource($source, $exception->getMessage(), $exception);
        }

        if (!is_array($decoded)) {
            throw InvalidConfigSyntaxException::forSource($source, 'Root value must decode to an array.');
        }

        return $decoded;
    }
}
