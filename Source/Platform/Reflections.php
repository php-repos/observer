<?php

namespace PhpRepos\Observer\Platform\Reflections;

use ReflectionAttribute;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

/**
 * Creates a reflection object for a callable.
 *
 * @param callable $handler The callable to reflect
 * @return ReflectionFunction The reflection object
 */
function get_reflection(callable $handler): ReflectionFunction
{
    return new ReflectionFunction($handler);
}

/**
 * Retrieves parameters from a reflection object.
 *
 * @param ReflectionFunction $reflection The reflection to get parameters from
 * @return array An array of ReflectionParameter objects
 */
function get_parameters(ReflectionFunction $reflection): array
{
    return $reflection->getParameters();
}

/**
 * Retrieves the type declaration of a parameter.
 *
 * @param ReflectionParameter $param The parameter to get the type from
 * @return ReflectionType|null The type declaration or null if none
 */
function get_type(ReflectionParameter $param): ?ReflectionType
{
    return $param->getType();
}

/**
 * Retrieves attributes of a specific class from a parameter.
 *
 * @param ReflectionParameter $param The parameter to get attributes from
 * @param string $class The attribute class name to filter by
 * @return array An array of ReflectionAttribute objects
 */
function get_attributes(ReflectionParameter $param, string $class): array
{
    return $param->getAttributes($class);
}

/**
 * Checks if a type is a union type.
 *
 * @param ReflectionType|null $type The type to check
 * @return bool True if the type is a union type, false otherwise
 */
function is_union_type(?ReflectionType $type): bool
{
    return $type instanceof ReflectionUnionType;
}

/**
 * Checks if a type is an intersection type.
 *
 * @param ReflectionType|null $type The type to check
 * @return bool True if the type is an intersection type, false otherwise
 */
function is_intersection_type(?ReflectionType $type): bool
{
    return $type instanceof ReflectionIntersectionType;
}

/**
 * Checks if a type is a named type.
 *
 * @param ReflectionType|null $type The type to check
 * @return bool True if the type is a named type, false otherwise
 */
function is_named_type(?ReflectionType $type): bool
{
    return $type instanceof ReflectionNamedType;
}

/**
 * Retrieves the component types from a union or intersection type.
 *
 * @param ReflectionUnionType|ReflectionIntersectionType $type The type to get components from
 * @return array An array of ReflectionType objects
 */
function get_types(ReflectionUnionType|ReflectionIntersectionType $type): array
{
    return $type->getTypes();
}

/**
 * Retrieves the name of a named type.
 *
 * @param ReflectionNamedType $type The named type to get the name from
 * @return string The type name
 */
function get_type_name(ReflectionNamedType $type): string
{
    return $type->getName();
}

/**
 * Checks if a parameter is optional.
 *
 * @param ReflectionParameter $param The parameter to check
 * @return bool True if the parameter is optional, false otherwise
 */
function is_optional(ReflectionParameter $param): bool
{
    return $param->isOptional();
}

/**
 * Checks if a parameter has a default value.
 *
 * @param ReflectionParameter $param The parameter to check
 * @return bool True if a default value is available, false otherwise
 */
function has_default_value(ReflectionParameter $param): bool
{
    return $param->isDefaultValueAvailable();
}

/**
 * Retrieves the default value of a parameter.
 *
 * @param ReflectionParameter $param The parameter to get the default value from
 * @return mixed The default value
 */
function get_default_value(ReflectionParameter $param): mixed
{
    return $param->getDefaultValue();
}

/**
 * Creates an instance of an attribute.
 *
 * @param ReflectionAttribute $attribute The attribute to instantiate
 * @return object The attribute instance
 */
function new_instance(ReflectionAttribute $attribute): object
{
    return $attribute->newInstance();
}

/**
 * Extracts type names from a reflection type into arrays.
 *
 * Converts reflection types into arrays of type name arrays:
 * - Union types: Each alternative becomes a separate single-element array
 * - Intersection types: All types combined into one array
 * - Named types: Single type in a single-element array
 * - No type: Empty array
 *
 * @param ReflectionType|null $type The reflection type to extract from
 * @return array An array of type name arrays
 */
function extract_reflection_types(?ReflectionType $type): array
{
    if (is_union_type($type)) {
        return array_map(fn ($named_type) => [get_type_name($named_type)], get_types($type));
    }

    if (is_intersection_type($type)) {
        return [array_map(fn ($named_type) => get_type_name($named_type), get_types($type))];
    }

    if (is_named_type($type)) {
        return [[get_type_name($type)]];
    }

    return [];
}

/**
 * Extracts type information from SignalType attributes.
 *
 * Converts an array of SignalType attributes into arrays of type names.
 *
 * @param array $attributes An array of ReflectionAttribute objects
 * @return array An array of single-element arrays containing type names
 */
function extract_attribute_types(array $attributes): array
{
    return array_map(fn ($attribute) => [new_instance($attribute)->type], $attributes);
}
