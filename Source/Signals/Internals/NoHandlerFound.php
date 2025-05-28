<?php

namespace PhpRepos\Observer\Signals\Internals;

use PhpRepos\Observer\Signals\Event;

/**
 * Represents an event indicating no handler was found for the given signals.
 *
 * This internal signal is emitted when the observer system cannot find any handlers
 * that match the dispatched signals, allowing for fallback behavior or logging.
 */
class NoHandlerFound extends Event
{
}
