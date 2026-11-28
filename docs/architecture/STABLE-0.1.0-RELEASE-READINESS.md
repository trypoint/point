# Point v0.1.0 Stable release readiness

**Disposition: READY FOR SEPARATE FINAL MAINTAINER PUBLICATION REVIEW — API Freeze APPROVED; real-host deployment verification explicitly DEFERRED for v0.1.0 by the maintainer on 2026-11-28.** No production host was validated. Merge, tag, GitHub Release and Packagist publication remain unauthorized; do not publish without a separate explicit decision. This record distinguishes the current release branch identity from the earlier exact package identity run through consumer CI.

## Source and distribution identity

| Item | Verified identity |
| --- | --- |
| Branch HEAD verified before production-deferral decision update | `4703e88b52701bd155494fe58ed19e06560cc291` |
| Source Git tree verified before decision update | `e69c4ce71c786107a4857ad265b9ef64df40dd9d` |
| Parent of source commit | `1af31cf7c576a7efb9c62354539da18d22de56ea` |
| CI-tested framework commit | `01fd91e6c51cd63aa73d2e5a1383353a5ba37789` |
| CI-tested framework tree | `4511d694e566426b35ee2005a11513fc27ce37b9` |
| Reproducible tree-based ZIP SHA-256 | `4F289FE5A94B710E732A8D74B9B349C05D5D3012EC58BB7A69B5988BC208FF37` |
| Current exported inventory | 80 files; `docs/migration-from-slim.md` is included; release, test, and development files follow `.gitattributes` export-ignore rules |
| Package identity | Installed as `trypoint/point` `0.1.0`; PHP `>=8.4.27` |
| Runtime dependency constraints | Unchanged from RC.1 |

The commits after the CI-tested source are documentation-only and excluded from Composer export. Reproducibly build the release archive with Git 2.56.1 by resolving the selected branch commit to its tree (`git rev-parse <release-branch-commit>^{tree}`), then running `git archive --format=zip --mtime=2026-11-28T00:00:00Z --output=point-v0.1.0.zip <tree-sha>`. Archiving the tree rather than the commit and fixing entry timestamps makes the ZIP hash independent of excluded documentation commits. The earlier review-base archive and prior tested archive each contained 80 entries with no content differences, as recorded by Codex. Independently, the current branch tree `e69c4ce71c786107a4857ad265b9ef64df40dd9d` and CI-tested tree `4511d694e566426b35ee2005a11513fc27ce37b9` were compared using the GitHub Git Trees API and the recorded `.gitattributes` export exclusions: 80/80 exportable files had identical paths and Git blob SHAs, with zero additions/removals/changes. This was an independent current-tree source-content check, not a local ZIP SHA-256 recomputation or a new CI run. The earlier consumer archive SHA-256, `FAD94F419BC4642F5171A0E2522CED28A150D094A1E5B0872531D48FF62C8D98`, identifies the artifact used in CI, not the current Git commit. Consumer CI installed `trypoint/point` `0.1.0` and byte-verified all 80 packaged files for the content-identical artifact. The current tree-based ZIP checksum itself has not been submitted to consumer CI.

## Quality and security gate matrix

