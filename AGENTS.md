# AGENTS.md

This guide helps agentic coding assistants work effectively with this PHP codebase.

## Build/Lint/Test Commands

### Running Commands (Makefile)
- `make test` - Run all tests (supports args: `make test args="--filter=testName"`)
- `make phpstan` - Run PHPStan static analysis (supports args: `make phpstan args="--level=0 --memory-limit=256M"`)
- `make phpcs` - Run PHP CodeSniffer linting (supports args: `make phpcs args="--standard=PSR12 src/"`)
- `make infection` - Run Infection mutation testing (supports args: `make infection args="--test-framework=phpunit --only-covering-test-cases"`)
- `make check` - Run all checks (test, phpstan, phpcs)
- `make serve` - Start application via Docker Compose
- `make sh` - Open shell in Docker container

**Note**: Makefile uses `-it` flags by default. For non-interactive TTY environments (CI/agents), use `CI=true make <target>`:
```bash
CI=true make test
CI=true make phpstan args="--level=0 --memory-limit=256M"
CI=true make phpcs args="--standard=PSR12 src/"
CI=true make infection args="--test-framework=phpunit --only-covering-test-cases"
CI=true make test args="--filter=testMethodName"
CI=true make check
```
Or run Composer scripts directly without Docker.

### Running Commands (Composer scripts)
- `composer test` - Run all PHPUnit tests
- `composer test-coverage` - Run tests with HTML coverage report (saved to storage/coverage/)
- `composer phpstan` - Run PHPStan at max level
- `composer phpcs` - Run PHPCS with PSR-12 standard
- `composer test-infection` - Run Infection mutation testing with HTML report (saved to storage/infection/)
- `composer check` - Run all checks sequentially

### Running Single Test
To run a specific test:
```bash
# Via Docker with CI mode (recommended for agents)
CI=true make test args="--filter=testMethodName"

# Via Composer (direct, no Docker needed)
composer test -- --filter=testMethodName

# Run specific test file
composer test -- test/src/Controller/HomeActionTest.php

# Additional examples:
CI=true make phpstan args="--level=0 --memory-limit=256M"
CI=true make phpcs args="--standard=PSR12 src/"
CI=true make infection args="--test-framework=phpunit --only-covering-test-cases"
```

## Code Style Guidelines

