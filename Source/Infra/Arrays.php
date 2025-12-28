<?php

namespace PhpRepos\Observer\Infra\Arrays;

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

function map(iterable $array, callable $callback): array
{
    $array = is_array($array) ? $array : iterator_to_array($array);
    return array_map($callback, array_values($array), array_keys($array));
}

function merge(iterable ...$arrays): array
{
    $result = [];
    foreach ($arrays as $array) {
        $result = array_merge($result, is_array($array) ? $array : iterator_to_array($array));
    }

    return $result;
}

function reduce(iterable $array, callable $callback, mixed $carry = null): mixed
{
    $array = is_array($array) ? $array : iterator_to_array($array);
    return array_reduce(
        array_keys($array),
        fn ($carry, $key) => $callback($carry, $array[$key], $key),
        $carry
    );
}

function unique(iterable $array): array
{
    $array = is_array($array) ? $array : iterator_to_array($array);
    return array_values(array_unique($array, SORT_REGULAR));
}
