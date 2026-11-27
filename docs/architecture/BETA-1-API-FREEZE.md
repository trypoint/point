# Beta.1 public API freeze

Baseline v0.1.0-alpha.7 at `978fac89d29cf6248ba8d2f8182709f3bb1dce89`. [Alpha.7 inventory](ALPHA-7-API-STABILITY-AUDIT.md) · [Machine-readable inventory](BETA-1-API-INVENTORY.json)

The inventory reflects 71 Point types, including the two revision implementation types excluded from Alpha.7's 69-type appendix. Each method records the declaring type, signature, constructor status, parameter defaults, return type and documented exceptions. All 69 Alpha.7 types and 232 method entries match by type and member name. The broader reflection scan also includes inherited Point methods. Git detects one production file change, confined to the implementation of response emission. There are no proposed breaking changes or newly deprecated symbols.

| Classification | Proposed guarantee |
| --- | --- |
| PUBLIC STABLE CANDIDATE | Preserve documented calling signatures, named arguments, return and exception contracts, and supported behavior during the Beta cycle. |
| PUBLIC EXTENSION CONTRACT | Preserve implementation signatures and documented protocol semantics. Additive changes must keep existing third-party implementations working. |
| INTERNAL | Implementation details may evolve while supported behavior remains compatible. |
| DEPRECATED | When used, document migration and removal targets. No current entries. |
| UNDECIDED | Keep current behavior while settling the support boundary. Any breaking proposal requires separate review. |

Application code should start with `AppFactory::create()`. `createFromContainer()` preserves external PSR-11 ownership and lets callers opt in with `withDefaults()` or configure middleware explicitly. FIFO user middleware, group scope, custom PSR-15 handlers and the independent Router 202 behavior remain compatibility requirements. The raw collector and direct construction of Router, Route and RouteGroup have an undecided support boundary. Internal pipeline types can still appear in service definitions and concrete signatures.

The inventory includes 31 core IDs and six server HTTP IDs. The HTTP slice provides Point's globals creator and PSR-17 response, server-request, stream, uploaded-file and URI factories. Outbound RequestFactoryInterface is outside the default service set. The existence of a concrete service key alone does not establish a supported override. Documented interface IDs, explicit HTTP factory overrides, definitions and invocation strategies are the preferred extension points.

SimpleContainer shares resolved values including null. Missing IDs and failed factories preserve the PSR-11 distinction. Named URLs observe route names at call time, include the configured base path and encode path parameters once. Unknown names raise UnexpectedValueException and missing route parameters raise InvalidArgumentException. Production error output masks private exception details unless explicitly configured otherwise. ResponseEmitter handles repeated fields and suppresses body emission for informational, 204, 205 and 304 responses.
