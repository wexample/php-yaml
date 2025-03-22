<?php

namespace Wexample\PhpYamlIncludes\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\PhpYamlIncludes\YamlIncludeResolver;

class YamlIncludeResolverTest extends TestCase
{
    private YamlIncludeResolver $resolver;
    private string $resourcesPath;

    protected function setUp(): void
    {
        $this->resolver = new YamlIncludeResolver();
        $this->resourcesPath = dirname(__DIR__) . '/Resources/yaml';

        // Register test YAML files
        $this->resolver->registerFile('domain.one', $this->resourcesPath . '/domain/one.yml');
        $this->resolver->registerFile('domain.two', $this->resourcesPath . '/domain/two.yml');
        $this->resolver->registerFile('domain.three', $this->resourcesPath . '/domain/three.yml');

        // Resolve includes
        $this->resolver->resolveIncludes();
    }

    public function testSimpleValues()
    {
        $content = $this->resolver->getResolvedContent('domain.one');
        
        $this->assertNotNull($content);
        $this->assertEquals('Simple value', $content['simple_key']);
        $this->assertEquals('Simple group value', $content['simple_group']['simple_group_key']);
    }

    public function testIncludeWithSameKey()
    {
        $content = $this->resolver->getResolvedContent('domain.one');
        
        $this->assertEquals(
            'Included string with short notation',
            $content['include_key_short_notation']
        );
    }

    public function testIncludeWithDifferentKey()
    {
        $content = $this->resolver->getResolvedContent('domain.one');
        
        $this->assertEquals(
            'Included value with different key',
            $content['include_different_key']
        );
    }

    public function testIncludeSubItem()
    {
        $content = $this->resolver->getResolvedContent('domain.one');
        
        $this->assertEquals(
            'First level item',
            $content['include_key_sub_item']
        );
    }

    public function testDeepValues()
    {
        $content = $this->resolver->getResolvedContent('domain.one');
        
        $this->assertIsArray($content['deep_values']);
        $this->assertEquals('Deep two', $content['deep_values']['deepTwo']);
        
        $this->assertIsArray($content['deep_values_2']);
        $this->assertEquals('Deep two', $content['deep_values_2']['deeper']['deepTwo']);
    }

    public function testGroupIncludes()
    {
        $content = $this->resolver->getResolvedContent('domain.one');
        
        $this->assertIsArray($content['simple_group']['include_group_short_notation']);
        $this->assertEquals(
            'Two',
            $content['simple_group']['include_group_short_notation']['sub_group']['two']
        );
    }

    public function testResolvableLoop()
    {
        $content = $this->resolver->getResolvedContent('domain.one');
        
        $this->assertIsArray($content['include_resolvable_loop']);
        $this->assertEquals(
            'Two',
            $content['include_resolvable_loop']['sub_group']['two']
        );
    }

    public function testMissingInclude()
    {
        $content = $this->resolver->getResolvedContent('domain.one');
        
        // Missing includes should return the original reference
        $this->assertEquals('@domain.two::missing', $content['include_missing']);
    }

    public function testExtends()
    {
        $content = $this->resolver->getResolvedContent('domain.three');
        
        // Should inherit all values from domain.one
        $this->assertEquals('Simple value', $content['simple_key']);
        $this->assertEquals('Simple group value', $content['simple_group']['simple_group_key']);
        
        // Should have its own specific values
        $this->assertEquals('Value specific to three', $content['three_specific_key']);
    }

    public function testRegisterDirectory()
    {
        $resolver = new YamlIncludeResolver();
        $resolver->registerDirectory($this->resourcesPath);
        $resolver->resolveIncludes();
        
        $allContent = $resolver->getAllResolvedContent();
        
        // Should have registered all domains
        $this->assertArrayHasKey('@domain.one', $allContent);
        $this->assertArrayHasKey('@domain.two', $allContent);
        $this->assertArrayHasKey('@domain.three', $allContent);
    }
}
