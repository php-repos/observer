# Observer Package

A message-passing system for connecting autonomous modules in PHP applications. Designed for **architectural integration at boot time**, not ad-hoc event handling.

[![Tests](https://github.com/php-repos/observer/workflows/tests/badge.svg)](https://github.com/php-repos/observer/actions)

## Installation

```bash
phpkg add observer
```

**Requirements**: PHP 8.3+, phpkg

---

## Quick Start (5 Minutes)

Copy this into your `bootstrap.php` and see Observer in action:

```php
<?php
// bootstrap.php - Run this file to see Observer work

use PhpRepos\Observer\API\{Event, Command};
use function PhpRepos\Observer\API\Bus\{subscribe, send};

// 1. Define your signals (things that happen in your system)
class UserRegistered extends Event {}
class SendWelcomeEmail extends Command {}

// 2. Wire modules together (subscribe handlers at boot time)
subscribe(
    // When user registers, request a welcome email
    function (UserRegistered $event) {
        echo "✓ User {$event->details['email']} registered\n";

        // Return a command for the email module
        return SendWelcomeEmail::create('Welcome Email', [
            'to' => $event->details['email']
        ]);
    },

    // Email module processes the command
    function (SendWelcomeEmail $cmd) {
        echo "✓ Sending welcome email to {$cmd->details['to']}\n";
        // mail($cmd->details['to'], 'Welcome!', 'Thanks for joining!');
    }
);

// 3. Dispatch signals from anywhere in your application
echo "Registering user...\n";
send(UserRegistered::create('User Registered', [
    'email' => 'user@example.com'
]));

echo "✓ Complete! User registered and email sent.\n";
```

**Run it**: `php bootstrap.php`

**Output**:
```
Registering user...
✓ User user@example.com registered
✓ Sending welcome email to user@example.com
✓ Complete! User registered and email sent.
```

**What just happened?**
- Registration module **announced** a user registered (Event)
- Email module **reacted** by returning a command
- Command was **executed** - email sent
- **Zero coupling** between registration and email modules!

---

## Understanding Observer

### The Mental Model: A Home with Residents

Think of your application as a **home** where each module is a **resident** (family member) operating independently:

**Dad's Shopping Trip (Using All 5 Signal Types):**

```php
// 1. PLAN - "I want to go shopping" (intention announced)
class GoingShoppingPlan extends Plan {}
send(GoingShoppingPlan::create('Planning shopping trip', []));
// Family hears the plan, prepares silently (add items to list)
// No blocking - dad continues

// 2. MESSAGE - "I'M GOING TO SHOP!" (broadcast announcement)
class ShoppingAnnouncement extends Message {}
send(ShoppingAnnouncement::create('Going shopping now!', []));
// Everyone knows, reacts independently
// Dad doesn't wait for responses

// 3. EVENT - Door shuts (already happened - past tense)
class DoorShut extends Event {}
$commands = send(DoorShut::create('Dad left', []));
// Too late to add items now!

// 4. COMMAND - "Buy me ice cream!" (instruction)
// Dad listens for commands (COSTLY - synchronization overhead)
foreach ($commands as $cmd) {
    if ($cmd instanceof BuyIceCream) {
        // Dad pays attention, remembers request
    }
}

// 5. INQUIRY - "What time will you be back?" (question)
class WhenWillYouReturn extends Inquiry {}
send(WhenWillYouReturn::create('Expected return time?', []));
// Dad can respond or not
```

### Core Principles

1. **Autonomous Modules**: Like family members, each module works independently
2. **Fire-and-Forget**: Broadcast signals without waiting (like dad announcing)
3. **Boot-Time Wiring**: Subscribe once during startup (like "shopping list on fridge" convention)
4. **Synchronization is Costly**: Returning signals = paying attention (use sparingly)
5. **No Priority Needed**: Why would shopping have priority over laundry? Modules are independent!

### What Observer IS

✅ **Message passing** between autonomous modules
✅ **Module integration** at boot time
✅ **Cross-cutting concerns** (logging, audit) without coupling
✅ **Decoupled architecture** without direct dependencies

### What Observer IS NOT

❌ **Request-response system** (use direct calls)
❌ **Workflow orchestration** (use workflow engine)
❌ **Ad-hoc events**
❌ **Replacement for good architecture**

---

## Common Use Cases

### 1. Module Wiring (Decoupling)

**Problem**: Payment module needs to update invoice, but they shouldn't depend on each other.

**Solution**: Wire them through signals at boot time.

```php
// bootstrap.php

class PaymentReceived extends Event {}
class MarkInvoicePaid extends Command {}

subscribe(
    // Payment module announces payment (fire-and-forget)
    function (PaymentReceived $payment) {
        echo "Payment received for invoice {$payment->details['invoice_id']}\n";

        // Request invoice update (return command)
        return MarkInvoicePaid::create('Mark Paid', [
            'invoice_id' => $payment->details['invoice_id']
        ]);
    },

    // Invoice module processes the command
    function (MarkInvoicePaid $cmd) {
        updateInvoiceStatus($cmd->details['invoice_id'], 'paid');
        echo "Invoice {$cmd->details['invoice_id']} marked as paid\n";
    }
);

// In payment processing code (anywhere)
send(PaymentReceived::create('Payment Received', ['invoice_id' => 'INV-001']));
```

### 2. Cross-Cutting Concerns (Logging, Audit)

**Problem**: Need to log all events without modifying every module.

**Solution**: One handler that listens to all events.

```php
// bootstrap.php

use PhpRepos\Observer\API\{Event, Signal};

// Log ALL events (cross-cutting concern)
subscribe(function (Event $event) {
    file_put_contents('audit.log',
        date('Y-m-d H:i:s') . " - {$event->title}\n",
        FILE_APPEND
    );
});

// Or log EVERYTHING (including commands, plans, etc.)
subscribe(function (Signal $signal) {
    echo "[{$signal->time->format('H:i:s')}] " . get_class($signal) . "\n";
});

// Now every signal in the entire application is automatically logged!
```

### 3. UI Layer Integration

**Problem**: Application needs different output based on UI type (CLI vs Web).

**Solution**: UI layer decides how to present signals.

```php
// bootstrap.php - Application dispatches signals from anywhere

class OrderPlaced extends Event {}
class PaymentProcessed extends Event {}

// CLI Application
if (php_sapi_name() === 'cli') {
    subscribe(function (Signal $signal) {
        // Output to console
        echo "[{$signal->time->format('H:i:s')}] {$signal->title}\n";
    });
}

// Web Application
if (isset($_SERVER['HTTP_HOST'])) {
    subscribe(function (Signal $signal) {
        // Send through WebSocket to browser
        $websocket->send(json_encode([
            'type' => get_class($signal),
            'title' => $signal->title,
            'time' => $signal->time->format('c'),
            'details' => $signal->details
        ]));
    });
}

// Application code (UI-agnostic)
send(OrderPlaced::create('Order Placed', ['order_id' => 'ORD-001']));
send(PaymentProcessed::create('Payment Complete', ['amount' => 99.99]));

// Same signals, different presentation!
```

### 4. Real-World: Payment System Down

**Scenario**: Payment gateway is down - stop processing new orders.

```php
// Signals
class PaymentGatewayDown extends Event {}
class OrderPlaced extends Event {}
class ProcessOrder extends Command {}
class StopOrderProcessing extends Command {}

// State management
$processing_enabled = true;

subscribe(
    // When gateway goes down, stop processing
    function (PaymentGatewayDown $event) use (&$processing_enabled) {
        $processing_enabled = false;
        echo "⚠ Payment gateway down - orders paused\n";

        return StopOrderProcessing::create('Stop Processing', [
            'reason' => 'Payment gateway unavailable'
        ]);
    },

    // Stop processing handler
    function (StopOrderProcessing $cmd) {
        notifyAdmins($cmd->details['reason']);
        pauseOrderQueue();
    },

    // Order handler checks if processing is enabled
    function (OrderPlaced $order) use (&$processing_enabled) {
        if (!$processing_enabled) {
            echo "✗ Order {$order->details['order_id']} queued (processing paused)\n";
            queueOrder($order->details['order_id']);
            return;
        }

        return ProcessOrder::create('Process Order', [
            'order_id' => $order->details['order_id']
        ]);
    }
);

// Simulate: gateway goes down
send(PaymentGatewayDown::create('Gateway Timeout', ['error' => 'Connection timeout']));

// Try to place order
send(OrderPlaced::create('Order Placed', ['order_id' => 'ORD-123']));
// Output: ✗ Order ORD-123 queued (processing paused)
```

### 5. Cascading Actions (Use Sparingly!)

**When**: Critical operations that need tracking (emails, confirmations)
**Cost**: Synchronization overhead - only use when justified

```php
class OrderPlaced extends Event {}
class SendConfirmation extends Command {}
class UpdateInventory extends Command {}

subscribe(
    // RETURN SIGNAL: Email is critical for customer
    function (OrderPlaced $order) {
        return SendConfirmation::create('Order Confirmation', [
            'email' => $order->details['customer_email'],
            'order_id' => $order->details['order_id']
        ]);
    },

    // FIRE-AND-FORGET: Inventory update is independent
    function (OrderPlaced $order) {
        updateInventory($order->details['items']);
        // No return = autonomous operation
    },

    // Process returned commands
    function (SendConfirmation $cmd) {
        mail($cmd->details['email'], 'Order Confirmed', '...');
    }
);
```

---

## Advanced Features

### Union Types (Handle Multiple Signals)

```php
class UserLoggedIn extends Event {}
class UserLoggedOut extends Event {}
class PasswordChanged extends Event {}

// Handle ANY of these events
subscribe(function (UserLoggedIn|UserLoggedOut|PasswordChanged $event) {
    auditLog($event);
});
```

**Performance Tip**: Use parent classes instead for better performance:

```php
abstract class UserSecurityEvent extends Event {}
class UserLoggedIn extends UserSecurityEvent {}
class UserLoggedOut extends UserSecurityEvent {}

// More efficient (one registry entry instead of three)
subscribe(function (UserSecurityEvent $event) {
    auditLog($event);
});
```

### Optional Parameters

```php
class UserLoggedIn extends Event {}
class SessionCreated extends Event {}

// Handler works with or without session
subscribe(function (UserLoggedIn $login, ?SessionCreated $session = null) {
    if ($session) {
        linkSessionToUser($login, $session);
    } else {
        createNewSession($login);
    }
});

// Both work:
send($login);                  // Session is null
send($login, $session);        // Both provided
```

### Multi-Signal Dispatch (Advanced!)

**When**: Complex business rules requiring coordination
**Example**: Only notify when BOTH payment AND shipping succeed

```php
class PaymentProcessed extends Event {}
class ShippingConfirmed extends Event {}
class SendCompleteNotification extends Command {}

// Handler ONLY executes when BOTH signals dispatched together
subscribe(function (
    PaymentProcessed $payment,
    ShippingConfirmed $shipping
) {
    // Both conditions met!
    if ($payment->details['order_id'] === $shipping->details['order_id']) {
        return SendCompleteNotification::create('Order Complete', [
            'order_id' => $payment->details['order_id']
        ]);
    }
});

// Dispatch both together (rare use case)
send($paymentEvent, $shippingEvent);
```

**Performance Note**: Multi-signal is slower than single-signal. Use only when you need transaction-like coordination.

### Intersection Types

```php
interface Loggable {}
interface Auditable {}

class SecurityEvent extends Event implements Loggable, Auditable {}

// Only handles signals that implement BOTH interfaces
subscribe(function (Loggable&Auditable $signal) {
    logAndAudit($signal);
});
```

### SignalType Attribute

```php
use PhpRepos\Observer\API\SignalType;

// Specify type without type hint
subscribe(function (#[SignalType(UserLoggedIn::class)] $event) {
    handleLogin($event);
});
```

---

## Architecture & Philosophy

### Design Goals

1. **Prevent "Event Hell"**: No dynamic subscribe/unsubscribe prevents spaghetti code
2. **Autonomous Modules**: Like Unix philosophy - each does one thing well
3. **Traceable Flow**: Boot-time wiring makes architecture visible
4. **Actor Model for PHP**: Message-passing between independent actors

### Why No Handler Priority?

Because modules are **autonomous** - they don't coordinate. Why would payment processing have priority over logging? They're independent systems that work in parallel, like family members doing separate tasks.

If you need ordering, you're likely trying to orchestrate - use a workflow engine instead.

---

## API Reference

### Signal Types

All signals extend `Signal` class with these properties:
- `id` (string): UUID v4
- `title` (string): Descriptive name
- `time` (DateTimeImmutable): Creation timestamp (UTC)
- `details` (array): Context data

**Creating Signals:**

```php
// Static create method (recommended)
$event = UserLoggedIn::create('User Login', ['user_id' => 123]);

// Factory functions
use function PhpRepos\Observer\API\Signals\event;
$event = event('User Login', ['user_id' => 123]);

// Direct instantiation
$event = new UserLoggedIn(uuid(), 'User Login', now(), ['user_id' => 123]);
```

### Bus Functions

**subscribe(callable ...$handlers): void**
- Registers handlers at boot time
- Handlers must have typed parameters
- Called when matching signals dispatched

**send(Signal ...$signals): array**
- Dispatches signals to matching handlers
- Returns array of signals returned by handlers
- Main dispatch function

**Convenience functions:**
- `broadcast(Event $event): array`
- `order(Command $command): array`
- `propose(Plan $plan): array`
- `ask(Inquiry $inquiry): array`
- `share(Message $message): array`

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for architecture details and development guidelines.

**Start with architecture** to understand the philosophy, then practical contribution steps.

---

## License

MIT License - See [LICENSE](LICENSE) file

---

## Links

- [GitHub](https://github.com/php-repos/observer)
- [phpkg](https://phpkg.com)
- [Logger Package](https://github.com/php-repos/logger)
