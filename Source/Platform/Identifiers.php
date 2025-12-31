<?php

namespace PhpRepos\Observer\Platform\Identifiers;

use Exception;
use Ramsey\Uuid\Uuid;

function uuid(): string
{
    try {
        return Uuid::uuid4()->toString();
    } catch (Exception $e) {
        throw new \RuntimeException('Failed to generate UUID: ' . $e->getMessage(), 0, $e);
    }
}
