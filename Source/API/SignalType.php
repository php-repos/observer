<?php

namespace PhpRepos\Observer\API;

use Attribute;

/**
 * Attribute to specify signal types for handler parameters.
 *
 * Used when a handler parameter doesn't have a type hint or needs additional
 * type constraints. Can be applied multiple times to the same parameter for
 * union-like behavior.
 */
#[Attribute(Attribute::TARGET_PARAMETER|Attribute::IS_REPEATABLE)]
class SignalType
{
    /**
     * Constructs a SignalType attribute.
     *
     * @param string $type The fully-qualified class name of the signal type
     */
    public function __construct(public string $type) {}
}
