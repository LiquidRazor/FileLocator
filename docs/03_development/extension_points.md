# Supported Extension Points

This library is intentionally narrow.
Safe extension happens mainly through configuration, not by widening traversal responsibilities.

## Configuration-Level Extension

The supported way to change discovery behavior is to adjust YAML config.

Safe extensions include:

- adding new roots
- disabling built-in roots
- changing `include_hidden`
- changing `follow_symlinks`
- changing `on_unreadable`
- adding global exclusions
- adding root-specific exclusions

Example:

```yaml
discovery:
  defaults:
    include_hidden: true

  exclude:
    - storage/tmp

  roots:
    lib:
      enabled: false

    modules:
      path: modules
      recursive: true

    src:
      exclude:
        - src/Legacy
```

## Runtime Extension Boundary

The traversal core should remain stable.

Do not extend this repository by adding:

- class discovery
- PHP parsing
- reflection
- service or container logic
- framework adapters
- alternate traversal frameworks or helper packages

`FileLocator` should remain responsible only for walking configured roots and yielding file paths.

## Config Schema Changes

If the config schema must change, update the full config pipeline together:

- default resource in `resources/config/roots.yaml`
- YAML loader expectations
- merge rules
- validation rules
- readonly value objects when needed
- tests for the new schema behavior

Schema changes must preserve:

- deterministic merge behavior
- absolute normalized paths
- lazy traversal
- early filtering
- repository scope limited to filesystem discovery

## YAML Fallback Boundary

The internal fallback parser may only support the YAML subset needed by the repository config format.
Do not turn it into a general-purpose YAML parser to accommodate unrelated config features.
