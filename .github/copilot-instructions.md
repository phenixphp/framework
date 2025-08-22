# Phenix Framework AI Instructions

Phenix is an async-first PHP web framework built on Amphp v3 for high-performance applications. This guide helps AI agents understand the framework's unique patterns and architecture. **Phenix** is an asynchronous PHP web framework built on the [AmPHP](https://amphp.org/) ecosystem. It serves as the core framework for building scalable, async-first API applications. Official documentation can be found at [Phenix Documentation](https://phenix.omarbarbosa.com/).

## Core Architecture

### Async-First Design
- Framework runs in CLI SAPI with its own HTTP server using `SocketHttpServer`
- All I/O operations are non-blocking using Amphp primitives
- Use `App::make()` for dependency resolution throughout the codebase
- Service providers register dependencies in `App::setup()` method

### Application Bootstrap
```php
// Standard app creation pattern
$app = AppBuilder::build($path, $env);
$app->run(); // Starts HTTP server and handles signals
```

### Dependency Container Pattern
- Uses League Container with service providers for registration
- Access via `App::make(ClassName::class)` static method
- Register services: `$app->register(Key::class, $concrete)`
- Facades provide static access: `Config::get('key')`, `Route::get('/path')`

## Key Components

### Routing System (`src/Routing/`)
- Fluent route builder: `Route::get('/users', $handler)->middleware($middleware)`
- Groups support: `Route::group(fn() => Route::get('/admin', $handler))`
- Automatic dependency injection in route handlers
- Middleware stacking via `stackMiddleware()`

### Queue System (`src/Queue/`) - Current Development Focus
- Abstract `QueuableTask` base class for background jobs
- Multiple drivers: `RedisQueue`, `DatabaseQueue`, `ParallelQueue`
- Tasks use `ShouldQueue` interface and `enqueue()` static method
- State management with `TaskState` for tracking job progress

### Database & Query Builder (`src/Database/`)
- Async database operations using Amphp MySQL/PostgreSQL drivers
- Fluent query builder with method chaining
- Connection factory pattern for multiple database connections
- Migration system with console commands

### Console Commands (`src/Console/`)
- Symfony Console-based with `Phenix` application class
- Maker commands extend `Maker` base class
- Commands registered via service providers in `pushCommands()`
- Stubs located in `src/stubs/` directory

## Development Patterns

### Service Provider Pattern
```php
class ExampleServiceProvider extends ServiceProvider
{
    public function register(): void {
        // Register bindings in container
    }

    public function provides(string $id): bool {
        // Declare what this provider provides
    }
}
```

### Testing with PestPHP
- Unit tests: `tests/Unit/` with simple `it('description', fn() => expect()->toBe())`
- Feature tests: `tests/Feature/` for HTTP integration
- Use `Tests\TestCase` base class for both unit and feature tests
- Run tests: `vendor/bin/pest`

### Command Creation
- Extend `Maker` class for generators
- Define `stub()`, `outputDirectory()`, `commonName()` methods
- Use `InputArgument` and `InputOption` for CLI parameters
- Stubs use Mustache-style templating

### Facades Pattern
- Extend `Runtime\Facade` and implement `getKeyName()`
- Provide `@method` docblocks for IDE support
- Static calls proxy to container-resolved instances

## File Organization

- **Core**: `src/App.php` (main application), `src/AppBuilder.php` (factory)
- **HTTP**: `src/Http/` (requests, responses, middleware)
- **Database**: `src/Database/` (query builder, migrations, models)
- **Queue**: `src/Queue/` (async job processing system)
- **Console**: `src/Console/Commands/` (CLI commands)
- **Config**: Framework uses dotenv with `src/Runtime/Environment`
- **Tests**: PestPHP in `tests/` with `Unit/` and `Feature/` directories

## Key Conventions

- All classes use strict types: `declare(strict_types=1);`
- PSR-12 code style with type hints for all methods
- Async operations return Amphp `Future` objects when applicable
- Service providers define dependencies in `provides()` method
- Queue tasks extend `QueuableTask` and implement `handle()` method
- Database models extend base model with async query methods
- Middleware implements Amphp HTTP middleware interface

## Integration Points

- **AmphpPHP**: Core async runtime and HTTP server
- **League Container**: Dependency injection container
- **Symfony Console**: CLI command framework
- **PestPHP**: Testing framework
- **Monolog**: Logging with async handlers
- **Redis/MySQL/PostgreSQL**: Async database drivers
