<?php

namespace Wexample\PhpYamlIncludes\Tests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use Wexample\PhpYamlIncludes\YamlIncludeResolver;

class YamlIncludeResolverAdvancedTest extends TestCase
{
    private YamlIncludeResolver $resolver;
    private string $resourcesPath;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->resolver = new YamlIncludeResolver();
        $this->resourcesPath = dirname(__DIR__) . '/Resources/yaml/advanced';

        // Register test YAML files
        $this->resolver->registerFile('config', $this->resourcesPath . '/config.yml');
        $this->resolver->registerFile('security', $this->resourcesPath . '/security.yml');
        $this->resolver->registerFile('logging', $this->resourcesPath . '/logging.yml');
        $this->resolver->registerFile('features', $this->resourcesPath . '/features.yml');
        $this->resolver->registerFile('common', $this->resourcesPath . '/common.yml');

        // Resolve includes
        $this->resolver->resolveIncludes();
    }

    /**
     * Teste la résolution des références simples entre domaines
     */
    public function testSimpleReferences(): void
    {
        $config = $this->resolver->getResolvedContent('config');
        
        $this->assertNotNull($config);
        $this->assertEquals('db_user', $config['config_group']['database']['user']);
        $this->assertEquals('secret_password', $config['config_group']['database']['password']);
    }

    /**
     * Teste la résolution des références avec wildcard (%)
     */
    public function testWildcardReferences(): void
    {
        $config = $this->resolver->getResolvedContent('config');
        
        // Vérifie que la référence avec wildcard dans un groupe imbriqué est correctement résolue
        $this->assertEquals(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            $config['config_group']['logging']['format']
        );
    }

    /**
     * Teste la résolution des références dans des structures profondément imbriquées
     */
    public function testNestedReferences(): void
    {
        $config = $this->resolver->getResolvedContent('config');
        
        // Vérifie que les références dans des structures imbriquées sont correctement résolues
        $this->assertIsArray($config['config_group']['features']['feature_one']['settings']['options']);
        $this->assertTrue($config['config_group']['features']['feature_one']['settings']['options']['cache']);
        $this->assertEquals('gzip', $config['config_group']['features']['feature_one']['settings']['options']['compression']);
    }

    /**
     * Teste la résolution des références à des groupes entiers
     */
    public function testGroupReferences(): void
    {
        $config = $this->resolver->getResolvedContent('config');
        
        // Vérifie que la référence à un groupe entier est correctement résolue
        $this->assertIsArray($config['all_features']);
        $this->assertArrayHasKey('feature_one', $config['all_features']);
        $this->assertArrayHasKey('feature_two', $config['all_features']);
        $this->assertArrayHasKey('feature_three', $config['all_features']);
    }

    /**
     * Teste la résolution des références à des clés qui n'existent pas
     */
    public function testMissingReferences(): void
    {
        $config = $this->resolver->getResolvedContent('config');
        
        // Vérifie que les références à des clés inexistantes retournent la référence originale
        $this->assertEquals('@nonexistent::key', $config['missing_reference']);
    }

    /**
     * Teste la résolution des références à des clés ambiguës
     */
    public function testAmbiguousReferences(): void
    {
        $config = $this->resolver->getResolvedContent('config');
        
        // Vérifie que les références ambiguës sont résolues avec la première correspondance trouvée
        // Dans ce cas, cela devrait être la valeur au niveau racine
        $this->assertEquals('Valeur dans common', $config['ambiguous_reference']);
    }

    /**
     * Teste la résolution des références avec le même nom de clé dans différents niveaux
     */
    public function testSameKeyDifferentLevels(): void
    {
        $features = $this->resolver->getResolvedContent('features');
        
        // Vérifie que les références à des clés avec le même nom à différents niveaux sont correctement résolues
        $this->assertIsArray($features['feature_two']['settings']);
        $this->assertEquals(60, $features['feature_two']['settings']['timeout']);
        $this->assertEquals(5, $features['feature_two']['settings']['retry']);
    }

    /**
     * Teste l'extension de fichiers YAML (héritage)
     */
    public function testFileExtends(): void
    {
        $features = $this->resolver->getResolvedContent('features');
        
        // Vérifie que l'extension fonctionne correctement
        $this->assertEquals('Feature Three', $features['feature_three']['name']);
        $this->assertEquals('Extended feature', $features['feature_three']['description']);
        $this->assertEquals('1.0.0', $features['feature_three']['version']);
        
        // Vérifie que les propriétés du fichier étendu sont héritées
        $this->assertIsArray($features['feature_three']['options']);
        $this->assertTrue($features['feature_three']['options']['cache']);
    }

    /**
     * Teste les références imbriquées (références à des références)
     */
    public function testNestedCrossReferences(): void
    {
        $features = $this->resolver->getResolvedContent('features');
        
        // Vérifie que les références imbriquées entre domaines sont correctement résolues
        $this->assertIsArray($features['feature_nested']['config']);
        $this->assertTrue($features['feature_nested']['config']['enabled']);
        
        $this->assertIsArray($features['feature_nested']['security']);
        $this->assertArrayHasKey('jwt', $features['feature_nested']['security']);
        $this->assertEquals('jwt_secret_key', $features['feature_nested']['security']['jwt']['secret']);
    }

    /**
     * Teste la résolution des références à des clés dans des chemins différents
     */
    public function testCrossPathReferences(): void
    {
        $config = $this->resolver->getResolvedContent('config');
        
        // Vérifie que les références à des clés dans des chemins différents sont correctement résolues
        $this->assertEquals(
            '7',
            $config['config_group']['logging']['rotation']['max_files']
        );
    }
}
