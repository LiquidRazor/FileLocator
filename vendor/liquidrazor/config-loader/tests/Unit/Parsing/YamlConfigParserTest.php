<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Tests\Unit\Parsing;

use LiquidRazor\ConfigLoader\Exception\InvalidConfigSyntaxException;
use LiquidRazor\ConfigLoader\Lib\Parsing\InternalYamlParser;
use LiquidRazor\ConfigLoader\Lib\Parsing\YamlConfigParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class YamlConfigParserTest extends TestCase
{
    public function testUsesExtensionParserWhenProvided(): void
    {
        $parser = new YamlConfigParser(
            static fn (string $yaml): array => ['mode' => 'extension', 'raw' => $yaml],
        );

        $parsed = $parser->parse("app: demo\n", 'config.yaml');

        self::assertSame(
            [
                'mode' => 'extension',
                'raw' => "app: demo\n",
            ],
            $parsed,
        );
    }

    public function testFallsBackToInternalParserWhenNativeExtensionIsDisabled(): void
    {
        $parser = new YamlConfigParser(null, false, new InternalYamlParser());

        $parsed = $parser->parse("app:\n  name: demo\n", 'config.yaml');

        self::assertSame(
            [
                'app' => ['name' => 'demo'],
            ],
            $parsed,
        );
    }

    public function testNormalizesExtensionParserFailures(): void
    {
        $parser = new YamlConfigParser(
            static function (): never {
                throw new RuntimeException('broken yaml');
            },
        );

        $this->expectException(InvalidConfigSyntaxException::class);

        $parser->parse("app: demo\n", 'config.yaml');
    }
}
