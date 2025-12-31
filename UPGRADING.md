# Upgrading from v1.x to v2.0

This guide provides step-by-step instructions for upgrading from Observer v1.x to v2.0.

## Overview

**v2.0 is a namespace reorganization with NO behavior changes.**

- ✅ All functions work the same
- ✅ Same features and capabilities
- ⚠️ Import statements need updating
- ⚠️ Internal signals removed (replaced with logger)

**Estimated time**: 5-10 minutes for most projects

---

## What Changed

### 1. Namespace Reorganization (BREAKING)

All imports moved to new namespaces following Natural Architecture:

| Component | v1.0.0 | v2.0.0 |
|-----------|--------|--------|
| Functions | `Observer\Observer\*` | `API\Bus\*` |
| Signals | `Observer\Signals\*` | `API\*` |
| Attributes | `Observer\Attributes\*` | `API\*` |
| Exceptions | `Observer\Exceptions\*` | `Core\Exceptions\*` |

### 2. Internal Signals Removed (BREAKING)

These classes no longer exist:
- `Signals\Internals\HandlerExecution`
- `Signals\Internals\HandlerFound`
- `Signals\Internals\NoHandlerFound`

**Replacement**: Use logger integration (see below)

### 3. Dependency Changes

- ❌ Removed: `php-repos/datatype` package
- ✅ Added: `php-repos/logger` package

---

## Step-by-Step Upgrade

### Step 1: Update Package

```bash
phpkg update observer --force
phpkg build
```

### Step 2: Update Imports

**Bus Functions (subscribe, send, etc.):**

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
use PhpRepos\Observer\Signals\Signal;
use PhpRepos\Observer\Signals\Event;
use PhpRepos\Observer\Signals\{Command, Plan, Inquiry, Message};

// v2.0.0
use PhpRepos\Observer\API\Signal;
use PhpRepos\Observer\API\Event;
use PhpRepos\Observer\API\{Command, Plan, Inquiry, Message};
```

**SignalType Attribute:**

```php
// v1.0.0
use PhpRepos\Observer\Attributes\SignalType;

// v2.0.0
use PhpRepos\Observer\API\SignalType;
```

**ObserverException:**

```php
// v1.0.0
use PhpRepos\Observer\Exceptions\ObserverException;

// v2.0.0
use PhpRepos\Observer\Core\Exceptions\ObserverException;
```

### Step 3: Replace Internal Signals (If Used)

**If you were using internal signals for debugging:**

```php
// v1.0.0 - Remove this
use PhpRepos\Observer\Signals\Internals\HandlerExecution;
use PhpRepos\Observer\Signals\Internals\HandlerFound;
use PhpRepos\Observer\Signals\Internals\NoHandlerFound;

subscribe(function (HandlerFound $signal) {
    // Debug handler execution
});
```

**v2.0.0 - Use logger integration:**

```php
use PhpRepos\Observer\API\Signal;

// Log all signals for debugging
subscribe(function (Signal $signal) {
    error_log("[Observer] " . get_class($signal) . ": {$signal->title}");
});
```

### Step 4: Test Your Application

```bash
# Run your tests
phpkg run test-runner --version=v4.0.0 run

# Or your application tests
php test.php
```

---

## Automated Migration

### Find-and-Replace Script

Use this bash script to update all imports automatically:

```bash
#!/bin/bash

# Find all PHP files and update namespaces
find . -name "*.php" -type f -exec sed -i \
  -e 's/PhpRepos\\Observer\\Observer\\/PhpRepos\\Observer\\API\\Bus\\/g' \
  -e 's/PhpRepos\\Observer\\Signals\\/PhpRepos\\Observer\\API\\/g' \
  -e 's/PhpRepos\\Observer\\Attributes\\/PhpRepos\\Observer\\API\\/g' \
  -e 's/PhpRepos\\Observer\\Exceptions\\/PhpRepos\\Observer\\Core\\Exceptions\\/g' \
  {} \;

echo "✓ Namespace migration complete"
echo "⚠ Review changes before committing"
echo "⚠ Update internal signal usage manually"
```

**Important**: Review all changes before committing!

### Manual Search-and-Replace

If you prefer IDE find-and-replace, do these in **exact order**:

```
1. use function PhpRepos\Observer\Observer\
   → use function PhpRepos\Observer\API\Bus\

2. use PhpRepos\Observer\Signals\
   → use PhpRepos\Observer\API\

3. use PhpRepos\Observer\Attributes\
   → use PhpRepos\Observer\API\

4. use PhpRepos\Observer\Exceptions\
   → use PhpRepos\Observer\Core\Exceptions\
```

---

## Verification Checklist

After migration, verify:

- [ ] Package updated (`phpkg update observer`)
- [ ] Project builds (`phpkg build`)
- [ ] All imports updated (no `Observer\Observer\*`)
- [ ] Internal signal usage removed/replaced
- [ ] Tests pass
- [ ] Application runs correctly

---

## Troubleshooting

### "Class not found" errors

**Problem**: `Class 'PhpRepos\Observer\Signals\Event' not found`

**Solution**: Update namespace to `PhpRepos\Observer\API\Event`

### "Function not found" errors

**Problem**: `Call to undefined function PhpRepos\Observer\Observer\subscribe()`

**Solution**: Update namespace to `PhpRepos\Observer\API\Bus\subscribe`

### Internal signal errors

**Problem**: `Class 'HandlerExecution' not found`

**Solution**: Replace with logger integration:

```php
use PhpRepos\Observer\API\Signal;

subscribe(function (Signal $signal) {
    // Your logging logic
});
```

### Build fails after update

**Problem**: Build errors after migration

**Solution**:
```bash
rm -rf build/
phpkg build
```

---

## Rollback

If you need to revert to v1.0.0:

```bash
# Revert package
phpkg update observer@1.0.0
phpkg build

# Revert code changes
git checkout HEAD -- .
```

---

## What Stays the Same

✅ All function signatures identical
✅ Handler matching behavior unchanged
✅ Type support (union, intersection, optional) unchanged
✅ Multi-signal dispatch works the same
✅ Signal creation (`::create()`) works the same
✅ Return value collection works the same

**Only imports changed - zero behavior changes!**

---

## Getting Help

- **Issues**: https://github.com/php-repos/observer/issues
- **Discussions**: https://github.com/php-repos/observer/discussions

---

## See Also

- [CHANGELOG.md](CHANGELOG.md) - Complete list of changes
- [README.md](README.md) - Updated documentation and examples
