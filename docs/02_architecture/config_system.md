# Configuration System

The configuration system exists to keep discovery behavior explicit, project-local, and deterministic.
It loads one built-in config file, optionally applies one project override, and produces a normalized `DiscoveryConfig`.

## Public API

Use `DiscoveryConfigFactory` to build runtime config:

```php
<?php

use LiquidRazor\FileLocator\Config\DiscoveryConfigFactory;

$factory = new DiscoveryConfigFactory();
$config = $factory->create('/absolute/project/root');
```

`create()` returns a `DiscoveryConfig` containing:

- `projectRoot`: normalized absolute project root
- `exclude`: normalized absolute global exclusions
- `defaults`: `DiscoveryDefaults`
- `roots`: ordered list of enabled `RootConfig` objects

The `projectRoot` argument must be an absolute path.

## Config Sources

The factory loads config in this order:

1. `resources/config/roots.yaml`
2. `config/roots.yaml`
3. `config/roots.yml`

Rules:

- the library config is always loaded first
- the project override is optional
- if both project files exist, `config/roots.yaml` wins
- invalid project config fails immediately

## Canonical Config Shape

The supported YAML structure is:

```yaml
discovery:
  defaults:
    follow_symlinks: false
    include_hidden: false
    on_unreadable: skip
    extensions: [php]

  exclude: []

  roots:
    name:
      enabled: true
      path: string
      recursive: true
      extensions: [php]
      exclude: []
```

The built-in default config shipped by the library is:

```yaml
discovery:
  defaults:
    follow_symlinks: false
    include_hidden: false
    on_unreadable: skip
    extensions: [php]

  exclude:
    - vendor
    - node_modules
    - .git
    - .idea
    - .vscode
    - var/cache

  roots:
    include:
      enabled: true
      path: include
      recursive: true
      extensions: [php]
      exclude: []

    lib:
      enabled: true
      path: lib
      recursive: true
      extensions: [php]
      exclude: []

    src:
      enabled: true
      path: src
      recursive: true
      extensions: [php]
      exclude: []
```

## Merge Behavior

The library merges defaults and project config before validation.

Merge rules are exact:

- scalar values are overridden by the project value
- lists are merged in first-seen order and deduplicated
- roots are merged by name
- existing root fields are overridden per field
- root `extensions` and `exclude` are merged as lists
- new roots are appended after existing roots
- disabled roots remain in raw merged data until validation removes them

Example override:

```yaml
discovery:
  defaults:
    include_hidden: true

  exclude:
    - storage/tmp

  roots:
    lib:
      enabled: false

    src:
      exclude:
        - src/Legacy

    modules:
      path: modules
      recursive: true
```

Result:

- hidden files become visible
- `storage/tmp` is globally excluded
- `lib` is removed from the runtime root list
- `src/Legacy` is excluded only for `src`
- `modules` is added as a new root

## Root Semantics

Each root is keyed by name under `discovery.roots`.

Enabled roots require:

- `path`: non-empty string
- `recursive`: boolean

Optional fields:

- `enabled`: boolean, default `true`
- `extensions`: list of extensions, default from `discovery.defaults.extensions`
- `exclude`: list of path prefixes, default `[]`

Disabled roots:

- may omit `path`
- are validated for any fields they do provide
- are not included in the final `DiscoveryConfig`

## Path Normalization

All configured paths are normalized before runtime objects are built.

Normalization rules:

- relative paths are resolved against the supplied project root
- absolute paths are normalized without touching the filesystem
- `.` segments are removed
- `..` segments are resolved
- path separators are normalized to `/`
- duplicate path list entries are removed after normalization

Rejected path values:

- empty strings
- paths containing null bytes
- relative paths that escape above the project root
- absolute paths that escape above their own root

## Validation Rules

The validator enforces the supported schema and value domain.

It rejects:

- missing required mappings
- unsupported keys at any level
- mappings where lists are required
- lists where mappings are required
- non-boolean flag values
- unsupported `on_unreadable` values
- empty root names
- invalid or unsafe path values
- extensions other than `php`

Extension handling is strict:

- values are canonicalized to lowercase
- a leading `.` is removed
- only `php` is accepted by the current implementation

## Exceptions

Configuration failures use repository-specific exceptions:

- `YamlParseException`
  - YAML file missing
  - YAML parse failure
  - YAML top-level value is not a mapping
- `InvalidDiscoveryConfigException`
  - schema mismatch
  - invalid value types
  - unsupported keys
  - invalid paths
  - unsupported extensions
