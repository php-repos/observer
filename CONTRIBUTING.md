# Contributing to Observer

Thank you for your interest in contributing! This guide will help you understand the **philosophy and architecture** before diving into code.

---

## Table of Contents

1. [Architecture & Philosophy](#architecture--philosophy) - **Start here!**
2. [Real-World Examples](#real-world-examples)
3. [Development Setup](#development-setup)
4. [Making Changes](#making-changes)
5. [Pull Request Process](#pull-request-process)

---

## Architecture & Philosophy

### The Core Idea

Observer implements a **message-passing architecture** for autonomous module integration.

**It is NOT**:
- ❌ A general-purpose event system
- ❌ For ad-hoc, request-scoped events
- ❌ A workflow orchestration tool

**It IS**:
- ✅ Module integration at boot time
- ✅ Message passing between independent components
- ✅ Permanent connections between modules

### The Home Analogy

Your application is a **home** with **residents** (modules). Each resident is autonomous:

```php
// Dad announces: "I want to go shopping" (Plan)
send(GoingShoppingPlan::create(...));
// Family hears, adds items to list independently
// No blocking, no waiting

// Dad shouts: "I'M GOING!" (Message)
send(ShoppingAnnouncement::create(...));
// Fire-and-forget broadcast

// Door shuts (Event - already happened)
send(DoorShut::create(...));

// Child: "Buy ice cream!" (Command - costly synchronization)
$commands = send(DoorShut::create(...));
foreach ($commands as $cmd) {
    // Dad actively listens (pays cognitive cost)
}
```

**Key Insight**: Family members (modules) work independently. No priority, no coordination, no orchestration.

### Natural Architecture (API/Core/Platform)

```
┌─────────────────────┐
│   API Layer         │  User-facing functions (subscribe, send)
├─────────────────────┤
│   Core Layer        │  Business logic (handler matching, dispatch)
├─────────────────────┤
│   Platform Layer    │  Primitives (reflection, arrays, UUIDs)
└─────────────────────┘
```

**Rules**:
- API calls Core, Core calls Platform
- Platform never calls Core or API
- Each layer has single responsibility

**Why**: Testability, maintainability, clear boundaries

---

## Real-World Examples

Understanding through examples from the test suite:

### Example 1: Payment Gateway Down

**Scenario**: Payment service unavailable - stop processing orders

```php
class PaymentGatewayDown extends Event {}
class OrderPlaced extends Event {}
class StopOrderProcessing extends Command {}

$processing_enabled = true;

subscribe(
    // React to gateway failure
    function (PaymentGatewayDown $e) use (&$processing_enabled) {
        $processing_enabled = false;
        return StopOrderProcessing::create('Stop', ['reason' => $e->details['error']]);
    },

    // Order processing checks state
    function (OrderPlaced $order) use (&$processing_enabled) {
        if (!$processing_enabled) {
            queueOrder($order);  // Defer processing
            return;
        }
        processOrder($order);
    }
);
```

**Key Points**:
- Modules react independently
- State managed via closure variables
- Fire-and-forget for events, return command for critical actions

### Example 2: Exception Handling

**Scenario**: Handler throws exception, stops processing

```php
subscribe(
    function (PaymentProcessed $payment) {
        if (!isset($payment->details['amount'])) {
            throw new \Exception('Amount required');
        }
        processPayment($payment);
    },

    function (PaymentProcessed $payment) {
        // This won't execute if first handler throws
        logPayment($payment);
    }
);

try {
    send($payment);
} catch (\Exception $e) {
    handleError($e);
}
```

**Key Points**:
- Exceptions stop handler chain
- Caller decides error handling strategy
- No silent failures

### Example 3: Multi-Signal Coordination

**Scenario**: Only notify when BOTH payment AND shipping confirm

```php
subscribe(function (PaymentProcessed $payment, ShippingConfirmed $shipping) {
    // Only executes when BOTH signals dispatched together
    if ($payment->details['order_id'] === $shipping->details['order_id']) {
        return SendCompleteNotification::create(...);
    }
});

// Must dispatch together
send($paymentEvent, $shippingEvent);
```

**Key Points**:
- Transaction-like coordination
- Rare use case, but powerful
- Performance cost justified by business need

---

## Development Setup

### Requirements

- PHP 8.3+
- [phpkg](https://phpkg.com)

### Installation

```bash
# Clone repository
git clone https://github.com/php-repos/observer.git
cd observer

# Build
phpkg build

# Run tests
phpkg run test-runner run
phpkg run test-runner run --filter=FilenameTest
```

### Project Structure

```
Source/
├── API/              # Client-facing interface
│   ├── Bus.php       # subscribe, send, broadcast, etc.
│   ├── Signals.php   # Factory functions
│   ├── Signal.php    # Base signal class
│   ├── Event.php     # Event signal
│   ├── Command.php   # Command signal
│   ├── Plan.php      # Plan signal
│   ├── Inquiry.php   # Inquiry signal
│   ├── Message.php   # Message signal
│   └── SignalType.php
├── Core/
│   ├── Data/
│   │   └── Registry.php      # Handler storage
│   ├── Exceptions/
│   │   └── ObserverException.php
│   ├── Handlers.php          # Handler registration logic
│   └── Dispatchers.php       # Signal dispatch logic
└── Platform/
    ├── DateTimes.php         # DateTime primitives
    ├── Identifiers.php       # UUID generation
    ├── Reflections.php       # Reflection utilities
    └── Arrays.php            # Array operations

Tests/
├── ObserverTest.php          # Feature tests
└── SignalTest.php            # Signal class tests
```

---

## Making Changes

### Testing Philosophy

We test **features from the user perspective**, not implementation details.

**Good Test** (feature-based):
```php
test('handler executes when parameter type matches signal', function () {
    $executed = false;

    subscribe(function (UserLoggedIn $event) use (&$executed) {
        $executed = true;
    });

    send(UserLoggedIn::create('Login', []));

    assert_true($executed);
});
```

**Bad Test** (implementation-based):
```php
test('registry stores handler by condition count', function () {
    // Testing internal implementation - will break on refactoring
});
```

### Adding a New Feature

**Example**: Add signal filtering capability

**1. Understand Architecture Impact**
- Is this API, Core, or Platform change?
- Does it align with boot-time wiring philosophy?
- Does it maintain module autonomy?

**2. Write Feature Test First**
```php
test('handler only executes when filter returns true', function () {
    $count = 0;

    subscribe(
        function (Event $e) use (&$count) {
            if ($e->details['priority'] === 'high') {
                $count++;
            }
        }
    );

    send(Event::create('Low', ['priority' => 'low']));   // Not counted
    send(Event::create('High', ['priority' => 'high'])); // Counted

    assert_equal(1, $count);
});
```

**3. Implement Minimally**
- Add to appropriate layer
- Keep functions small (<30 lines)
- Update PHPDoc

**4. Update Documentation**
- Add to README if user-facing
- Update CHANGELOG

### Code Standards

**Functions**:
- Small, focused (one thing well)
- Descriptive names (`is_union_type`, not `check_type`)
- PHPDoc with `@param`, `@return`, `@throws`

**Example**:
```php
/**
 * Checks if a type is a union type.
 *
 * @param ReflectionType|null $type The type to check
 * @return bool True if union type
 */
function is_union_type(?ReflectionType $type): bool
{
    return $type instanceof ReflectionUnionType;
}
```

**Namespaces**:
- Functions: `namespace PhpRepos\Observer\{Layer}\{Domain};`
- Classes: PascalCase
- Functions: snake_case (functional programming convention)

---

## Pull Request Process

### Before Submitting

1. **Tests Pass**: `phpkg run test-runner run`
2. **Build Succeeds**: `phpkg build`
3. **Feature Tests Added**: For new functionality
4. **Documentation Updated**: README, CHANGELOG

### PR Title Format

```
feat: Add signal filtering capability
fix: Handler matching with nullable union types
docs: Clarify multi-signal dispatch use cases
refactor: Simplify handler registration logic
```

### PR Description Template

```markdown
## Problem
What issue does this solve? What use case does it enable?

## Solution
How does this change solve it?

## Breaking Changes
Are there any breaking changes? Migration path?

## Testing
How to test this change?

## Examples
Code examples demonstrating the feature

## Checklist
- [ ] Tests pass
- [ ] Documentation updated
- [ ] CHANGELOG updated
```

### Review Process

1. **Automated Tests**: Must pass
2. **Code Review**: Maintainer reviews
3. **Philosophy Alignment**: Does it fit Observer's purpose?
4. **Merge**: Once approved

---

## Questions?

- **Architecture Questions**: Open a discussion
- **Bug Reports**: Create an issue
- **Feature Ideas**: Propose in discussions first

Thank you for contributing to Observer! 🎉
