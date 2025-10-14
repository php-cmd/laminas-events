# Documentation Index

Complete documentation for the Laminas Events integration for CmdBus.

## Getting Started

New to the package? Start here:

1. **[Getting Started Guide](./getting-started.md)** - Installation and basic setup
2. **[Configuration Guide](./configuration.md)** - Configure the package for your needs
3. **[Quick Examples](./examples/simple-logging.md)** - See it in action

## Core Concepts

Understanding the fundamentals:

- **[Events Reference](./events.md)** - PreHandleEvent and PostHandleEvent explained
- **[Middleware Guide](./middleware.md)** - How middleware triggers events
- **[Listener Configuration](./listeners.md)** - Setting up event listeners

## Guides by Topic

### Architecture & Design

- [Best Practices](./best-practices.md) - Recommended patterns and practices
- [Advanced Usage](./advanced-usage.md) - Advanced patterns and techniques
- Event-driven architecture principles

### Configuration

- [Service Configuration](./configuration.md#service-configuration)
- [Middleware Configuration](./configuration.md#middleware-configuration)
- [Listener Registration](./configuration.md#listener-configuration)
- [Priority Management](./listeners.md#listener-priorities)

### Development

- [Creating Listeners](./listeners.md#listener-types)
- [Custom Middleware](./middleware.md#creating-custom-middleware)
- [Custom Events](./advanced-usage.md#custom-events)
- [Testing Strategies](./best-practices.md#testing)

### Integration

- PSR-11 Container integration
- Laminas ServiceManager integration
- Mezzio framework integration
- Laminas MVC integration

## Reference Documentation

### API Reference

Complete API documentation:

- **[API Reference](./api-reference.md)** - Full class and method documentation
  - [Events API](./api-reference.md#events)
  - [Middleware API](./api-reference.md#middleware)
  - [Container Factories](./api-reference.md#containers)
  - [Exceptions](./api-reference.md#exceptions)

### Examples

Real-world, copy-paste examples:

#### Basic Examples

- **[Simple Logging](./examples/simple-logging.md)** - Log command execution
- **[Validation](./examples/validation-listener.md)** - Validate commands
- Authorization - Check permissions (coming soon)

#### Intermediate Examples

- Audit Trail - Complete audit logging (coming soon)
- Caching - Cache command results (coming soon)
- Notifications - Send notifications (coming soon)
- Metrics - Collect performance metrics (coming soon)

#### Advanced Examples

- Saga Pattern - Multi-step workflows (coming soon)
- Event Sourcing - Event sourcing integration (coming soon)
- CQRS - Command-Query separation (coming soon)
- Multi-Tenant - Multi-tenant support (coming soon)

## Troubleshooting

Having issues? Check here:

- **[Troubleshooting Guide](./troubleshooting.md)** - Common issues and solutions
  - [Installation Issues](./troubleshooting.md#installation-issues)
  - [Configuration Problems](./troubleshooting.md#configuration-problems)
  - [Event Listener Issues](./troubleshooting.md#event-listener-issues)
  - [Performance Issues](./troubleshooting.md#performance-issues)
  - [Debugging Tips](./troubleshooting.md#debugging-tips)

## Quick Reference

### Event Names

```php
PreHandleEvent::NAME  // 'pre.handle'
PostHandleEvent::NAME // 'post.handle'
```

### Default Priorities

| Component | Priority | Execution Order |
|-----------|----------|-----------------|
| PreHandleMiddleware | 100 | Early (before handlers) |
| PostHandleMiddleware | -100 | Late (after handlers) |
| Default Listener | 1 | Standard |

### Common Patterns

```php
// Attach listener
$events->attach(PreHandleEvent::NAME, $listener, $priority);

// Create event
$event = new PreHandleEvent($command);

// Trigger event
$eventManager->triggerEvent($event);

// Stop propagation
$event->stopPropagation(true);
```

## Learning Path

### Beginner Path

1. Read [Getting Started](./getting-started.md)
2. Follow [Simple Logging Example](./examples/simple-logging.md)
3. Review [Events Reference](./events.md)
4. Try [Validation Example](./examples/validation-listener.md)

### Intermediate Path

1. Study [Configuration Guide](./configuration.md)
2. Learn [Listener Patterns](./listeners.md)
3. Understand [Middleware](./middleware.md)
4. Review [Best Practices](./best-practices.md)

### Advanced Path

1. Explore [Advanced Usage](./advanced-usage.md)
2. Study advanced examples (saga, CQRS, event sourcing)
3. Read [API Reference](./api-reference.md)
4. Contribute to the project

## Additional Resources

### External Documentation

- [Laminas EventManager Documentation](https://docs.laminas.dev/laminas-eventmanager/)
- [CmdBus Documentation](https://github.com/php-cmd/cmd-bus)
- [PSR-11 Container Interface](https://www.php-fig.org/psr/psr-11/)

### Community

- [GitHub Repository](https://github.com/php-cmd/laminas-events)
- [Issue Tracker](https://github.com/php-cmd/laminas-events/issues)
- [Discussions](https://github.com/php-cmd/laminas-events/discussions)

### Contributing

Want to contribute? See:

- CONTRIBUTING.md (in project root)
- Code of Conduct
- Development setup

## Document Status

| Document | Status | Last Updated |
|----------|--------|--------------|
| Getting Started | ✅ Complete | 2025-10-13 |
| Configuration | ✅ Complete | 2025-10-13 |
| Events Reference | ✅ Complete | 2025-10-13 |
| Middleware Guide | ✅ Complete | 2025-10-13 |
| Listener Configuration | ✅ Complete | 2025-10-13 |
| Best Practices | ✅ Complete | 2025-10-13 |
| Advanced Usage | ✅ Complete | 2025-10-13 |
| API Reference | ✅ Complete | 2025-10-13 |
| Troubleshooting | ✅ Complete | 2025-10-13 |
| Examples | 🚧 In Progress | 2025-10-13 |

## Search Tips

Looking for something specific?

- **Installation**: See [Getting Started](./getting-started.md#installation)
- **Configuration**: See [Configuration Guide](./configuration.md)
- **Events**: See [Events Reference](./events.md)
- **Listeners**: See [Listener Configuration](./listeners.md)
- **Middleware**: See [Middleware Guide](./middleware.md)
- **Examples**: See [Examples Directory](./examples/)
- **Errors**: See [Troubleshooting](./troubleshooting.md)
- **API**: See [API Reference](./api-reference.md)

## Feedback

Found an issue with the documentation? Please:

1. Check if it's already reported in [Issues](https://github.com/php-cmd/laminas-events/issues)
2. If not, open a new issue with:
   - Document name and section
   - What's unclear or incorrect
   - Suggestions for improvement

Thank you for helping improve our documentation! 🙏
