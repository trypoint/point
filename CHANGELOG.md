# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0-alpha.1] - Unreleased

### Added

#### Developer Experience
- Professional README with installation, quick start, and feature overview
- Comprehensive documentation in `docs/`:
  - Installation guide
  - Application lifecycle
  - Routing (parameters, groups, middleware, URL generation)
  - Middleware (built-in, custom, execution order)
  - Container (PSR-11, autowiring, PHP-DI integration)
  - Request & Response (PSR-7, body parsers, file uploads)
  - Error handling (content negotiation, HTTP exceptions, custom renderers)
  - Deployment (Apache, Nginx, Docker, base path)
  - Testing (unit, integration, PSR-7 compatibility)
- Example applications:
  - `examples/01-hello-world` - Basic routing and text responses
  - `examples/02-json-api` - JSON API with GET/POST/DELETE and validation
- GitHub Actions CI workflow (`.github/workflows/ci.yml`)
  - Matrix testing on PHP 8.4 and 8.5
  - Composer validation, PSR-4 autoload check
  - PHPUnit, PHPStan level 8, PHP-CS-Fixer
  - No-dev installation verification

#### Core Improvements (Phase 1 & 2)
- `SimpleContainer` - PSR-11 compatible DI container (no mandatory PHP-DI)
- Fixed HTTP status codes: 404, 405 (with Allow header), 500
- Content negotiation: empty Accept header defaults to HTML
- PSR-4 strict autoloading compliance
- Middleware pipeline: FIFO order, proper error middleware wrapping
- PHPStan level 8 clean
- PHP-CS-Fixer configured with PSR-12 + PHP 8.3 migration rules
- 153 tests passing (113 unit + 40 integration)
- 4 PSR-7 implementation integration test suites (Nyholm, Laminas, Guzzle, HttpSoft)

### Changed
- README rewritten for developer audience (was agent-focused)
- SECURITY.md updated with Point Framework contact
- Repository cleaned of temporary debugging scripts
- `.gitignore` updated for vendor, caches, and local files

### Fixed
- `ContainerResolver` handles static closures correctly
- `MethodOverrideMiddleware` accepts both `_method` and `_METHOD`
- `EndpointMiddleware` properly receives request after routing
- All integration tests pass with all 4 PSR-7 implementations

### Security
- No known vulnerabilities
- Error details disabled by default in production

## [0.0.1] - 2024-01-XX

### Added
- Initial Point Framework extraction from Slim 4
- Core routing, middleware, and DI container
- PSR-7/PSR-15/PSR-17/PSR-11 compliance
- FastRoute integration
- Multiple error renderers (HTML, JSON, XML, Plain Text)