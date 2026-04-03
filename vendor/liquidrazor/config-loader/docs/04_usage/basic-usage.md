# Basic Usage

This document shows the minimal setup for loading a config file.

## YAML by Default

```php
use LiquidRazor\ConfigLoader\ConfigLoader;
use LiquidRazor\ConfigLoader\Value\LoaderOptions;

$loader = new ConfigLoader(
    new LoaderOptions(
        configRoot: __DIR__ . '/config',
    ),
);

$config = $loader->load('services');
```

With YAML as the default format, `services` resolves to either:

- `config/services.yaml`
- `config/services.yml`

## Expected Behavior

- the config root is validated first
- the file is resolved for the selected format
- the file is parsed into an array
- the resulting array is interpolated and returned

## Failure Cases

- invalid root path
- missing config file
- invalid YAML syntax
- missing environment variables referenced without defaults

## Related Documents

- [Concepts](../01_overview/concepts.md)
- [Format selection](format-selection.md)
- [Layered config](layered-config.md)
