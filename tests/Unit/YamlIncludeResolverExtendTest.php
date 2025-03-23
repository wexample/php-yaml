<?php

namespace Wexample\PhpYaml\Tests\Unit;

use Exception;
use Wexample\PhpYaml\Test\AbstractYamlIncludeResolverTest;

class YamlIncludeResolverExtendTest extends AbstractYamlIncludeResolverTest
{
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Register test YAML files
        $this->resolver->registerFile('domain.one', $this->resourcesPath . '/domain/one.yml');
        $this->resolver->registerFile('domain.three', $this->resourcesPath . '/domain/three.yml');
    }

    public function testExtends()
    {
        $this->assertEquals('Simple value', $this->resolver->getValueResolved('@domain.three::simple_key'));
        $this->assertEquals('Simple group value', $this->resolver->getValueResolved('@domain.three::simple_group.simple_group_key'));
    }
}
