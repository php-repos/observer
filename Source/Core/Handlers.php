<?php

namespace PhpRepos\Observer\Core\Handlers;

use PhpRepos\Observer\API\SignalType;
use PhpRepos\Observer\Core\Data\Registry;
use PhpRepos\Observer\Core\Exceptions\ObserverException;
use function PhpRepos\Observer\Platform\Arrays\{cartesian_product, map, merge};
use function PhpRepos\Observer\Platform\Reflections\{
    extract_attribute_types,
    extract_reflection_types,
    get_attributes,
    get_default_value,
    get_parameters,
    get_reflection,
    get_type,
    has_default_value,
    is_optional
};

/**
 * Stores a handler with its matching conditions in the registry.
 *
 * Handlers are indexed by their condition count for efficient lookup during dispatch.
 *
 * @param callable $handler The handler callable to store
 * @param array ...$conditions Variable number of condition arrays
 * @return void
 */
function store(callable $handler, array ...$conditions): void
{
    Registry::register($handler, ...$conditions);
}

/**
 * Retrieves handlers with a specific number of conditions.
 *
 * Returns handlers that expect exactly the given number of signals.
 *
 * @param int $count The number of conditions (signal parameters) to match
 * @return array An array of handler entries with their conditions
 */
function get(int $count): array
{
    return Registry::get($count);
}

/**
 * Resets the handler registry to its initial empty state.
 *
 * This removes all registered handlers. Primarily used for testing.
 *
 * @return void
 */
function reset(): void
{
    Registry::reset();
}

/**
 * Retrieves all registered handlers.
 *
 * Returns the complete registry indexed by condition count.
 *
 * @return array The complete handler registry
 */
function all(): array
{
    return Registry::all();
}

/**
 * Registers a handler by analyzing its parameters and creating matching conditions.
 *
 * Analyzes the handler's parameter types (including union and intersection types)
 * and SignalType attributes to determine which signals it should handle. Creates
 * all possible combinations of conditions for handlers with optional parameters.
 *
 * @param callable $handler The handler to register
 * @return void
 * @throws ObserverException If handler has no parameters or parameters lack type information
 */
function register(callable $handler): void
{
    $reflection = get_reflection($handler);
    $parameters = get_parameters($reflection);

    if (empty($parameters)) {
        throw new ObserverException('Handler should care for at least one signal.');
    }

    $conditions = [];

    foreach ($parameters as $param) {
        $type = get_type($param);
        $attributes = get_attributes($param, SignalType::class);

        if (is_optional($param) && has_default_value($param)) {
            foreach (cartesian_product($conditions) as $combined_conditions) {
                store($handler, ...$combined_conditions);
            }
        }

        $reflection_types = extract_reflection_types($type);
        $attribute_types = extract_attribute_types($attributes);

        if (count($reflection_types) === 0 && count($attribute_types) === 0) {
            throw new ObserverException('Handler parameter must have a type or SignalType attribute.');
        }

        $conditions[] = map(merge($reflection_types, $attribute_types), fn (array $types) => [
            'types' => $types,
            'optional' => is_optional($param),
            'default_is_available' => has_default_value($param),
            'default_value' => has_default_value($param) ? get_default_value($param) : null,
        ]);
    }

    foreach (cartesian_product($conditions) as $combined_conditions) {
        store($handler, ...$combined_conditions);
    }
}
