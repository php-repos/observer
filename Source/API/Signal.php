<?php

namespace PhpRepos\Observer\API;

use DateTimeImmutable;
use JsonSerializable;
use function PhpRepos\Observer\Platform\DateTimes\utc_now;
use function PhpRepos\Observer\Platform\Identifiers\uuid;

/**
 * Base signal class for the observer pattern implementation.
 *
 * Signals carry information about events, commands, plans, inquiries, or messages
 * throughout the system. Each signal has a unique ID, title, timestamp, and optional details.
 */
class Signal implements JsonSerializable
{
    /**
     * Constructs a new Signal instance.
     *
     * @param string $id Unique identifier for the signal
     * @param string $title Descriptive title of the signal
     * @param DateTimeImmutable $time When the signal was created
     * @param array $details Additional contextual information
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly DateTimeImmutable $time,
        public readonly array $details,
    ) {}

    /**
     * Factory method to create a signal with auto-generated ID and timestamp.
     *
     * @param string $title Descriptive title of the signal
     * @param array|null $details Optional additional contextual information
     * @return static A new signal instance
     */
    public static function create(string $title, ?array $details = []): static
    {
        return new static(uuid(), $title, utc_now(), $details ?? []);
    }

    /**
     * Serializes the signal to JSON format.
     *
     * @return array The signal data as an associative array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'details' => $this->details,
            'time' => $this->time->format('Y-m-d\TH:i:s.uP'),
        ];
    }
}
