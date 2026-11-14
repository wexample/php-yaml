<?php

namespace Wexample\PhpYaml\Exception;

use LogicException;

/** References or `~extends` leading back to where they started: none of them resolves. */
class CircularYamlReferenceException extends LogicException
{
    /** @param string[] $steps what was met, the first again at the end */
    public static function forPath(array $steps): self
    {
        return new self(sprintf('YAML references leading back to themselves: %s.', implode(' → ', $steps)));
    }
}
