# YAML Loading Strategy

The library reads discovery config from YAML and supports two parsing paths:

- native parsing through the PHP `yaml` extension
- a strict internal fallback parser

The fallback exists so the library can run without external packages or mandatory extensions.

## Native Extension Path

`YamlDiscoveryConfigLoader` checks `extension_loaded('yaml')` first.

When the extension is available:

- the loader calls `yaml_parse_file($path)`
- a `false` result is treated as a parse failure
- the parsed top-level value must be a mapping

If parsing fails, the loader throws `YamlParseException`.

## Fallback Parser Path

When the `yaml` extension is not available, the loader uses `MinimalYamlParser`.

The fallback parser is:

- internal to this repository
- intentionally small
- limited to the discovery config format used by this library

It is not part of the public API.

## Supported YAML Subset

The fallback parser supports the subset required by `resources/config/roots.yaml` and project overrides.

Supported constructs:

- top-level mapping
- nested mappings
- two-space indentation
- plain scalar keys using letters, digits, `_`, `.`, and `-`
- booleans: `true`, `false`
- strings:
  - plain strings
  - single-quoted strings
  - double-quoted strings
- scalar lists:
  - block form with `- item`
  - inline form such as `[php]`
- empty inline list: `[]`

This is sufficient for the supported discovery schema.

## Unsupported YAML Features

The fallback parser rejects features that are outside the library config format:

- tabs for indentation
- indentation levels not divisible by two
- inline mappings
- nested lists
- list items containing mappings
- anchors
- aliases
- YAML merge keys
- multiline scalars

It also rejects trailing or malformed content that does not fit the supported mapping and list grammar.

## Error Behavior

`YamlParseException` is thrown when:

- the YAML file does not exist
- the native extension cannot parse the file
- the fallback parser encounters unsupported or malformed YAML
- the parsed top-level value is not a mapping

The exception message includes the YAML file path.
Fallback parser errors also include the source line number.

## Scope Limit

The fallback parser is not a general-purpose YAML parser.
It exists only to support this repository's own discovery configuration format.
New features should not expand it into a broad YAML implementation.
