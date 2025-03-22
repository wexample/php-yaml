<?php

namespace Wexample\PhpYaml\Tests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use Wexample\PhpYaml\YamlIncludeResolver;

class YamlIncludeResolverIncludeTest extends TestCase
{
    private YamlIncludeResolver $resolver;
    private string $resourcesPath;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->resolver = new YamlIncludeResolver();
        $this->resourcesPath = dirname(__DIR__) . '/Resources/yaml';

        // Register test YAML files
        $this->resolver->registerFile('@domain.one', $this->resourcesPath . '/domain/one.yml');
        $this->resolver->registerFile('@domain.two', $this->resourcesPath . '/domain/two.yml');
        $this->resolver->registerFile('@domain.three', $this->resourcesPath . '/domain/three.yml');
    }

    public function testSimpleValues()
    {
        $this->assertEquals('Simple value', $this->resolver->getValue('@domain.one::simple_key'));
        $this->assertEquals('Simple group value', $this->resolver->getValue('@domain.one::simple_group.simple_group_key'));
    }

    public function testIncludeWithSameKey()
    {
        $this->assertEquals(
            'Included string with short notation',
            $this->resolver->getValue('@domain.one::include_key_short_notation')
        );
    }

    public function testIncludeWithDifferentKey()
    {
        $this->assertEquals(
            'Included value with different key',
            $this->resolver->getValue('@domain.one::include_different_key')
        );
    }

    public function testIncludeSubItem()
    {
        $this->assertEquals(
            'First level item',
            $this->resolver->getValue('@domain.one::include_key_sub_item')
        );
    }

    public function testDeepValues()
    {
        $this->assertEquals('Deep two', $this->resolver->getValue('@domain.one::deep_values.deepTwo'));
        $this->assertEquals('Deep two', $this->resolver->getValue('@domain.one::deep_values_2.deeper.deepTwo'));
    }

    public function testGroupIncludes()
    {
        $this->assertIsArray(
            $this->resolver->getValue('@domain.one::simple_group.include_group_short_notation')
        );

        $this->assertEquals(
            'One',
            $this->resolver->getValue('@domain.one::simple_group.include_group_short_notation.sub_group.one')
        );
    }

    public function testResolvableLoop()
    {
        $this->assertIsArray(
            $this->resolver->getValue('@domain.one::include_resolvable_loop')
        );

        $this->assertEquals(
            'Two',
            $this->resolver->getValue('@domain.one::include_resolvable_loop.sub_group.two')
        );
    }

    public function testMissingInclude()
    {
        $this->assertEquals(
            'missing',
            $this->resolver->getValue('missing')
        );

        // Missing includes should return the original reference
        $this->assertEquals(
            '@domain.one::missing',
            $this->resolver->getValue('@domain.one::missing')
        );

        // Missing includes should return the original reference
        $this->assertEquals(
            '@domain.two::missing',
            $this->resolver->getValue('@domain.one::include_missing')
        );
    }
}
