# Test Execution Guide

## Overview

This guide provides comprehensive instructions for executing the complete test suite for the validate-links Laravel Zero console application. The test suite includes Unit, Integration, Feature, End-to-End (E2E), and Architecture tests using Pest 4 testing framework.

## Test Suite Structure

### 1. Unit Tests (`tests/Unit/`)
- **Purpose**: Test individual classes and methods in isolation
- **Coverage**: Services, Value Objects, Enums, Exceptions
- **Files**: 
  - `LinkValidationServiceTest.php` - Core validation logic
  - `ValidationResultCollectionTest.php` - Collection behavior
  - `ValidationConfigTest.php` - Configuration handling
  - `EnumTest.php` - Enum functionality
  - `ExceptionTest.php` - Exception handling

### 2. Integration Tests (`tests/Integration/`)
- **Purpose**: Test component interactions and service integrations
- **Coverage**: Service layer interactions, file system operations, command integrations
- **Files**:
  - `ServiceIntegrationTest.php` - Service layer integration
  - `CommandIntegrationTest.php` - Command execution integration

### 3. Feature Tests (`tests/Feature/`)
- **Purpose**: Test complete features from user perspective
- **Coverage**: Command-line interface, file processing, report generation
- **Files**:
  - `ValidateCommandTest.php` - Main validation command
  - `ReportGenerationTest.php` - Report output functionality

### 4. End-to-End Tests (`tests/E2E/`)
- **Purpose**: Test complete user scenarios and workflows
- **Coverage**: Real-world usage patterns, performance, error handling
- **Files**:
  - `CompleteUserScenarioTest.php` - Full workflow testing
  - `PerformanceAndLoadTest.php` - Performance validation
  - `ErrorHandlingTest.php` - Error scenario testing

### 5. Architecture Tests (`tests/Architecture/`)
- **Purpose**: Enforce architectural constraints and code quality
- **Coverage**: Dependency structure, naming conventions, design patterns
- **Files**:
  - `CodeQualityTest.php` - Code quality enforcement
  - `DependencyStructureTest.php` - Dependency validation

## Execution Commands

### Run All Tests
```bash
./vendor/bin/pest
```

### Run Tests by Type
```bash
# Unit Tests
./vendor/bin/pest tests/Unit/

# Integration Tests  
./vendor/bin/pest tests/Integration/

# Feature Tests
./vendor/bin/pest tests/Feature/

# End-to-End Tests
./vendor/bin/pest tests/E2E/

# Architecture Tests
./vendor/bin/pest tests/Architecture/
```

### Run Specific Test Files
```bash
# Single test file
./vendor/bin/pest tests/Unit/LinkValidationServiceTest.php

# Multiple test files
./vendor/bin/pest tests/Unit/LinkValidationServiceTest.php tests/Integration/ServiceIntegrationTest.php
```

### Run Tests with Coverage
```bash
# Generate coverage report
./vendor/bin/pest --coverage

# Coverage with minimum threshold
./vendor/bin/pest --coverage --min=90

# Coverage report in HTML format
./vendor/bin/pest --coverage-html=reports/coverage
```

### Run Tests with Filtering
```bash
# Run tests matching pattern
./vendor/bin/pest --filter="validation"

# Run tests in specific group
./vendor/bin/pest --group=integration

# Exclude specific groups
./vendor/bin/pest --exclude-group=slow
```

## Test Configuration

### Environment Setup
Tests use a separate environment configuration:
- **Environment File**: `.env.testing`
- **Database**: In-memory SQLite (if needed)
- **Logging**: Disabled for performance
- **Cache**: Array driver

### Test Data
- **Temporary Files**: Created in `sys_get_temp_dir()`
- **Sample Documents**: Generated programmatically
- **Mock Services**: Used for external dependencies

## Quality Assurance

### PHPStan Analysis
```bash
# Analyze application code (Level 10 compliant)
./vendor/bin/phpstan analyse --level=10 app/

# Analyze all code including tests
./vendor/bin/phpstan analyse --level=10
```

### Code Style
```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

## Performance Considerations

### Test Execution Time
- **Unit Tests**: ~2-5 seconds
- **Integration Tests**: ~10-15 seconds  
- **Feature Tests**: ~15-20 seconds
- **E2E Tests**: ~30-60 seconds (includes external calls)
- **Architecture Tests**: ~5-10 seconds

### Optimization Tips
1. **Parallel Execution**: Use `--parallel` flag for faster execution
2. **Test Filtering**: Run specific test suites during development
3. **Coverage Exclusions**: Exclude vendor and test files from coverage
4. **Mock External Services**: Use mocks to avoid network calls

## Troubleshooting

### Common Issues

#### 1. External Network Dependencies
**Problem**: E2E tests fail due to network issues
**Solution**: 
```bash
# Skip external tests
./vendor/bin/pest --exclude-group=external

# Use local test environment
export APP_ENV=testing
```

#### 2. File Permission Issues
**Problem**: Tests fail creating temporary files
**Solution**:
```bash
# Ensure temp directory is writable
chmod 755 /tmp
```

#### 3. Memory Limits
**Problem**: Large test suites exceed memory
**Solution**:
```bash
# Increase memory limit
php -d memory_limit=512M ./vendor/bin/pest
```

### Debug Mode
```bash
# Run tests with verbose output
./vendor/bin/pest -v

# Run tests with debug information
./vendor/bin/pest --debug
```

## Continuous Integration

### GitHub Actions
The test suite is configured to run automatically on:
- Pull requests
- Push to main branch
- Scheduled daily runs

### Test Matrix
- **PHP Versions**: 8.1, 8.2, 8.3
- **Operating Systems**: Ubuntu, macOS, Windows
- **Dependencies**: Lowest, Latest

## Reporting

### Test Results
- **Console Output**: Real-time test results
- **JUnit XML**: `reports/junit.xml`
- **Coverage Reports**: `reports/coverage/`
- **PHPStan Reports**: `reports/phpstan/`

### Metrics Tracking
- **Test Count**: Total number of tests
- **Coverage Percentage**: Code coverage metrics
- **Execution Time**: Performance benchmarks
- **Quality Score**: PHPStan compliance level

## Best Practices

### Writing Tests
1. **Follow AAA Pattern**: Arrange, Act, Assert
2. **Use Descriptive Names**: Clear test method names
3. **Test One Thing**: Single responsibility per test
4. **Use Data Providers**: For testing multiple scenarios
5. **Mock External Dependencies**: Avoid network calls in unit tests

### Maintenance
1. **Regular Updates**: Keep test dependencies current
2. **Performance Monitoring**: Track test execution times
3. **Coverage Goals**: Maintain high coverage percentages
4. **Documentation**: Update guides when adding new tests

## Support

For questions or issues with the test suite:
1. Check this documentation
2. Review test file comments
3. Examine existing test patterns
4. Consult Pest documentation: https://pestphp.com/
