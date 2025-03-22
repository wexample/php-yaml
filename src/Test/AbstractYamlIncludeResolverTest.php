<?php

namespace Wexample\PhpYaml\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use Wexample\PhpYaml\YamlIncludeResolver;

abstract class AbstractYamlIncludeResolverTest extends TestCase
{
    protected YamlIncludeResolver $resolver;
    protected string $resourcesPath;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->resolver = new YamlIncludeResolver();
        $this->resourcesPath = dirname(__DIR__) . '/../tests/Resources/yaml';
    }
}
