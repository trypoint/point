# Point v0.1.0 Stable release readiness

**Disposition: blocked pending the release gates below.** This report records evidence; it does not approve a release.

## Source and distribution evidence

The last fully validated framework candidate is commit `0600aa6f07214904613fce9ab88255d22d881295`, tree `32ac15dbe0d3e70433433eb0d5d6b730945a25e6`. Its newly built Composer ZIP had SHA-256 `06fa590b5db07453bc67f041cd1f7036eea9841246193b97b7863f17dd3b91e5`, contains 80 files, and matches the earlier validated archive across all 80 files after line-ending normalization. The exact artifact passed consumer validation and GitHub Actions run [46146926886](https://github.com/trypoint/point-consumer/actions/runs/46146926886), six of six jobs.

The current finalization edits change package-visible documentation and add excluded test/documentation files. Therefore the archive hash and source commit above identify the previously validated candidate only. They must be rebuilt and revalidated after these edits are selected. The new security tests have only local evidence until the final artifact is run through point-consumer.

## Security gate matrix

| Gate | Evidence | Status |
| --- | --- | --- |
| XML exception title escaping and well-formed error response | Existing PHPUnit regression covers ampersand, markup, and Unicode; HTTP status is asserted. | PASS on the validated candidate |
| XML parser restores libxml error mode on success/failure and can parse a later valid body | Existing PHPUnit regression covers both initial error modes and invalid-then-valid parsing. | PASS on the validated candidate |
| External file entity | Dedicated PHPUnit case uses a disposable local sentinel file; parser did not expose its contents. | PASS locally on PHP 8.5.10 (32 MiB limit, 5 second execution limit) |
| External network entity, loopback only | Dedicated PHPUnit case targets only an ephemeral closed `127.0.0.1` port; no external host is contacted. | PASS locally on PHP 8.5.10 (32 MiB limit, 5 second execution limit) |
| Recursive entity | Dedicated PHPUnit case expects the parser to reject the request with HTTP 400. | PASS locally on PHP 8.5.10 (32 MiB limit, 5 second execution limit) |
| Controlled entity amplification | Four-level, 2,560-character maximum controlled expansion payload; parser output is asserted at or below 4,096 characters. | PASS locally on PHP 8.5.10 (32 MiB limit, 5 second execution limit) |
| Request-body size limits | Enforced at the trusted ingress by deployment configuration; middleware does not set an application limit. | APPLICATION/DEPLOYMENT RESPONSIBILITY |
| Dependency audit | Locked dependency audit passed for the previously validated candidate. | PASS on the validated candidate |

## Runtime, quality, and deployment evidence

- The previous exact candidate passed Composer strict validation, strict optimized autoload, PHPUnit, PHPStan, dependency audit, consumer package identity checks, and all six consumer workflow jobs.
- PHP-CS-Fixer on the five changed PHP files passed. The full-tree check remains **FAIL** under PHP 8.5.10 and PHP-CS-Fixer 3.95.32: clean RC.1 reported 104/104 files; the candidate reports 100/105; all five changed PHP files have zero violations. See the [baseline exception](../audit/release-validation/STYLE-BASELINE-EXCEPTION.md). The exception documents existing baseline debt and does not make the global gate pass.
- Ubuntu Nginx/PHP-FPM passed as a CI fixture in the consumer matrix. Earlier release evidence records Windows Apache/Nginx fixtures. Production host configuration, production TLS termination, and the intended live reverse-proxy topology remain unverified.
- No deployment environment beyond those named fixtures is claimed as supported by test evidence. Configure host validation, request limits, forwarding-header trust, TLS, file permissions, process limits, logging, monitoring, and backups for the actual deployment.

## Final release blockers

1. Run the new XML security cases and existing XML regression tests against the final artifact on the consumer matrix; local bounded tests alone do not satisfy cross-platform release evidence.
2. Rebuild the archive from the final selected commit, record its file inventory and SHA-256, and verify installed package identity.
3. Rerun the consumer workflow against the final archive and final Git tree reference.
4. Obtain explicit maintainer sign-off on the API freeze and the three undecided inventory types.
5. Obtain an explicit maintainer decision accepting the documented global style baseline exception or define an approved remediation. The style gate remains failed until its command exits zero.
6. Validate the intended production deployment topology separately from CI fixtures, or record a maintainer decision to defer that environment-specific acceptance.
