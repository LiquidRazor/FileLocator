# Parsers

ConfigLoader contains two format parsers and one YAML fallback parser.

## Responsibility

- convert file contents into arrays
- reject invalid syntax
- reject non-array roots
- keep format handling explicit

## JSON Parser

`JsonConfigParser` handles explicit JSON mode.

### Inputs

- raw JSON string
- source path for error messages

### Output

- `array<mixed>`

### Behavior

- validates that `ext-json` is available during construction
- uses `json_decode(..., JSON_THROW_ON_ERROR)`
- throws on invalid syntax
- throws when the root value is not an array

### Constraints

- JSON is never used as a fallback
- missing `ext-json` is a hard failure

## YAML Parser

`YamlConfigParser` handles YAML mode.

### Behavior

- uses `ext-yaml` when available
- otherwise delegates to `InternalYamlParser`
- normalizes parser errors into `InvalidConfigSyntaxException`
- rejects non-array roots

### Constraints

- no external YAML library is used
- only one parser path is active per parse call

## Internal YAML Parser

`InternalYamlParser` is the dependency-free fallback.

### Supported Behavior

- indentation-based mappings and sequences
- quoted and unquoted scalar values
- booleans, `null`, integers, and floats
- line comments outside quoted strings

### Failure Conditions

- tabs used for indentation
- invalid mapping syntax
- mixed sequence and mapping entries at the same indentation level
- unexpected nested blocks

### Constraints

- it is a strict fallback for declarative config structures in this library
- it is not documented or implemented as a full YAML specification parser

## Related Documents

- [Pipeline](../02_architecture/pipeline.md)
- [Format selection](../04_usage/format-selection.md)
- [Exceptions](exceptions.md)
