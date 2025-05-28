<?php

namespace PhpRepos\Observer\Signals\Internals;

use PhpRepos\Observer\Signals\Plan;

/**
 * Represents a plan to execute a handler in the observer system.
 *
 * This internal signal is emitted before a handler is executed, indicating the
 * system's intent to dispatch the given signals to a matching handler.
 */
class HandlerExecution extends Plan
{
}
