# Merge

`ConfigMerger` combines parsed configuration layers into one array.

## Responsibility

- merge parsed layers in deterministic order
- preserve recursive behavior for associative arrays
- replace indexed arrays entirely
- allow later values to override earlier ones

## Inputs

- one or more parsed arrays

## Output

- one merged array

## Behavior

### Associative Arrays

Associative arrays merge recursively.

Example:

```php
[
    'service' => ['timeout' => 10, 'options' => ['cache' => true]],
]
```

merged with:

```php
[
    'service' => ['options' => ['cache' => false]],
]
```

results in:

```php
[
    'service' => ['timeout' => 10, 'options' => ['cache' => false]],
]
```

### Scalars

Later scalar values override earlier ones.

### Indexed Arrays

Indexed arrays are replaced, not appended.

## Constraints

- merge order matters
- interpolation happens after merge, not during merge

## Related Documents

- [Pipeline](../02_architecture/pipeline.md)
- [Layered config usage](../04_usage/layered-config.md)
