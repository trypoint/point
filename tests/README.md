# Tests

Point keeps its tests in two groups:

- `Unit/`: small tests of individual components, middleware, routing, and containers.
- `Integration/`: application bootstrap, HTTP correctness, providers, and cross-component behavior.

The folders and test namespaces remain unchanged. PHPUnit discovers both groups through `phpunit.xml`. Run the usual commands:

```bash
composer test
composer stan
composer cs:check
```

For a focused run, use `vendor/bin/phpunit --testsuite Unit` or `vendor/bin/phpunit --testsuite Integration`.

Do not duplicate the same regression across suites unless it checks a different contract. Do not remove security or integration tests just to shorten the suite. Only rerun full validation when implementation, dependencies, or the release artifact changes. A previously approved v0.1.0 style-baseline exception does not make the global style check pass.
