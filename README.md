# LiquidRazor File Discovery

A lightweight, high-performance filesystem discovery component for the LiquidRazor ecosystem.

This library provides a **deterministic, memory-efficient, and extensible way** to locate PHP files across a project using convention-first defaults with optional project-level overrides.

It is the first step in the LiquidRazor pipeline:

```
filesystem → files → classes → descriptors → DI registry
```

---

## Features: Core Capabilities

* Fast, iterator-based filesystem traversal with low memory use
* Convention-first defaults for `include/`, `lib/`, and `src/`
* YAML-based configuration loaded through `liquidrazor/config-loader`
* Clean separation of concerns with no DI, reflection, or PHP parsing
* Safe traversal with configurable symlink, hidden file, and unreadable path handling
* Config loading delegated to a dedicated LiquidRazor library

---

## Default Behavior: Built-In Roots And Exclusions

Out of the box, the discovery system scans:

* `include/`
* `lib/`
* `src/`

With automatic exclusions:

* `vendor/`
* `node_modules/`
* `.git/`
* `.idea/`
* `.vscode/`
* `var/cache/`

Only `.php` files are considered.

---

## Configuration: Config Sources

### 1. Default Configuration (Library)

The library ships with an internal configuration:

```
resources/config/roots.yaml
```

This defines the baseline discovery rules.

---

### 2. Project Override

You can override or extend discovery behavior by adding:

```
config/roots.yaml
```

or

```
config/roots.yml
```

Config loading is delegated to `liquidrazor/config-loader` with YAML selected explicitly.
If both `config/roots.yaml` and `config/roots.yml` exist, loading fails instead of silently picking one.

---

## Configuration Structure: Supported YAML Shape

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

---

## Adding Custom Roots: Extending Discovery

```yaml
discovery:
  roots:
    modules:
      path: modules
      recursive: true
```

---

## Excluding Paths: Global And Root-Specific Rules

Global exclusions:

```yaml
discovery:
  exclude:
    - storage/tmp
```

Root-specific exclusions:

```yaml
discovery:
  roots:
    src:
      exclude:
        - src/Legacy
        - src/Experimental
```

---

## Disabling Default Roots: Removing Built-In Paths

```yaml
discovery:
  roots:
    lib:
      enabled: false
```

---

## Config Loading Boundary

`FileLocator` no longer locates config files by full path, reads raw config contents, parses YAML, merges raw config layers, or interpolates environment variables itself.

Those responsibilities now belong to `liquidrazor/config-loader`.

`DiscoveryConfigFactory` only:

* asks ConfigLoader for normalized config arrays using the logical name `roots`
* validates the discovery schema
* normalizes project-relative paths
* builds `DiscoveryConfig`, `DiscoveryDefaults`, and `RootConfig`

---

## Merge Strategy: How Config Is Built

Configuration is built as:

```
library defaults + project overrides → final discovery config
```

The default resource is loaded from `resources/config/roots.yaml`.
The optional project override is loaded from the project `config` root using the logical name `roots`.

Raw config merge behavior now follows `liquidrazor/config-loader`:

* Associative arrays → merged recursively
* Scalars → overridden by later config
* Indexed arrays → replaced by later config

After loading, FileLocator still validates the discovery schema and removes disabled roots from the runtime config.

---

## Architecture: Main Layers

The system is intentionally split into layers:

### 1. Config Layer

* `DiscoveryConfigFactory`
* `DiscoveryConfigValidator`
* `DiscoveryConfig`

### 2. Discovery Layer

* `FileLocator`
* streaming traversal using generators
* zero accumulation by default

### 3. Future Layers

* Class discovery (static parsing)
* DI registry loading

---

## Usage: Basic Flow

```php
use LiquidRazor\FileLocator\Config\DiscoveryConfigFactory;
use LiquidRazor\FileLocator\FileLocator;

$factory = new DiscoveryConfigFactory();
$config = $factory->create(__DIR__);
$locator = new FileLocator($config);

foreach ($locator->locate() as $file) {
    // process file
}
```

The locator yields files **lazily**, ensuring minimal memory usage.

---

## Performance Principles: Traversal Behavior

* Depth-first traversal
* Early directory pruning
* Generator-based streaming
* No reflection or file inclusion
* No unnecessary allocations

---

## Safety: Failure And Traversal Controls

* Unreadable paths: configurable (`skip` or `fail`)
* Symlinks: disabled by default
* Hidden files: excluded by default
* Deterministic behavior (no implicit magic)

---

## Design Philosophy: Implementation Priorities

* Convention over configuration (but never forced)
* Explicit over implicit
* Small, composable components
* No framework lock-in
* Predictable behavior under load

---

## Role in LiquidRazor: Pipeline Position

This component is the **foundation of the DI pipeline**:

```
FileLocator
   ↓
ClassLocator (future)
   ↓
RegistryLoader (future)
   ↓
DIRegistry
```

---

## Notes: Additional Documentation

- [Discovery flow documentation](docs/01_data_flow/discovery_flow.md)
- [Architecture overview documentation](docs/02_architecture/overview.md)
- [Configuration system documentation](docs/02_architecture/config_system.md)
- [File locator API documentation](docs/02_architecture/file_locator.md)
- [Config loading integration documentation](docs/02_architecture/yaml_strategy.md)
- [Development setup documentation](docs/03_development/setup.md)
- [Implementation guidelines documentation](docs/03_development/implementation_guidelines.md)
- [Testing strategy documentation](docs/03_development/testing.md)
- [Supported extension points documentation](docs/03_development/extension_points.md)

---

## License: Package Terms

MIT
