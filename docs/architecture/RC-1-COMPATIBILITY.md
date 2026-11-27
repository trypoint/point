# RC.1 compatibility

RC.1 carries forward the approved Beta.1 policy: 52 supported public API types, 10 supported extension contracts, eight internal types, one unresolved type and zero deprecated types. The approved classifications take precedence over the proposal-era labels in the historical Beta inventory. RC.1 integrates the existing base-path middleware into default Point routing and corrects detection. Public signatures, Composer requirements and the dependency lock are unchanged.

## Compatibility guarantee

Supported public API preserves documented signatures, named parameters, return and exception contracts, and supported observable behavior. Supported extension contracts also preserve compatibility for existing implementers and documented service overrides. Internal implementation details can change while supported behavior remains compatible. Public PHP visibility and concrete service IDs alone do not confer support. Any proposed breaking change needs separate approval.

The public API includes the documented definitions produced by `PointDefinitions::getDefinitions()` and `HttpDefinitions::getDefinitions()`, as well as opt-in use of `BasePathMiddleware`. The documented `Router::getRouteCollector()` and its `RouterInterface` signature preserve access to the application collector. FastRoute defines that collector's own semantics.

## Boundaries kept provisional

| Surface | RC.1 decision |
| --- | --- |
| `PipelineRunner`, including its constructor, `handle()` and `withPipeline()` | Preserve behavior and signatures. Independent pipeline composition remains unresolved. |
| `Router::__construct(PipelineRunner)` | Preserve direct construction. The regression protects a configured PSR-15 endpoint returning 202, including middleware wrapping and HEAD behavior. |
| `Route::__construct()` | Preserve direct construction. The stable application path remains registration through App, Router or RouteGroup. |
| `RouteGroup::__construct()` | Preserve direct construction and collector binding. Keep `RouteGroup::__invoke()` internal to registration. |
| `parseAcceptHeader()` on the trait and its four supplied renderers | Preserve behavior and signatures. Negotiated error responses are covered independently of this helper's API classification. |

## Deployment boundary

Point uses the PSR-7 request supplied by its host or provider. The edge owns host validation, TLS termination and forwarding-header filtering. Applications that consume those headers must verify the proxy connection. Preserve the original request path through the front controller. Default Point routing detects the mount from SCRIPT_NAME and keeps a detected mount for the application instance. Set the base path explicitly if the public prefix differs from the script directory or the server cannot supply that directory. An explicit path takes precedence over detection. Forwarded headers play no role in this detector.

See [RC.1 release readiness](RC-1-RELEASE-READINESS.md) for the tested server stacks and unavailable environments.
