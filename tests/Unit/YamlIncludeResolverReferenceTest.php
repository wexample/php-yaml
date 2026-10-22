<?php

namespace Wexample\PhpYaml\Tests\Unit;

use Wexample\PhpYaml\Test\AbstractYamlIncludeResolverTest;

/**
 * Network's #152: a text that only looks like a reference — an "@", a
 * percentage, an address — is kept as text, never resolved.
 */
class YamlIncludeResolverReferenceTest extends AbstractYamlIncludeResolverTest
{
    public function testOnlyADomainAndAKeyMakeAReference(): void
    {
        foreach (['@', '50 %', '@ home', 'contact@example.com', '@domain', 'domain::key'] as $text) {
            $this->assertFalse($this->resolver->isIncludeReference($text), $text);
        }

        foreach (['@domain::key', '@domain.sub::key.deep'] as $reference) {
            $this->assertTrue($this->resolver->isIncludeReference($reference), $reference);
        }
    }

    public function testTextThatLooksLikeAReferenceIsLeftAsIs(): void
    {
        $values = ['sign' => '@', 'rate' => '50 %', 'mail' => 'contact@example.com'];

        $this->assertSame($values, $this->resolver->resolveValues($values));
    }
}
