# Contributing

This project is intentionally constrained. Contributions should preserve that shape.

## Working Rules

From `AGENTS.md` and the current implementation:

- keep the library data-only
- keep the library framework-agnostic
- add no runtime dependencies beyond PHP itself
- keep PHPUnit as the only dev dependency
- preserve deterministic behavior and strict failures

## Architectural Rules

- respect the `include/ -> lib/ -> src/` dependency direction
- keep `src/` minimal
- put contracts, enums, value objects, and exceptions in `include/`
- put reusable logic in `lib/`

## Code Rules

- require PHP 8.3+
- use `declare(strict_types=1);`
- prefer small, focused classes
- avoid hidden side effects
- avoid adding behavior not covered by the library responsibilities

## Scope Rules

Do not add:

- schema validation
- PHP config execution
- framework-specific integration
- multi-format loading in one loader instance

## Related Documents

- [Project structure](../02_architecture/structure.md)
- [Architecture decisions](../02_architecture/decisions.md)
- [Testing](testing.md)
