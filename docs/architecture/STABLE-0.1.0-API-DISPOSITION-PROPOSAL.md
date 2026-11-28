# Point v0.1.0 Stable API disposition — maintainer review proposal

**Status: DRAFT — NOT APPROVED.** Prepared on 2026-11-28. This is a proposed policy record, not full API Freeze approval, a runtime change, or release authorization.

## Scope

- Branch: `release/v0.1.0-stable-candidate`
- CI-validated source: `01fd91e6c51cd63aa73d2e5a1383353a5ba37789`; tree `4511d694e566426b35ee2005a11513fc27ce37b9`.
- Machine-readable inventory: `docs/architecture/BETA-1-API-INVENTORY.json` (71 types: 49 PUBLIC STABLE CANDIDATE, 10 PUBLIC EXTENSION CONTRACT, 9 INTERNAL, 3 UNDECIDED).
- Policy/decision record: `docs/architecture/STABLE-0.1.0-API-FREEZE.md`.

## Recommended dispositions requiring explicit maintainer approval

1. **v0.1.x compatibility.** Maintain documented stable API signatures, parameter names/defaults, return types, documented exceptions and behavior, and the ten extension interface contracts. Any breaking exception requires separate documented maintainer approval.
2. **Inventory classification.** Accept the existing 49 stable-candidate types and ten extension contracts, with an explicit method-level clarification for provisional constructors below. Keep the nine internal types classified as internal.
3. **Already approved provisional types.** `Point\Container\Definition\HttpDefinitions`, `Point\Container\Definition\PointDefinitions`, `Point\Routing\PipelineRunner` remain UNDECIDED/provisional, compatible for v0.1.0 without a promised stable public contract (maintainer decision 2026-11-28).
4. **Direct construction.** Application routing through documented `App`, `RouterInterface` and `RouteInterface` is part of the stable surface where inventoried. Direct `new Router(...)`, `new Route(...)`, and `new RouteGroup(...)` must keep current behavior working in v0.1.0, but their constructor signatures and low-level collaborators are **provisional** until the maintainer approves making them stable. Align the method-level inventory before full freeze sign-off; current inventoried constructor categories may imply stability.
5. **FastRoute boundary.** Preserve `RouterInterface::getRouteCollector(): FastRoute\RouteCollector` as a public interface method with its observed contract. Direct collector mutations and integration with FastRoute internals remain provisional but compatible during v0.1.0. Do not silently break the method or its callers.
6. **Interoperability.** Accept the present PSR-7/11/15/17 integration boundary, documented HTTP provider behavior and existing Composer runtime dependencies (`php >=8.4.27`). Application/server responsibilities include trusted Host/Forwarded headers, TLS termination, body-size ingress policy and production deployment configuration.

## Required follow-through after explicit approval

- Reconcile any constructor/method-level provisional categories in `BETA-1-API-INVENTORY.json`, without modifying runtime source or public signatures.
- Record approver, decision date and exact approved policy in `STABLE-0.1.0-API-FREEZE.md`.
- Update release-readiness evidence and verify that document-only changes do not affect the Composer distribution.
- Preserve the existing one-release PHP-CS-Fixer exception; full-tree style remains FAIL.
- Keep publication BLOCKED until real production deployment acceptance or explicit maintainer deferral and separate final release authorization.

No merge, Stable tag, GitHub Release or Packagist publication is authorized by this draft.
