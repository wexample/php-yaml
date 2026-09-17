# php_yaml

Version: 1.0.72

`wexample/php-yaml` is a PHP 8.1+ library that resolves cross-file references and inheritance between YAML files. It registers each YAML file under a named domain, then resolves any value written as `@domain.name::key.path` — including the `%` wildcard for same-key references and the `~extends` directive for full file inheritance — into its concrete counterpart. It is aimed at PHP projects that spread structured configuration or data across multiple YAML files and need a single resolver to flatten those references at runtime.

## Table of Contents

- [Architecture](#architecture)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Architecture

The library is a single class, src/YamlIncludeResolver.php, with no sub-packages. It keeps two pieces of private state: `$domains`, which maps dot-notation names to their parsed YAML arrays, and `$valueCache`, a flat lookup keyed by `"domain|key"` that is wiped whenever a new file enters `$domains`.

### Source layout

```
src/
  YamlIncludeResolver.php          # the whole library
  Test/
    AbstractYamlIncludeResolverTest.php   # shared PHPUnit base class
tests/
  Unit/                            # five test suites
  Resources/yaml/domain/           # three fixture files
```

The `Wexample\PhpYaml\` namespace maps to `src/`; `Wexample\PhpYaml\Tests\` maps to `tests/`.

### Loading phase

Files enter the resolver one of two ways.

**`registerFile(string $domain, string $filePath)`** parses a single file with `Symfony\Component\Yaml\Yaml::parseFile()` and stores the result under `$this->domains[$domain]`. It clears `$valueCache` on every call, because a newly registered domain may satisfy a reference that previously fell back to its default.

**`scanDirectory(string $relativeBasePath)`** delegates to `FileHelper::scanDirectoryForFiles()` (from `wexample/php-helpers`), which walks the directory for `.yml` files and calls `registerFile()` for each one. The domain name is built by `buildDomainFromFile()`: the file's path relative to the parent of `$relativeBasePath` is turned into a dot-separated prefix, and the filename stem is appended. A file at `domain/one.yml` scanned from `…/yaml/domain` therefore registers under `domain.one`.

### Reference syntax

References inside YAML values drive the entire resolution mechanism. Three forms exist:

| Syntax | Meaning |
|---|---|
| `@domain.name::key.path` | value of `key.path` in domain `domain.name` |
| `%` | same key as the one being resolved, in the domain passed to `resolveValues()` |
| `~extends: '@domain.name'` | top-level key: inherit all keys from another domain |

`isIncludeReference()` identifies cross-domain references by checking for a leading `@` and the presence of `::`. The static helpers `splitDomain()`, `splitKey()`, and `trimDomainPrefix()` then break a reference into its parts before any lookup takes place.

### Resolution call path

**Single value — `getValueResolved(string $ref)`**

1. `splitDomainAndTrimPrefix($ref)` strips `@` and isolates the domain name.
2. `splitKey($ref)` extracts the key after `::`.
3. `getValue(key, domain)` is called.

**`getValue(string $key, string $domain)`** is the core. It looks the pair up in `$valueCache` and returns immediately on a hit. On a miss it walks `$this->domains[$domain]`, following each dot-separated segment of `$key` into the nested array. Four outcomes are possible:

- The found value is itself a reference → `getValue()` is called recursively on that target, forwarding any remaining path segments that could not be traversed (because the intermediate node was already a reference rather than a nested array).
- The key is not found and the domain carries `~extends` → `getValue()` is called recursively on the parent domain with the same key.
- The key is not found and there is no `~extends` → the original reference string is returned as a fallback (`domain::key`).
- The key is found and the value is a plain scalar or array → returned directly.

Every path through `getValue()` ends by writing to `$valueCache[$domain.'|'.$key]` before returning.

**Batch resolution — `resolveValues(array $values, ?string $domain)`**

Iterates the supplied array. For each entry:

- key is `~extends` → resolves the parent domain's values first (so inherited keys appear in `$resolved` before the current domain's own keys can override them).
- value is a string → `resolveValue()` dispatches: `@…::…` goes to `getValueResolved()`; `%` calls `getValue()` with the current key in `$domain`; anything else passes through unchanged.
- value is not a string → copied as-is.

The accumulator `$resolved` is passed by reference so inherited and own keys share the same output array.

### Inheritance path

tests/Resources/yaml/domain/three.yml illustrates the `~extends` mechanism:

```yaml
~extends: '@domain.one'

three_specific_key: Value specific to three
```

When `getValueResolved('@domain.three::simple_key')` is called, `getValue()` fails to find `simple_key` in `domain.three`, detects `~extends: '@domain.one'`, and recurses into `getValue('simple_key', 'domain.one')`, returning `Simple value`. The child domain therefore inherits every key of its parent without copying any data.

### Test support

src/Test/AbstractYamlIncludeResolverTest.php is a PHPUnit base class that constructs a fresh `YamlIncludeResolver` in `setUp()` and sets `$resourcesPath` to `tests/Resources/yaml`. Each concrete test suite extends it, registers the fixture files it needs, and exercises a specific part of the API (includes, extends, `resolveValues`, directory scanning, `getAllDomainsContent`).

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

Visit the [Wexample Suite documentation](https://docs.wexample.com) for the complete package ecosystem.

## Dependencies

- php: >=8.5
- symfony/yaml: ^6.0|^7.0
- wexample/php-helpers: >=4.0.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