| Gate | Evidence | Status |
| --- | --- | --- |
| XML title escaping, markup, Unicode, error status, malformed-then-valid recovery, and libxml state restoration | Local PHPUnit and consumer integration checks | **PASS** |
| External file entity, loopback-only external network entity, recursive entities, bounded amplification | Four isolated PHPUnit cases; PHP 8.5.10, 32 MiB memory limit, 5 s execution limit; no public network target | **PASS locally** |
| Full PHPUnit suite with warning/deprecation failures | PHP 8.5.10; 283 tests, 690 assertions | **PASS** |
| Composer strict validation and optimized strict autoload | PHP 8.5.10 / Composer 2.11.3 | **PASS** |
| PHPStan level 8 | PHP 8.5.10 | **PASS** |
| Locked dependency audit | PHP 8.5.10 | **PASS** |
| PHP-CS-Fixer on five changed PHP files | PHP-CS-Fixer 3.95.32; 0/5 violations | **PASS** |
| Full-tree PHP-CS-Fixer | 100/105 files reported; RC.1 baseline 104/104 | **FAIL — accepted v0.1.0-only exception** |
| Six-job independent consumer matrix | [GitHub Actions run 46150260829](https://github.com/trypoint/point-consumer/actions/runs/46150260829); artifact FAD…C8D98, consumer commit `9e9cb87f8aaafdc71e5ceaea05b8009fdf105fa7` | **PASS** |
| Exact current-HEAD archive checksum in consumer CI | New SHA 4F28…FF37 not dispatched; contents independently match CI archive 80/80 files | **NOT RUN for this archive identity; no package-content delta** |

The six passing jobs were Ubuntu PHP 8.4.27, Ubuntu PHP 8.4, Ubuntu PHP 8.5, Windows PHP 8.4, Windows PHP 8.5, and Ubuntu Nginx/PHP-FPM. The PHP 8.4.27 job confirmed the 80 installed files byte for byte. XML security cases were run locally in a bounded isolated process; the consumer matrix did not run those four PHPUnit cases on every platform.

The global style result remains **FAIL**. The maintainer accepted the pre-existing baseline exception for v0.1.0 only; that acceptance is documented in [STYLE-BASELINE-EXCEPTION.md](../audit/release-validation/STYLE-BASELINE-EXCEPTION.md). It does not suppress the command or turn FAIL into PASS.

## API freeze status

**APPROVED — complete API Freeze, 2026-11-28.** The maintainer explicitly approved worksheet items 1, 2, 3, 4, 5 and 7, retaining the already approved item 6. The freeze covers 49 stable-candidate types, 10 extension contracts, 9 internal types, provisional constructors and helpers, the public `getRouteCollector()` extension contract, provisional raw FastRoute interaction, PSR interoperability, named arguments, return values, documented exceptions and the v0.1.x compatibility policy. `HttpDefinitions`, `PointDefinitions`, and `PipelineRunner` remain `UNDECIDED`/provisional but compatible in v0.1.0. See [the approved API Freeze](STABLE-0.1.0-API-FREEZE.md) and [approval worksheet](STABLE-0.1.0-API-DISPOSITION-PROPOSAL.md). This approval does not constitute hosting acceptance or publication authorization.

## Production deployment acceptance

**MAINTAINER DECISION — EXPLICIT DEFERRAL APPROVED FOR v0.1.0 ON 2026-11-28.** The project maintainer explicitly authorized deferring the real production-host validation gate for this library release. This is a risk acceptance for the absence of real-host evidence, **not** verification of real-world deployment readiness and **not** authorization to publish. The Ubuntu Nginx/PHP-FPM workflow validates a disposable CI fixture only. No production host, credential, or live configuration was accessed. The checklist below is retained as **NOT RUN** evidence and must be completed for any separately approved production deployment. Point is a Composer-distributed library; each consuming application's deployment owner must verify the applicable controls in their real target environment. Do not describe the deferred checks as PASS.

| Acceptance item | Required evidence at target deployment | Status |
| --- | --- | --- |
| PHP runtime and SAPI | `php -v`, FPM/Apache module version, required extensions, `composer check-platform-reqs`; verify PHP `>=8.4.27` | **NOT RUN** |
| Web server and document root | Identify Apache/Nginx and front controller; confirm only `public/` is web-accessible and vendor, source, secrets, and configuration are not directly served | **NOT RUN** |
| Rewrite and routing | Verify direct route, nested path, 404, base path, and front-controller fallback against the real virtual host | **NOT RUN** |
| HTTPS/TLS termination | Record certificate chain, redirect behavior, TLS policy, and which trusted component terminates TLS | **NOT RUN** |
| Trusted Host and forwarded headers | Verify edge host allowlist, removal/replacement of client-supplied forwarding headers, backend network restriction, and scheme/host/path interpretation | **NOT RUN** |
| Request-body limits | Record edge/web-server/FPM limits; prove oversized fixed-length and streamed bodies are rejected before parsers process them | **NOT RUN** |
| Private files and permissions | Prove private files cannot be fetched; record least-privilege ownership/modes for code, secrets, logs, and writable paths | **NOT RUN** |
| Error reporting and logs | Verify detailed errors are not returned, production display errors are disabled, required errors are logged without secrets, and rotation/retention works | **NOT RUN** |
| Operational configuration | Record environment/secrets handling, process/resource limits, health checks, monitoring/alerting, restart behavior, and rollback procedure | **NOT RUN** |

Do not use PHP's built-in server as a production target. Point has no built-in trusted-proxy configuration; the deployment must enforce the edge trust boundary and configure its PSR-7 request creator appropriately.

## Explicit production validation deferral — scope and limitations

- **Decision:** ACCEPTED / DEFERRED for Point Framework **v0.1.0 only**, explicitly approved by the project maintainer on **2026-11-28** in conversation; no inferred authorization for future versions.
- **What is deferred:** Live target-host acceptance of the nine checklist items above: supported PHP/SAPI and extensions, protected document root, rewrites, TLS termination, Host/forwarded-header trust, request-size limits, private-file permissions, error handling/logging, and operations.
- **What remains established:** Local PHPUnit, XML-security regression tests, PHPStan, Composer checks and six independent consumer CI jobs on their stated platforms; the Nginx/PHP-FPM CI job is an isolated fixture.
- **Limitations and residual risk:** Different hosts, TLS terminators, reverse proxies, filesystem policies and request-ingress limits may behave differently. The framework does not provide built-in trusted-proxy enforcement. Applications must implement and validate their actual hosting security controls; no claim of production-host certification is made.
- **Release effect:** This particular real-host verification requirement is no longer a blocking pre-publication gate for v0.1.0 *by explicit maintainer decision*. The checklist remains **NOT RUN**, not PASS. Actual application deployment must not rely on the CI fixture as proof of production readiness.
- **Scope of authorization:** Documentation and release-readiness update only. This does **not** authorize a branch merge, tag creation, GitHub Release, Packagist publication or live deployment.

## Decisions already recorded

- **Production host gate: DEFERRED, 2026-11-28.** Maintainer-approved version-limited risk acceptance; all live-host checks remain NOT RUN.
- **Full API Freeze: APPROVED, 2026-11-28.** All seven worksheet dispositions are approved, including the earlier provisional-type decision.
- **Provisional API types:** current signatures and behavior preserved in v0.1.0, without stable-contract promises.
- **Global PHP-CS-Fixer baseline exception:** accepted for v0.1.0 only. Global style remains FAIL.

These decisions do not approve production deployment, merge, tag, GitHub Release, or Packagist publication.

## Remaining release gates

1. **Final source/distribution identity review:** Reconfirm the exact publication target branch HEAD and the exported 80-file package at release time; rebuild and revalidate if package-visible files change. Current Git-tree exported content matches the CI-tested package 80/80 paths and blob SHAs; the previously recorded fixed-timestamp ZIP SHA-256 has not been recomputed in this GitHub-only verification.
2. **Separate final publication authorization:** Obtain explicit maintainer permission before any merge to `main`, Stable tag, GitHub Release, or Packagist publication. These actions remain **NOT AUTHORIZED**.

**Closed or dispositioned gates:** Complete API Freeze **APPROVED**; real-host production validation **EXPLICITLY DEFERRED for v0.1.0** (NOT RUN, never PASS); PHP-CS-Fixer full-tree **FAIL with approved version-limited exception**. The existing CI evidence is unaffected by these export-ignored documentation updates.

No merge to `main`, Stable tag, GitHub Release, or Packagist publication is authorized by this report.
