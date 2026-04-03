<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Tests\Integration;

use LiquidRazor\ConfigLoader\ConfigLoader;
use LiquidRazor\ConfigLoader\Enum\ConfigFormat;
use LiquidRazor\ConfigLoader\Exception\InvalidConfigSyntaxException;
use LiquidRazor\ConfigLoader\Exception\MissingConfigFileException;
use LiquidRazor\ConfigLoader\Exception\MissingEnvironmentVariableException;
use LiquidRazor\ConfigLoader\Exception\UnsupportedFormatException;
use LiquidRazor\ConfigLoader\Tests\Support\ArrayEnvironmentReader;
use LiquidRazor\ConfigLoader\Value\LoaderOptions;
use PHPUnit\Framework\TestCase;

final class ConfigLoaderTest extends TestCase
{
    private string $configRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configRoot = sys_get_temp_dir() . '/config-loader-tests-' . bin2hex(random_bytes(8));

        mkdir($this->configRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->configRoot);

        parent::tearDown();
    }

    public function testLoadsYamlConfigurationByDefault(): void
    {
        $this->writeConfig('services.yaml', <<<'YAML'
service:
  host: localhost
  ports:
    - 80
    - 443
YAML);

        $loader = new ConfigLoader(new LoaderOptions($this->configRoot));

        self::assertSame(
            [
                'service' => [
                    'host' => 'localhost',
                    'ports' => [80, 443],
                ],
            ],
            $loader->load('services'),
        );
    }

    public function testLoadsJsonConfigurationWhenExplicitlyEnabled(): void
    {
        $this->writeConfig('services.json', '{"service":{"host":"localhost","enabled":true}}');

        $loader = new ConfigLoader(
            new LoaderOptions($this->configRoot, ConfigFormat::JSON),
        );

        self::assertSame(
            [
                'service' => [
                    'host' => 'localhost',
                    'enabled' => true,
                ],
            ],
            $loader->load('services'),
        );
    }

    public function testLayeredLoadMergesBeforeInterpolation(): void
    {
        $this->writeConfig('services.yaml', <<<'YAML'
service:
  host: ${APP_HOST}
  ports:
    - 80
  options:
    retries: 2
    timeout: 10
YAML);

        $this->writeConfig('services.prod.yaml', <<<'YAML'
service:
  host: ${APP_HOST:-prod.example}
  ports:
    - 443
  options:
    timeout: 30
YAML);

        $loader = new ConfigLoader(
            new LoaderOptions($this->configRoot),
            new ArrayEnvironmentReader([]),
        );

        self::assertSame(
            [
                'service' => [
                    'host' => 'prod.example',
                    'ports' => [443],
                    'options' => [
                        'retries' => 2,
                        'timeout' => 30,
                    ],
                ],
            ],
            $loader->loadLayered('services', ['prod']),
        );
    }

    public function testThrowsForMissingConfigFiles(): void
    {
        $loader = new ConfigLoader(new LoaderOptions($this->configRoot));

        $this->expectException(MissingConfigFileException::class);

        $loader->load('missing');
    }

    public function testThrowsForInvalidYamlSyntax(): void
    {
        $this->writeConfig('services.yaml', "service:\n\tbroken: true\n");

        $loader = new ConfigLoader(new LoaderOptions($this->configRoot));

        $this->expectException(InvalidConfigSyntaxException::class);

        $loader->load('services');
    }

    public function testThrowsForMissingEnvironmentVariables(): void
    {
        $this->writeConfig('services.yaml', <<<'YAML'
service:
  host: ${APP_HOST}
YAML);

        $loader = new ConfigLoader(
            new LoaderOptions($this->configRoot),
            new ArrayEnvironmentReader([]),
        );

        $this->expectException(MissingEnvironmentVariableException::class);

        $loader->load('services');
    }

    public function testEnforcesConfiguredFormat(): void
    {
        $this->writeConfig('services.json', '{"service":{"host":"localhost"}}');

        $loader = new ConfigLoader(new LoaderOptions($this->configRoot));

        $this->expectException(UnsupportedFormatException::class);

        $loader->load('services');
    }

    private function writeConfig(string $filename, string $contents): void
    {
        file_put_contents($this->configRoot . '/' . $filename, $contents);
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
