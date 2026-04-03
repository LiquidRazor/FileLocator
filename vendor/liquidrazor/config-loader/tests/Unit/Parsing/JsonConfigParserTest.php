<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Tests\Unit\Parsing;

use LiquidRazor\ConfigLoader\Exception\InvalidConfigSyntaxException;
use LiquidRazor\ConfigLoader\Exception\MissingJsonExtensionException;
use LiquidRazor\ConfigLoader\Lib\Parsing\JsonConfigParser;
use PHPUnit\Framework\TestCase;

final class JsonConfigParserTest extends TestCase
{
    public function testParsesJsonArrays(): void
    {
        $parser = new JsonConfigParser();

        $parsed = $parser->parse('{"app":{"name":"demo"},"ports":[80,443]}', 'config.json');

        self::assertSame(
            [
                'app' => ['name' => 'demo'],
                'ports' => [80, 443],
            ],
            $parsed,
        );
    }

    public function testThrowsWhenJsonExtensionIsUnavailable(): void
    {
        $this->expectException(MissingJsonExtensionException::class);
        $this->expectExceptionMessage(
            'JSON configuration requires the PHP ext-json extension. Either install ext-json or switch the ConfigLoader format to YAML.',
        );

        new JsonConfigParser(
            static fn (string $extension): bool => $extension !== 'json',
        );
    }

    public function testRejectsInvalidJsonSyntax(): void
    {
        $parser = new JsonConfigParser();

        $this->expectException(InvalidConfigSyntaxException::class);

        $parser->parse('{"app":', 'config.json');
    }

    public function testRejectsNonArrayJsonRoot(): void
    {
        $parser = new JsonConfigParser();

        $this->expectException(InvalidConfigSyntaxException::class);

        $parser->parse('"demo"', 'config.json');
    }
}
