# ConfigLoader

A lightweight, data-only configuration loading library for PHP 8.3+.

ConfigLoader provides a deterministic and strict pipeline for loading application configuration from a single declarative format, applying layered overrides and environment interpolation, and returning a final normalized array.

No executable configuration. No framework coupling. No hidden magic.

## Documentation

- Overview
  - [Purpose](docs/01_overview/purpose.md)
  - [Concepts](docs/01_overview/concepts.md)
- Architecture
  - [Structure](docs/02_architecture/structure.md)
  - [Pipeline](docs/02_architecture/pipeline.md)
  - [Decisions](docs/02_architecture/decisions.md)
- Components
  - [ConfigLoader](docs/03_components/config-loader.md)
  - [Parsers](docs/03_components/parsers.md)
  - [Interpolation](docs/03_components/interpolation.md)
  - [Merge](docs/03_components/merge.md)
  - [Exceptions](docs/03_components/exceptions.md)
- Usage
  - [Basic Usage](docs/04_usage/basic-usage.md)
  - [Layered Config](docs/04_usage/layered-config.md)
  - [Interpolation](docs/04_usage/interpolation.md)
  - [Format Selection](docs/04_usage/format-selection.md)
- Development
  - [Contributing](docs/05_development/contributing.md)
  - [Testing](docs/05_development/testing.md)
  - [Extension Guidance](docs/05_development/extension.md)

---

## Philosophy

Config is **data**, not code.

ConfigLoader enforces:

- Single format per project (no YAML + JSON chaos)
- Declarative configuration only (no PHP execution)
- Deterministic behavior (no surprises, no implicit merging tricks)
- Strict failure on invalid syntax or unresolved values

This keeps configuration:

- predictable
- portable
- inspectable
- safe to evolve

---

## Features

- YAML support (default)
- JSON support (explicit opt-in)
- Config root resolution
- Layered configuration merging
- Environment variable interpolation
- Strict error handling
- Array output only

---

## Non-Goals

ConfigLoader deliberately does **not** provide:

- Schema validation (handled by a separate future library)
- Executable config (PHP files are not supported)
- Application-specific logic
- Framework integration
- File system discovery beyond config root

---

## Installation

```bash
composer require liquidrazor/config-loader
```

---

## Basic Usage

```php
$loader = new ConfigLoader(
    new LoaderOptions(
        configRoot: __DIR__ . '/config'
    )
);

$config = $loader->load('services');
```

---

## Config Root

By default, configuration is expected in:

```
<project-root>/config
```

You can override this:

```php
new LoaderOptions(
    configRoot: '/custom/path/to/config'
);
```

---

## Supported Formats

### YAML (default)

- Preferred format
- Uses `ext-yaml` if available
- Falls back to internal parser otherwise

Supported extensions:

```
.yaml
.yml
```

---

### JSON (explicit)

Must be explicitly enabled:

```php
new LoaderOptions(
    configRoot: __DIR__ . '/config',
    format: ConfigFormat::JSON
);
```

Supported extension:

```
.json
```

JSON requires the PHP `ext-json` extension. If `ext-json` is unavailable, install it or switch the loader format to YAML.

---

### Important

A loader instance supports **only one format**.

Mixing formats is not allowed.

---

## File Resolution

Config is loaded by logical name:

```php
$loader->load('services');
```

Resolves to:

```
config/services.yaml
```

(or `.json` depending on format)

---

## Layered Configuration

ConfigLoader supports layered overrides.

Example:

```php
$loader->loadLayered('services', ['prod', 'local']);
```

Resolves and merges in order:

```
services.yaml
services.prod.yaml
services.local.yaml
```

---

## Merge Behavior

Default merge rules:

- Associative arrays → recursive merge
- Scalar values → overridden by later layers
- Indexed arrays → fully replaced (not appended)

This ensures predictable behavior and avoids duplication issues.

---

## Environment Interpolation

Environment variables can be interpolated inside config values.

### Syntax

```
${VAR_NAME}
${VAR_NAME:-default}
```

### Example

```yaml
database:
  host: ${DB_HOST:-localhost}
  port: ${DB_PORT}
```

### Behavior

- Interpolation is applied **after merging**
- Missing variables without defaults throw an exception
- Only string values are interpolated
- No expression evaluation or casting is performed

---

## Error Handling

ConfigLoader fails fast and loudly:

- Invalid syntax → exception
- Missing config file → exception
- Unsupported format → exception
- Missing env variable → exception

No silent fallbacks. No guessing.

---

## Internal Pipeline

The configuration loading process follows a strict pipeline:

```
resolve files → parse → merge → interpolate → return array
```

Each stage is isolated and deterministic.

---

## Architecture

Project structure follows:

```
include/   → contracts, enums, value objects, exceptions
lib/       → core logic (loader, parsers, interpolation, merge)
src/       → optional bootstrap/factory (minimal)
```

### Core Components

- ConfigLoader
- YamlParser
- JsonParser
- EnvInterpolator
- LayeredConfigMerger

---

## Design Principles

- No hidden behavior
- No implicit format mixing
- No execution in config
- No framework dependencies
- Minimal, composable components

---

## Future Scope

Planned but intentionally excluded from this library:

- Schema validation (separate library)
- DSN parsing
- Advanced config composition
- Environment profiles abstraction

---

## Summary

ConfigLoader is a strict, minimal, and predictable configuration pipeline.

It does one job:

**Load configuration as data, correctly.**

Nothing more. Nothing less.
