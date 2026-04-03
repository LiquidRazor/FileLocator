# Testing

ConfigLoader uses PHPUnit for deterministic unit and integration tests.

## Running Tests

```bash
vendor/bin/phpunit
```

Or through Composer:

```bash
composer test
```

## What Is Tested

The current test suite covers:

- YAML parsing
- JSON parsing
- YAML extension path and internal fallback path
- interpolation behavior
- layered merge behavior
- missing file failures
- invalid syntax failures
- missing environment variable failures
- format enforcement
- JSON `ext-json` availability checks

## Why These Tests Matter

- parsing tests guard the strict format behavior
- merge tests preserve deterministic override rules
- interpolation tests preserve explicit environment handling
- integration tests verify the full `resolve -> parse -> merge -> interpolate -> return` pipeline

## Test Design Constraints

- tests must be deterministic
- no external services
- no framework dependencies
- environment-dependent behavior should be simulated through explicit test doubles when needed

## Related Documents

- [Pipeline](../02_architecture/pipeline.md)
- [Exceptions](../03_components/exceptions.md)
- [Extension guidance](extension.md)
