# FileLocator Documentation

FileLocator is a PHP 8.3+ library that loads discovery configuration, validates it, and lazily yields matching PHP files from configured roots.
It is limited to filesystem discovery.
It does not parse PHP, inspect classes, or build a registry.

## Quick Start

```php
<?php

use LiquidRazor\FileLocator\Config\DiscoveryConfigFactory;
use LiquidRazor\FileLocator\FileLocator;

$factory = new DiscoveryConfigFactory();
$config = $factory->create(__DIR__);
$locator = new FileLocator($config);

foreach ($locator->locate() as $path) {
    echo $path, PHP_EOL;
}
```

`DiscoveryConfigFactory::create()` expects an absolute project root path.
`FileLocator::locate()` yields normalized absolute file paths with `/` separators.

## Documentation Map

- [Discovery flow](01_data_flow/discovery_flow.md): How configuration becomes a stream of discovered files.
- [Architecture overview](02_architecture/overview.md): Main entry points, internal layers, boundaries, and pipeline position.
- [Configuration system](02_architecture/config_system.md): Config sources, schema, merge behavior, normalization, and validation.
- [File locator API](02_architecture/file_locator.md): `FileLocator` usage, traversal rules, filtering, and failure modes.
- [YAML loading strategy](02_architecture/yaml_strategy.md): Native `yaml` extension handling and fallback parser limits.
- [Development setup](03_development/setup.md): Runtime requirements and test commands.
- [Implementation guidelines](03_development/implementation_guidelines.md): Repository structure, dependency rules, and performance constraints.
- [Testing strategy](03_development/testing.md): Filesystem test approach, environment-sensitive cases, and expected coverage.
- [Supported extension points](03_development/extension_points.md): Safe ways to extend config without widening the library scope.
