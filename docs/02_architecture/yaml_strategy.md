# Config Loading Integration

FileLocator no longer implements YAML parsing internally.

Discovery config is loaded through `liquidrazor/config-loader`, with YAML selected explicitly for every `DiscoveryConfigFactory` instance.

## Loader Usage

`DiscoveryConfigFactory` creates `ConfigLoader` instances with:

- config root `resources/config` for library defaults
- config root `<project-root>/config` for the optional project override
- logical config name `roots`
- format `ConfigFormat::YAML`

## What ConfigLoader Owns

ConfigLoader is responsible for:

- locating `roots.yaml` or `roots.yml` from the logical name `roots`
- reading the file contents
- selecting the YAML parser
- applying environment interpolation
- surfacing syntax and format errors

## What FileLocator Still Owns

After loading, FileLocator still:

- validates the `discovery` schema
- normalizes project-relative paths
- converts the validated array into `DiscoveryConfig`

## Deterministic Format Rules

- YAML is the only format used by FileLocator
- `.yaml` and `.yml` are both accepted
- if both variants exist for the same logical name, loading fails
- if only `roots.json` exists, loading fails instead of silently switching format
