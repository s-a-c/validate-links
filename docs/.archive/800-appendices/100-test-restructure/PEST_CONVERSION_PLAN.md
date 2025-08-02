# Pest Syntax Conversion Plan

## Overview
Convert all PHPUnit-style tests to Pest syntax throughout the entire test suite.

## Files to Convert

### Commands (6 files)
- [x] BaseValidationCommandTest.php (PARTIALLY CONVERTED)
- [x] ConfigCommandTest.php (PARTIALLY CONVERTED)
- [ ] FixCommandTest.php
- [ ] InspireCommandTest.php
- [ ] ReportCommandTest.php
- [ ] ValidateCommandTest.php

### Contracts (4 files)
- [ ] ExtensionInterfaceTest.php
- [ ] LinkValidationInterfaceTest.php
- [ ] ReportingInterfaceTest.php
- [ ] ServiceContractsTest.php

### Exceptions (3 files)
- [ ] ConfigurationExceptionTest.php
- [ ] RemainingExceptionsTest.php
- [ ] ValidateLinksExceptionTest.php

### Providers (2 files)
- [ ] AppServiceProviderTest.php
- [ ] ValidateLinksServiceProviderTest.php

### Services (2 files)
- [ ] LinkValidationServiceTest.php
- [ ] SecurityValidationServiceTest.php

### Other (3 files)
- [ ] CreatesApplicationTest.php
- [ ] ExampleTest.php
- [ ] ValidateCommandTesst.php (typo in filename)

## Conversion Pattern

### From PHPUnit to Pest

#### Class Structure
```php
// PHPUnit
class SomeTest extends TestCase
{
    use SomeTrait;
    
    protected function setUp(): void { ... }
    protected function tearDown(): void { ... }
    
    /** @test */
    public function it_does_something(): void { ... }
}

// Pest
use SomeClass;
use Tests\Traits\SomeTrait;

uses(SomeTrait::class);

describe('SomeClass', function () {
    beforeEach(function () { ... });
    afterEach(function () { ... });
    
    test('it does something', function () { ... });
});
```

#### Assertions
```php
// PHPUnit
$this->assertEquals($expected, $actual);
$this->assertInstanceOf(SomeClass::class, $object);
$this->assertTrue($condition);
$this->expectException(Exception::class);

// Pest
expect($actual)->toBe($expected);
expect($object)->toBeInstanceOf(SomeClass::class);
expect($condition)->toBeTrue();
expect(fn() => $code)->toThrow(Exception::class);
```

#### Test Methods
```php
// PHPUnit
/** @test */
public function it_does_something(): void
{
    // test code
}

// Pest
test('it does something', function () {
    // test code
});
```

## Implementation Strategy

1. **Convert one file at a time** to ensure accuracy
2. **Test each conversion** to ensure functionality is preserved
3. **Update trait usage** to use `uses()` function
4. **Convert assertions** to Pest expectations
5. **Update test structure** to use `describe()` and `test()` functions
6. **Handle setup/teardown** with `beforeEach()` and `afterEach()`

## Key Conversion Rules

1. Remove `namespace` declarations
2. Convert `use` statements for test classes to direct imports
3. Replace `class XTest extends TestCase` with `describe('X', function () { ... })`
4. Convert `use TraitName;` to `uses(TraitName::class);`
5. Replace `setUp()` with `beforeEach(function () { ... })`
6. Replace `tearDown()` with `afterEach(function () { ... })`
7. Convert `/** @test */ public function test_name()` to `test('name', function () { ... })`
8. Replace PHPUnit assertions with Pest expectations
9. Handle exception testing with `->throws()` or `expect(fn() => ...)->toThrow()`

## Status
- **In Progress**: Converting command tests
- **Next**: Convert contract tests, then exceptions, providers, and services
- **Goal**: All tests use consistent Pest syntax
