# Exceptions

ConfigLoader uses dedicated exceptions to keep failures explicit.

## Responsibility

- represent distinct failure modes
- provide actionable error messages
- avoid generic runtime failures leaking into public behavior

## Base Exception

- `ConfigException`

All library-specific exceptions extend this type.

## Exception Types

- `InvalidConfigRootException`
  - config root does not exist
  - config root is not a directory
- `InvalidConfigNameException`
  - logical config name is empty
  - logical name or layer contains path traversal
- `MissingConfigFileException`
  - expected file for the selected format is missing
- `UnsupportedFormatException`
  - a file exists, but only in a different format than the selected loader format
- `InvalidConfigSyntaxException`
  - YAML or JSON syntax is invalid
  - parsed root is not an array
- `MissingEnvironmentVariableException`
  - `${VAR}` cannot be resolved and has no default
- `MissingJsonExtensionException`
  - JSON format was selected without `ext-json`

## Behavior

- resolution failures happen before parsing
- parsing failures happen before merge
- interpolation failures happen after merge

## Constraints

- failures are not ignored
- the library does not provide recovery or fallback behavior for hard errors

## Related Documents

- [Pipeline](../02_architecture/pipeline.md)
- [Parsers](parsers.md)
- [Interpolation](interpolation.md)
