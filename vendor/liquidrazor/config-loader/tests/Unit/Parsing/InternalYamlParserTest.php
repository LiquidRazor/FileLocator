<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Tests\Unit\Parsing;

use LiquidRazor\ConfigLoader\Exception\InvalidConfigSyntaxException;
use LiquidRazor\ConfigLoader\Lib\Parsing\InternalYamlParser;
use PHPUnit\Framework\TestCase;

final class InternalYamlParserTest extends TestCase
{
    public function testParsesNestedYamlStructures(): void
    {
        $parser = new InternalYamlParser();

        $parsed = $parser->parse(<<<'YAML'
app:
  name: "demo"
  debug: true
  ports:
    - 80
    - 443
database:
  host: localhost
  retries: 2
YAML, 'config.yaml');

        self::assertSame(
            [
                'app' => [
                    'name' => 'demo',
                    'debug' => true,
                    'ports' => [80, 443],
                ],
                'database' => [
                    'host' => 'localhost',
                    'retries' => 2,
                ],
            ],
            $parsed,
        );
    }

    public function testRejectsInvalidYamlSyntax(): void
    {
        $parser = new InternalYamlParser();

        $this->expectException(InvalidConfigSyntaxException::class);

        $parser->parse("app:\n\tname: demo\n", 'config.yaml');
    }
}
