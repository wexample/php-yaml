<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Wexample\PhpYamlIncludes\YamlIncludeResolver;

// Créer une instance du résolveur
$resolver = new YamlIncludeResolver();

// Chemin vers les fichiers YAML d'exemple
$yamlPath = __DIR__ . '/../tests/Resources/yaml';

// Méthode 1: Enregistrer des fichiers individuellement
echo "Méthode 1: Enregistrement de fichiers individuels\n";
$resolver->registerFile('domain.one', $yamlPath . '/domain/one.yml');
$resolver->registerFile('domain.two', $yamlPath . '/domain/two.yml');
$resolver->registerFile('domain.three', $yamlPath . '/domain/three.yml');

// Résoudre les inclusions
$resolver->resolveIncludes();

// Afficher le contenu résolu pour le domaine 'one'
$contentOne = $resolver->getResolvedContent('domain.one');
echo "\nContenu résolu pour domain.one:\n";
print_r($contentOne['include_different_key']);
echo "\n";
print_r($contentOne['deep_values']);

// Réinitialiser le résolveur
$resolver = new YamlIncludeResolver();

// Méthode 2: Enregistrer un répertoire entier
echo "\n\nMéthode 2: Enregistrement d'un répertoire entier\n";
$resolver->registerDirectory($yamlPath);

// Résoudre les inclusions
$resolver->resolveIncludes();

// Afficher le contenu résolu pour le domaine 'three' (qui étend 'one')
$contentThree = $resolver->getResolvedContent('domain.three');
echo "\nContenu résolu pour domain.three (qui étend domain.one):\n";
print_r($contentThree['simple_key']); // Hérité de domain.one
echo "\n";
print_r($contentThree['three_specific_key']); // Spécifique à domain.three
