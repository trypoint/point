# Beta.1 release readiness

The verified Alpha.7 GitHub prerelease and Packagist distribution identify parent commit `978fac89d29cf6248ba8d2f8182709f3bb1dce89`, tree `989694447cdce8ed33363508ce9d306b535244cf`, and composer.lock blob `9239127f23a7bca6b44f4b2d06b360f5423888f3`. The standalone candidate was prepared in a detached clone. [Published prerelease](https://github.com/trypoint/point/releases/tag/v0.1.0-alpha.7)

The [API freeze](BETA-1-API-FREEZE.md) and [compatibility report](BETA-1-COMPATIBILITY.md) record the proposed contracts and observed HTTP behavior. The adjacent beta1-evidence directory contains the archive, tree and SHA-256 attestation, included and excluded paths, and command transcripts. Recording archive identity externally lets the source tree contain this report without a self-referential hash.

Documentation changes clarify the PHP minimum and deployment trust boundary, correct parser registration in the JSON example and add an Unreleased Beta.1 changelog entry. The response emitter fix preserves public signatures and corrects verified HTTP 204 and 205 framing. Locked runtime dependencies are unchanged from Alpha.7.

## Exact-runtime validation

| Check | PHP 8.4.27 | PHP 8.5.12 |
| --- | --- | --- |
| composer validate --strict | Pass | Pass |
| composer dump-autoload -o --strict-psr | Pass | Pass |
| composer test | 271 tests, 648 assertions | 271 tests, 648 assertions |
| composer stan | Pass | Pass |
| composer cs:check | 0 of 104 files | 0 of 104 files |
| composer audit --locked | No advisories found | No advisories found |
| Clean no-dev install from candidate ZIP and strict optimized autoload | Pass | Pass |
| Packaged Hello World and HTTP fixture | 14 of 14 | 14 of 14 |
| Published Alpha.7 and candidate website and REST consumers | Pass | Pass |

Alpha.7 ran 269 tests and 641 assertions on both runtimes. The candidate adds two cases and seven assertions. The first 8.4.27 PHPUnit attempt ran before dependency installation finished. After installation, strict autoload and PHPUnit passed. Style checks passed using the committed LF line endings in the isolated Windows checkout. PHP-CS-Fixer on 8.5.12 advises using the lowest supported runtime.

All 88 PHP documentation snippets and bundled PHP example entry points passed syntax checks after the guide corrections. The two bundled entry points also ran over HTTP. Consumer and HTTP fixtures cover the main application workflows. Context-dependent guide fragments received syntax checks rather than full execution.

## Release gates

- P0 and P1 — none confirmed in the tested scope.
- The reproduced 204/205 framing defect and the broken JSON example have targeted corrections and regression evidence.
- Production Linux PHP-FPM, TLS, proxy trust configuration, and full execution of every documentation fragment remain outside the verified matrix. The release makes no production deployment claim for those environments.
- The compatibility categories are proposed for maintainer review. The candidate contains no breaking signature change.
- Exact runtime, package and consumer transcripts are located alongside the archive. The package retains Alpha.7's export policy. It excludes repository reports, tests, examples, tooling and caches.

## RC.1 checklist

- Review unresolved API classifications and approve the compatibility guarantee.
- Verify the final candidate archive and rerun the exact-runtime suite and consumer checks.
- Record any production SAPI or proxy coverage needed for claims made in release documentation.
- Refresh the dependency advisory check and review open P0 and P1 findings.
- Review the release notes, archive manifest and SHA-256 before requesting publication.
