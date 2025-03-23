<?php

namespace Wexample\PhpYaml\Tests\Unit;

use Exception;
use Wexample\PhpYaml\Test\AbstractYamlIncludeResolverTest;

class YamlIncludeResolverIncludeTest extends AbstractYamlIncludeResolverTest
{

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Register test YAML files
        $this->resolver->registerFile('domain.one', $this->resourcesPath . '/domain/one.yml');
        $this->resolver->registerFile('domain.two', $this->resourcesPath . '/domain/two.yml');
    }

    public function testSimpleValues()
    {
        $this->assertEquals('Simple value', $this->resolver->getValueResolved('@domain.one::simple_key'));
        $this->assertEquals('Simple group value', $this->resolver->getValueResolved('@domain.one::simple_group.simple_group_key'));
    }

    public function testIncludeWithSameKey()
    {
        $this->assertEquals(
            'Included string with short notation',
            $this->resolver->getValueResolved('@domain.one::include_key_short_notation')
        );
    }

    public function testIncludeWithDifferentKey()
    {
        $this->assertEquals(
            'Included value with different key',
            $this->resolver->getValueResolved('@domain.one::include_different_key')
        );
    }

    public function testIncludeSubItem()
    {
        $this->assertEquals(
            'First level item',
            $this->resolver->getValueResolved('@domain.one::include_key_sub_item')
        );
    }

    public function testDeepValues()
    {
        $this->assertEquals('Deep two', $this->resolver->getValueResolved('@domain.one::deep_values.deepTwo'));
        $this->assertEquals('Deep two', $this->resolver->getValueResolved('@domain.one::deep_values_2.deeper.deepTwo'));
    }

    public function testGroupIncludes()
    {
        $this->assertIsArray(
            $this->resolver->getValueResolved('@domain.one::simple_group.include_group_short_notation')
        );

        $this->assertEquals(
            'One',
            $this->resolver->getValueResolved('@domain.one::simple_group.include_group_short_notation.sub_group.one')
        );
    }

    public function testResolvableLoop()
    {
        $this->assertIsArray(
            $this->resolver->getValueResolved('@domain.one::include_resolvable_loop')
        );

        $this->assertEquals(
            'Two',
            $this->resolver->getValueResolved('@domain.one::include_resolvable_loop.sub_group.two')
        );
    }

    public function testMissingInclude()
    {
        $this->assertEquals(
            '@missing',
            $this->resolver->getValueResolved('@missing')
        );

        // Missing includes should return the original reference
        $this->assertEquals(
            'domain.one::missing',
            $this->resolver->getValueResolved('@domain.one::missing')
        );

        // Missing includes should return the original reference
        $this->assertEquals(
            'domain.two::missing',
            $this->resolver->getValueResolved('@domain.one::include_missing')
        );
    }
}
