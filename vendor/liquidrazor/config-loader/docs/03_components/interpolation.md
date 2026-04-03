# Interpolation

`EnvironmentInterpolator` applies environment variable replacement to the merged config array.

## Responsibility

- traverse the merged array recursively
- interpolate string values
- leave non-string values unchanged
- throw when required environment variables are missing

## Inputs

- merged `array<mixed>`
- an `EnvironmentReaderInterface` implementation

## Output

- interpolated `array<mixed>`

## Supported Syntax

```text
${VAR}
${VAR:-default}
```

## Behavior

- strings may contain placeholders as the whole value or inline inside larger strings
- defaults are used only when the environment variable is absent
- values remain strings after interpolation
- arrays are traversed recursively

## Environment Source

The default reader is `PhpEnvironmentReader`, which checks:

1. `getenv()`
2. `$_ENV`
3. `$_SERVER`

## Constraints

- no expression evaluation
- no implicit type casting
- invalid unresolved placeholder syntax is rejected

## Related Documents

- [Concepts](../01_overview/concepts.md)
- [Interpolation usage](../04_usage/interpolation.md)
- [Exceptions](exceptions.md)
