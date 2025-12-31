# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-01-01

### Breaking Changes

#### Namespace Reorganization

All function and class namespaces have been reorganized to follow Natural Architecture pattern:

**Functions:**
```php
// v1.0.0
use function PhpRepos\Observer\Observer\subscribe;
use function PhpRepos\Observer\Observer\send;
use function PhpRepos\Observer\Observer\{broadcast, propose, ask, share, order};

// v2.0.0
use function PhpRepos\Observer\API\Bus\subscribe;
use function PhpRepos\Observer\API\Bus\send;
use function PhpRepos\Observer\API\Bus\{broadcast, propose, ask, share, order};
```

**Signal Classes:**
```php
// v1.0.0
use PhpRepos\Observer\Signals\{Signal, Event, Command, Plan, Inquiry, Message};

// v2.0.0
use PhpRepos\Observer\API\{Signal, Event, Command, Plan, Inquiry, Message};
```

**Attributes:**
```php
// v1.0.0
use PhpRepos\Observer\Attributes\SignalType;

// v2.0.0
use PhpRepos\Observer\API\SignalType;
```

**Exceptions:**
```php
// v1.0.0
use PhpRepos\Observer\Exceptions\ObserverException;

// v2.0.0
use PhpRepos\Observer\Core\Exceptions\ObserverException;
```

#### Removed Internal Signals

The following internal signals have been removed:

- `PhpRepos\Observer\Signals\Internals\HandlerExecution`
- `PhpRepos\Observer\Signals\Internals\HandlerFound`
- `PhpRepos\Observer\Signals\Internals\NoHandlerFound`

**Migration**: If you were using these for debugging, use the logger integration instead:

```php
// v2.0.0 - Log all signals
use PhpRepos\Observer\API\Signal;

subscribe(function (Signal $signal) {
    error_log(get_class($signal) . ': ' . $signal->title);
});
```

#### Dependency Changes

**Removed:**
- `php-repos/datatype` package dependency

**Added:**
- `php-repos/logger` (v2.0.0) package dependency

### Changed

- Restructured codebase into three layers (API/Core/Platform) for better maintainability
- All internal array operations now use built-in PHP functions instead of external package

### Fixed

- Improved error messages for handler registration failures
- Better type validation for handler parameters

---

## [1.0.0] - 2024-12-30

Initial release.

### Features

- Observer pattern implementation with type-safe signal handling
- Five signal types: Event, Command, Plan, Inquiry, Message
- Handler registration with typed parameters (`subscribe`)
- Signal dispatching (`send`, `broadcast`, `propose`, `ask`, `share`, `order`)
- Support for union types, intersection types, and optional parameters
- Multi-signal dispatch support
- SignalType attribute for custom type specification
- Handler return value collection

---

## Migration Guide: v1.x to v2.0

### Quick Migration

**Step 1**: Update all imports

```bash
# Find and replace in your codebase:
PhpRepos\Observer\Observer\          → PhpRepos\Observer\API\Bus\
PhpRepos\Observer\Signals\           → PhpRepos\Observer\API\
PhpRepos\Observer\Attributes\        → PhpRepos\Observer\API\
PhpRepos\Observer\Exceptions\        → PhpRepos\Observer\Core\Exceptions\
```

**Step 2**: Remove internal signal usage (if any)

```php
// v1.0.0 - Remove these
use PhpRepos\Observer\Signals\Internals\{HandlerExecution, HandlerFound, NoHandlerFound};

// v2.0.0 - Use logger integration instead
use PhpRepos\Observer\API\Signal;
subscribe(function (Signal $signal) {
    // Log all signals for debugging
});
```

**Step 3**: Update package

```bash
phpkg update observer
phpkg build
```

### Behavior Changes

**None** - All functions behave identically. This is purely a reorganization for better architecture.

### See Also

- [UPGRADING.md](UPGRADING.md) - Detailed step-by-step upgrade guide
- [README.md](README.md) - Updated documentation with examples
