# Format Selection

Each `ConfigLoader` instance is locked to one format.

## YAML

YAML is the default format.

```php
use LiquidRazor\ConfigLoader\ConfigLoader;
use LiquidRazor\ConfigLoader\Value\LoaderOptions;

$loader = new ConfigLoader(
    new LoaderOptions(
        configRoot: __DIR__ . '/config',
    ),
);
```

Accepted extensions:

- `.yaml`
- `.yml`

## JSON

JSON must be selected explicitly.

```php
use LiquidRazor\ConfigLoader\ConfigLoader;
use LiquidRazor\ConfigLoader\Enum\ConfigFormat;
use LiquidRazor\ConfigLoader\Value\LoaderOptions;

$loader = new ConfigLoader(
    new LoaderOptions(
        configRoot: __DIR__ . '/config',
        format: ConfigFormat::JSON,
    ),
);
```

Accepted extension:

- `.json`

JSON also requires PHP `ext-json`. If that extension is unavailable, construction fails with `MissingJsonExtensionException`.

## Failure Cases

- selecting YAML while only `.json` exists throws `UnsupportedFormatException`
- selecting JSON while only `.yaml` or `.yml` exists throws `UnsupportedFormatException`
- selecting JSON without `ext-json` throws `MissingJsonExtensionException`

## Related Documents

- [Parsers](../03_components/parsers.md)
- [Exceptions](../03_components/exceptions.md)
- [Basic usage](basic-usage.md)
