<?php

namespace PhpRepos\Observer\Attributes;

use Attribute;

/**
 * Defines a custom signal type for a handler parameter.
 *
 * This attribute allows specifying an exact signal type for a handler parameter
 * in the observer system, enabling more precise signal matching beyond PHP's
 * native type system. It can be applied multiple times to a single parameter to
 * allow matching against multiple signal types.
 *
 * @Attribute(Attribute::TARGET_PARAMETER|Attribute::IS_REPEATABLE)
 */
#[Attribute(Attribute::TARGET_PARAMETER|Attribute::IS_REPEATABLE)]
class SignalType
{
    /**
     * Constructs a new SignalType attribute instance.
     *
     * @param string $type The fully qualified class name of the signal type (e.g., 'PhpRepos\Observer\Signals\Event').
     */
    public function __construct(public string $type) {}
}
