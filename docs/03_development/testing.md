# Testing Strategy

The test suite verifies the implemented behavior of config loading, path normalization, filtering, and real filesystem traversal.

## Test Runner

Run the suite with:

- `composer test`
- `php tests/run.php`

The repository uses a custom test runner.
It discovers `*Test.php` files under `tests/` and executes them through `tests/bootstrap.php`.

## Test Layout

The suite is organized by behavior:

- `tests/Config/`
  - ConfigLoader-backed factory integration
  - validation
  - value objects
  - exception behavior
- `tests/Filesystem/`
  - path normalization
  - path filtering
- `tests/Integration/`
  - end-to-end config plus traversal
  - recursive and non-recursive roots
  - hidden paths
  - symlinks
  - unreadable paths

## Filesystem Test Method

Filesystem tests use real temporary directories created by `TemporaryFilesystem`.

Typical pattern:

1. create a temporary root
2. write real directories and files
3. configure discovery against that root
4. run `FileLocator`
5. assert on yielded normalized absolute paths
6. clean up the temporary tree

This keeps tests close to the actual runtime behavior of the library.

## Coverage Expectations

Any change affecting discovery behavior should preserve coverage for:

- default config loading
- deterministic project config resolution
- ConfigLoader-backed merge behavior
- schema validation failures
- invalid YAML failures
- unsupported format failures
- path normalization failures
- global exclusions
- root-specific exclusions
- nested directories
- recursive and non-recursive roots
- hidden files and directories
- symlink behavior with follow disabled and enabled
- unreadable paths with `skip` and `fail`

## Environment-Sensitive Cases

Some tests depend on host capabilities:

- symlink tests are skipped on Windows
- unreadable-path tests are skipped on Windows
- unreadable-path tests are skipped when running as root on POSIX systems

When validating permission-sensitive behavior, run the suite in an environment that matches the target deployment model.
