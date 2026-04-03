# Extension Guidance

ConfigLoader is intentionally small, so extension points should be used carefully.

## Safe Extension Areas

### Environment Reader

`ConfigLoader` accepts a custom `EnvironmentReaderInterface` implementation. This is the main supported customization point in the public API.

Use this when:

- tests need deterministic environment values
- a hosting environment exposes variables through a custom source

Related:

- [Interpolation component](../03_components/interpolation.md)

### Internal Core Work

Inside the codebase, parser and merge behavior are separated into focused classes. That makes changes easier to reason about when evolving the library itself.

Examples:

- `JsonConfigParser`
- `YamlConfigParser`
- `ConfigMerger`

This is an implementation detail, not a promise of public plugin APIs.

## What Must Not Be Extended

Do not extend the library by adding:

- executable PHP config support
- schema validation inside the loader
- mixed-format loading in a single loader instance
- implicit interpolation behavior
- silent recovery from missing files or invalid syntax

## Safe Change Direction

If the library evolves, changes should preserve:

- strict pipeline order
- one format per loader instance
- array output only
- explicit exception-driven failures

## Related Documents

- [Contributing](contributing.md)
- [Project structure](../02_architecture/structure.md)
- [ConfigLoader component](../03_components/config-loader.md)
