<?php

namespace PhpRepos\Observer\API\Signals;

use PhpRepos\Observer\API\{Command, Event, Inquiry, Message, Plan};
use function PhpRepos\Observer\Platform\DateTimes\utc_now;
use function PhpRepos\Observer\Platform\Identifiers\uuid;

/**
 * Creates a new Event signal with auto-generated ID and timestamp.
 *
 * @param string $title The event title
 * @param array|null $details Optional additional details
 * @return Event The created event signal
 */
function event(string $title, ?array $details = []): Event
{
    return new Event(uuid(), $title, utc_now(), $details ?? []);
}

/**
 * Creates a new Command signal with auto-generated ID and timestamp.
 *
 * @param string $title The command title
 * @param array|null $details Optional additional details
 * @return Command The created command signal
 */
function command(string $title, ?array $details = []): Command
{
    return new Command(uuid(), $title, utc_now(), $details ?? []);
}

/**
 * Creates a new Plan signal with auto-generated ID and timestamp.
 *
 * @param string $title The plan title
 * @param array|null $details Optional additional details
 * @return Plan The created plan signal
 */
function plan(string $title, ?array $details = []): Plan
{
    return new Plan(uuid(), $title, utc_now(), $details ?? []);
}

/**
 * Creates a new Inquiry signal with auto-generated ID and timestamp.
 *
 * @param string $title The inquiry title
 * @param array|null $details Optional additional details
 * @return Inquiry The created inquiry signal
 */
function inquiry(string $title, ?array $details = []): Inquiry
{
    return new Inquiry(uuid(), $title, utc_now(), $details ?? []);
}

/**
 * Creates a new Message signal with auto-generated ID and timestamp.
 *
 * @param string $title The message title
 * @param array|null $details Optional additional details
 * @return Message The created message signal
 */
function message(string $title, ?array $details = []): Message
{
    return new Message(uuid(), $title, utc_now(), $details ?? []);
}
