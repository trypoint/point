# Point v0.1.0 Stable release readiness

**Disposition: BLOCKED — publication not authorized.** The exact finalization candidate passed the independent six-job CI matrix. The maintainer has accepted a v0.1.0-only baseline style exception and decided to preserve three undecided API types as compatible but provisional. The full API freeze and actual production deployment gate are not yet closed.

## Source and distribution evidence

- Framework branch: `release/v0.1.0-stable-candidate`
- CI-validated source commit: `01fd91e6c51cd63aa73d2e5a1383353a5ba37789`
- Git tree: `4511d694e566426b35ee2005a11513fc27ce37b9`
- Parent: `0600aa6f07214904613fce9ab88255d22d881295`
- Commit-built distribution ZIP SHA-256: `FAD94F419BC4642F5171A0E2522CED28A150D094A1E5B0872531D48FF62C8D98`
- Composer identity: `trypoint/point`, version `0.1.0`, PHP `>=8.4.27`
- Distribution: 80 files; installed consumer inventory verified byte for byte
- Independent consumer commit: `9e9cb87f8aaafdc71e5ceaea05b8009fdf105fa7`
- GitHub Actions: [run 46150260829](https://github.com/trypoint/point-consumer/actions/runs/46150260829), **six of six jobs PASS** (Ubuntu PHP 8.4.27/8.4/8.5; Windows PHP 8.4/8.5; Ubuntu Nginx/PHP-FPM).

The package has the same 80-file inventory as the earlier selected archive; four package-visible documentation files (`CHANGELOG.md`, `README.md`, `SECURITY.md`, `docs/migration-from-slim.md`) differ. Runtime XML fixes, signatures, and dependencies remain unchanged. The archive identity above refers to the tested candidate before this decision-record-only update. These three approval/readiness documents are excluded from Composer export under `.gitattributes`; final branch-head and distribution identity should be reconfirmed before publication.

## Security and quality evidence

| Gate | Evidence | Status |
| --- | --- | --- |
| XML error text, escaping, Unicode and HTTP status framing | PHPUnit regression and consumer tests | PASS |
| libxml error mode restoration and malformed-then-valid parsing | PHPUnit regression and consumer tests | PASS |
| External file entity | Isolated bounded PHPUnit test on PHP 8.5.10, 32 MiB/5 s | PASS locally |
| External network entity (closed loopback only) | Isolated bounded PHPUnit test, no public network target | PASS locally |
| Recursive entity handling | Isolated bounded PHPUnit test | PASS locally |
| Controlled entity amplification | Isolated bounded PHPUnit test | PASS locally |
| Local full PHPUnit suite | PHP 8.5.10, 283 tests / 690 assertions, warnings/deprecations fail | PASS |
| Composer strict validation, strict autoload, PHPStan level 8, locked audit | Local finalization report | PASS |
| Modified PHP files PHP-CS-Fixer | 0/5 violations | PASS |
| Global PHP-CS-Fixer | 100/105 reported; clean RC.1 baseline 104/104 | **FAIL — accepted exception for v0.1.0 only** |
| Exact packaged candidate installed in consumer | All 80 files byte-for-byte verified | PASS |
| Independent consumer six-job matrix | GitHub Actions run 46150260829 | PASS |

The four dedicated XML security cases were separately executed locally in a bounded isolated process. The six-job consumer workflow passed, but this record does not claim that those four *new PHPUnit cases* themselves ran on every CI axis without explicit evidence.

The global fixer exception is recorded in [STYLE-BASELINE-EXCEPTION.md](../audit/release-validation/STYLE-BASELINE-EXCEPTION.md). It was explicitly accepted by the maintainer for this release only; the actual global command remains FAIL. No formatter configuration change or unrelated mass reformat is authorized.

## Maintainer decisions — 2026-11-28

- **Provisional API types: DECIDED.** `Point\Container\Definition\HttpDefinitions`, `Point\Container\Definition\PointDefinitions`, and `Point\Routing\PipelineRunner` retain the inventory status `UNDECIDED`, remain compatible in v0.1.0 and do not receive a promised stable public API contract. The other API classification and direct-construction/collector decisions remain pending. See [Stable API Freeze](STABLE-0.1.0-API-FREEZE.md).
- **Global PHP-CS-Fixer baseline exception: ACCEPTED for v0.1.0 only.** The global check remains **FAIL** and is disclosed; all five changed PHP files passed. See [STYLE-BASELINE-EXCEPTION.md](../audit/release-validation/STYLE-BASELINE-EXCEPTION.md).

## Deployment evidence

The Ubuntu Nginx/PHP-FPM and HTTP ingress checks passed in isolated CI fixtures, not the intended real production configuration. Host validation, TLS termination, trusted proxy/header propagation, body limits, file permissions, process boundaries, logging, and operational readiness must be assessed at the target host or explicitly deferred by the maintainer.

## Final release blockers

1. **Complete API Freeze maintainer approval.** Items 1, 2, 4 and 5 in the API approval document still require explicit disposition. The provisional-type decision above is not blanket approval.
2. **Real production deployment acceptance or explicit maintainer deferral.** CI fixtures do not establish live TLS, trusted proxy and hosting topology.
3. **Final release integrity and publication approval.** Reconfirm final commit and distribution identity after documentation-only changes. No merge to `main`, Stable tag, GitHub Release or Packagist publication is authorized by this decision record.

The global style failure is an **accepted, version-limited exception**, not a PASS and not a remaining request for style approval.
