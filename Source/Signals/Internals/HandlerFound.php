<?php

namespace PhpRepos\Observer\Signals\Internals;

use PhpRepos\Observer\Signals\Event;

/**
 * Represents an event indicating a handler was found and executed.
 *
 * This internal signal is emitted after a handler successfully processes the given
 * signals, providing a notification of the handler execution.
 */
class HandlerFound extends Event
{
}
