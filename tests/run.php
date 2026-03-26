<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$testFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile()) {
        continue;
    }

    if (!str_ends_with($fileInfo->getFilename(), 'Test.php')) {
        continue;
    }

    $testFiles[] = $fileInfo->getPathname();
}

usort($testFiles, static function (string $left, string $right): int {
    $leftWeight = testFileSortWeight($left);
    $rightWeight = testFileSortWeight($right);

    if ($leftWeight !== $rightWeight) {
        return $leftWeight <=> $rightWeight;
    }

    return $left <=> $right;
});

$passed = 0;
$failed = 0;

foreach ($testFiles as $testFile) {
    $relativePath = substr($testFile, strlen(__DIR__) + 1);

    try {
        $definitions = require $testFile;

        if ($definitions === 1 || $definitions === null) {
            fwrite(STDOUT, "PASS {$relativePath}\n");
            ++$passed;
            continue;
        }

        if (!is_array($definitions)) {
            throw new RuntimeException(sprintf(
                'Test file "%s" must return an array of test definitions or no value.',
                $relativePath
            ));
        }

        if ($definitions === []) {
            fwrite(STDOUT, "PASS {$relativePath}\n");
            ++$passed;
            continue;
        }

        foreach ($definitions as $index => $definition) {
            if (!is_array($definition) || !isset($definition['name'], $definition['test']) || !$definition['test'] instanceof \Closure) {
                throw new RuntimeException(sprintf(
                    'Invalid test definition at index %d in "%s".',
                    $index,
                    $relativePath
                ));
            }

            $definition['test']();
            fwrite(STDOUT, sprintf("PASS %s :: %s\n", $relativePath, $definition['name']));
            ++$passed;
        }
    } catch (\Throwable $throwable) {
        fwrite(STDERR, sprintf("FAIL %s\n%s\n", $relativePath, formatThrowable($throwable)));
        ++$failed;
    }
}

if ($passed === 0 && $failed === 0) {
    fwrite(STDOUT, "PASS no tests found\n");
    exit(0);
}

fwrite(STDOUT, sprintf("Summary: %d passed, %d failed\n", $passed, $failed));

exit($failed === 0 ? 0 : 1);

function formatThrowable(\Throwable $throwable): string
{
    return sprintf(
        '%s: %s in %s:%d',
        $throwable::class,
        $throwable->getMessage(),
        $throwable->getFile(),
        $throwable->getLine()
    );
}

function testFileSortWeight(string $path): int
{
    $relativePath = substr($path, strlen(__DIR__) + 1);

    return match (true) {
        str_starts_with($relativePath, 'Config/') => 0,
        str_starts_with($relativePath, 'Filesystem/') => 1,
        str_starts_with($relativePath, 'Integration/') => 2,
        default => 3,
    };
}
