# PHP YAML Includes

Une bibliothèque PHP légère pour résoudre les inclusions entre fichiers YAML.

## Installation

```bash
composer require wexample/php-yaml-includes
```

## Fonctionnalités

- Résolution des inclusions entre fichiers YAML
- Support des références avec la syntaxe `@domain::key`
- Support des références avec la même clé via `@domain::%`
- Héritage de fichiers YAML complets avec `~extends: @domain`
- Support des chemins imbriqués avec la notation par points (`key.subkey.value`)

## Utilisation

### Example de base

```php
use Wexample\PhpYaml\YamlIncludeResolver;

// Créer une instance du résolveur
$resolver = new YamlIncludeResolver();

// Enregistrer des fichiers YAML individuels
$resolver->registerFile('domain.one', '/path/to/one.yml');
$resolver->registerFile('domain.two', '/path/to/two.yml');

// Ou enregistrer un répertoire entier (avec sous-répertoires)
$resolver->registerDirectory('/path/to/yaml/files');

// Résoudre toutes les inclusions
$resolver->resolveIncludes();

// Récupérer le contenu résolu pour un domaine spécifique
$content = $resolver->getResolvedContent('domain.one');

// Ou récupérer tout le contenu résolu
$allContent = $resolver->getAllResolvedContent();
```

### Format des fichiers YAML

#### Inclusions simples

```yaml
# one.yml
include_key: '@domain.two::some_key'
```

```yaml
# two.yml
some_key: "Valeur incluse"
```

#### Inclusions avec la même clé

```yaml
# one.yml
my_key: '@domain.two::%'
```

```yaml
# two.yml
my_key: "Valeur de la même clé dans un autre domaine"
```

#### Héritage complet

```yaml
# child.yml
~extends: '@parent'
child_key: "Valeur enfant"
```

```yaml
# parent.yml
parent_key: "Valeur parent"
```

Après résolution, `child.yml` contiendra :
```yaml
child_key: "Valeur enfant"
parent_key: "Valeur parent"
```

## Licence

MIT
