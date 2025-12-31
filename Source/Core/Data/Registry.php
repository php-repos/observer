<?php

namespace PhpRepos\Observer\Core\Data;

/**
 * Manages the storage and retrieval of registered handlers in the observer system.
 *
 * This class provides a static registry for storing handlers along with their conditions,
 * allowing the observer system to match signals to appropriate handlers during dispatch.
 * It supports registering handlers, retrieving handlers by the number of parameters they
 * expect, and resetting the registry for testing or cleanup purposes.
 */
class Registry
{
    /**
     * @var array<int, array<array{conditions: array, handler: callable}>> Storage for registered handlers, indexed by the number of parameters.
     */
    private static array $storage = [];

    /**
     * Retrieves all registered handlers.
     *
     * @return array<int, array<array{conditions: array, handler: callable}>> An array of all registered handlers, indexed by parameter count.
     */
    public static function all(): array
    {
        return static::$storage;
    }

    /**
     * Registers a handler with the given conditions.
     *
     * @param callable $handler The handler to register.
     * @param array ...$conditions The conditions under which the handler should be invoked, based on signal types.
     * @return void
     */
    public static function register(callable $handler, array ...$conditions): void
    {
        static::$storage[count($conditions)][] = ['conditions' => $conditions, 'handler' => $handler];
    }

    /**
     * Resets the registry by clearing all registered handlers.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$storage = [];
    }

    /**
     * Retrieves handlers that expect a specific number of parameters.
     *
     * @param int $count The number of parameters expected by the handlers.
     * @return array<array{conditions: array, handler: callable}> An array of handlers that expect the given number of parameters.
     */
    public static function get(int $count): array
    {
        return static::$storage[$count] ?? [];
    }
}
