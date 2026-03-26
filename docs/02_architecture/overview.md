# Architecture Overview

This repository implements the filesystem discovery stage for LiquidRazor.
Its job is to turn a validated discovery configuration into a lazy stream of PHP file paths.

## Primary Entry Points

The public runtime entry points are:

- `LiquidRazor\FileLocator\Config\DiscoveryConfigFactory`
- `LiquidRazor\FileLocator\FileLocator`

Typical flow:

```php
<?php

use LiquidRazor\FileLocator\Config\DiscoveryConfigFactory;
use LiquidRazor\FileLocator\FileLocator;

$config = (new DiscoveryConfigFactory())->create('/absolute/project/root');
$locator = new FileLocator($config);
```

`DiscoveryConfigFactory` assembles a `DiscoveryConfig`.
`FileLocator` consumes that config and yields files.

## Runtime Data Model

The runtime config is composed of readonly value objects:

- `DiscoveryConfig`
- `DiscoveryDefaults`
- `RootConfig`

These objects hold normalized values so traversal does not re-interpret YAML or re-normalize config paths.

## Internal Layers

The repository follows a strict dependency direction:

`include <- lib <- src`

Layer responsibilities:

- `include/`
  - config value objects
  - enums
  - exceptions
- `lib/`
  - config loading
  - config merge and validation
  - path normalization
  - path filtering
  - internal YAML fallback parser
- `src/`
  - runtime composition
  - public locator entry point

## Repository Boundaries

Implemented here:

- YAML config loading
- config merge and validation
- path normalization
- directory traversal
- file filtering
- lazy file output

Explicitly not implemented here:

- class discovery
- reflection
- tokenization or AST parsing
- service container logic
- descriptor generation
- registry loading

## Pipeline Position

This library is the first stage in the larger LiquidRazor pipeline:

`FileLocator -> ClassLocator -> RegistryLoader`

Only `FileLocator` is part of this repository.
