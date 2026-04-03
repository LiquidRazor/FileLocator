# ConfigLoader

`ConfigLoader` is the public runtime entry point in `src/`.

## Responsibility

- validate and normalize the config root
- select the parser for the chosen format
- resolve config files by logical name
- run the fixed load pipeline
- return the final merged and interpolated array

## Public API

### `load(string $logicalName): array`

Loads one config file for the selected format.

Example:

```php
$config = $loader->load('services');
```

### `loadLayered(string $logicalName, array $layers): array`

Loads a base config plus one file per layer suffix.

Example:

```php
$config = $loader->loadLayered('services', ['prod', 'local']);
```

## Inputs

- `LoaderOptions`
  - config root
  - selected format
- optional `EnvironmentReaderInterface`

## Output

- final `array<mixed>`

## Behavior

Construction wires:

- `ConfigRootResolver`
- `ConfigFileResolver`
- `JsonConfigParser` or `YamlConfigParser`
- `ConfigMerger`
- `EnvironmentInterpolator`

Load execution performs:

1. resolve file paths
2. read file contents
3. parse each file
4. merge parsed layers
5. interpolate strings
6. return the resulting array

## Constraints

- one format per loader instance
- no framework integration
- no schema validation
- no executable config

## Related Documents

- [Pipeline](../02_architecture/pipeline.md)
- [Parsers](parsers.md)
- [Basic usage](../04_usage/basic-usage.md)
