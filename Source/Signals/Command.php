<?php

namespace PhpRepos\Observer\Signals;

/**
 * Represents a command in the observer system.
 *
 * A command is a signal that issues an authoritative instruction, typically expecting
 * an action to be performed by handlers. Commands are used to trigger specific behaviors
 * in the system.
 */
class Command extends Signal
{
}