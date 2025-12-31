<?php

namespace PhpRepos\Observer\API\Bus;

use PhpRepos\Observer\API\{Command, Event, Inquiry, Message, Plan, Signal};
use PhpRepos\Observer\Core\Exceptions\ObserverException;
use PhpRepos\Observer\Core\Dispatchers;
use PhpRepos\Observer\Core\Handlers;

/**
 * Subscribes one or more handlers to the observer bus.
 *
 * Handlers will be invoked when signals matching their parameter types are dispatched.
 * Each handler must accept at least one Signal parameter.
 *
 * @param callable ...$handlers One or more handler callables to register
 * @return void
 */
function subscribe(callable ...$handlers): void
{
    if (empty($handlers)) {
        return;
    }

    foreach ($handlers as $handler) {
        Handlers\register($handler);
    }
}

/**
 * Sends one or more signals through the observer bus.
 *
 * Dispatches the provided signals to all matching handlers and returns
 * any signals produced by those handlers.
 *
 * @param Signal ...$signals One or more signals to dispatch
 * @return array An array of signals returned by handlers
 */
function send(Signal ...$signals): array
{
    return Dispatchers\dispatch(...$signals);
}

/**
 * Broadcasts an event signal through the observer bus.
 *
 * This is a convenience function for sending Event signals.
 *
 * @param Event $event The event to broadcast
 * @return array An array of signals returned by handlers
 */
function broadcast(Event $event): array
{
    return send($event);
}

/**
 * Proposes a plan signal through the observer bus.
 *
 * This is a convenience function for sending Plan signals.
 *
 * @param Plan $plan The plan to propose
 * @return array An array of signals returned by handlers
 */
function propose(Plan $plan): array
{
    return send($plan);
}

/**
 * Asks an inquiry signal through the observer bus.
 *
 * This is a convenience function for sending Inquiry signals.
 *
 * @param Inquiry $inquiry The inquiry to ask
 * @return array An array of signals returned by handlers
 */
function ask(Inquiry $inquiry): array
{
    return send($inquiry);
}

/**
 * Shares a message signal through the observer bus.
 *
 * This is a convenience function for sending Message signals.
 *
 * @param Message $message The message to share
 * @return array An array of signals returned by handlers
 */
function share(Message $message): array
{
    return send($message);
}

/**
 * Orders a command signal through the observer bus.
 *
 * This is a convenience function for sending Command signals.
 *
 * @param Command $command The command to order
 * @return array An array of signals returned by handlers
 */
function order(Command $command): array
{
    return send($command);
}
