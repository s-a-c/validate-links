# PHPStan Level 10 Test Compliance Automation Scripts

This directory contains automation scripts to help fix the 5,156 PHPStan Level 10 errors across all test files.

## 🚀 Quick Start

### Step 1: Preview Changes (Dry Run)
```bash
php scripts/simple-pest-fixes.php --dry-run
```

### Step 2: Apply Safe Bulk Fixes
```bash
php scripts/simple-pest-fixes.php
```

### Step 3: Check Progress
```bash
./vendor/bin/phpstan analyse tests/ --level=10 --no-progress | grep "Found.*error"
```

## 📁 Available Scripts

### `simple-pest-fixes.php` (Recommended)
**Purpose**: Handles the most common and safe bulk replacements

**What it fixes**:
- ✅ `$this->property` → `$property` (variable references)
- ✅ `mock(Class::class)` → `Mockery::mock(Class::class)`
- ✅ Adds missing `use Mockery;` imports

**Usage**:
```bash
# Preview changes without modifying files
php scripts/simple-pest-fixes.php --dry-run

# Apply changes to all test files
php scripts/simple-pest-fixes.php

# Apply changes to specific directory
php scripts/simple-pest-fixes.php --dir tests/Unit
```

**Expected Impact**: Should fix ~2,000-3,000 of the 5,156 errors

### `phpstan-test-fixes.php` (Advanced)
**Purpose**: More comprehensive fixes including JSON operations

**What it fixes**:
- ✅ All fixes from simple-pest-fixes.php
- ✅ `json_encode()` type safety with `File::put()`
- ✅ `json_decode()` type assertions
- ✅ Helper method call conversions

**Usage**:
```bash
php scripts/phpstan-test-fixes.php
```

## 🔧 Manual Steps Required After Automation

The scripts handle the bulk replacements, but some patterns require manual intervention:

### 1. Add `use` Clauses to Test Functions

**Before** (Broken):
```php
it('test name', function () {
    expect(File::exists($projectDir))->toBeTrue();
});
```

**After** (Fixed):
```php
it('test name', function () use (&$projectDir) {
    expect(File::exists($projectDir))->toBeTrue();
});
```

### 2. Add Variable Declarations to Describe Blocks

**Before** (Missing):
```php
describe('Test Suite', function () {
    beforeEach(function () {
        // ...
    });
});
```

**After** (Fixed):
```php
/** @var string */
$projectDir = '';
/** @var string */
$docsDir = '';

describe('Test Suite', function () use (&$projectDir, &$docsDir) {
    beforeEach(function () use (&$projectDir, &$docsDir) {
        // ...
    });
});
```

### 3. Fix Helper Function Calls

**Before** (Broken):
```php
$this->createRealisticDocumentation();
```

**After** (Fixed):
```php
createRealisticDocumentation($docsDir);
```

### 4. Add Type Assertions for Mixed Types

**Before** (Mixed type):
```php
$data = json_decode($content, true);
expect($data['key'])->toBe('value');
```

**After** (Type safe):
```php
$data = json_decode($content, true);
expect($data)->toBeArray();
expect($data['key'])->toBe('value');
```

## 📊 Expected Progress by Phase

### Phase 1: Automation (Scripts)
- **Target**: 2,000-3,000 errors fixed
- **Time**: 5-10 minutes
- **Remaining**: ~2,000-3,000 errors

### Phase 2: Manual Use Clauses
- **Target**: 1,000-1,500 errors fixed  
- **Time**: 2-4 hours
- **Remaining**: ~1,000-1,500 errors

### Phase 3: Type Safety & Edge Cases
- **Target**: All remaining errors
- **Time**: 4-8 hours
- **Remaining**: 0 errors

## 🎯 Validation Commands

### Check Overall Progress
```bash
./vendor/bin/phpstan analyse tests/ --level=10 --no-progress | grep "Found.*error"
```

### Check Specific Directories
```bash
./vendor/bin/phpstan analyse tests/E2E/ --level=10 --no-progress | grep "Found.*error"
./vendor/bin/phpstan analyse tests/Unit/ --level=10 --no-progress | grep "Found.*error"
```

### Ensure Tests Still Pass
```bash
./vendor/bin/pest
```

## 🚨 Safety Notes

1. **Backup First**: The scripts modify files in place. Consider committing current state first.

2. **Test After Changes**: Always run `./vendor/bin/pest` after applying fixes to ensure tests still work.

3. **Incremental Approach**: Start with `--dry-run` to preview changes.

4. **Directory-Specific**: Use `--dir` flag to fix one directory at a time for better control.

## 🔍 Troubleshooting

### Script Errors
```bash
# Check PHP syntax
php -l scripts/simple-pest-fixes.php

# Run with error reporting
php -d display_errors=1 scripts/simple-pest-fixes.php --dry-run
```

### Unexpected Results
```bash
# Revert changes if needed
git checkout -- tests/

# Try directory-specific fixes
php scripts/simple-pest-fixes.php --dir tests/E2E --dry-run
```

### PHPStan Still Shows Errors
The scripts handle bulk patterns. Remaining errors typically need manual fixes:
- Complex type annotations
- Specific Pest framework issues  
- Custom helper function signatures
- Edge case type safety issues

## 📈 Success Metrics

**Goal**: Achieve 0 PHPStan Level 10 errors across all test files

**Current**: 5,156 errors
**Target**: 0 errors

**Validation**:
```bash
./vendor/bin/phpstan analyse tests/ --level=10 --no-progress
# Should show: "No errors"
```
