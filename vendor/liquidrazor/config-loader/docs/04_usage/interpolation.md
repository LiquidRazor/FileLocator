# Interpolation Usage

Interpolation is applied after all selected config files have been parsed and merged.

## Supported Syntax

```text
${VAR}
${VAR:-default}
```

## Example

```yaml
database:
  host: ${DB_HOST:-localhost}
  user: ${DB_USER}
  dsn: mysql:host=${DB_HOST:-localhost};dbname=app
```

## Expected Behavior

- `${DB_HOST:-localhost}` resolves to the environment value when present
- otherwise it resolves to `localhost`
- `${DB_USER}` must exist in the environment
- the final values remain strings

## Failure Cases

- missing variable without default throws `MissingEnvironmentVariableException`
- invalid unresolved placeholder content throws `ConfigException`

## Notes

- interpolation only affects string values
- booleans, numbers, `null`, and arrays are not cast or transformed

## Related Documents

- [Interpolation component](../03_components/interpolation.md)
- [Layered config](layered-config.md)
- [Concepts](../01_overview/concepts.md)
