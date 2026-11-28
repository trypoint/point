# Contributing to Point Framework

Thank you for your interest in contributing to Point Framework! This document outlines the process for contributing code, documentation, and reporting issues.

## Code of Conduct

Be respectful in issues, reviews, and discussions. Report security issues privately using the instructions in [SECURITY.md](SECURITY.md).

## Getting Started

### Prerequisites

- PHP >= 8.4.27
- Composer 2.x
- Git

### Development Setup

```bash
# Fork and clone the repository
git clone https://github.com/trypoint/point.git
cd point

# Install dependencies (including dev)
composer install

# Run the full test suite
composer test:all
```

## How to Contribute

### Reporting Bugs

1. Check existing [issues](https://github.com/trypoint/point/issues) first
2. Create a new issue with:
   - Clear title and description
   - Steps to reproduce
   - Expected vs actual behavior
   - PHP version and environment
   - Minimal code example if possible

### Suggesting Features

1. Open an issue with the `enhancement` label
2. Describe the use case and proposed API
3. Discuss implementation approach

### Pull Requests

1. **Fork** the repository
2. **Create a branch** from `main`: `git checkout -b feature/your-feature-name`
3. **Make changes** following the coding standards
4. **Add tests** for new functionality
5. **Run validation**: `composer test`, `composer stan`, and `composer cs:check` (report any pre-existing baseline failure)
6. **Commit** with clear messages
7. **Push** and open a Pull Request

## Coding Standards

Point Framework follows:

- **PSR-12** for code style
- **PHPStan Level 8** for static analysis
- **Strict typing** (`declare(strict_types=1)`)
- **PSR-4** autoloading

### Automated Checks

```bash
# Check code style (dry-run)
composer cs:check

# Fix code style automatically
composer cs:fix

# Static analysis
composer stan

# Run tests
composer test

# All checks (CI pipeline)
composer test:all
```

New or changed code must pass relevant tests and static analysis. Any pre-existing style-baseline exception requires an explicit, version-scoped maintainer decision; never report a failing global check as passing.

## Testing

- Write **PHPUnit** tests for all new features
- Maintain or improve coverage
- Test both unit and integration scenarios
- Test with multiple PSR-7 implementations when relevant

## Documentation

- Update `README.md` for user-facing changes
- Update `docs/` for detailed documentation
- Add PHPDoc to all public APIs
- Keep `CHANGELOG.md` updated (maintainers handle releases)

## Dependency Management

- **Runtime dependencies**: Minimal, only what's essential
- **Dev dependencies**: Testing, static analysis, code style tools
- **Optional dependencies**: PSR-7 implementations, PHP-DI
- No new runtime dependencies without discussion

## Release Process

Releases are handled by maintainers:

1. Update `CHANGELOG.md`
2. Create release tag: `vX.Y.Z-alpha.N` / `vX.Y.Z`
3. GitHub Actions builds and validates
4. Publish to Packagist

## License

By contributing, you agree that your contributions will be licensed under the MIT License (see `LICENSE`).

---

**Questions?** Open an issue or check the [wiki](https://github.com/trypoint/point/wiki).