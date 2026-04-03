# Pipeline

ConfigLoader uses a strict loading pipeline:

```text
resolve -> parse -> merge -> interpolate -> return
```

The order is fixed and documented in both the repository rules and the implementation.

## 1. Resolve

`ConfigRootResolver` validates the configured root directory.

`ConfigFileResolver` then:

- validates the logical config name
- builds the expected file path for the selected format
- resolves layered variants when requested
- rejects mismatched format files

Related:

- [ConfigLoader component](../03_components/config-loader.md)
- [Format selection](../04_usage/format-selection.md)

## 2. Parse

Each resolved file is parsed independently.

- YAML uses `YamlConfigParser`
- JSON uses `JsonConfigParser`

Rules:

- YAML prefers `ext-yaml` and otherwise uses the internal parser
- JSON uses `json_decode()` with strict error handling
- both parsers require the root value to be an array

Related:

- [Parsers](../03_components/parsers.md)

## 3. Merge

Parsed layers are merged in order using `ConfigMerger`.

Rules:

- associative arrays merge recursively
- scalar values are overwritten by later layers
- indexed arrays are replaced entirely

Related:

- [Merge component](../03_components/merge.md)
- [Layered config usage](../04_usage/layered-config.md)

## 4. Interpolate

The merged array is passed to `EnvironmentInterpolator`.

Rules:

- only string values are interpolated
- `${VAR}` and `${VAR:-default}` are supported
- missing variables without defaults throw

Related:

- [Interpolation component](../03_components/interpolation.md)
- [Interpolation usage](../04_usage/interpolation.md)

## 5. Return

The final result is returned as a PHP array.

ConfigLoader does not return objects, execute config, or apply schema validation.

## Related Documents

- [Purpose](../01_overview/purpose.md)
- [Architecture decisions](decisions.md)
