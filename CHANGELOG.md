# Phenix PHP release notes

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

# Release Notes for 0.5.x

## [v0.5.2 (2025-01-03)](https://github.com/phenixphp/framework/compare/0.5.1...0.5.2)

### Added
- Session management with support for local in memory and redis drivers. ([#49](https://github.com/phenixphp/framework/pull/49))

## [v0.5.1 (2024-12-24)](https://github.com/phenixphp/framework/compare/0.5.0...0.5.1)

### Added
- Options to make models with controller and migrations. ([#46](https://github.com/phenixphp/framework/pull/46))

## [v0.5.0 (2024-12-20)](https://github.com/phenixphp/framework/compare/0.4.0...0.5.0)

### Added
- Log facade. ([#42](https://github.com/phenixphp/framework/pull/42))
- Database model API. ([#41](https://github.com/phenixphp/framework/pull/41))
- Commands to make models, queries and model collections. ([#40](https://github.com/phenixphp/framework/pull/40))
- Database models using Data Mapper pattern. ([#39](https://github.com/phenixphp/framework/pull/39))

# Release Notes for 0.4.x

## [v0.4.0 (2024-09-27)](https://github.com/phenixphp/framework/compare/0.3.8...0.4.0)

### Added
- Database validations (unique, exists). ([#36](https://github.com/phenixphp/framework/pull/36))
- File validation. ([#35](https://github.com/phenixphp/framework/pull/35))
- Move validation layer to framework. ([#34](https://github.com/phenixphp/framework/pull/34))
- Stream form parser. ([#33](https://github.com/phenixphp/framework/pull/33))
- Basic validation layer using form request. ([#32](https://github.com/phenixphp/framework/pull/32))
- Add basic form request. ([#31](https://github.com/phenixphp/framework/pull/31))
- Integrate CORS middleware. ([#30](https://github.com/phenixphp/framework/pull/30))

# Release Notes for 0.3.x

## [v0.3.8 (2023-10-10)](https://github.com/phenixphp/framework/compare/0.3.7...0.3.8)

### Fixed
- Load router on booting. ([#27](https://github.com/phenixphp/framework/pull/27))

## [v0.3.7 (2023-10-10)](https://github.com/phenixphp/framework/compare/0.3.6...0.3.7)

### Added
- Add FakerPHP to Seed class. ([#24](https://github.com/phenixphp/framework/pull/24))
- Command to make service providers. ([#23](https://github.com/phenixphp/framework/pull/23))
- `pcntl` as required extension. ([#22](https://github.com/phenixphp/framework/pull/22))

## [v0.3.6 (2023-10-07)](https://github.com/phenixphp/framework/compare/0.3.5...0.3.6)

### Added
- HTTP utility to handle request attributes. ([#19](https://github.com/phenixphp/framework/pull/19))

## [v0.3.5 (2023-10-06)](https://github.com/phenixphp/framework/compare/0.3.4...0.3.5)

### Fixed
- Update README. ([#16](https://github.com/phenixphp/framework/pull/16))

## [v0.3.4 (2023-10-06)](https://github.com/phenixphp/framework/compare/0.3.3...0.3.4)

### Fixed
- JSON header in responses. ([#13](https://github.com/phenixphp/framework/pull/13))

## [v0.3.3 (2023-10-06)](https://github.com/phenixphp/framework/compare/0.3.2...0.3.3)

### Fixed
- Get stubs content from framework path. ([#10](https://github.com/phenixphp/framework/pull/10))

## [v0.3.2 (2023-10-05)](https://github.com/phenixphp/framework/compare/0.3.1...0.3.2)

### Changed
- Add method to get environment file in test case. ([#7](https://github.com/phenixphp/framework/pull/7))

## [v0.3.1 (2023-10-05)](https://github.com/phenixphp/framework/compare/0.3.0...0.3.1)

### Changed
- Test utilities moved to framework. ([#4](https://github.com/phenixphp/framework/pull/4))

## v0.3.0 (2023-10-05)

### Changed
- The query builder `selectAllColumns` method now is optional. ([#1](https://github.com/phenixphp/framework/pull/1))

# Release Notes for 0.2.x

## [v0.2.1 (2023-09-30)](https://github.com/phenixphp/phenix/compare/0.2.0...0.2.1)

### Fixed
- Ensure dabatase directory exists before create migration. ([49](https://github.com/phenixphp/phenix/pull/49))

## [v0.2.0 (2023-09-29)](https://github.com/phenixphp/phenix/compare/0.1.0...0.2.0)

### Added
- Add `paginate` method to the query builder. ([42](https://github.com/phenixphp/phenix/pull/42))
- Add `count` method to the query builder. ([42](https://github.com/phenixphp/phenix/pull/42))
- Add `insert` method to the query builder. ([43](https://github.com/phenixphp/phenix/pull/43))
- Add `exists` and `doesntExists` methods to the query builder. ([#44](https://github.com/phenixphp/phenix/pull/44))
- Add `delete` method to the query builder. ([#45](https://github.com/phenixphp/phenix/pull/45))

### Changed
- Load routes before server running. ([#41](https://github.com/phenixphp/phenix/pull/41))
- Load custom environment files. ([#40](https://github.com/phenixphp/phenix/pull/40))
- Improve service provider structure. ([#38](https://github.com/phenixphp/phenix/pull/38))
- Improve class API to `\Phenix\Database\QueryGenerator`, now it has final methods. ([#44](https://github.com/phenixphp/phenix/pull/44))

### Fixed
- Apply provides in database service provider. ([#46](https://github.com/phenixphp/phenix/pull/46))

# Release Notes for 0.1.x

## [v0.1.0 (2023-09-15)](https://github.com/phenixphp/phenix/compare/0.0.1-alpha.1...0.1.0)

### Added
- Migrations and seeder support. ([#35](https://github.com/phenixphp/phenix/pull/35))
- Basic query builder ([#33](https://github.com/phenixphp/phenix/pull/33))
- Routes with support for groups ([#28](https://github.com/phenixphp/phenix/pull/28))
- Ability to use multiple logger channels. ([#24](https://github.com/phenixphp/phenix/pull/24))
- Command to make middlewares. ([#19](https://github.com/phenixphp/phenix/pull/19))
- SonarCloud integration. ([#13](https://github.com/phenixphp/phenix/pull/13))
- PHPInsights integration. ([#12](https://github.com/phenixphp/phenix/pull/12))
- PHPStan integration. ([#11](https://github.com/phenixphp/phenix/pull/11))
- GitHub actions integration. ([#10](https://github.com/phenixphp/phenix/pull/10))
- Command to make test `make:test`. ([#9](https://github.com/phenixphp/phenix/pull/9))
- Tests for the `make:controller` command. ([#6](https://github.com/phenixphp/phenix/pull/6))
