<?php

namespace PhpRepos\Observer\Observer;

use Exception;
use PhpRepos\Observer\Attributes\SignalType;
use PhpRepos\Observer\Exceptions\ObserverException;
use PhpRepos\Observer\Registry;
use PhpRepos\Observer\Signals\Command;
use PhpRepos\Observer\Signals\Event;
use PhpRepos\Observer\Signals\Inquiry;
use PhpRepos\Observer\Signals\Internals\HandlerExecution;
use PhpRepos\Observer\Signals\Internals\HandlerFound;
use PhpRepos\Observer\Signals\Internals\NoHandlerFound;
use PhpRepos\Observer\Signals\Message;
use PhpRepos\Observer\Signals\Plan;
use PhpRepos\Observer\Signals\Signal;
use ReflectionAttribute;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use function PhpRepos\Observer\Infra\Arrays\any;
use function PhpRepos\Observer\Infra\Arrays\cartesian_product;
use function PhpRepos\Observer\Infra\Arrays\map;
use function PhpRepos\Observer\Infra\Arrays\merge;
use function PhpRepos\Observer\Infra\Arrays\reduce;
use function PhpRepos\Observer\Infra\Arrays\unique;

/**
 * Registers handlers to listen for specific signals in the observer system.
 *
 * This function allows registering one or more callable handlers that will be invoked
 * when matching signals are dispatched via the `send` function. Each handler's parameters
 * are analyzed to determine the types of signals it can handle, supporting union types,
 * intersection types, and custom signal types via the `SignalType` attribute. Optional
 * parameters with default values are also supported, allowing flexible handler definitions.
 *
 * @param callable ...$handlers One or more callables to register as handlers.
 * @throws ObserverException If a handler has no parameters or if a parameter lacks a type or SignalType attribute.
 */
function subscribe(callable ...$handlers): void
{
    if (empty($handlers)) {
        return;
    }

    foreach ($handlers as $handler) {
        $reflection = new ReflectionFunction($handler);
        $parameters = $reflection->getParameters();

        if (empty($parameters)) {
            throw new ObserverException('Handler should care for at least one signal.');
        }

        $conditions = [];

        foreach ($parameters as $param) {
            $type = $param->getType();
            $attributes = $param->getAttributes(SignalType::class);

            if ($param->isOptional() && $param->isDefaultValueAvailable()) {
                foreach (cartesian_product($conditions) as $combined_conditions) {
                    Registry::register($handler, ...$combined_conditions);
                }
            }

            if ($type instanceof ReflectionUnionType) {
                $reflection_types = map($type->getTypes(), fn (ReflectionNamedType $named_type) => [$named_type->getName()]);
            } else if ($type instanceof ReflectionIntersectionType) {
                $reflection_types = [map($type->getTypes(), fn (ReflectionNamedType $named_type) => $named_type->getName())];
            } else if ($type instanceof ReflectionNamedType) {
                $reflection_types = [[$type->getName()]];
            } else {
                $reflection_types = [];
            }

            $attribute_types = map($attributes, fn (ReflectionAttribute $attribute) => [$attribute->newInstance()->type]);

            if (count($reflection_types) === 0 && count($attribute_types) === 0) {
                throw new ObserverException('Handler parameter must have a type or SignalType attribute.');
            }

            $conditions[] = map(merge($reflection_types, $attribute_types), fn (array $types) => [
                'types' => $types,
                'optional' => $param->isOptional(),
                'default_is_available' => $param->isDefaultValueAvailable(),
                'default_value' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            ]);
        }

        foreach (cartesian_product($conditions) as $combined_conditions) {
            Registry::register($handler, ...$combined_conditions);
        }
    }
}

/**
 * Dispatches signals to all registered handlers that match the signal types.
 *
 * This function sends one or more signals to all handlers registered via `subscribe`
 * that match the signal types. It supports handlers with union types, intersection types,
 * optional parameters, and custom signal types via the `SignalType` attribute. Handlers
 * are executed in the order they were registered, and any signals returned by handlers
 * are collected and returned. Internal signals (e.g., `HandlerExecution`, `HandlerFound`,
 * `NoHandlerFound`) are emitted during the dispatch process to provide observability.
 *
 * @param Signal ...$signals The signals to dispatch.
 * @return array An array of signals returned by the handlers.
 * @throws Exception If a handler throws an exception during execution, it is propagated.
 */
