# Implementation Guidelines

These guidelines summarize the repository rules that shape the current implementation.

## Repository Structure

Code is split into three mandatory directories:

- `include/`
  - config value objects
  - enums
  - exceptions
- `lib/`
  - reusable internal logic
- `src/`
  - runtime wiring and public entry points

This structure is part of the design, not an organizational preference.

## Dependency Direction

Dependencies must always point in one direction:

`include <- lib <- src`

Rules:

- `include/` must not depend on `lib/` or `src/`
- `lib/` must not depend on `src/`
- `src/` may compose `include/` and `lib/`

## Scope Rules

This repository implements filesystem discovery only.

Allowed responsibilities:

- config loading
- config merge
- config validation
- path normalization
- directory traversal
- file filtering
- yielding discovered files

Out of scope:

- class discovery
- reflection
- PHP parsing
- container logic
- framework-specific integration

## Native PHP Requirement

The implementation stays on bare PHP 8.3+.

Use native features:

- SPL iterators
- generators
- native filesystem functions
- readonly value objects
- native exceptions

Do not add:

- third-party libraries
- helper packages for YAML, path handling, or traversal
- framework components

The only YAML fallback allowed is the existing internal minimal parser, kept as a private implementation detail.

## Performance Rules

The code should preserve the current traversal model:

- lazy output through `Generator`
- depth-first traversal
- early directory pruning
- immediate file rejection for non-matches
- no full-array accumulation of discovered files
- no unnecessary re-normalization of config paths

Prefer simple string-based path checks when they remain clear and correct.

## Design Rules

Keep implementation choices explicit.

Expected style:

- small focused classes
- immutable runtime config objects
- no hidden side effects
- no mutable global caches
- no abstraction layers without a concrete need

Changes that widen scope or add indirection without solving a real repository problem should be rejected.
