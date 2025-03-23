<?php

namespace Wexample\PhpYaml\Tests\Unit;

use Exception;
use Wexample\PhpYaml\Test\AbstractYamlIncludeResolverTest;

class YamlIncludeResolverGetDomainsTest extends AbstractYamlIncludeResolverTest
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
     * Test that getDomains returns all registered domains
     */
    public function testGetDomains()
    {
        $domains = $this->resolver->getDomains();
        
        // Check that we have the expected domains
        $this->assertIsArray($domains);
        $this->assertCount(2, $domains);
        $this->assertArrayHasKey('domain.one', $domains);
        $this->assertArrayHasKey('domain.two', $domains);
        
        // Check that the domains contain the expected data
        $this->assertIsArray($domains['domain.one']);
        $this->assertIsArray($domains['domain.two']);
        
        // Check some specific values from the domains
        $this->assertEquals('Simple value', $domains['domain.one']['simple_key']);
        $this->assertEquals('Included value', $domains['domain.two']['include_key']);
    }

    /**
     * Test that getDomains reflects changes when new files are registered
     */
    public function testGetDomainsAfterRegistration()
    {
        // Register a new domain
        $this->resolver->registerFile('domain.three', $this->resourcesPath . '/domain/three.yml');
        
        $domains = $this->resolver->getDomains();
        
        // Check that the new domain is included
        $this->assertCount(3, $domains);
        $this->assertArrayHasKey('domain.three', $domains);
    }
}
