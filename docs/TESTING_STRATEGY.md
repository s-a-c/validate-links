# Testing Strategy

This guide provides a step-by-step approach for testing at Unit, Feature, Integration, and End-to-End (E2E) levels.

## 1. Unit Testing

**Purpose:** Test individual functions, methods, and classes in isolation.

**Tools:** Pest, PHPUnit

**Steps:**
1. Identify the smallest testable parts (functions, methods, classes).
2. Write tests for each public method, covering normal, edge, and error cases.
3. Use mocks/stubs for dependencies.
4. Assert expected outputs and side effects.
5. Run tests with `vendor/bin/pest` or `vendor/bin/phpunit`.
6. Review coverage reports in `reports/coverage/`.

## 2. Feature Testing

**Purpose:** Test application features as experienced by users.

**Tools:** Pest, Laravel Zero Test Helpers

**Steps:**
1. Identify user-facing features (CLI commands, validation flows).
2. Write tests simulating user input and command execution.
3. Assert expected outputs, file changes, and error handling.
4. Use test doubles for external services.
5. Run feature tests and review results.

## 3. Integration Testing

**Purpose:** Test interactions between multiple components or services.

**Tools:** Pest, PHPUnit

**Steps:**
1. Identify key integrations (e.g., command + service + provider).
2. Write tests that exercise real interactions (no mocks for internal dependencies).
3. Set up test data and environment as needed.
4. Assert correct data flow and error handling.
5. Run integration tests and review results.

## 4. End-to-End (E2E) Testing

**Purpose:** Test the entire application workflow from start to finish.

**Tools:** Pest, custom E2E scripts

**Steps:**
1. Define critical user journeys (e.g., validate links, generate reports).
2. Prepare test scenarios and data.
3. Execute CLI commands as a user would.
4. Assert final outputs, reports, and side effects.
5. Clean up test artifacts.
6. Review E2E results and coverage.

## General Best Practices
- Use descriptive test names and clear assertions.
- Keep tests independent and repeatable.
- Automate test runs in CI/CD pipelines.
- Update tests with code changes.
- Document test cases and expected outcomes.

## Troubleshooting
- Review error logs and reports in `reports/`.
- Use static analysis tools (PHPStan, Psalm) for additional validation.
- Refactor tests for clarity and maintainability.
