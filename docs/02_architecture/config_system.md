# Configuration System

The configuration system exists to keep discovery behavior explicit, project-local, and deterministic.
It delegates raw config loading to `liquidrazor/config-loader`, then validates the discovery schema and produces a normalized `DiscoveryConfig`.

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

`DiscoveryConfigFactory` uses `ConfigLoader` with:

- config root: `resources/config`
- logical name: `roots`
- format: YAML

It then attempts an optional project load with:

- config root: `<project-root>/config`
- logical name: `roots`
- format: YAML

The effective sources are:

1. `resources/config/roots.yaml`
2. `config/roots.yaml`
3. `config/roots.yml`

Rules:

- the library config is always loaded first
- the project override is optional
- if both project files exist, ConfigLoader fails because both match the same logical config name
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

Defaults and project config are merged before validation using ConfigLoader's merge rules.

Merge rules are exact:

- scalar values are overridden by the project value
- indexed arrays are fully replaced by the project value
- roots are merged by name
- existing root fields are overridden per field
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
The default global exclude list is replaced when the project config provides its own `discovery.exclude` list.

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

Config loading failures are surfaced from `liquidrazor/config-loader`, including:

- `MissingConfigFileException`
- `InvalidConfigSyntaxException`
- `UnsupportedFormatException`
- `ConfigException`

Discovery schema and path validation failures still use:

- `InvalidDiscoveryConfigException`
  - YAML parse failure
  - YAML top-level value is not a mapping
- `InvalidDiscoveryConfigException`
  - schema mismatch
  - invalid value types
  - unsupported keys
  - invalid paths
  - unsupported extensions
