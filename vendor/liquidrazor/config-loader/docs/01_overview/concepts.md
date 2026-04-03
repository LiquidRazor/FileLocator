# Concepts

This document defines the main terms used throughout ConfigLoader.

## Config Root

The config root is the base directory used for all file resolution. It is validated and normalized before any loading begins.

Example:

```php
new LoaderOptions(
    configRoot: __DIR__ . '/config'
);
```

Related:

- [Basic usage](../04_usage/basic-usage.md)
- [Project structure](../02_architecture/structure.md)

## Format Locking

A `ConfigLoader` instance works with exactly one format:

- YAML by default
- JSON only when explicitly selected

The format determines:

- which parser is created
- which file extensions are accepted
- which files are considered invalid for that loader instance

Related:

- [Format selection](../04_usage/format-selection.md)
- [Parsers](../03_components/parsers.md)

## Layered Config

Layered loading resolves a base file and then one file per layer suffix.

Example logical name and layers:

```php
$loader->loadLayered('services', ['prod', 'local']);
```

Resolution order:

```text
services.yaml
services.prod.yaml
services.local.yaml
```

Each parsed layer is merged in order before interpolation.

Related:

- [Layered config usage](../04_usage/layered-config.md)
- [Merge behavior](../03_components/merge.md)

## Interpolation

Interpolation applies only to string values after parsing and merging.

Supported forms:

```text
${VAR}
${VAR:-default}
```

Missing variables without defaults throw an exception.

Related:

- [Interpolation component](../03_components/interpolation.md)
- [Interpolation usage](../04_usage/interpolation.md)

## Pipeline

The loading pipeline is fixed:

```text
resolve -> parse -> merge -> interpolate -> return
```

There are no alternative execution paths for successful loads.

Related:

- [Pipeline details](../02_architecture/pipeline.md)
- [ConfigLoader component](../03_components/config-loader.md)
