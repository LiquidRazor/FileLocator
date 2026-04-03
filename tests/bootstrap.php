<?php

declare(strict_types=1);

define('LIQUIDRAZOR_FILELOCATOR_ROOT', dirname(__DIR__));

if (is_file(LIQUIDRAZOR_FILELOCATOR_ROOT . '/vendor/autoload.php')) {
    require LIQUIDRAZOR_FILELOCATOR_ROOT . '/vendor/autoload.php';
}

spl_autoload_register(
    static function (string $class): void {
        $prefixes = [
            'LiquidRazor\\FileLocator\\Tests\\' => [
                LIQUIDRAZOR_FILELOCATOR_ROOT . '/tests/',
            ],
            'LiquidRazor\\FileLocator\\' => [
                LIQUIDRAZOR_FILELOCATOR_ROOT . '/include/',
                LIQUIDRAZOR_FILELOCATOR_ROOT . '/lib/',
                LIQUIDRAZOR_FILELOCATOR_ROOT . '/src/',
            ],
        ];

        foreach ($prefixes as $prefix => $directories) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

            foreach ($directories as $directory) {
                $path = $directory . $relativePath;

                if (is_file($path)) {
                    require $path;
                    return;
                }
            }
        }
    }
);

set_error_handler(
    static function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }
);

function test(string $name, \Closure $test): array
{
    return [
        'name' => $name,
        'test' => $test,
    ];
}
