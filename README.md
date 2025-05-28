# Observer Package Documentation

The **Observer** package provides a robust implementation of the observer pattern in PHP, enabling developers to create event-driven systems where signals (events, plans, inquiries, messages, or commands) can be broadcasted to registered handlers. This package is designed for flexibility, supporting various signal types, handler parameter configurations, and internal signals for observability.

## Table of Contents

- [Installation](#installation)
- [Core Concepts](#core-concepts)
  - [Signals](#signals)
  - [Handlers](#handlers)
  - [Internal Signals](#internal-signals)
- [Usage](#usage)
  - [Registering Handlers with `subscribe`](#registering-handlers-with-subscribe)
  - [Broadcasting Signals with `send`](#broadcasting-signals-with-send)
  - [Signal-Specific Functions](#signal-specific-functions)
- [Advanced Features](#advanced-features)
  - [Union and Intersection Types](#union-and-intersection-types)
  - [Nullable Parameters and Default Values](#nullable-parameters-and-default-values)
  - [Custom Signal Types with `SignalType` Attribute](#custom-signal-types-with-signaltype-attribute)
- [Error Handling in Handlers](#error-handling-in-handlers)
- [Order of Multi-Signal Broadcasting](#order-of-multi-signal-broadcasting)
- [Logging Signals (Including Internal Signals)](#logging-signals-including-internal-signals)
- [Full Example](#full-example)

## Installation

To use the **Observer** package in your project, install it via **phpkg**:

```bash
phpkg add https://github.com/php-repos/observer.git
```

## Core Concepts

### Signals

Signals are the core entities in the observer system, representing events, plans, inquiries, messages, or commands. All signals extend the `PhpRepos\Observer\Signals\Signal` class, which provides:

- **Properties** (all `public readonly`):
  - `id`: A unique identifier (UUID), accessible as `$signal->id`.
  - `title`: A descriptive title of the signal, accessible as `$signal->title`.
  - `time`: A `DateTimeImmutable` object representing the creation time (in UTC), accessible as `$signal->time`.
  - `details`: An array of additional information, accessible as `$signal->details`.

- **Methods**:
  - `jsonSerialize()`: Converts the signal to a JSON-compatible array.

#### Signal Types
- `Event`: Represents something that has happened (e.g., `UserLoggedIn`).
- `Plan`: Proposes a future action (e.g., `PasswordChangePlan`).
- `Inquiry`: Requests input or a decision.
- `Message`: Shares information without expecting a response.
- `Command`: Issues an authoritative instruction.

#### Creating a Custom Signal
```php
use PhpRepos\Observer\Signals\Event;

class UserLoggedIn extends Event {
    public function __construct(string $id, string $title, $time, array $details) {
        parent::__construct($id, $title, $time, $details);
    }
}

$signal = new UserLoggedIn(
    id: '123e4567-e89b-12d3-a456-426614174000',
    title: 'UserLoggedIn',
    time: new DateTimeImmutable('now', new DateTimeZone('UTC')),
    details: ['user_id' => 1]
);

// Access properties directly
echo $signal->id; // 123e4567-e89b-12d3-a456-426614174000
echo $signal->title; // UserLoggedIn
```

Alternatively, use the `create` factory method:
```php
$signal = UserLoggedIn::create('UserLoggedIn', ['user_id' => 1]);
echo $signal->id; // A generated UUID
echo $signal->title; // UserLoggedIn
```

### Handlers

Handlers are callables (e.g., closures, functions) registered to respond to specific signals. They are defined with typed parameters to specify which signals they handle. The `subscribe` function analyzes these parameters to determine matching signals.

#### Example Handler
```php
use PhpRepos\Observer\Observer\subscribe;

subscribe(function (UserLoggedIn $event) {
    echo "User {$event->details['user_id']} logged in at {$event->time->format('Y-m-d H:i:s')}.\n";
});
```

### Internal Signals

The package emits internal signals to provide observability into its operations:
- `HandlerExecution` (extends `Plan`): Emitted before a handler is executed.
- `HandlerFound` (extends `Event`): Emitted after a handler is executed.
- `NoHandlerFound` (extends `Event`): Emitted when no handlers match the dispatched signals.

These signals can be used for logging or debugging, as shown in the [Logging Signals](#logging-signals-including-internal-signals) section.

## Usage

### Registering Handlers with `subscribe`

The `subscribe` function registers one or more handlers to listen for signals. Each handler's parameters are analyzed to determine the signal types it can handle.

#### Basic Example
```php
use PhpRepos\Observer\Observer\subscribe;
use PhpRepos\Observer\Signals\Event;

class UserLoggedIn extends Event {}

subscribe(
    function (UserLoggedIn $event) {
        echo "User logged in: {$event->title}\n";
    }
);
```

#### Multiple Handlers
You can register multiple handlers at once:
```php
subscribe(
    function (UserLoggedIn $event) {
        echo "Logging user login: {$event->title}\n";
    },
    function (UserLoggedIn $event) {
        echo "Notifying admin: {$event->title}\n";
    }
);
```

#### Constraints
- **At Least One Parameter**: Handlers must have at least one parameter, or an `ObserverException` is thrown.
- **Typed Parameters**: Each parameter must have a type or a `SignalType` attribute, or an `ObserverException` is thrown.

### Broadcasting Signals with `send`

The `send` function dispatches signals to all matching handlers and returns an array of signals returned by those handlers.

#### Example
```php
use PhpRepos\Observer\Observer\send;

$signal = UserLoggedIn::create('UserLoggedIn', ['user_id' => 1]);
$results = send($signal);
// Outputs:
// Logging user login: UserLoggedIn
// Notifying admin: UserLoggedIn
```

#### Returning Signals
Handlers can return signals, which are collected by `send`:
```php
subscribe(
    function (UserLoggedIn $event) {
        return Event::create('LoginLogged', ['user_id' => $event->details['user_id']]);
    }
);

$results = send(UserLoggedIn::create('UserLoggedIn', ['user_id' => 1]));
// $results contains one Event with title 'LoginLogged'
```

### Signal-Specific Functions

The package provides helper functions for specific signal types:
- `broadcast(Event $event)`: Broadcasts an event.
- `propose(Plan $plan)`: Proposes a plan.
- `ask(Inquiry $inquiry)`: Asks an inquiry.
- `share(Message $message)`: Shares a message.
- `order(Command $command)`: Orders a command.

#### Example
```php
use PhpRepos\Observer\Observer\broadcast;
use PhpRepos\Observer\Observer\propose;

class PasswordChangePlan extends Plan {}

subscribe(
    function (UserLoggedIn $event) {
        echo "User logged in: {$event->title}\n";
    },
    function (PasswordChangePlan $plan) {
        echo "Plan proposed: {$plan->title}\n";
    }
);

broadcast(UserLoggedIn::create('UserLoggedIn', ['user_id' => 1]));
propose(PasswordChangePlan::create('PasswordChangePlan', ['user_id' => 1]));
// Outputs:
// User logged in: UserLoggedIn
// Plan proposed: PasswordChangePlan
```

## Advanced Features

### Union and Intersection Types

Handlers can use union types (`|`) to match any of the specified types and intersection types (`&`) to match signals implementing all specified interfaces.

#### Union Type Example
```php
subscribe(
    function (UserLoggedIn|PasswordChangePlan $signal) {
        echo "Signal received: {$signal->title}\n";
    }
);

send(UserLoggedIn::create('UserLoggedIn')); // Executes
send(PasswordChangePlan::create('PasswordChangePlan')); // Executes
send(Message::create('Message')); // Does not execute
```

**Best Practice for Union Types**: While union types are supported, they increase the size of the internal lookup array that the Observer uses to match handlers, which can impact performance with many handlers. Instead, consider defining a parent class for the signals in the union and typing the parameter with that parent class. For example, if `UserLoggedIn` and `PasswordChangePlan` both extend a `UserAction` class, you can type the parameter as `UserAction` to reduce the lookup complexity.

#### Intersection Type Example
```php
interface Loggable {}
interface Trackable {}

class CustomSignal extends Event implements Loggable, Trackable {
    public function __construct(string $id, string $title, $time, array $details) {
        parent::__construct($id, $title, $time, $details);
    }
}

subscribe(
    function (Loggable&Trackable $signal) {
        echo "Custom signal: {$signal->title}\n";
    }
);

send(CustomSignal::create('CustomSignal')); // Executes
send(UserLoggedIn::create('UserLoggedIn')); // Does not execute
```

### Nullable Parameters and Default Values

Handlers can use nullable parameters or parameters with default values to handle optional signals, but the first parameter must always be required.

#### Example
```php
subscribe(
    function (UserLoggedIn $event, ?Message $message = null) {
        $message_title = $message ? $message->title : 'No message';
        echo "Event: {$event->title}, Message: $message_title\n";
    }
);

send(UserLoggedIn::create('UserLoggedIn')); // Outputs: Event: UserLoggedIn, Message: No message
send(UserLoggedIn::create('UserLoggedIn'), Message::create('Greeting')); // Outputs: Event: UserLoggedIn, Message: Greeting
send(PasswordChangePlan::create('PasswordChangePlan')); // Handler does not execute (no UserLoggedIn signal)
```

**Important Notes**:

- **Behavior with Optional Signals**: When a handler has optional parameters (e.g., `?Message $message = null`), the Observer will attempt to execute the handler by satisfying the required signals (e.g., `UserLoggedIn $event`) and passing `null` for the optional ones. This means the handler may run with the minimum requirements (only the required signals). Developers should ensure this behavior aligns with their intent. In the example above, the handler executes when only a `UserLoggedIn` signal is provided, with `$message` set to `null`. If this is not desired, consider making the parameter required or adding logic to handle the `null` case appropriately.

- **Unrelated Signals**: If the dispatched signal does not match the required parameters (e.g., sending a `PasswordChangePlan` when the handler requires a `UserLoggedIn`), the handler will not execute, as shown in the example above.

- **Parameter Order Warning**: In PHP, having optional parameters before required parameters has been deprecated and will be removed in future versions. The Observer package does not support this pattern at all. Defining a handler with optional parameters before required ones (e.g., `function (?Message $message = null, UserLoggedIn $event)`) will lead to unexpected behavior, as the Observer's matching logic assumes required parameters are satisfied first. Always place required parameters before optional ones.

### Custom Signal Types with `SignalType` Attribute

The `SignalType` attribute allows specifying a signal type for untyped parameters or overriding the parameter type.

#### Example with Local Signal
```php
use PhpRepos\Observer\Attributes\SignalType;

subscribe(
    function (#[SignalType(UserLoggedIn::class)] $signal) {
        echo "Custom signal: {$signal->title}\n";
    }
);

send(UserLoggedIn::create('UserLoggedIn')); // Executes
send(PasswordChangePlan::create('PasswordChangePlan')); // Does not execute
```

#### Example with External Signal (Using String Class Name)
For signals from external libraries or projects where you cannot directly reference the class (e.g., via `Class::class`), you can specify the fully qualified class name as a string in the `SignalType` attribute. This is particularly useful for integrating with events from other systems.

```php
use PhpRepos\Observer\Attributes\SignalType;

subscribe(
    function (#[SignalType('App\Events\ExternalEvent')] $signal) {
        echo "External signal received: {$signal->title}\n";
    }
);

// Assuming App\Events\ExternalEvent is a class from an external library
// You would dispatch an instance of it like this:
$external_event = new class('123e4567-e89b-12d3-a456-426614174000', 'ExternalEvent', new DateTimeImmutable(), []) extends Signal {
    public function __construct(string $id, string $title, $time, array $details) {
        parent::__construct($id, $title, $time, $details);
    }
};
$external_event_class = new ReflectionClass($external_event);
$external_event_class->setName('App\Events\ExternalEvent'); // Simulate the external class name

send($external_event); // Outputs: External signal received: ExternalEvent
```

In this example, the `SignalType` attribute uses the string `'App\Events\ExternalEvent'` to match signals from an external library, allowing the handler to process them without requiring a direct class reference.

## Error Handling in Handlers

Handlers may throw exceptions during execution, and the `send` function propagates these exceptions, stopping further handler execution for the current signal dispatch.

### Example: Handling Exceptions in Handlers
```php
subscribe(
    function (UserLoggedIn $event) {
        if (!isset($event->details['user_id'])) {
            throw new \Exception('Missing user_id in UserLoggedIn signal.');
        }
        echo "User logged in: {$event->title}\n";
    },
    function (UserLoggedIn $event) {
        echo "This handler will not execute if the first throws.\n";
    }
);

try {
    send(UserLoggedIn::create('UserLoggedIn', []));
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    // Handle the error, e.g., log it or notify an admin
}
// Outputs: Error: Missing user_id in UserLoggedIn signal.
```

### Best Practices
- **Validate Signal Data**: Check the signal's `details` for required keys to avoid runtime errors.
- **Graceful Degradation**: Return early or provide fallback behavior instead of throwing exceptions when possible.
- **Centralized Error Handling**: Wrap `send` calls in a try-catch block to handle exceptions at a higher level.

## Order of Multi-Signal Broadcasting

When broadcasting multiple signals using `send`, the order of signals matters:
- Handlers are matched based on the exact order of signal types in their parameter list.
- If the signal order does not match, the handler will not execute.

### Example: Signal Order Matters
```php
subscribe(
    function (UserLoggedIn $event, PasswordChangePlan $plan) {
        echo "Event: {$event->title}, Plan: {$plan->title}\n";
    }
);

send(
    UserLoggedIn::create('UserLoggedIn'),
    PasswordChangePlan::create('PasswordChangePlan')
);
// Outputs: Event: UserLoggedIn, Plan: PasswordChangePlan

send(
    PasswordChangePlan::create('PasswordChangePlan'),
    UserLoggedIn::create('UserLoggedIn')
);
// Does not execute (order mismatch)
```

### Broadcasting Multiple Signals
When `send` receives multiple signals, it processes them recursively by dispatching each signal individually. This means:
- Each signal is dispatched to all matching handlers.
- The order of signals in the `send` call determines the order of individual dispatches.
- Internal signals (`HandlerExecution`, `HandlerFound`) are emitted for each handler execution.

#### Example: Recursive Dispatch
```php
subscribe(
    function (Signal $signal) {
        echo "Signal: {$signal->title}\n";
    }
);

send(
    UserLoggedIn::create('UserLoggedIn'),
    PasswordChangePlan::create('PasswordChangePlan')
);
// Outputs:
// Signal: UserLoggedIn
// Signal: PasswordChangePlan
```

## Logging Signals (Including Internal Signals)

To log all signals, including internal ones, you can register a global handler for the `Signal` type. Internal signals (`HandlerExecution`, `HandlerFound`, `NoHandlerFound`) extend `Signal`, so they will be captured as well.

### Example: Logging All Signals
```php
use PhpRepos\Observer\Signals\Signal;
use PhpRepos\Observer\Signals\Internals\HandlerExecution;
use PhpRepos\Observer\Signals\Internals\HandlerFound;
use PhpRepos\Observer\Signals\Internals\NoHandlerFound;

// Register a global logger
subscribe(
    function (Signal $signal) {
        $type = get_class($signal);
        $title = $signal->title;
        $details = json_encode($signal->details);
        $time = $signal->time->format('Y-m-d H:i:s');
        file_put_contents('signals.log', "[$time] $type: $title - Details: $details\n", FILE_APPEND);
    }
);

// Register a regular handler
subscribe(
    function (UserLoggedIn $event) {
        echo "User logged in: {$event->title}\n";
    }
);

// Dispatch a signal
send(UserLoggedIn::create('UserLoggedIn', ['user_id' => 1]));
```

#### Log Output (`signals.log`)
```
[2025-04-27 12:00:00] UserLoggedIn: UserLoggedIn - Details: {"user_id":1}
[2025-04-27 12:00:00] PhpRepos\Observer\Signals\Internals\HandlerExecution: Handler Execution Planned - Details: {"id":"1-1","signal_types":["UserLoggedIn"]}
[2025-04-27 12:00:00] PhpRepos\Observer\Signals\Internals\HandlerFound: Handler Found - Details: {"id":"1-1","signal_types":["UserLoggedIn"]}
```

### Filtering Internal Signals
If you want to log only non-internal signals, you can filter them out:
```php
subscribe(
    function (Signal $signal) {
        if ($signal instanceof HandlerExecution || $signal instanceof HandlerFound || $signal instanceof NoHandlerFound) {
            return; // Skip internal signals
        }
        $type = get_class($signal);
        $title = $signal->title;
        $details = json_encode($signal->details);
        $time = $signal->time->format('Y-m-d H:i:s');
        file_put_contents('signals.log', "[$time] $type: $title - Details: $details\n", FILE_APPEND);
    }
);
```

## Full Example

Here’s a complete example demonstrating the package’s features, including handler registration, signal dispatching, error handling, and logging.

```php
<?php

use PhpRepos\Observer\Observer\subscribe;
use PhpRepos\Observer\Observer\send;
use PhpRepos\Observer\Signals\Event;
use PhpRepos\Observer\Signals\Plan;
use PhpRepos\Observer\Signals\Signal;
use PhpRepos\Observer\Signals\Internals\HandlerExecution;
use PhpRepos\Observer\Signals\Internals\HandlerFound;
use PhpRepos\Observer\Signals\Internals\NoHandlerFound;

// Define custom signals
class UserLoggedIn extends Event {}
class PasswordChangePlan extends Plan {}

// Register a global logger for all signals
subscribe(
    function (Signal $signal) {
        $type = get_class($signal);
        $title = $signal->title;
        $details = json_encode($signal->details);
        $time = $signal->time->format('Y-m-d H:i:s');
        file_put_contents('signals.log', "[$time] $type: $title - Details: $details\n", FILE_APPEND);
    }
);

// Register handlers with various features
subscribe(
    // Basic handler
    function (UserLoggedIn $event) {
        if (!isset($event->details['user_id'])) {
            throw new \Exception('Missing user_id in UserLoggedIn signal.');
        }
        echo "User logged in: {$event->title} (User ID: {$event->details['user_id']})\n";
        return Event::create('LoginLogged', ['user_id' => $event->details['user_id']]);
    },
    // Handler with multiple parameters
    function (UserLoggedIn $event, ?PasswordChangePlan $plan = null) {
        $plan_title = $plan ? $plan->title : 'No plan';
        echo "Combined: Event {$event->title}, Plan: $plan_title\n";
    },
    // Handler for debugging internal signals
    function (HandlerFound $event) {
        echo "Handler executed: {$event->title} for signals " . json_encode($event->details['signal_types']) . "\n";
    }
);

// Dispatch signals with error handling
try {
    $results = send(UserLoggedIn::create('UserLoggedIn', ['user_id' => 1]));
    foreach ($results as $result) {
        echo "Returned signal: {$result->title}\n";
    }

    send(
        UserLoggedIn::create('UserLoggedIn', ['user_id' => 2]),
        PasswordChangePlan::create('PasswordChangePlan', ['user_id' => 2])
    );

    // This will throw an exception
    send(UserLoggedIn::create('UserLoggedIn', []));
} catch (\Exception $e) {
    echo "Error during signal dispatch: {$e->getMessage()}\n";
}
```

#### Output
```
User logged in: UserLoggedIn (User ID: 1)
Handler executed: Handler Found for signals ["UserLoggedIn"]
Returned signal: LoginLogged
Combined: Event UserLoggedIn, Plan: No plan
Handler executed: Handler Found for signals ["UserLoggedIn"]
User logged in: UserLoggedIn (User ID: 2)
Handler executed: Handler Found for signals ["UserLoggedIn"]
Combined: Event UserLoggedIn, Plan: PasswordChangePlan
Handler executed: Handler Found for signals ["UserLoggedIn","PasswordChangePlan"]
Error during signal dispatch: Missing user_id in UserLoggedIn signal.
```

#### Log File (`signals.log`)
```
[2025-04-27 12:00:00] UserLoggedIn: UserLoggedIn - Details: {"user_id":1}
[2025-04-27 12:00:00] PhpRepos\Observer\Signals\Internals\HandlerExecution: Handler Execution Planned - Details: {"id":"1-1","signal_types":["UserLoggedIn"]}
[2025-04-27 12:00:00] PhpRepos\Observer\Signals\Internals\HandlerFound: Handler Found - Details: {"id":"1-1","signal_types":["UserLoggedIn"]}
[2025-04-27 12:00:00] UserLoggedIn: UserLoggedIn - Details: {"user_id":2}
[2025-04-27 12:00:00] PasswordChangePlan: PasswordChangePlan - Details: {"user_id":2}
[2025-04-27 12:00:00] PhpRepos\Observer\Signals\Internals\HandlerExecution: Handler Execution Planned - Details: {"id":"1-2","signal_types":["UserLoggedIn"]}
[2025-04-27 12:00:00] PhpRepos\Observer\Signals\Internals\HandlerFound: Handler Found - Details: {"id":"1-2","signal_types":["UserLoggedIn"]}
[2025-04-27 12:00:00] PhpRepos\Observer\Signals\Internals\HandlerExecution: Handler Execution Planned - Details: {"id":"2-3","signal_types":["UserLoggedIn","PasswordChangePlan"]}
[2025-04-27 12:00:00] PhpRepos\Observer\Signals\Internals\HandlerFound: Handler Found - Details: {"id":"2-3","signal_types":["UserLoggedIn","PasswordChangePlan"]}
[2025-04-27 12:00:00] UserLoggedIn: UserLoggedIn - Details: {}
[2025-04-27 12:00:00] PhpRepos\Observer\Signals\Internals\HandlerExecution: Handler Execution Planned - Details: {"id":"1-1","signal_types":["UserLoggedIn"]}
```

---
