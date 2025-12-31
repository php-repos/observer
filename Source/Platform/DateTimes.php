<?php

namespace PhpRepos\Observer\Platform\DateTimes;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

function utc_now(): DateTimeImmutable
{
    try {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    } catch (Exception $e) {
        throw new \RuntimeException('Failed to create UTC datetime: ' . $e->getMessage(), 0, $e);
    }
}

function from_string(string $datetime, string $timezone = 'UTC'): DateTimeImmutable
{
    try {
        return new DateTimeImmutable($datetime, new DateTimeZone($timezone));
    } catch (Exception $e) {
        throw new \RuntimeException("Failed to create datetime from '$datetime': " . $e->getMessage(), 0, $e);
    }
}
