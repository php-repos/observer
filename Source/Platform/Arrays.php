<?php

namespace PhpRepos\Observer\Platform\Arrays;

/**
 * Checks if any element in an array satisfies a condition.
 *
 * If a condition callback is provided, returns true if any element passes the test.
 * If no condition is provided, returns true if the array is not empty.
 * Uses native array_any function if available (PHP 8.4+).
 *
 * @param iterable $array The array to check
 * @param callable|null $condition Optional callback(value, key): bool
 * @return bool True if any element satisfies the condition, false otherwise
 */
function any(iterable $array, ?callable $condition = null): bool
{
    $array = is_array($array) ? $array : iterator_to_array($array);

    if (is_callable($condition)) {
        if (function_exists('array_any')) {
            return array_any($array, $condition);
        }

        foreach ($array as $key => $value) {
            if ($condition($value, $key)) {
                return true;
            }
        }

        return false;
    }

    return !empty($array);
}

/**
 * Computes the cartesian product of arrays.
 *
 * Given an array of arrays, returns all possible combinations where one element
 * is selected from each sub-array. For example:
 * [[1, 2], [3, 4]] -> [[1, 3], [1, 4], [2, 3], [2, 4]]
 *
 * @param iterable $array An array of arrays
 * @return array An array of all possible combinations
 */
function cartesian_product(iterable $array): array
{
    $array = is_array($array) ? $array : iterator_to_array($array);

    if (empty($array)) {
        return [[]];
    }

    $first = array_shift($array);
    $sub_product = cartesian_product($array);

    $result = [];
    foreach ($first as $item) {
        foreach ($sub_product as $product) {
            $result[] = array_merge([$item], $product);
        }
    }

    return $result;
}

/**
 * Maps a callback function over an array.
 *
 * Applies the callback to each element with both value and key as parameters.
 *
 * @param iterable $array The array to map over
 * @param callable $callback The callback(value, key): mixed
 * @return array The mapped array
 */
function map(iterable $array, callable $callback): array
{
    $array = is_array($array) ? $array : iterator_to_array($array);
    return array_map($callback, array_values($array), array_keys($array));
}

/**
 * Merges multiple arrays into a single array.
 *
 * Combines all provided arrays into one, preserving all values.
 *
 * @param iterable ...$arrays Variable number of arrays to merge
 * @return array The merged array
 */
function merge(iterable ...$arrays): array
{
    $result = [];
    foreach ($arrays as $array) {
        $result = array_merge($result, is_array($array) ? $array : iterator_to_array($array));
    }

    return $result;
}

/**
 * Reduces an array to a single value.
 *
 * Iteratively applies the callback to accumulate a result. The callback
 * receives the accumulated value, current element value, and current key.
 *
 * @param iterable $array The array to reduce
 * @param callable $callback The callback(carry, value, key): mixed
 * @param mixed $carry The initial value for the accumulator
 * @return mixed The final reduced value
 */
function reduce(iterable $array, callable $callback, mixed $carry = null): mixed
{
    $array = is_array($array) ? $array : iterator_to_array($array);
    return array_reduce(
        array_keys($array),
        fn ($carry, $key) => $callback($carry, $array[$key], $key),
        $carry
    );
}

/**
 * Returns unique values from an array.
 *
 * Removes duplicate values and re-indexes the array.
 *
 * @param iterable $array The array to process
 * @return array An array containing only unique values
 */
function unique(iterable $array): array
{
    $array = is_array($array) ? $array : iterator_to_array($array);
    return array_values(array_unique($array, SORT_REGULAR));
}
