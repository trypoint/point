# Point v0.1.0 Stable release readiness

**Disposition: BLOCKED — API Freeze APPROVED; production acceptance and final publication authorization remain open.** Do not publish until the remaining mandatory gates are closed. This record distinguishes the current release branch identity from the earlier exact package identity run through consumer CI.

## Source and distribution identity

| Item | Verified identity |
| --- | --- |
| Branch HEAD reviewed before this readiness-record update | `c40d34652e707e12162431d89dd228c04acf9aaa` |
| Review-base Git tree | `f18bd42b594996d047581bfb60c4ca4ed7cccf96` |
| Parent | `2e1ba56a4511e8ab641cf73315f394f4d93a570b` |
| CI-tested framework commit | `01fd91e6c51cd63aa73d2e5a1383353a5ba37789` |
| CI-tested framework tree | `4511d694e566426b35ee2005a11513fc27ce37b9` |
| Reproducible tree-based ZIP SHA-256 | `4F289FE5A94B710E732A8D74B9B349C05D5D3012EC58BB7A69B5988BC208FF37` |
| Current exported inventory | 80 files; `docs/migration-from-slim.md` is included; release, test, and development files follow `.gitattributes` export-ignore rules |
| Package identity | Installed as `trypoint/point` `0.1.0`; PHP `>=8.4.27` |
| Runtime dependency constraints | Unchanged from RC.1 |

The commits after the CI-tested source are documentation-only and excluded from Composer export. Reproducibly build the release archive with Git 2.56.1 by resolving the selected branch commit to its tree (`git rev-parse <release-branch-commit>^{tree}`), then running `git archive --format=zip --mtime=2026-11-28T00:00:00Z --output=point-v0.1.0.zip <tree-sha>`. Archiving the tree rather than the commit and fixing entry timestamps makes the ZIP hash independent of excluded documentation commits. The review-base archive and prior tested archive each contain 80 entries; per-file SHA-256 comparison found zero additions, zero removals, and zero content differences. The earlier consumer archive SHA-256, `FAD94F419BC4642F5171A0E2522CED28A150D094A1E5B0872531D48FF62C8D98`, identifies the artifact used in CI, not the current Git commit. Consumer CI installed `trypoint/point` `0.1.0` and byte-verified all 80 packaged files for the content-identical artifact. The current tree-based ZIP checksum itself has not been submitted to consumer CI.

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

The Ubuntu Nginx/PHP-FPM workflow validates a disposable CI fixture only. It is **not** production deployment acceptance. No production host, credential, or live configuration was accessed. Complete this target-environment checklist and attach evidence before publication, or record a separate explicit maintainer deferral. No deferral is currently recorded.

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

## Decisions already recorded

- **Full API Freeze: APPROVED, 2026-11-28.** All seven worksheet dispositions are approved, including the earlier provisional-type decision.
- **Provisional API types:** current signatures and behavior preserved in v0.1.0, without stable-contract promises.
- **Global PHP-CS-Fixer baseline exception:** accepted for v0.1.0 only. Global style remains FAIL.

These decisions do not approve production deployment, merge, tag, GitHub Release, or Packagist publication.

## Remaining release gates

1. **Production deployment:** complete and review the target-host checklist above, or make an explicit documented maintainer deferral. No deferral is approved.
2. **Final release integrity and authorization:** after any package-visible change, rebuild and validate the distribution. Before publication, reconfirm the final branch and artifact identity, then obtain separate authorization for merge, tag, GitHub Release, and Packagist publication.

**Closed gate:** Complete API Freeze — **APPROVED**, 2026-11-28. The existing CI and PHP-CS-Fixer evidence is unchanged by these export-ignored documentation updates.

No merge to `main`, Stable tag, GitHub Release, or Packagist publication is authorized by this report.
