# Contributing to Laminas Events

Thank you for your interest in contributing to the Laminas Events integration for CmdBus! This document provides guidelines and instructions for contributing.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [How to Contribute](#how-to-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)
- [Pull Request Process](#pull-request-process)

## Code of Conduct

This project adheres to a Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

### Our Standards

- Be respectful and inclusive
- Welcome newcomers and help them learn
- Focus on what is best for the community
- Show empathy towards others

## Getting Started

### Ways to Contribute

- **Report Bugs**: Found a bug? Open an issue
- **Suggest Features**: Have an idea? Let us know
- **Fix Issues**: Check open issues and submit PRs
- **Improve Documentation**: Help make docs better
- **Write Examples**: Share real-world usage examples
- **Answer Questions**: Help others in discussions

### Before You Start

1. Check existing issues and PRs to avoid duplicates
2. For major changes, open an issue first to discuss
3. Make sure you agree with the project's license (BSD-3-Clause)

## How to Contribute

### Reporting Bugs

When reporting bugs, include:

- PHP version
- Package version
- Minimal reproduction code
- Expected vs actual behavior
- Full error message and stack trace

**Template:**

```markdown
## Bug Description
Brief description of the bug

## Environment
- PHP version: 8.2.0
- Package version: 0.2.0
- Framework: Mezzio 3.x

## Reproduction

```php
// Minimal code to reproduce
```

## Expected Behavior

What should happen

## Actual Behavior

What actually happens

## Error Message

```text
Full error message and stack trace
```

### Suggesting Features

When suggesting features, include:

- Use case and motivation
- Proposed API or implementation
- Examples of usage
- Potential impact on existing code

### Submitting Changes

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Update documentation
6. Submit a pull request

## Development Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git

### Setup Steps

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/laminas-events.git
cd laminas-events

# Install dependencies
composer install

# Run tests to verify setup
composer test
```

### Project Structure

```text
laminas-events/
├── src/                    # Source code
│   ├── ConfigProvider.php
│   ├── PreHandleEvent.php
│   ├── PostHandleEvent.php
│   ├── Container/
│   ├── Exception/
│   └── Middleware/
├── test/                   # Tests
│   ├── unit/
│   └── integration/
├── docs/                   # Documentation
└── vendor/                 # Dependencies
```

## Coding Standards

### Code Style

This project follows [Laminas Coding Standard](https://github.com/laminas/laminas-coding-standard).

```bash
# Check code style
composer cs-check

# Fix code style automatically
composer cs-fix
```

### PHP Standards

- Use PHP 8.2+ features
- Declare strict types
- Type hint everything
- Use readonly properties where appropriate
- Use named arguments for clarity

**Example:**

```php
<?php

declare(strict_types=1);

namespace PhpCmd\Event;

use PhpCmd\CmdBus\CommandInterface;

final class MyClass
{
    public function __construct(
        private readonly CommandInterface $command
    ) {}

    public function process(): void
    {
        // Implementation
    }
}
```

### PHPStan

All code must pass PHPStan at level 9:

```bash
# Run static analysis
composer static-analysis
```

### Documentation Comments

Use PHPDoc blocks for all public APIs:

```php
/**
 * Processes the command through the middleware.
 *
 * @param CommandInterface $command The command to process
 * @param CommandHandlerInterface $handler The next handler
 * @return mixed Result from the handler
 * @throws ValidationException If validation fails
 */
public function process(
    CommandInterface $command,
    CommandHandlerInterface $handler
): mixed {
    // Implementation
}
```

## Testing

### Test Requirements

- All new features must have tests
- Bug fixes should include regression tests
- Maintain or improve code coverage
- Tests must pass before merging

### Running Tests

```bash
# Run all tests
composer test

# Run specific test suite
composer test -- --filter TestClassName

# Run with coverage
composer test-coverage

# Run integration tests
composer test-integration
```

### Writing Tests

**Unit Test Example:**

```php
<?php

declare(strict_types=1);

namespace PhpCmd\EventTest;

use PhpCmd\Event\PreHandleEvent;
use PHPUnit\Framework\TestCase;

final class PreHandleEventTest extends TestCase
{
    public function testEventCreation(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $event = new PreHandleEvent($command);

        $this->assertSame(PreHandleEvent::NAME, $event->getName());
        $this->assertSame($command, $event->getTarget());
    }
}
```

### Test Coverage

Aim for 100% coverage on new code:

```bash
# Generate coverage report
composer test-coverage

# View in browser
open clover.xml
```

## Documentation

### Documentation Requirements

All contributions should include documentation:

- Update relevant documentation files
- Add code examples
- Update API reference if needed
- Add troubleshooting entries for common issues

### Documentation Style

- Use clear, concise language
- Include code examples
- Use headings and lists for structure
- Cross-reference related documents

### Building Documentation

Documentation is in Markdown format in the `docs/` directory.

## Pull Request Process

### Before Submitting

Run all checks:

```bash
composer check
```

Update documentation:

- Update docs for new features
- Add examples if applicable

Write good commit messages:

```text
Short summary (50 chars or less)

More detailed explanation if needed. Wrap at 72 characters.

- Bullet points are okay
- Use present tense: "Add feature" not "Added feature"

Fixes #123
```

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Checklist
- [ ] Tests pass
- [ ] Code style checked
- [ ] Static analysis passes
- [ ] Documentation updated
- [ ] Examples added (if applicable)

## Related Issues
Fixes #123

## Additional Notes
Any additional information
```

### Review Process

1. Automated checks must pass
2. At least one maintainer approval required
3. Address review feedback promptly
4. Maintainers will merge when ready

### After Merge

- Your contribution will be in the next release
- You'll be credited in release notes
- Thank you! 🎉

## Development Workflow

### Branching Strategy

- `main` - Stable release branch
- `0.2.x` - Current development branch
- `feature/xyz` - Feature branches
- `bugfix/xyz` - Bug fix branches

### Creating a Feature Branch

```bash
# Update your fork
git checkout 0.2.x
git pull upstream 0.2.x

# Create feature branch
git checkout -b feature/my-new-feature

# Make changes and commit
git add .
git commit -m "Add new feature"

# Push to your fork
git push origin feature/my-new-feature
```

## Questions?

- Check existing documentation in `docs/`
- Search [existing issues](https://github.com/php-cmd/laminas-events/issues)
- Ask in [discussions](https://github.com/php-cmd/laminas-events/discussions)
- Contact maintainers if needed

## Recognition

Contributors will be:

- Listed in release notes
- Credited in the repository

Thank you for contributing to PhpCmd Laminas Events! 🙏
