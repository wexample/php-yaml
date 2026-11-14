<?php

declare(strict_types=1);

namespace Wexample\PhpYaml\Tests\Unit;

use Wexample\PhpYaml\Exception\CircularYamlReferenceException;
use Wexample\PhpYaml\Test\AbstractYamlIncludeResolverTest;

/**
 * References and `~extends` leading back to where they started are refused
 * with an exception naming the circle, instead of recursing until the stack
 * runs out; the resolver stays usable after one.
 */
class YamlIncludeResolverCircularTest extends AbstractYamlIncludeResolverTest
{
    public function testAReferenceToItselfIsRefused(): void
    {
        $this->resolver->registerContent('loop', ['key' => '@loop::key']);

        $this->expectException(CircularYamlReferenceException::class);
        $this->expectExceptionMessage('loop|key → loop|key');
        $this->resolver->getValueResolved('@loop::key');
    }

    public function testReferencesInACircleAreRefused(): void
    {
        $this->resolver->registerContent('a', ['key' => '@b::key']);
        $this->resolver->registerContent('b', ['key' => '@a::%']);

        $this->expectException(CircularYamlReferenceException::class);
        $this->resolver->getValueResolved('@a::key');
    }

    public function testDomainsExtendingEachOtherAreRefusedWhenLookingUpAValue(): void
    {
        $this->resolver->registerContent('a', ['~extends' => '@b']);
        $this->resolver->registerContent('b', ['~extends' => '@a']);

        $this->expectException(CircularYamlReferenceException::class);
        $this->resolver->getValue('missing', 'a');
    }

    public function testDomainsExtendingEachOtherAreRefusedWhenResolvingValues(): void
    {
        $this->resolver->registerContent('a', ['~extends' => '@b', 'one' => '1']);
        $this->resolver->registerContent('b', ['~extends' => '@a', 'two' => '2']);

        $this->expectException(CircularYamlReferenceException::class);
        $this->resolver->resolveValues($this->resolver->getAllDomainsContent()['a'], 'a');
    }

    public function testAChainWithoutACircleStillResolvesAfterOneWasRefused(): void
    {
        $this->resolver->registerContent('loop', ['key' => '@loop::key']);
        $this->resolver->registerContent('base', ['title' => 'Base title']);
        $this->resolver->registerContent('child', ['~extends' => '@base', 'alias' => '@base::title']);

        try {
            $this->resolver->getValueResolved('@loop::key');
            $this->fail('A reference to itself should be refused.');
        } catch (CircularYamlReferenceException) {
        }

        $this->assertSame('Base title', $this->resolver->getValueResolved('@child::title'));
        $this->assertSame('Base title', $this->resolver->getValueResolved('@child::alias'));
        $this->assertSame(
            ['title' => 'Base title', 'alias' => 'Base title'],
            $this->resolver->resolveValues(['~extends' => '@base', 'alias' => '@base::title'], 'child')
        );
    }
}
