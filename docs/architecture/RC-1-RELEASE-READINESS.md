# RC.1 release readiness

## Source and package

The published Beta.1 tag and the Packagist dist reference resolve to parent commit `951c22d77c982dc316fd680f8fd511c84003debf`, tree `a7a52d769a9b75a292dd2d1b203fd02b0eb1a9c3`. An independent Composer project installed exactly `v0.1.0-beta.1` with no local repositories. Composer downloaded the ZIP and passed strict optimized autoloading. The downloaded ZIP and local tag export contain 80 identical files after removing GitHub's enclosing directory. Published ZIP SHA-256 `f664bf691bc90df26748e4729d18fc2af2cfbb8f2745360aaa746a35f819657b`.

RC.1 includes the automatic base-path correction and its regression tests. This build supersedes the earlier documentation-only RC.1 ZIP. The nine locked runtime packages and the Composer manifests are unchanged. The lock blob is `9239127f23a7bca6b44f4b2d06b360f5423888f3`. The adjacent rc1-rebuilt-evidence directory records the candidate tree, ZIP SHA-256, reproducibility command and included and excluded paths. Keeping the attestation beside the source tree avoids a self-referential hash.

## Checks and consumer installations

| Check | PHP 8.4.27 | PHP 8.5.12 |
| --- | --- | --- |
| Beta.1 and RC.1 PHPUnit with deprecation failures enabled | Beta.1: 271 tests, 648 assertions. RC.1: 277 tests, 673 assertions. | Beta.1: 271 tests, 648 assertions. RC.1: 277 tests, 673 assertions. |
| Strict Composer validation and strict optimized autoload | Pass | Pass |
| PHPStan level 8 and PHP-CS-Fixer | Pass | Pass |
| Locked dependency advisory check | No advisories reported | No advisories reported |
| Independent published package consumer and clean candidate no-dev consumer | Pass | Pass |

On both runtimes the rebuilt RC.1 website consumer passed ten HTTP requests across root, single-level and nested mounts, a public document root and an explicitly prefixed public app. The REST consumer passed 17 HTTP checks. The twelve scripted metadata and middleware reuse cases passed on both runtimes. Four-provider checks used the source checkout with development dependencies. The application fixture verifies HTTP bootstrap, routing, middleware, error negotiation, 204 and 205 response framing, base-path URL generation and uploaded file bytes. The PHPUnit suite retains the independent Router 202 regression, safe error bodies and response framing checks. The source suite adds six cases and 25 assertions compared with Beta.1. No confirmed P0/P1 framework regression was found.

## Deployment evidence and limits

| Environment | Result |
| --- | --- |
| Windows Apache 2.4.69 + mod_php | 17 checks passed with `AllowEncodedSlashes NoDecode`, loopback HTTP |
| Windows Nginx 1.22.0 + PHP CGI/FastCGI | 17 checks passed, loopback HTTP |
| Windows Nginx 1.22.0 reverse proxy to PHP development server | 17 HTTP checks and 18 HTTPS checks passed on loopback. HttpSoft derives the scheme from the edge-provided header. The fixture verifies a local certificate, rejects an unexpected Host, clears client Forwarded and X-Forwarded-Host, and sets X-Forwarded-Proto at the edge. |
| Linux PHP-FPM and Nginx + PHP-FPM | **NOT RUN**. Docker and a WSL runtime were unavailable. Windows PHP CGI is a different SAPI. |
| Production TLS and deployment topology | **NOT RUN**. Loopback TLS and an explicit edge allowlist were exercised. The beta and RC checks do not certify production configuration. |

The bundled HttpSoft request creator can derive URI host and scheme from forwarding fields. This is provider behavior supplied to Point through PSR-7. Keep backends reachable only through trusted infrastructure, validate Host and clear or replace forwarding fields from clients. The fixture uses a trusted local edge. Multipart evidence covers POST. The auto detector uses SCRIPT_NAME and keeps the first valid mount for each application instance. Use an explicit path if the public prefix differs from that script directory.

See [RC.1 compatibility](RC-1-COMPATIBILITY.md) and [proposed stable acceptance criteria](STABLE-0.1.0-ACCEPTANCE.md). Linux PHP-FPM and the actual production proxy topology remain acceptance gaps for stable v0.1.0.
