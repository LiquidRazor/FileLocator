# Layered Config

Layered loading combines a base config with one or more suffix-based override files.

## Example

```php
use LiquidRazor\ConfigLoader\ConfigLoader;
use LiquidRazor\ConfigLoader\Value\LoaderOptions;

$loader = new ConfigLoader(
    new LoaderOptions(
        configRoot: __DIR__ . '/config',
    ),
);

$config = $loader->loadLayered('services', ['prod', 'local']);
```

For YAML, this resolves in order:

```text
services.yaml
services.prod.yaml
services.local.yaml
```

## Expected Behavior

- the base file is always loaded first
- layers are resolved in the order provided
- each layer is parsed independently
- merge happens before interpolation

## Merge Outcome

- associative arrays merge recursively
- indexed arrays replace earlier indexed arrays
- later scalars override earlier scalars

## Failure Cases

- any missing layer file throws
- invalid syntax in any layer throws
- unresolved environment variables after merge throw

## Related Documents

- [Merge component](../03_components/merge.md)
- [Pipeline](../02_architecture/pipeline.md)
- [Interpolation usage](interpolation.md)
