<?php

namespace Wexample\PhpYaml\Tests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use Wexample\PhpYaml\YamlIncludeResolver;

class YamlIncludeResolverScanDirectoryTest extends TestCase
{
    protected YamlIncludeResolver $resolver;
    protected string $resourcesPath;
    protected string $testDirectoryPath;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new YamlIncludeResolver();
        $this->resourcesPath = dirname(__DIR__) . '/Resources/yaml';
        $this->testDirectoryPath = $this->resourcesPath . '/domain';
    }

    /**
     * Test scanning a directory for YAML files
     *
     * @throws Exception
     */
    public function testScanDirectory()
    {
        // Scan the test directory
        $this->resolver->scanDirectory($this->testDirectoryPath);

        $this->assertEquals(
            'Simple value',
            $this->resolver->getValue('@domain.three::simple_key')
        );
    }

    /**
     * Test scanning a directory with an alias prefix
     *
     * @throws Exception
     */
    public function testScanDirectoryWithAliasPrefix()
    {
        // Scan the test directory with an alias prefix
        $this->resolver->scanDirectory($this->testDirectoryPath, '@custom');

        $this->assertEquals(
            'Simple value',
            $this->resolver->getValue('@custom.domain.one::simple_key')
        );
    }

    /**
     * Test scanning a non-existent directory
     */
    public function testScanNonExistentDirectory()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Directory not found:');
        // Try to scan a non-existent directory
        $this->resolver->scanDirectory($this->resourcesPath . '/non_existent');
    }
}
