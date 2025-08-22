---
applyTo: '**'
---
Coding standards, domain knowledge, and preferences that AI should follow.

- Use clear and descriptive variable names.
- Follow the PSR-12 coding style guide.
- Write unit tests for all new features and bug fixes.
- Keep functions and methods small and focused on a single task.
- Use type hints and return types for all functions and methods.
- Avoid using global variables.
- Use dependency injection instead of singletons.
- Write code that is easy to read and understand.
- Keep the codebase organized and modular.

## Context

- This project is a framework to build API applications.
- The primary language used is PHP.
- This framework is based on Amphp version 3, a high-performance asynchronous PHP framework.
- The application uses a MySQL, PostgreSQL, and SQLite database for data storage.
- The application uses Redis for caching.
- The application uses PestPHP for unit testing.
- The application uses Composer for dependency management.

## Paths

- Application code is located in the `src` directory.
- Tests are located in the `tests` directory.

## Framework components

- Crypto: `src/Crypto`
- Database: `src/Database`
- Query Builder: `src/Database/QueryBuilder`
- Filesystem: `src/Filesystem`
- Logging: `src/Logging`
- Mail: `src/Mail`
- Queue: `src/Queue`
- Routing: `src/Routing`
- Session: `src/Session`
- Tasks: `src/Tasks`
- Validation: `src/Validation`
- Views: `src/Views`

## Current feature under development

- The current feature under development is the `src/Queue` component, which provides a unified API for managing background tasks processing.
- The `src/Queue` uses queuable task `src/Queue/QueuableTask` for defining tasks that can be processed in the background.
- The `src/Queue` component supports multiple queue drivers, including redis, database, and parallel.