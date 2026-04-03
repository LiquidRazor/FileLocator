# Decisions

This document records the design constraints visible in the codebase and repository rules.

## No PHP Config

ConfigLoader is intentionally data-only.

Why:

- PHP config files are executable
- executable config introduces hidden behavior
- the project explicitly forbids executable configuration

Result:

- only YAML and JSON are supported
- file resolution only accepts `.yaml`, `.yml`, or `.json`

## No Schema Validation

Schema validation is not part of this library.

Why:

- the current responsibility is loading and normalization
- schema validation is listed as a separate future concern

Result:

- parsers only validate syntax and array shape
- no semantic validation rules are applied to config values

## No Multi-Format Mixing

A single loader instance is locked to one format.

Why:

- mixed formats make resolution ambiguous
- explicit format choice keeps the pipeline predictable

Result:

- YAML is the default
- JSON must be explicitly selected
- mismatched files for the selected loader format throw

## Strict Failure

ConfigLoader fails fast on invalid input.

Why:

- silent fallbacks hide configuration problems
- deterministic behavior requires explicit failure modes

Result:

- invalid syntax throws
- missing files throw
- missing environment variables throw
- missing `ext-json` for JSON throws

## Related Documents

- [Purpose](../01_overview/purpose.md)
- [Pipeline](pipeline.md)
- [Exceptions](../03_components/exceptions.md)
