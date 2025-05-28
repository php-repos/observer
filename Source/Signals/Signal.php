<?php

namespace PhpRepos\Observer\Signals;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;

/**
 * Base class for all signals in the observer system.
 *
 * A signal is an immutable object that encapsulates a specific event, plan, inquiry,
 * message, or command within the system. It includes a unique identifier, a descriptive
 * title, the creation time, and optional additional details. Signals are used to communicate
 * between different parts of the system via the observer pattern, allowing handlers to
 * react to specific signals.
 *
 * This class implements JsonSerializable to enable straightforward conversion to JSON
 * format for purposes like logging, transmission, or debugging.
 */
class Signal implements JsonSerializable
{
    /**
     * Constructs a new Signal instance.
     *
     * @param string $id The unique identifier for the signal, typically a UUID.
     * @param string $title A brief description of the signal (e.g., "UserLoggedIn").
     * @param DateTimeImmutable $time The time when the signal was created.
     * @param array $details Optional additional information about the signal, stored as key-value pairs.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly DateTimeImmutable $time,
        public readonly array $details,
    ) {}

    /**
     * Factory method to create a new Signal instance.
     *
     * This method simplifies signal creation by automatically generating a UUID for the
     * signal's ID and setting the creation time to the current UTC time.
     *
     * @param string $title A brief description of the signal.
     * @param array|null $details Optional additional information about the signal. Defaults to an empty array if null.
     * @return static A new Signal instance.
     * @throws Exception If there is an error generating the UUID or creating the DateTimeImmutable object.
     */
    public static function create(string $title, ?array $details = []): static
    {
        return new static(Uuid::uuid4()->toString(), $title, new DateTimeImmutable('now', new DateTimeZone('UTC')), $details);
    }

    /**
     * Serializes the Signal object to a JSON-compatible array.
     *
     * Returns an array representation of the signal, including its ID, title, details,
     * and creation time formatted in ISO 8601 format. This is useful for logging,
     * transmission, or debugging purposes.
     *
     * @return array An associative array containing the signal's properties.
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