function send(Signal ...$signals): array
{
    if (empty($signals)) {
        return [];
    }

    $signals = unique($signals);

    $results = [];

    if (count($signals) > 1) {
        $results = reduce($signals, fn (array $carry, Signal $signal) => merge($carry, send($signal)), $results);
    }

    $is_internal_signal = any($signals, fn (Signal $signal) => $signal instanceof HandlerExecution || $signal instanceof HandlerFound || $signal instanceof NoHandlerFound);

    $possible_listeners = Registry::get(count($signals));

    $handler_found = false;

    foreach ($possible_listeners as $listener_index => $listener) {
        $handler = $listener['handler'];
        $conditions = $listener['conditions'];

        $args = [];
        $is_match = true;

        foreach ($conditions as $index => $condition) {
            if ($index >= count($signals)) {
                if ($condition['optional'] && $condition['default_is_available']) {
                    $args[] = $condition['default_value'];
                    continue;
                }
                $is_match = false;
                break;
            }

            $signal = $signals[$index];
            $instance_of_types = true;
            foreach ($condition['types'] as $condition_type) {
                if (!$signal instanceof $condition_type) {
                    $instance_of_types = false;
                    break;
                }
            }

            if ($instance_of_types) {
                $args[] = $signal;
            } elseif ($condition['optional'] && $condition['default_is_available']) {
                $args[] = $condition['default_value'];
            } else {
                $is_match = false;
                break;
            }
        }

        if ($is_match) {
            $handler_found = true;

            if (!$is_internal_signal) {
                send(HandlerExecution::create('Handler Execution Planned', ['id' => count($signals) . '-' . $listener_index, 'signal_types' => map($signals, fn (Signal $signal) => get_class($signal))]));
            }

            $result = $handler(...$args);
            if ($result instanceof Signal) {
                $results[] = $result;
            }

            if (!$is_internal_signal) {
                send(HandlerFound::create('Handler Found', ['id' => count($signals) . '-' . $listener_index, 'signal_types' => map($signals, fn (Signal $signal) => get_class($signal))]));
            }
        }
    }

    if (!$handler_found && !$is_internal_signal) {
        send(NoHandlerFound::create('No Handler Found', ['signal_types' => map($signals, fn (Signal $signal) => get_class($signal))]));
    }

    return $results;
}

/**
 * Broadcasts an event signal to all matching handlers.
 *
 * This function is a specialized version of `send` for broadcasting event signals,
 * which represent something that has already happened in the system.
 *
 * @param Event $event The event signal to broadcast.
 * @return array An array of signals returned by the handlers.
 * @throws Exception If a handler throws an exception during execution, it is propagated.
 */
function broadcast(Event $event): array
{
    return send($event);
}

/**
 * Proposes a plan signal to all matching handlers.
 *
 * This function is a specialized version of `send` for proposing plan signals,
 * which represent a proposed future action or intention in the system.
 *
 * @param Plan $plan The plan signal to propose.
 * @return array An array of signals returned by the handlers.
 * @throws Exception If a handler throws an exception during execution, it is propagated.
 */
function propose(Plan $plan): array
{
    return send($plan);
}

/**
 * Asks an inquiry signal to all matching handlers.
 *
 * This function is a specialized version of `send` for asking inquiry signals,
 * which request input or a decision from handlers.
 *
 * @param Inquiry $inquiry The inquiry signal to ask.
 * @return array An array of signals returned by the handlers.
 * @throws Exception If a handler throws an exception during execution, it is propagated.
 */
function ask(Inquiry $inquiry): array
{
    return send($inquiry);
}

/**
 * Shares a message signal with all matching handlers.
 *
 * This function is a specialized version of `send` for sharing message signals,
 * which share information without expecting a specific response.
 *
 * @param Message $message The message signal to share.
 * @return array An array of signals returned by the handlers.
 * @throws Exception If a handler throws an exception during execution, it is propagated.
 */
function share(Message $message): array
{
    return send($message);
}

/**
 * Orders a command signal to all matching handlers.
 *
 * This function is a specialized version of `send` for ordering command signals,
 * which issue authoritative instructions to handlers.
 *
 * @param Command $command The command signal to order.
 * @return array An array of signals returned by the handlers.
 * @throws Exception If a handler throws an exception during execution, it is propagated.
 */
function order(Command $command): array
{
    return send($command);
}
