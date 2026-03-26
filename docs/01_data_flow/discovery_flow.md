# Discovery Flow

This document describes the runtime flow from project root input to yielded file paths.

## Entry Point

Typical usage starts with `DiscoveryConfigFactory` and `FileLocator`:

```php
<?php

use LiquidRazor\FileLocator\Config\DiscoveryConfigFactory;
use LiquidRazor\FileLocator\FileLocator;

$config = (new DiscoveryConfigFactory())->create('/absolute/project/root');

foreach ((new FileLocator($config))->locate() as $path) {
    // $path is an absolute normalized file path
}
```

The flow has two phases:

- configuration assembly
- filesystem traversal

## Configuration Assembly

`DiscoveryConfigFactory::create(string $projectRoot)` performs the full config build.

It does the following in order:

1. Normalizes the supplied project root.
2. Loads the library default config from `resources/config/roots.yaml`.
3. Looks for a project override:
   - `config/roots.yaml`
   - `config/roots.yml`
4. Merges defaults and override.
5. Validates and normalizes the merged config.
6. Builds immutable runtime value objects.

If both override files exist, `config/roots.yaml` is used and `config/roots.yml` is ignored.

## Config Merge

Merge behavior is deterministic and field-specific:

- scalar values are overridden by the project config
- list values are merged in declaration order and deduplicated
- roots are merged by root name
- root `exclude` and `extensions` are merged as lists
- root scalar fields such as `path`, `recursive`, and `enabled` are overridden

`enabled: false` is preserved during merge, then removed when the runtime config is built.

## Root Resolution

After validation, the runtime config contains:

- `projectRoot`: normalized absolute project path
- `exclude`: normalized absolute global exclusions
- `defaults`: normalized discovery defaults
- `roots`: enabled roots only, in merged order

Each root contains:

- `name`
- `path`
- `recursive`
- `extensions`
- `exclude`

All root and exclusion paths are normalized to absolute paths with `/` separators.

## Filesystem Traversal

`FileLocator::locate()` processes roots in config order.

For each root:

1. Skip the root if the directory does not exist.
2. Skip the root if the root path is a symlink and symlink following is disabled.
3. Check root readability.
4. Build a recursive SPL iterator stack.
5. Prune directories before descending into them.
6. Yield matching files lazily.

Traversal uses:

- `RecursiveDirectoryIterator`
- `RecursiveCallbackFilterIterator`
- `RecursiveIteratorIterator`

Traversal is depth-first.

## Filtering

Filtering happens as early as possible.

Directory pruning rules:

- do not descend into hidden directories when `include_hidden` is `false`
- do not descend into globally excluded paths
- do not descend into root-specific excluded paths
- do not descend below the root when `recursive` is `false`

File yield rules:

- skip hidden files when `include_hidden` is `false`
- skip globally excluded files
- skip root-specific excluded files
- skip files without a matching extension

In the current implementation, the only supported extension is `php`.

## Output

`locate()` returns a `Generator<int, string>`.

Each yielded value is:

- a file path string
- absolute
- normalized to `/`
- produced lazily

The locator does not accumulate a complete file list in memory.

## Failure Modes

Configuration phase failures:

- `YamlParseException` when a YAML file cannot be parsed
- `InvalidDiscoveryConfigException` when merged config fails schema or path validation

Traversal phase failures:

- unreadable paths are skipped when `on_unreadable: skip`
- unreadable paths throw `PathAccessException` when `on_unreadable: fail`
