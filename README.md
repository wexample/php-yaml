# PHP YAML Includes

A lightweight PHP library for resolving includes between YAML files with support for inheritance, references, and performance-optimized caching.

Developed by [Wexample](https://wexample.com).

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
- High-performance multi-level caching system for optimized lookups
- Batch processing of multiple values with automatic reference resolution

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
$value = $resolver->getValueResolved('@domain.one::some_key');
$value = $resolver->getValue('some_key', 'domain.one');

// Get nested values using dot notation
$nestedValue = $resolver->getValueResolved('@domain.one::group.subgroup.key');
```

### Batch Processing

```php
// Process multiple values at once, resolving all references
$translations = [
    'key1' => 'Simple value',
    'key2' => '@domain.one::some_key',
    'key3' => '@domain.two::other_key',
    'key4' => '%' // Same key wildcard
];

// Resolve all references in the array
$resolved = $resolver->resolveValues($translations);

// With a specific domain for wildcard references
$resolved = $resolver->resolveValues($translations, '@domain.two');
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

### Integration with Symfony Translations

This library integrates seamlessly with the Symfony Translation component through the companion package [wexample/symfony-translations](https://github.com/wexample/symfony-translations):

```php
use Wexample\PhpYaml\YamlIncludeResolver;
use Wexample\SymfonyTranslations\Translation\Translator;

// Create a resolver instance
$resolver = new YamlIncludeResolver();

// Create a translator that uses the resolver
$translator = new Translator($resolver);

// The translator will use the resolver to handle references in translation files
```

## License

MIT
