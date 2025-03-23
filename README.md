# PHP YAML Includes

A lightweight PHP library for resolving includes between YAML files with support for inheritance and references.

## Installation

```bash
composer require wexample/php-yaml
```

## Features

- Resolve includes between YAML files
- Support for references with `@domain::key` syntax
- Support for same-key references via `@domain::%`
- Complete YAML file inheritance with `~extends: @domain`
- Support for nested paths with dot notation (`key.subkey.value`)

## Usage

### Basic Example

```php
use Wexample\PhpYaml\YamlIncludeResolver;

// Create a resolver instance
$resolver = new YamlIncludeResolver();

// Register individual YAML files with domain names
$resolver->registerFile('@domain.one', '/path/to/one.yml');
$resolver->registerFile('@domain.two', '/path/to/two.yml');

// Get values using domain and key references
$value = $resolver->getValue('@domain.one::some_key');

// Get nested values using dot notation
$nestedValue = $resolver->getValue('@domain.one::group.subgroup.key');
```

### YAML File Format

#### Simple References

```yaml
# one.yml
include_key: '@domain.two::some_key'
```

```yaml
# two.yml
some_key: "Included value"
```

#### Same-Key References

```yaml
# one.yml
my_key: '@domain.two::%'
```

```yaml
# two.yml
my_key: "Value of the same key in another domain"
```

#### Complete Inheritance

```yaml
# child.yml
~extends: '@parent'
child_key: "Child value"
```

```yaml
# parent.yml
parent_key: "Parent value"
```

After resolution, `child.yml` will contain:
```yaml
child_key: "Child value"
parent_key: "Parent value"
```

### Constants

The YamlIncludeResolver class defines several constants that you can use:

- `DOMAIN_PREFIX`: '@' - Prefix for domain references
- `DOMAIN_SEPARATOR`: '::' - Separator between domain and key
- `DOMAIN_SAME_KEY_WILDCARD`: '%' - Wildcard to reference the same key in another domain
- `FILE_EXTENDS`: '~extends' - Key used for extending another YAML file
- `KEYS_SEPARATOR`: '.' - Separator for nested keys

## License

MIT
