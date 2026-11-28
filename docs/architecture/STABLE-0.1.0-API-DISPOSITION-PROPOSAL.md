# Point v0.1.0 Stable API disposition — maintainer approval worksheet

**Status: PENDING MAINTAINER APPROVAL.** This worksheet contains proposed dispositions, not an approved API freeze, runtime change, or release authorization. The canonical record is [STABLE-0.1.0-API-FREEZE.md](STABLE-0.1.0-API-FREEZE.md).

## Reviewed scope

- Release branch review base before these approval-record updates: `c40d34652e707e12162431d89dd228c04acf9aaa`; tree `f18bd42b594996d047581bfb60c4ca4ed7cccf96`.
- CI-tested package source: `01fd91e6c51cd63aa73d2e5a1383353a5ba37789`; tree `4511d694e566426b35ee2005a11513fc27ce37b9`.
- Reproducible tree-based distribution: SHA-256 `4F289FE5A94B710E732A8D74B9B349C05D5D3012EC58BB7A69B5988BC208FF37`, 80 files (`git archive --format=zip --mtime=2026-11-28T00:00:00Z`). Its file contents match the prior CI-tested 80-file distribution byte for byte; release decision documents are export-ignored.
- Inventory: 71 types — 49 `PUBLIC STABLE CANDIDATE`, 10 `PUBLIC EXTENSION CONTRACT`, 9 `INTERNAL`, 3 `UNDECIDED`.

The current runtime is unchanged from the CI-tested package. See the canonical API freeze for the reproducible artifact comparison and current package identity.

## Recommended dispositions

Please mark each item **Approve**, **Amend**, or **Reject** and add any amendment to the decision record.

| # | Proposed disposition | Maintainer decision |
| --- | --- | --- |
| 1 | **Stable surface.** Approve the 49 stable-candidate types and 10 extension contracts; retain the 9 internal types as internal. The method-level category is authoritative where it narrows its containing type's category. | PENDING |
| 2 | **Compatibility policy.** In v0.1.x, preserve documented stable signatures and behavior, including parameter names/defaults for named arguments, return types/values, documented exceptions, and extension protocol semantics. Additions must remain compatible. | PENDING |
| 3 | **Routing constructors.** Keep `Router::__construct()`, `Route::__construct()`, and `RouteGroup::__construct()` callable with their current signatures and behavior in v0.1.0, but treat direct construction and constructor contracts as provisional. The `App`, `RouterInterface`, and `RouteInterface` documented routing surfaces remain the supported usage path. | PENDING |
| 4 | **Collector access.** Keep `RouterInterface::getRouteCollector(): FastRoute\\RouteCollector` and the `Router` implementation method public, with their existing signatures and behavior classified as `PUBLIC EXTENSION CONTRACT`. Treat direct mutation of the collector and reliance on FastRoute internals as advanced provisional integration; preserve present behavior in v0.1.0. | PENDING |
| 5 | **Other member exceptions.** Keep `RouteGroup::__invoke()` internal to group callback execution. Keep the public `parseAcceptHeader()` helper available with current behavior for v0.1.0 but provisional (`UNDECIDED`), without a stable future contract. | PENDING |
| 6 | **Already decided provisional types.** `HttpDefinitions`, `PointDefinitions`, and `PipelineRunner` remain `UNDECIDED`; preserve current signatures and behavior for v0.1.0 without a stable public contract. This was explicitly decided on 2026-11-28 and is not reopened by this worksheet. | RECORDED — not a new request |
| 7 | **PSR/dependencies.** Preserve current PSR-7, PSR-11, PSR-15, and PSR-17 interoperability and the existing runtime dependency set in `composer.json` (`php >=8.4.27`). Server and application responsibilities include trusted Host/forwarded-header handling, TLS termination, request-size limits, and deployment configuration. | PENDING |

### Classification detail

- The class/type category communicates its candidate role. It does not promote every method automatically.
- Method-level categories govern each inventoried member. In particular, the three constructors above are `UNDECIDED`; `RouteGroup::__invoke()` is `INTERNAL`; `parseAcceptHeader()` is `UNDECIDED`; `getRouteCollector()` is a public extension contract.
- `UNDECIDED` means preserve current signatures and behavior for v0.1.0 while withholding a stable compatibility promise. It does not permit an unannounced break in this release.
- The remaining inventory must continue to match these dispositions before approval. No runtime signature or dependency change is part of this proposal.

## Approval record

- Decision: PENDING — approve / amend / reject each numbered disposition.
- Approved policy revision: PENDING.
- Approver: PENDING.
- Date: PENDING.
- Exceptions / migration notes / follow-up issue: PENDING.

Approval statement: “I approve the v0.1.x compatibility policy and API dispositions above, subject to the exceptions recorded here.”

Approver: ____________________  Date: ____________________

No Stable tag, merge, GitHub Release, or Packagist publication is authorized by this worksheet.
