# Structure

ConfigLoader follows the repository structure required by `AGENTS.md`.

## Layers

### `include/`

Contains stable types:

- contracts
- enums
- value objects
- exceptions

This layer does not depend on `lib/` or `src/`.

### `lib/`

Contains reusable core logic:

- root resolution
- file resolution
- parsers
- merge logic
- interpolation
- environment access

This layer may depend on `include/`.

### `src/`

Contains the public runtime entry point:

- `ConfigLoader`

This layer wires the core components and exposes the public loading methods.

### `tests/`

Contains PHPUnit coverage for unit and integration behavior. Production code does not depend on this layer.

## Dependency Direction

```text
include <- lib <- src
```

Rules:

- `include/` is dependency-free
- `lib/` uses `include/`
- `src/` uses `include/` and `lib/`
- production code never depends on `tests/`

## Related Documents

- [Pipeline](pipeline.md)
- [Architecture decisions](decisions.md)
- [Components](../03_components/config-loader.md)
- [Development rules](../05_development/contributing.md)
