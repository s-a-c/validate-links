# Development Standards

## Code Style
- Follow PSR-12 coding standards for PHP.
- Use meaningful variable, class, and method names.
- Keep functions and classes small and focused.
- Use type hints and strict types wherever possible.

## Architecture Patterns
- Organize code by domain: Commands, Services, Contracts, Enums, Exceptions, Providers.
- Use dependency injection for services and providers.
- Favor composition over inheritance.
- Write reusable, testable components.

## Workflow
- Use feature branches for new work.
- Commit frequently with clear, descriptive messages.
- Run tests and static analysis before pushing changes.
- Submit pull requests with a summary of changes and testing evidence.

## Error Handling
- Use custom exceptions for domain-specific errors.
- Log errors with context for debugging.
- Validate inputs and handle edge cases gracefully.

## Documentation
- Document all public classes, methods, and functions.
- Keep documentation up-to-date with code changes.
