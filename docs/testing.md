# Testing

Point uses PHPUnit, PHPStan and PHP-CS-Fixer. Tests live under `tests/Unit/` and `tests/Integration/`, both configured in the root `phpunit.xml`.

## Commands

```bash
composer install
composer test
composer stan
composer cs:check
```

To run one group or a focused test:

```bash
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
vendor/bin/phpunit --filter RouterTest
```

`composer test:all` runs style, static analysis and PHPUnit in sequence. A failing style check is a failure, even when a release-specific maintainer exception permits publication; the existing v0.1.0 exception is documented in its release record.

## Structure

```text
tests/
├── bootstrap.php
├── Unit/          # Individual classes and behavior
└── Integration/   # Application, HTTP, routing, PSR providers
```

Unit tests check component contracts; integration tests check how components interact, including request/response behavior and different PSR-7 implementations. Preserve relevant security regressions. Add a focused test when changing behavior, but avoid duplicating the same check in multiple suites.

The PHP runtime requirement is `>=8.4.27`. See [Installation](installation.md) for development dependencies and [Deployment](deployment.md) for host responsibilities. The CI configuration is in `.github/workflows/ci.yml`.
