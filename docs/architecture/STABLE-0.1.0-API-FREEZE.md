# Point v0.1.0 Stable API freeze — approval record

**Status: PARTIAL MAINTAINER DECISION — 2026-11-28.** The three undecided types have a maintainer decision; the full API freeze is not yet approved.

## Candidate scope

- Release branch: `release/v0.1.0-stable-candidate`
- Reviewed runtime candidate: `0600aa6f07214904613fce9ab88255d22d881295`
- Reviewed runtime tree: `32ac15dbe0d3e70433433eb0d5d6b730945a25e6`
- PHP minimum: `>=8.4.27`
- Composer package: `trypoint/point`

The reviewed source change from RC.1 changes the value of `App::VERSION` to `0.1.0`, updates XML middleware implementation, and adds a private XML rendering helper. It does not change public method or interface signatures. Runtime dependencies are unchanged from RC.1. These facts do not substitute for approval of the API policy below.

## Proposed compatibility policy

The machine-readable [API inventory](BETA-1-API-INVENTORY.json) records 71 Point types: 49 `PUBLIC STABLE CANDIDATE`, 10 `PUBLIC EXTENSION CONTRACT`, 9 `INTERNAL`, and 3 `UNDECIDED`. The inventory preserves the Beta.1 classifications and has been refreshed to identify this candidate and `App::VERSION`; a future release that changes the inventory must review the changed signatures and classifications explicitly.

If approved, the v0.1.x compatibility policy will be:

- Preserve documented public signatures, parameter names/defaults, return values, exceptions, and behavior for the stable candidate APIs.
- Preserve the listed extension interface signatures and protocol semantics; additive changes must keep existing implementations working.
- Keep provisional and `UNDECIDED` behavior available and compatible until a separate maintainer-approved change documents any deprecation or removal. Do not infer permission to break them from their provisional classification.
- Keep internal implementation details out of the compatibility promise, while preserving supported behavior and PSR interoperability.
- Require a separate review record for any compatibility exception. Do not treat a release note, issue, or implementation change as approval by itself.

## Decisions requiring maintainer sign-off

1. Approve or amend the proposed v0.1.x compatibility policy above.
2. Confirm the inventory's 49 stable-candidate and 10 extension-contract classifications.
3. **DECIDED (2026-11-28):** `HttpDefinitions`, `PointDefinitions`, and `PipelineRunner` remain `UNDECIDED` in the inventory. Preserve their current signatures and behavior compatibly for v0.1.0, without promising a stable public contract. Any later breaking change requires separate maintainer review and explicit notice.
4. Decide whether direct construction of `Router`, `Route`, and `RouteGroup`, and the raw collector integration, are supported public contracts. Until that decision, preserve their current behavior.
5. Confirm that the documented PSR-7, PSR-11, PSR-15, and PSR-17 interoperability boundary and the current runtime dependency set are acceptable for v0.1.0.

## Approval record

| Field | Maintainer entry |
| --- | --- |
| Decision | PARTIAL: provisional compatibility policy for the three undecided types approved; remaining freeze decisions pending |
| Approved policy revision | Partial decision only; no blanket approval for the 49 stable candidates, 10 extension contracts or direct-construction/collector boundaries |
| Approver | Project maintainer (explicit decision recorded in conversation) |
| Date | 2026-11-28 |
| Exceptions or follow-up issue | Complete sign-off for decisions 1, 2, 4 and 5 above; the three types remain provisionally compatible, not stable |
