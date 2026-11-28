# Tests

- `Unit/`: component tests.
- `Integration/`: HTTP, bootstrap, routing and provider integration tests.

Use `composer test` to run both suites. For targeted runs: `vendor/bin/phpunit --testsuite Unit` or `vendor/bin/phpunit --testsuite Integration`.

See the [testing guide](../docs/testing.md) for the other checks. Keep regression and security tests; simplifying the layout does not mean removing coverage.