### File Structure
- All PHP files must start with `<?php`
- All files must declare `declare(strict_types=1);`
- Namespace follows PSR-4: `S3\Log\Viewer\` prefix
- Test namespace: `Test\S3\Log\Viewer\` or `Test\Support\S3\Log\Viewer\`

### Class Design
- Use `readonly` for simple immutable DTO classes
- Use `final` for classes that shouldn't be extended
- Use interfaces for contracts (e.g., `ActionHandler`, `LogStorage`)
- Prefer constructor property promotion with visibility modifiers
- Keep classes focused and single-responsibility

### Type System
- Full type declarations required on all methods and properties
- Use PHPDoc annotations for complex array shapes: `@param array{key: type, ...} $var`
- Use PHPDoc for return types when PHP types aren't sufficient
- PHPStan runs at max level - ensure all types are correct
- Import exceptions for try-catch blocks (e.g., `JsonException`, `PDOException`)

### Naming Conventions
- Classes: PascalCase (e.g., `HomeAction`, `LogService`, `GenericEventDispatcher`)
- Methods: camelCase (e.g., `createChannelStream`, `addLog`, `handleStream`)
- Test methods: `testMethodName_ShouldExpectedBehavior()` or snake_case allowed
- Interfaces: Describe capability (e.g., `ActionHandler`, `EventHandler`)
- Properties: camelCase private/protected, snake_case for database columns

### Import Organization
Order imports by category:
1. Standard library types (e.g., `use PDO;`, `use JsonException;`)
2. Third-party library types (e.g., `use React\Http\Message\Response;`)
3. Local types (e.g., `use S3\Log\Viewer\Controller\HomeAction;`)
4. `use function` statements at the bottom (e.g., `use function FastRoute\simpleDispatcher;`)

### Code Formatting (PSR-12)
- Line length: 180 characters (excludes comments, test files)
- Use short array syntax: `[]` not `array()`
- Space after `!` operator
- No trailing whitespace
- Indent with 4 spaces
- One blank line between methods, two blank lines between classes

### Arrow Functions (fn)
Use arrow functions (`fn () =>`) for single-line functions instead of traditional closure syntax:

**Preferred:**
```php
$next = fn () => throw new RuntimeException('Error message');
$mapper = fn (array $entry) => $entry['level'];
```

**Avoid:**
```php
$next = function () {
    throw new RuntimeException('Error message');
};
```

**When to use:**
- Test callbacks (see ErrorHandlerMiddlewareTest.php for examples)
- Middleware next handlers
- Simple array transformations
- Any function that can be expressed in a single expression

**Return type inference:** Arrow functions automatically capture variables from parent scope, making them ideal for callbacks.

### Error Handling
- Use try-catch for recoverable exceptions (e.g., `JsonException`, `PDOException`)
- For database operations, set `PDO::ATTR_ERRMODE` to `PDO::ERRMODE_EXCEPTION`
- Return empty array/error value for silent failures in specific cases (see LogStorageSQLite:84)
- Use `@throws` PHPDoc only for documented public API exceptions

### Database/SQL
- Use PDO with prepared statements for all queries
- Use heredoc syntax for multi-line SQL: `<<<SQL ... SQL;`
- Bind parameters with `bindValue()` or `bindParam()`
- Always use FTS5 full-text search when searching text fields
- Set foreign keys: `PRAGMA foreign_keys = ON`

### Testing (PHPUnit 12.5)
- All test files end with `Test.php` suffix
- Extend `PHPUnit\Framework\TestCase`
- Use `createStub()` for simple test doubles without expectations
- Use `createMock()` when setting up expectations with `expects()`
- Use `#[DataProvider('methodName')]` attribute for data-driven tests
- Data providers return `Generator` yielding test case arrays
- Test names should describe behavior: `testMethod_ShouldDoX()`
- Use `assertSame()` for value equality, `assertInstanceOf()` for type checking
- Avoid implementation details in assertions; focus on observable behavior
- **Avoid trailing whitespace** - PSR-12 requires no whitespace at end of lines (detected by PHPCS Squiz.WhiteSpace.SuperfluousWhitespace.EndLine)
- **Use PHPUnit attributes** for setup/teardown: `#[Before]` and `#[After]` instead of `setUp()` and `tearDown()` methods
- When using `#[Before]`/`#[After]`, methods must be `protected` or `public`

### Common Pitfalls
- **Trailing whitespace**: When adding new test methods or code blocks, ensure no spaces at end of lines. PHPCS will fail with "Whitespace found at end of line" error.
- **Mutation testing**: Use `CI=true make infection` or `composer test-infection` to run Infection. Tests added for mutation testing should explicitly verify the behavior being mutated (e.g., boundary conditions, default parameter values).
- **Make test verification**: After completing a phase, always run `make test`, `make check`, and `CI=true make infection` to ensure all checks pass before proceeding.
- **Command argument support**: All make commands support optional arguments via `args="..."` parameter for flexible usage in CI/automation workflows.

### Event System
- Events are plain classes with readonly properties
- Use `#[EventHandler]` attribute on methods that handle events
- Event handlers must have exactly one parameter of class type
- Single parameter type determines which event is handled
- Methods named `handle{EventClass}()` or `on{EventClass}()`

### HTTP/Controllers
- Controllers implement `ActionHandler` interface with `__invoke(ServerRequestInterface $request): ResponseInterface`
- Use `React\Http\Message\Response` for creating responses
- Return structured data via JSON for API endpoints
- Use view models for HTML responses
- Path parameters from request attributes, query params from request query params
