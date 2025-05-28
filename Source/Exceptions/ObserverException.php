<?php

namespace PhpRepos\Observer\Exceptions;

use LogicException;

/**
 * Exception thrown when an error occurs in the observer system.
 *
 * This exception is used for logical errors in the observer pattern implementation,
 * such as invalid handler configurations or signal dispatching issues.
 */
class ObserverException extends LogicException
{
}
