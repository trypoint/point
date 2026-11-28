# Point v0.1.0 Stable API freeze — approval record

**Status: APPROVED — full v0.1.0 API freeze, 2026-11-28.** The maintainer explicitly approved worksheet items 1, 2, 3, 4, 5 and 7 on 2026-11-28; the earlier decision for item 6 remains in force. The freeze defines the supported compatibility surface without authorizing release publication.

## Candidate scope

- Release branch: `release/v0.1.0-stable-candidate`
- Branch HEAD reviewed before this approval-record update: `c40d34652e707e12162431d89dd228c04acf9aaa`
- Review-base Git tree: `f18bd42b594996d047581bfb60c4ca4ed7cccf96`
- Runtime and CI-tested source: `01fd91e6c51cd63aa73d2e5a1383353a5ba37789`; tree `4511d694e566426b35ee2005a11513fc27ce37b9`
- PHP minimum: `>=8.4.27`
- Composer package: `trypoint/point`, version `0.1.0`
- Reproducible tree-based ZIP SHA-256: `4F289FE5A94B710E732A8D74B9B349C05D5D3012EC58BB7A69B5988BC208FF37` (80 packaged files; `git archive --format=zip --mtime=2026-11-28T00:00:00Z`)

The changes from the CI-tested source commit to the review base are confined to API, readiness, and style-exception records, all excluded from Composer export. The review-base archive and the previously CI-tested archive each contain 80 files; independent per-file SHA-256 comparison found no added, removed, or content-different packaged files. This record update is also export-ignored. The reproducible archive uses the branch tree object and fixed entry timestamps, so excluded documentation commits do not change its bytes. The previously tested archive SHA-256 is `FAD94F419BC4642F5171A0E2522CED28A150D094A1E5B0872531D48FF62C8D98`; that hash identifies the earlier CI artifact only.

## Inventory and classification rule

The machine-readable [API inventory](BETA-1-API-INVENTORY.json) records 71 Point types: 49 `PUBLIC STABLE CANDIDATE`, 10 `PUBLIC EXTENSION CONTRACT`, 9 `INTERNAL`, and 3 `UNDECIDED`.

The type category groups each type by its proposed role. A method-level category is authoritative for that member and may narrow the type-level category. Therefore, a public type does not make every public helper or constructor stable by implication. Current exceptions are explicit:

- `Router`, `Route`, and `RouteGroup` remain public stable-candidate types for their stable-candidate methods. Their constructors are `UNDECIDED`; direct construction remains callable with current behavior in v0.1.0 but is not a stable constructor contract.
- `RouterInterface::getRouteCollector()` and `Router::getRouteCollector()` are classified as `PUBLIC EXTENSION CONTRACT`. Keep the method, return type, and existing behavior compatible. Direct mutation of the returned FastRoute collector and reliance on FastRoute internals remain provisional.
- `RouteGroup::__invoke()` is `INTERNAL`; it is a public PHP method used to run group callbacks, not a supported direct-call contract. Preserve its framework behavior.
- `ExceptionMiddlewareTrait::parseAcceptHeader()` and the inherited middleware methods remain `UNDECIDED`; preserve their current signatures and behavior in v0.1.0 without promising a stable helper API.

## Approved v0.1.x compatibility policy — 2026-11-28

1. Preserve documented public signatures and behavior for methods classified `PUBLIC STABLE CANDIDATE`, including parameter names and defaults (and thus named-argument calls), return types and values, and documented exceptions.
2. Preserve all ten `PUBLIC EXTENSION CONTRACT` signatures and protocol semantics. Additions must keep existing extension implementations working.
3. Preserve current behavior and signatures for every `UNDECIDED` member during v0.1.0. Its provisional classification does not authorize a breaking change; any later deprecation, removal, or behavior change requires separate maintainer review and explicit notice. No stable contract is promised for these members by this release.
4. Treat `INTERNAL` types and members as implementation details outside the public compatibility promise. Keep existing supported behavior and PSR interoperability intact.
5. Preserve the current PSR-7, PSR-11, PSR-15, and PSR-17 interoperability boundary and runtime dependency constraints recorded in `composer.json`. Do not introduce a breaking change or runtime dependency change under this freeze.
6. Record any compatibility exception separately with the affected symbol, rationale, migration path, approver, and date. A release note, issue, inventory entry, or code change alone is not approval.

## Decisions already made

**Three provisional types — DECIDED 2026-11-28.** `Point\\Container\\Definition\\HttpDefinitions`, `Point\\Container\\Definition\\PointDefinitions`, and `Point\\Routing\\PipelineRunner` remain `UNDECIDED`. Preserve their current signatures and behavior for v0.1.0 without promising a stable public API contract. This decision is retained as part of the full API freeze approved on 2026-11-28.

**PHP-CS-Fixer baseline exception — ACCEPTED for v0.1.0 only, 2026-11-28.** The global check remains FAIL; the acceptance does not turn it into PASS. Details are recorded in [the style exception](../audit/release-validation/STYLE-BASELINE-EXCEPTION.md).

## Full API freeze approval — maintainer decision 2026-11-28

The maintainer explicitly approved all remaining dispositions in [the API disposition worksheet](STABLE-0.1.0-API-DISPOSITION-PROPOSAL.md), including:

1. **APPROVED (worksheet #1):** 49 public stable-candidate types, 10 public extension contracts and 9 internal types, with inventoried member-level categories taking precedence over containing type categories.
2. **APPROVED (#2):** v0.1.x compatibility policy for documented stable signatures and behavior, named arguments and defaults, return types/values, documented exceptions, and compatible extension additions.
3. **APPROVED (#3):** `Router::__construct()`, `Route::__construct()` and `RouteGroup::__construct()` remain callable and behavior-compatible in v0.1.0 but their direct-construction contracts are provisional.
4. **APPROVED (#4):** `RouterInterface::getRouteCollector()` and `Router::getRouteCollector()` remain public extension contracts with existing return type and behavior; raw FastRoute collector mutation/internal integration remains provisional.
5. **APPROVED (#5):** `RouteGroup::__invoke()` is internal; the public `parseAcceptHeader()` helper remains available but provisional, with current behavior preserved for v0.1.0.
6. **PREVIOUSLY APPROVED (#6):** `HttpDefinitions`, `PointDefinitions` and `PipelineRunner` remain `UNDECIDED`/provisional, compatible for v0.1.0 without a stable public API promise.
7. **APPROVED (#7):** Existing PSR-7/11/15/17 interoperability, Composer runtime dependencies, and PHP `>=8.4.27` remain the accepted boundary. Host/proxy/TLS/body-limit and deployment security remain application/server responsibilities.

## Full API freeze approval record

| Field | Maintainer entry |
| --- | --- |
| Decision | **APPROVED — complete Point v0.1.0 API Freeze** |
| Approved policy revision | The seven dispositions above and the v0.1.x compatibility policy in this record, as proposed in the worksheet on the `1af31cf7c576a7efb9c62354539da18d22de56ea` branch HEAD |
| Approver | Project maintainer (explicit approval in conversation) |
| Date | 2026-11-28 |
| Exceptions or follow-up issue | Provisional constructors/helpers/types and raw FastRoute interaction retain existing v0.1.0 behavior without stable-contract promises; no new breaking changes are authorized |

**Approval statement:** The maintainer approved the full v0.1.0 API Freeze, including worksheet items 1, 2, 3, 4, 5, and 7, while preserving the previously approved item 6. This is approval of the API contract only, not production deployment or publication.

No Stable tag, merge, GitHub Release, or Packagist publication is authorized by this API record.
