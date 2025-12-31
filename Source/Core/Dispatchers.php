<?php

namespace PhpRepos\Observer\Core\Dispatchers;

use PhpRepos\Observer\API\Signal;
use PhpRepos\Logger\API\Logs;
use PhpRepos\Observer\Core\Handlers;
use PhpRepos\Observer\Platform\Arrays;

/**
 * Dispatches signals to matching handlers and collects resulting signals.
 *
 * Processes signals by finding handlers whose parameter conditions match the
 * provided signal types. For multiple signals, handles them recursively. Logs
 * handler execution and matching status. Returns any signals produced by handlers.
 *
 * @param Signal ...$signals One or more signals to dispatch
 * @return array An array of signals returned by executed handlers
 */
function dispatch(Signal ...$signals): array
{
    if (empty($signals)) {
        return [];
    }

    $signals = Arrays\unique($signals);
    $results = [];

    if (count($signals) > 1) {
        $results = Arrays\reduce($signals, fn (array $carry, Signal $signal) => Arrays\merge($carry, dispatch($signal)), $results);
    }

    $possible_listeners = Handlers\get(count($signals));
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

            Logs\info('Handler Execution Planned', [
                'id' => count($signals) . '-' . $listener_index,
                'signal_types' => Arrays\map($signals, fn (Signal $signal) => get_class($signal))
            ]);

            $result = $handler(...$args);
            if ($result instanceof Signal) {
                $results[] = $result;
            }

            Logs\info('Handler Found', [
                'id' => count($signals) . '-' . $listener_index,
                'signal_types' => Arrays\map($signals, fn (Signal $signal) => get_class($signal))
            ]);
        }
    }

    if (!$handler_found) {
        Logs\info('No Handler Found', [
            'signal_types' => Arrays\map($signals, fn (Signal $signal) => get_class($signal))
        ]);
    }

    return $results;
}
