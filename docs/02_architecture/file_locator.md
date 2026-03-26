# FileLocator API

`FileLocator` is the runtime traversal component.
It consumes a prepared `DiscoveryConfig` and lazily yields matching files.

## Constructor and Method

Public constructor:

```php
<?php

use LiquidRazor\FileLocator\Config\DiscoveryConfig;
use LiquidRazor\FileLocator\FileLocator;
use LiquidRazor\FileLocator\Filesystem\PathFilter;

$locator = new FileLocator(DiscoveryConfig $config, ?PathFilter $pathFilter = null);
```

Public traversal method:

```php
<?php

$files = $locator->locate(); // Generator<int, string>
```

Normal usage is to create the config through `DiscoveryConfigFactory` and pass it directly to `FileLocator`.
The optional `PathFilter` argument exists for advanced or internal composition and is not required for standard use.

## Basic Usage

```php
<?php

use LiquidRazor\FileLocator\Config\DiscoveryConfigFactory;
use LiquidRazor\FileLocator\FileLocator;

$config = (new DiscoveryConfigFactory())->create(__DIR__);
$locator = new FileLocator($config);

foreach ($locator->locate() as $path) {
    echo $path, PHP_EOL;
}
```

Each yielded value is:

- a string
- an absolute path
- normalized to `/`
- produced lazily

## Traversal Behavior

`FileLocator` processes roots in config order.

For each root:

- skip the root if its path does not exist
- skip the root if the root is a symlink and symlink following is disabled
- check readability before building iterators
- traverse with SPL recursive iterators
- yield only file entries

Traversal implementation:

- `RecursiveDirectoryIterator`
- `RecursiveCallbackFilterIterator`
- `RecursiveIteratorIterator`

Iterator mode:

- depth-first traversal
- `RecursiveIteratorIterator::LEAVES_ONLY`

## Recursive and Non-Recursive Roots

Root recursion is controlled per root.

- `recursive: true`
  - descend into subdirectories
- `recursive: false`
  - do not descend below the root directory

The same iterator stack is used in both cases.
Directory descent is controlled by the callback filter.

## Filtering Rules

Filtering is split between directory pruning and file yield checks.

Directory pruning:

- hidden directories are skipped when `include_hidden` is `false`
- globally excluded directories are skipped
- root-specific excluded directories are skipped

File checks:

- hidden files are skipped when `include_hidden` is `false`
- globally excluded files are skipped
- root-specific excluded files are skipped
- files must match one of the root extensions

The current validator restricts extensions to `php`, so runtime discovery only yields PHP files.

## Hidden Path Rules

Hidden-path detection is segment-based.

A path is treated as hidden when any non-empty segment starts with `.`.

Examples treated as hidden:

- `/project/.git`
- `/project/src/.generated/File.php`

## Exclusion Rules

Exclusions are prefix-based after path normalization.

An exclusion matches when:

- the file or directory path is exactly the excluded path
- the file or directory path is a child of the excluded path

Examples:

- excluding `/project/vendor` excludes `/project/vendor`
- excluding `/project/vendor` also excludes `/project/vendor/bin/tool.php`
- excluding `/project/vendor` does not exclude `/project/vendorized`

## Symlink Handling

Default behavior:

- symlinked roots are skipped
- symlinked directories are not descended into
- symlinked files are not yielded

When `follow_symlinks` is enabled:

- symlinked directories may be traversed
- symlinked files may be yielded
- visited directory real paths are tracked to avoid infinite loops

This cycle protection matters when a symlink points back into an already visited directory tree.

## Unreadable Paths

Unreadable-path behavior is controlled by `DiscoveryDefaults->onUnreadable`.

- `skip`
  - unreadable paths are ignored
  - traversal continues
- `fail`
  - traversal throws `PathAccessException`

`PathAccessException` exposes:

- `path()`: the path that failed
- `operation()`: the attempted operation, currently `read`

## Performance Characteristics

`FileLocator` is designed around streaming traversal.

Key decisions:

- no full file list is built
- excluded directories are pruned before descent
- non-matching files are rejected immediately
- filtering is string-based
- no cache is populated during traversal

## What FileLocator Does Not Do

`FileLocator` only discovers files.

It does not:

- load or execute PHP files
- inspect file contents
- parse classes
- resolve services
- merge YAML config
- validate config schema
