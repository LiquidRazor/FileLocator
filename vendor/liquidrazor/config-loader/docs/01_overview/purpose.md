# Purpose

ConfigLoader is a data-only configuration loading library for PHP 8.3+.

It exists to load configuration from a single declarative format, apply deterministic overrides, interpolate environment variables, and return a final array. The implementation is intentionally small, explicit, and framework-agnostic.

## Why It Exists

- To keep configuration as data instead of executable code
- To enforce one format per loader instance
- To make loading behavior predictable and traceable
- To fail early on invalid input or unresolved values

## Philosophy

- Data-only: no PHP config files and no executable configuration
- Deterministic: the pipeline and merge behavior are fixed
- Strict: invalid syntax, missing files, and unresolved environment variables throw exceptions
- Minimal: no schema validation, framework integration, or application-specific logic

## Related Documents

- [Core concepts](concepts.md)
- [Project structure](../02_architecture/structure.md)
- [Loading pipeline](../02_architecture/pipeline.md)
- [Basic usage](../04_usage/basic-usage.md)
