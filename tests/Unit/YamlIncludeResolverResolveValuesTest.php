<?php

namespace Wexample\PhpYaml\Tests\Unit;

use Exception;
use Wexample\PhpYaml\Test\AbstractYamlIncludeResolverTest;

class YamlIncludeResolverResolveValuesTest extends AbstractYamlIncludeResolverTest
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

    /**
     * Test resolveValues with simple values (no references)
     */
    public function testResolveSimpleValues()
    {
        $values = [
            'key1' => 'Simple value 1',
            'key2' => 'Simple value 2',
            'key3' => 123,
            'key4' => true,
        ];

        $resolved = $this->resolver->resolveValues($values);

        // Simple values should remain unchanged
        $this->assertEquals($values, $resolved);
    }

    /**
     * Test resolveValues with mixed values (some references, some simple values)
     */
    public function testResolveMixedValues()
    {
        $values = [
            'key1' => 'Simple value',
            'key2' => '@domain.one::simple_key',
            'key3' => '@domain.two::include_key',
            'key4' => 123,
        ];

        $resolved = $this->resolver->resolveValues($values);

        // Check that references are resolved but simple values remain unchanged
        $this->assertEquals('Simple value', $resolved['key1']);
        $this->assertEquals('Simple value', $resolved['key2']); // Resolved from domain.one
        $this->assertEquals('Included value', $resolved['key3']); // Resolved from domain.two
        $this->assertEquals(123, $resolved['key4']);
    }

    /**
     * Test resolveValues with nested references
     */
    public function testResolveNestedReferences()
    {
        $values = [
            'key1' => '@domain.one::include_key_short_notation',
            'key2' => '@domain.one::include_different_key',
        ];

        $resolved = $this->resolver->resolveValues($values);

        // Check that nested references are fully resolved
        $this->assertEquals('Included string with short notation', $resolved['key1']);
        $this->assertEquals('Included value with different key', $resolved['key2']);
    }

    /**
     * Test resolveValues with missing references
     */
    public function testResolveMissingReferences()
    {
        $values = [
            'key1' => '@domain.one::missing',
            'key2' => '@domain.missing::key',
        ];

        $resolved = $this->resolver->resolveValues($values);

        // Missing references should return the original reference
        $this->assertEquals('domain.one::missing', $resolved['key1']);
        $this->assertEquals('domain.missing::key', $resolved['key2']);
    }

    /**
     * Test resolveValues with array values
     */
    public function testResolveArrayValues()
    {
        $values = [
            'key1' => '@domain.one::simple_group.include_group_short_notation',
        ];

        $resolved = $this->resolver->resolveValues($values);

        // Check that array values are resolved correctly
        $this->assertIsArray($resolved['key1']);
        $this->assertArrayHasKey('sub_group', $resolved['key1']);
        $this->assertEquals('One', $resolved['key1']['sub_group']['one']);
        $this->assertEquals('Two', $resolved['key1']['sub_group']['two']);
        $this->assertEquals('Three', $resolved['key1']['sub_group']['three']);
    }

    /**
     * Test resolveValues with a specific domain
     */
    public function testResolveWithDomain()
    {
        $values = [
            'simple_key' => 'This should not be resolved',
            'include_key' => '%', // This should resolve to the value of simple_key in domain.two
        ];

        $resolved = $this->resolver->resolveValues($values, 'domain.two');

        // Check that the wildcard reference is resolved using the specified domain
        $this->assertEquals('This should not be resolved', $resolved['simple_key']);
        $this->assertEquals('Included value', $resolved['include_key']); // From domain.two
    }
}
