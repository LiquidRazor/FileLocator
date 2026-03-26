# Development Setup

This repository is a standalone PHP 8.3+ library with no runtime package dependencies.

## Requirements

- PHP 8.3 or newer
- optional `yaml` extension for native YAML parsing
- Composer only if you want to use the package script in `composer.json`

## Install and Autoload

The package namespace is `LiquidRazor\FileLocator\`.

Composer autoload maps that namespace across:

- `include/`
- `lib/`
- `src/`

The test suite does not require Composer autoloading.
It uses the local bootstrap file in `tests/bootstrap.php`.

## Run the Test Suite

Use either command:

- `composer test`
- `php tests/run.php`

`composer test` runs the same custom test runner defined in `composer.json`.

## Environment Notes

This repository does not define a Docker-specific workflow.

If it is developed inside a parent project that requires Docker for tests, run this library's tests in that same container environment so filesystem permissions and symlink behavior match the target runtime.

Symlink and unreadable-path tests are environment-sensitive:

- symlink tests are skipped on Windows
- unreadable-path tests are skipped on Windows
- unreadable-path tests are also skipped when running as root on POSIX systems
