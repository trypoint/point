# Point Framework v0.1.0-alpha.7 API stability audit

## Alpha.7 implementation follow-up

The fixes were applied in an isolated copy of the candidate. The original working tree and staging were preserved. The fetched Alpha.6 tag and remote main both resolve to `d408aa78ae32d3bde76e944dba10cd77334381aa`.

| Finding | Root cause and correction | Verification |
| --- | --- | --- |
| P1 · Independent Router endpoint | The original candidate classified every concrete Router instance as the built-in routing endpoint. Restored Alpha.6's identity check against the application router and concrete-router container binding. The existing pipeline wraps the independently configured handler. | Published Alpha.6 returned 202, the original candidate returned 404, and the corrected candidate returned 202 on both PHP versions. Added tests cover an explicit alias and an independent Router, single execution, middleware wrapping, HEAD, and defaults disabled. Existing tests cover the default router, non-Router custom handlers, 404/405/500, and external PSR-11 configuration. |
| P2 · SimpleContainer examples | Earlier examples assumed reflection-based construction. The README and container guide now demonstrate registered instances, closures, shared results, and missing IDs. PHP-DI remains the optional autowiring path. | The documented built-in examples ran from each clean production install. The PHP-DI bootstrap examples also passed on both runtimes with PHP-DI installed separately as a consumer dependency. |
| P2 · PHPUnit discovery | Two secondary classes shared the HTTP factory test file. Moved the globals test to its matching PSR-4 file and the emitter assertion into the existing emitter test class. | PHPUnit listed and ran `ServerRequestCreatorTest::testCanCreateFromGlobals` and `ResponseEmitterTest::testEmitterExists`. Both original assertions about emitter registration and the original globals assertion were retained. |

### Corrected candidate validation

| Check | PHP 8.4.27 | PHP 8.5.12 |
| --- | --- | --- |
| `composer validate --strict` | Pass | Pass |
| `composer dump-autoload -o --strict-psr` | Pass | Pass |
| `composer test` | 269 tests, 641 assertions | 269 tests, 641 assertions |
| `composer stan` | Level 8, zero errors | Level 8, zero errors |
| `composer cs:check` | 0 of 104 files | 0 of 104 files |
| Packaged clean `composer install --no-dev` and strict optimized autoload | Pass, nine packages | Pass, nine packages |
| Packaged Hello World over HTTP | 200, exact greeting | 200, exact greeting |
| External web and REST consumers | 15 of 15 | 15 of 15 |
| Independent Router consumer probe | 202, wrapped once, empty HEAD body | 202, wrapped once, empty HEAD body |
| HttpSoft, Nyholm, Laminas, Guzzle and single response factory override | Pass | Pass |

The corrected production PHP source matches published Alpha.6 after line-ending normalization. Both Composer manifests match as well. Public signatures, runtime dependencies, minimum PHP version, and service IDs therefore match that release. The production ZIP was generated from an isolated Git tree using the candidate's export rules.

The three audit findings are resolved for this candidate. The consumer suites use in-process PSR-7 requests. The Hello World smoke test verifies loopback HTTP emission through PHP's built-in server. The broader multipart, SAPI, and proxy coverage limits documented below still apply. Repeat the package checks on the artifact selected for Beta.1 and again before RC.1.

## Original audit record

## 1. Executive summary

Point's published Alpha.6 supports the tested web and REST workflows with its current core. The local Alpha.7 candidate regresses one custom PSR-15 endpoint configuration that Alpha.6 supports. Restore that behavior, correct the container examples and repair HTTP test discovery before freezing the API for Beta.1. RC.1 should follow validation of the actual Beta release artifact.

## 2. Repository and release baseline

| Ref | Observed revision |
| --- | --- |
| Starting local `HEAD`, tag `v0.1.0-alpha.3` | `c313b2c9320d0eef916e7164d1f138a5f80a6bf3` |
| Fetched `origin/main`, peeled remote `v0.1.0-alpha.6` tag, Packagist Alpha.6 | `d408aa78ae32d3bde76e944dba10cd77334381aa` |
| Remote Alpha.6 annotated tag object | `af24b02b1fad5330eae9ce8a03025c02cfd438b3` |

GitHub marks Alpha.6 as a public prerelease. The GitHub API's conventional latest-release endpoint returned Alpha.1 because it omits prereleases. Alpha.6 is the highest remote tag and is published on Packagist. The local branch was seven commits behind, with two staged paths (`.gitignore` and removal of `START-HERE.md`), existing unstaged source, tests and documentation, and untracked audit and routing test files. The candidate was tested from a snapshot of effective files, including the untracked routing tests. [GitHub Alpha.6 prerelease](https://github.com/trypoint/point/releases/tag/v0.1.0-alpha.6) · [Packagist](https://packagist.org/packages/trypoint/point)

Graphify identified the bootstrap, container, request pipeline, route collection, dispatcher and URL generation boundaries. The earlier Alpha.3 audit corrections are recorded in the changelog, the Alpha.4 and Alpha.5 reviews are available in the local architecture directory, and the published Alpha.6 includes the routing performance report. Alpha.3 addressed status, HEAD, negotiation, encoding, late registration and container handling. Alpha.4 corrected retained groups and incomplete provider detection. Alpha.5 corrected direct collector prefixes and scope, container exception classification and informational emission. Alpha.6 split undiscovered routing classes and added the architecture regressions discussed below.

## 3. Public API inventory

The appendix lists named public declarations from the release, including effective trait methods and constants declared by each reflected type. The count excludes inherited external methods, anonymous implementation classes and the two revision classes marked `@internal`. PHP visibility includes implementation surfaces, so inclusion here does not assign a stability guarantee.

| Surface | Behavior to preserve |
| --- | --- |
| `App`, `AppFactory` | Default factory enables routing and error middleware. External factory retains caller ownership and leaves defaults disabled until requested. `handle()` returns a PSR-7 response, while `run()` can create a request from globals and emit. Factory overrides are keyed by container service ID. |
| `Router`, `Route`, `RouteGroup`, `RouteMatch` | Verb registration and `map()` accept callables or container-resolved strings. Groups apply path prefixes and inherited middleware. Match status controls access to route, arguments and allowed methods. The collector is exposed and supports late registration. |
| `UrlGenerator` | Container-resolved contract with an explicit route name, argument map and query map. Relative URLs include the configured base path. Parameters are encoded once and lookup observes names at call time, with compiled traversal order deciding duplicate precedence. |
| `SimpleContainer`, factories, definitions and resolver | Explicit values and lazy factories, shared results including null, distinction between missing service and failed construction. External PSR-11 containers can supply equivalent services. |
| Middleware and strategies | PSR-15 instances plus documented callable and container ID adapters. FIFO for user middleware with defaults surrounding the user pipeline. Group order is outer to inner, followed by route middleware. Invocation strategy determines how PSR-7 messages and route arguments reach handlers. |
| HTTP adapter definitions | Select first complete installed provider in order Nyholm, Laminas, Guzzle, HttpSoft. Built-in runtime dependencies provide the HttpSoft fallback. Definitions bind five server-side PSR-17 interfaces and Point's globals creator. |
| Exceptions and renderers | Specialized statuses, 405 Allow, Accept-dependent rendering, opt-in detailed output, optional PSR-3 logging. Renderer and stream factory services are replaceable. |
| `ResponseEmitter` | Emitter interface, configurable chunk size, stream-aware detection, repeated response headers, and body suppression for 1xx, 204, 205 and 304. PSR-7 objects and SAPI emission have different responsibilities. |

## 4. PSR interoperability matrix

On both PHP runtimes a separate override created a Guzzle response for a Nyholm request using a single explicit ResponseFactoryInterface binding. The provider probe supplied each adapter explicitly through `AppFactory::create($definitions)`. It checked request and response classes, URI and stream creation, an uploaded-file object passed through the request, and globals creation. `RequestFactoryInterface` for outbound requests was absent for all four, so describe Point's server-side bindings explicitly. PSR-17 does not require a framework container to provide all factory interfaces.

| Provider and version | PSR-7 request and response | PSR-17 server-side adapters | Globals creation |
| --- | --- | --- | --- |
| HttpSoft Message 1.1.6, Server Request 1.1.1 | Pass | Pass | Pass |
| Nyholm PSR-7 1.8.2, PSR-7 Server 1.1.0 | Pass | Pass | Pass |
| Laminas Diactoros 3.10.0 | Pass | Pass | Pass |
| Guzzle PSR-7 2.13.4 | Pass | Pass | Pass |

PSR-11 coverage includes explicit null definitions, factory failures and external PHP-DI configuration. PSR-15 coverage includes FIFO, consecutive requests, short-circuiting, exception propagation and custom handlers. For the three Alpha.6 architecture fixes, explicit renderer/logger failure recovery and nested plain FastRoute group prefix recovery remain covered by their existing tests. Custom endpoint routing has the additional candidate-only regression below.

## 5. Confirmed defects

| Severity | Published Alpha.6 | Local candidate |
| --- | ---: | ---: |
| P0 | 0 | 0 |
| P1 | 0 | 1 |
| P2 | 2 | 2 |
| P3 | 0 | 0 |

### P1 · Automatic defaults bypass an independent Router endpoint

- **Reproduction.** Override `RequestHandlerInterface::class` in `AppFactory::create()` with a factory that creates a fresh `Router($container->get(PipelineRunner::class))`. Register a middleware on that router returning a 202 response. Handle `GET /probe` without registering it on the app router. Published Alpha.6 returns 202. The candidate returns 404.
- **Root cause.** `App::buildEffectiveMiddleware()` treats every instance of the concrete `Router` as the built-in routing endpoint. Alpha.6 also checks the identity of the application router or configured concrete-router service.
- **Impact.** An explicitly configured PSR-15 endpoint and its middleware can be skipped when automatic defaults are enabled.
- **Minimal correction.** Retain identity-sensitive recognition of Point's routing endpoint. Cover split bindings as well as the ordinary alias.
- **Regression recommendation.** Bind a second Router as the request handler and assert its distinct response and that application middleware still wraps it. Keep the default-alias, explicitly configured routing and external-container cases. [App::buildEffectiveMiddleware](../../../Point/App.php#L278) · [Default bootstrap integration tests](../../../tests/Integration/DefaultBootstrapTest.php)

### P2 · The container guide promises autowiring that SimpleContainer does not implement

- **Reproduction.** Evaluate the documented one-argument `$container->set(stdClass::class)`, which throws `ArgumentCountError`. Resolving the unregistered class through `get(stdClass::class)` throws a PSR-11 not-found exception.
- **Root cause.** `SimpleContainer::set()` requires a definition and `get()` resolves registered entries. The README and container guide describe automatic construction.
- **Impact.** Onboarding examples fail before the request can be handled.
- **Minimal correction.** Document instances and factory closures for `SimpleContainer`. Keep autowiring examples in the PHP-DI section.
- **Regression recommendation.** Run the replacement example with development dependencies absent and retain the existing missing-entry tests. [SimpleContainer](../../../Point/Container/SimpleContainer.php#L56) · [Container guide](../../container.md#L37)

### P2 · Two HTTP test cases are excluded by PHPUnit discovery

- **Reproduction.** Run `php vendor/bin/phpunit --list-tests tests/Unit/Http/Psr7FactoryTest.php`. PHPUnit lists six methods from `Psr7FactoryTest`. It omits `ServerRequestCreatorTest::testCanCreateFromGlobals` and the secondary `ResponseEmitterTest::testEmitterExists`.
- **Root cause.** Both secondary classes live in a file whose basename matches only `Psr7FactoryTest`.
- **Impact.** The reported suite count excludes those two assertions about default bootstrap. The separate provider probes supply globals coverage for this audit.
- **Minimal correction.** Move the two methods into the matching class or give each class its own PSR-4 filename.
- **Regression recommendation.** Verify both names with `--list-tests` and execute the full suite on both supported patch runtimes. [HTTP factory tests](../../../tests/Unit/Http/Psr7FactoryTest.php#L102)

## 6. Developer experience

Both applications used one Point package dependency and one `AppFactory::create()` call. The web app registered four route handlers, one group and one PSR-15 middleware. Named URLs required a container lookup plus one generator call. The REST app registered five routes, one parser and one PSR-15 middleware. Its JSON helper wrote the body, selected a status and set Content-Type. One handler shared validation across three write methods. This is a workable amount of application code for a microframework.

Clarify three points before Beta.1. Show the default and external-container bootstraps side by side. Explain that explicit error middleware should wrap middleware that can throw in a FIFO pipeline. Show JSON parsing separately from application validation, including a JSON Accept header for framework errors.

## 7. Web application results

Six checks passed on PHP 8.4.27 and 8.5.12. The isolated application returned the exact `Hello, World!` body, added a middleware header, served grouped static HTML, generated and dispatched a named dynamic route, emitted expected 404 and 405 results through `handle()` with implicit HEAD in Allow, and rendered a generic 500 without the synthetic private exception message.

## 8. REST API results

Nine checks passed on both runtimes. GET returned 200 with the route argument. POST returned 201. PUT and PATCH returned 200 with parsed input. DELETE returned 204 and an empty body. Invalid input returned application-level 422, malformed JSON returned 400, and the framework supplied JSON 404 and 405 responses when requested. Successful JSON responses carried the middleware header. The demos used in-process PSR-7 requests and response assertions. Each external directory then installed the exact published Point version through its own Composer manifest, and all assertions passed again on both runtimes.

## 9. Slim comparison

Source comparison pinned Slim 4.15.3 to `3675bf6baac66b07032575b7bef4200b60b7974b` and Slim 5 development to `b0d9f8131f13a1b1ac5c786e5ebc63d41eecef02`. Slim 5 results describe that development revision. [Slim 4.15.3 source](https://github.com/slimphp/Slim/tree/3675bf6baac66b07032575b7bef4200b60b7974b) · [Slim 5 development source](https://github.com/slimphp/Slim/tree/b0d9f8131f13a1b1ac5c786e5ebc63d41eecef02)

| Area | Point Alpha.6 | Slim 4.15.3 | Slim 5 development |
| --- | --- | --- | --- |
| Install and HTTP | Bundled HttpSoft fallback, optional provider overrides | Install a supported PSR-7 implementation or supply a response factory | Factory uses container definitions and provider discovery with equivalent explicit override options |
| Bootstrap | `AppFactory::create()` enables defaults | Factory discovers response factory. Application explicitly adds routing and error middleware as needed | `create($definitions)` or `createFromContainer()`. `App` exposes explicit routing and error registration |
| Routes and middleware | Verb helpers and groups, PSR-15 FIFO user middleware | Similar verb helpers and groups, LIFO application middleware | Similar verbs and groups, revised middleware composition |
| DI and extension | Shared definitions and optional external PSR-11 container, public strategy and routing contracts | Optional PSR-11 with explicit factory injection and resolver interfaces | Container-composed construction and service bindings |
| Error handling and ergonomics | HTML/JSON defaults make the quick start short. Explicit FIFO order needs a clear example | Explicit setup exposes order and renderer configuration | Explicit setup with container-configured services |

Point can keep the one-call bootstrap. The strongest comparative improvement is clearer documentation of middleware order and the service IDs needed for external containers.

## 10. Security and correctness findings

No P0 was confirmed. Existing coverage exercises production error masking, safe HTML rendering, Accept quality and exclusions, HEAD and 405 Allow, informational responses, 204 length handling, output buffering, single encoding and encoded-slash rejection, and live dispatcher rebuilding. For uploaded files the provider probe covered PSR-17 factory creation and consumption by a route. Actual multipart upload normalization and a comprehensive SAPI header or proxy matrix remain uncovered by this audit. The P1 above affects handler selection. It does not establish an authorization bypass.

## 11. Performance observations

Alpha.6's report attributes large mixed-table named-route cost to FastRoute data generation and traversal. Its 10,000-route medians were about 2.34 ms for the last static name and 23.74 ms for the last mixed name. These are historical results, with no new timing claim. Renames, duplicate precedence, custom collectors, and late registration make a registration-only revision unreliable for name lookup. No performance change is indicated by the confirmed defects. [Alpha.6 report](ALPHA-6-ROUTING-PERFORMANCE.md)

## 12. PHP compatibility results

Both Windows CLI runtimes were verified with `php -v`. PHPUnit 13.4.8, PHPStan 2.3.7 and PHP-CS-Fixer 3.95.32 came from the lock file.

| Check | Published Alpha.6 · 8.4.27 | Published Alpha.6 · 8.5.12 | Candidate · 8.4.27 | Candidate · 8.5.12 |
| --- | --- | --- | --- | --- |
| `composer validate --strict` | Pass | Pass | Pass | Pass |
| `composer dump-autoload -o --strict-psr` | Pass | Pass | Pass | Pass |
| `composer test` | 265 tests, 629 assertions | 265 tests, 629 assertions | 265 tests, 629 assertions | 265 tests, 629 assertions |
| `composer stan` | Level 8, zero errors | Level 8, zero errors | Level 8, zero errors | Level 8, zero errors |
| `composer cs:check` | 0 of 103 files | 0 of 103 files | 0 of 103 files | 0 of 103 files |
| Clean Alpha.6 `composer install --no-dev --no-interaction --prefer-dist` and strict optimized autoload | Pass, nine runtime packages | Pass, nine runtime packages | — | — |

The actual Packagist distribution ZIP was downloaded and independently passed the clean install and strict optimized autoload checks on PHP 8.5.12. It contains the migration guide linked from the README. The initial tag archive excluded tests and CI tooling. The test snapshots restored those files from the exact tag blobs and normalized the archived Windows PHP line endings to LF for the style check. The first style run on the unnormalized archive reported 71 files. The initial local-candidate PHPStan run hit a Windows result-cache rename error. It passed when rerun with an isolated cache directory. Early PHP 8.4.27 Composer attempts required enabling OpenSSL and ZIP in the isolated runtime. PHP-CS-Fixer on PHP 8.5.12 emitted its advisory about running above the PHP 8.4 minimum.

During setup a failed directory switch caused Composer to remove development dependencies in the repository. After explicit user authorization, Composer restored the 71 removed packages, and the installed package list returned to 80. The lock file hash was unchanged and the three development tool launchers were present afterward.

## 13. Recommended minimal corrections

1. Preserve explicit endpoint selection for a separately configured `Router`.
2. Correct the README and container guide examples for explicit definitions. Document the server-side factory bindings and the automatic provider order.
3. Restore PHPUnit discovery for the two HTTP cases. Before API freeze, identify the intended extension contracts and classify public helper classes. Keep signatures compatible and retain the exact canonical greeting.

## 14. Beta readiness checklist

- [x] Verify the published release commit separately from local HEAD.
- [x] Exercise default bootstrap with both consumer applications and a clean production install.
- [x] Identify and inventory public declarations and routing and DI extension points.
- [ ] Correct the candidate's independent Router endpoint selection.
- [ ] Correct documented examples and run the exact candidate checks again after approved changes.
- [ ] Confirm the release tag and exported package with both supported patch runtimes before RC.1.

## 15. Release blockers

The candidate P1 should block promotion until its regression test passes. Correct the two P2 defects before a Beta API and documentation freeze. A short correction cycle followed by Beta.1 preparation is appropriate once the checklist is satisfied.

## Appendix · Public declaration signatures

### `Point\App`

- `__construct(Psr\Container\ContainerInterface $container)`
- `add(Psr\Http\Server\MiddlewareInterface|callable|string $middleware): Point\App`
- `addErrorMiddleware(bool $displayErrorDetails = false, bool $logErrors = true, bool $logErrorDetails = true, ?Psr\Log\LoggerInterface $logger = NULL): Point\App`
- `addMiddleware(Psr\Http\Server\MiddlewareInterface $middleware): Point\App`
- `addRoutingMiddleware(): Point\App`
- `getBasePath(): string`
- `getContainer(): Psr\Container\ContainerInterface`
- `withDefaults(bool $enable = true): Point\App`
- `group(string $path, callable $handler): Point\Routing\RouteGroup`
- `handle(Psr\Http\Message\ServerRequestInterface $request): Psr\Http\Message\ResponseInterface`
- `map(array $methods, string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `run(?Psr\Http\Message\ServerRequestInterface $request = NULL): void`
- `setBasePath(string $basePath): Point\App`
- `any(string $pattern, callable|string $handler): Point\Interfaces\RouteInterface`
- `delete(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `get(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `options(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `patch(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `post(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `put(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `query(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `const VERSION`

### `Point\Container\ContainerResolver`

- `__construct(Psr\Container\ContainerInterface $container)`
- `resolve(callable|object|array|string $identifier): mixed`
- `resolveCallable(callable|array|string $identifier): callable`

### `Point\Container\Definition\GuzzleDefinitions`

- `getDefinitions(): array`

### `Point\Container\Definition\HttpDefinitions`

- `getDefinitions(): array`

### `Point\Container\Definition\HttpSoftDefinitions`

- `getDefinitions(): array`

### `Point\Container\Definition\LaminasDefinitions`

- `getDefinitions(): array`

### `Point\Container\Definition\NyholmDefinitions`

- `getDefinitions(): array`

### `Point\Container\Definition\PointDefinitions`

- `getDefinitions(): array`

### `Point\Container\DiContainerFactory`

- `createContainer(array $definitions = array ()): Psr\Container\ContainerInterface`

### `Point\Container\SimpleContainer`

- `__construct(array $definitions = array ())`
- `get(string $id): mixed`
- `has(string $id): bool`
- `set(string $id, callable|object|array|string|int|float|bool|null $definition): Point\Container\SimpleContainer`

### `Point\Emitter\ResponseEmitter`

- `__construct(int $responseChunkSize = 4096)`
- `emit(Psr\Http\Message\ResponseInterface $response): void`
- `isResponseEmpty(Psr\Http\Message\ResponseInterface $response): bool`

### `Point\Exception\HttpBadRequestException`


### `Point\Exception\HttpException`

- `__construct(Psr\Http\Message\ServerRequestInterface $request, string $message = '', int $code = 0, ?Throwable $previous = NULL)`
- `getDescription(): string`
- `getRequest(): Psr\Http\Message\ServerRequestInterface`
- `getTitle(): string`
- `setDescription(string $description): Point\Exception\HttpException`
- `setTitle(string $title): Point\Exception\HttpException`

### `Point\Exception\HttpForbiddenException`


### `Point\Exception\HttpGoneException`


### `Point\Exception\HttpInternalServerErrorException`


### `Point\Exception\HttpMethodNotAllowedException`

- `getAllowedMethods(): array`
- `setAllowedMethods(array $methods): Point\Exception\HttpMethodNotAllowedException`

### `Point\Exception\HttpNotFoundException`


### `Point\Exception\HttpNotImplementedException`


### `Point\Exception\HttpSpecializedException`

- `__construct(Psr\Http\Message\ServerRequestInterface $request, ?string $message = NULL, ?Throwable $previous = NULL)`

### `Point\Exception\HttpTooManyRequestsException`


### `Point\Exception\HttpUnauthorizedException`


### `Point\Factory\AppFactory`

- `static create(array $definitions = array ()): Point\App`
- `static createFromContainer(Psr\Container\ContainerInterface $container): Point\App`
- `static setContainerFactory(Point\Interfaces\ContainerFactoryInterface $containerFactory): void`

### `Point\Interfaces\ContainerFactoryInterface`

- `createContainer(array $definitions = array ()): Psr\Container\ContainerInterface`

### `Point\Interfaces\ContainerResolverInterface`

- `resolve(callable|object|array|string $identifier): mixed`
- `resolveCallable(callable|array|string $identifier): callable`

### `Point\Interfaces\DefinitionsInterface`

- `getDefinitions(): array`

### `Point\Interfaces\DispatcherInterface`

- `dispatch(string $httpMethod, string $uri): array`
- `const FOUND`
- `const METHOD_NOT_ALLOWED`
- `const NOT_FOUND`

### `Point\Interfaces\EmitterInterface`

- `emit(Psr\Http\Message\ResponseInterface $response): void`

### `Point\Interfaces\ExceptionRendererInterface`

- `__invoke(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, ?Throwable $exception = NULL, bool $displayErrorDetails = false): Psr\Http\Message\ResponseInterface`

### `Point\Interfaces\MiddlewareCollectionInterface`

- `add(Psr\Http\Server\MiddlewareInterface|callable|string $middleware): Point\Interfaces\MiddlewareCollectionInterface`
- `addMiddleware(Psr\Http\Server\MiddlewareInterface $middleware): Point\Interfaces\MiddlewareCollectionInterface`

### `Point\Interfaces\RequestHandlerInvocationStrategyInterface`

- `__invoke(callable $callable, Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, array $routeArguments): Psr\Http\Message\ResponseInterface`

### `Point\Interfaces\RouteCollectionInterface`

- `any(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `delete(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `get(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `group(string $path, callable $handler): Point\Routing\RouteGroup`
- `map(array $methods, string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `options(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `patch(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `post(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `put(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `query(string $path, callable|string $handler): Point\Interfaces\RouteInterface`

### `Point\Interfaces\RouteInterface`

- `getHandler(): callable|string`
- `getMethods(): array`
- `getMiddleware(): array`
- `getName(): ?string`
- `getPattern(): string`
- `getRouteGroup(): ?Point\Routing\RouteGroup`
- `setName(string $name): Point\Interfaces\RouteInterface`

### `Point\Interfaces\RouterInterface`

- `add(Psr\Http\Server\MiddlewareInterface|callable|string $middleware): Point\Interfaces\RouterInterface`
- `addMiddleware(Psr\Http\Server\MiddlewareInterface $middleware): Point\Interfaces\RouterInterface`
- `any(string $pattern, callable|string $handler): Point\Interfaces\RouteInterface`
- `delete(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `get(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `getBasePath(): ?string`
- `getMiddleware(): array`
- `getRouteCollector(): FastRoute\RouteCollector`
- `group(string $path, callable $handler): Point\Routing\RouteGroup`
- `map(array $methods, string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `options(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `patch(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `post(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `put(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `query(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `setBasePath(string $basePath): void`

### `Point\Interfaces\ServerRequestCreatorInterface`

- `createServerRequestFromGlobals(): Psr\Http\Message\ServerRequestInterface`

### `Point\Interfaces\UrlGeneratorInterface`

- `fullUrlFor(Psr\Http\Message\UriInterface $uri, string $routeName, array $data = array (), array $queryParams = array ()): string`
- `relativeUrlFor(string $routeName, array $data = array (), array $queryParams = array ()): string`
- `urlFor(string $routeName, array $data = array (), array $queryParams = array ()): string`

### `Point\Middleware\BasePathMiddleware`

- `__construct(Point\Interfaces\RouterInterface $router)`
- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\ContentLengthMiddleware`

- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\EndpointMiddleware`

- `__construct(Point\Routing\RouteInvoker $routeInvoker, Point\Routing\PipelineRunner $pipelineRunner)`
- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\ErrorExceptionMiddleware`

- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\ExceptionLoggingMiddleware`

- `__construct(Psr\Log\LoggerInterface $logger)`
- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`
- `withLogErrorDetails(bool $logErrorDetails): Point\Middleware\ExceptionLoggingMiddleware`
- `withLogger(Psr\Log\LoggerInterface $logger): Point\Middleware\ExceptionLoggingMiddleware`

### `Point\Middleware\ExceptionMiddlewareTrait`

- `__construct(Psr\Http\Message\ResponseFactoryInterface $responseFactory)`
- `parseAcceptHeader(?string $accept): array`
- `withErrorDetails(bool $flag): self`
- `withMimeType(string $mimeType): self`

### `Point\Middleware\FormBodyParserMiddleware`

- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\HeadMethodMiddleware`

- `__construct(Psr\Http\Message\StreamFactoryInterface $streamFactory)`
- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\HtmlExceptionMiddleware`

- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`
- `__construct(Psr\Http\Message\ResponseFactoryInterface $responseFactory)`
- `parseAcceptHeader(?string $accept): array`
- `withErrorDetails(bool $flag): self`
- `withMimeType(string $mimeType): self`

### `Point\Middleware\JsonBodyParserMiddleware`

- `__construct(int $jsonFlags = 0)`
- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\JsonExceptionMiddleware`

- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`
- `withHtmlFallback(): Point\Middleware\JsonExceptionMiddleware`
- `withJsonOptions(int $options): Point\Middleware\JsonExceptionMiddleware`
- `__construct(Psr\Http\Message\ResponseFactoryInterface $responseFactory)`
- `parseAcceptHeader(?string $accept): array`
- `withErrorDetails(bool $flag): self`
- `withMimeType(string $mimeType): self`

### `Point\Middleware\MethodOverrideMiddleware`

- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\OutputBufferingMiddleware`

- `__construct(Psr\Http\Message\StreamFactoryInterface $streamFactory, string $style = 'append')`
- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`
- `const APPEND`
- `const PREPEND`

### `Point\Middleware\PlainTextExceptionMiddleware`

- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`
- `__construct(Psr\Http\Message\ResponseFactoryInterface $responseFactory)`
- `parseAcceptHeader(?string $accept): array`
- `withErrorDetails(bool $flag): self`
- `withMimeType(string $mimeType): self`

### `Point\Middleware\ResponseFactoryMiddleware`

- `__construct(Psr\Http\Message\ResponseFactoryInterface $responseFactory)`
- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\RoutingArgumentsMiddleware`

- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\RoutingMiddleware`

- `__construct(Point\Interfaces\DispatcherInterface $dispatcher, Point\Interfaces\RouterInterface $router)`
- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\XmlBodyParserMiddleware`

- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`

### `Point\Middleware\XmlExceptionMiddleware`

- `process(Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Server\RequestHandlerInterface $handler): Psr\Http\Message\ResponseInterface`
- `__construct(Psr\Http\Message\ResponseFactoryInterface $responseFactory)`
- `parseAcceptHeader(?string $accept): array`
- `withErrorDetails(bool $flag): self`
- `withMimeType(string $mimeType): self`

### `Point\Routing\FastRouteDispatcher`

- `__construct(Point\Interfaces\RouterInterface $router)`
- `dispatch(string $httpMethod, string $uri): array`

### `Point\Routing\MiddlewareCollectionTrait`

- `add(Psr\Http\Server\MiddlewareInterface|callable|string $middleware): self`
- `addMiddleware(Psr\Http\Server\MiddlewareInterface $middleware): self`
- `getMiddleware(): array`

### `Point\Routing\PipelineRunner`

- `__construct(Point\Interfaces\ContainerResolverInterface $resolver)`
- `handle(Psr\Http\Message\ServerRequestInterface $request): Psr\Http\Message\ResponseInterface`
- `withPipeline(array $pipeline): Point\Routing\PipelineRunner`

### `Point\Routing\Route`

- `__construct(array $methods, string $pattern, callable|string $handler, ?Point\Routing\RouteGroup $group = NULL)`
- `getHandler(): callable|string`
- `getMethods(): array`
- `getName(): ?string`
- `getPattern(): string`
- `getRouteGroup(): ?Point\Routing\RouteGroup`
- `setName(string $name): Point\Routing\Route`
- `add(Psr\Http\Server\MiddlewareInterface|callable|string $middleware): self`
- `addMiddleware(Psr\Http\Server\MiddlewareInterface $middleware): self`
- `getMiddleware(): array`

### `Point\Routing\RouteCollectionTrait`

- `any(string $pattern, callable|string $handler): Point\Interfaces\RouteInterface`
- `delete(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `get(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `group(string $path, callable $handler): Point\Routing\RouteGroup`
- `map(array $methods, string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `options(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `patch(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `post(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `put(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `query(string $path, callable|string $handler): Point\Interfaces\RouteInterface`

### `Point\Routing\RouteGroup`

- `__construct(string $prefix, callable $callback, FastRoute\RouteCollector $routeCollector, ?Point\Routing\RouteGroup $group = NULL)`
- `__invoke(): void`
- `getPrefix(): string`
- `getRouteGroup(): ?Point\Routing\RouteGroup`
- `group(string $path, callable $handler): Point\Routing\RouteGroup`
- `map(array $methods, string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `add(Psr\Http\Server\MiddlewareInterface|callable|string $middleware): self`
- `addMiddleware(Psr\Http\Server\MiddlewareInterface $middleware): self`
- `getMiddleware(): array`
- `any(string $pattern, callable|string $handler): Point\Interfaces\RouteInterface`
- `delete(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `get(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `options(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `patch(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `post(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `put(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `query(string $path, callable|string $handler): Point\Interfaces\RouteInterface`

### `Point\Routing\RouteInvoker`

- `__construct(Psr\Http\Message\ResponseFactoryInterface $responseFactory, Point\Interfaces\RequestHandlerInvocationStrategyInterface $invocationStrategy, Point\Interfaces\ContainerResolverInterface $resolver)`
- `handle(Psr\Http\Message\ServerRequestInterface $request): Psr\Http\Message\ResponseInterface`
- `withHandler(callable|string $handler, array $arguments = array ()): Point\Routing\RouteInvoker`

### `Point\Routing\RouteMatch`

- `static found(Point\Interfaces\RouteInterface $route, array $arguments = array ()): Point\Routing\RouteMatch`
- `getAllowedMethods(): array`
- `getArgument(string $name, mixed $default = NULL): mixed`
- `getArguments(): array`
- `getRoute(): ?Point\Interfaces\RouteInterface`
- `getStatus(): int`
- `isFound(): bool`
- `isMethodNotAllowed(): bool`
- `isNotFound(): bool`
- `static methodNotAllowed(array $allowedMethods): Point\Routing\RouteMatch`
- `static notFound(): Point\Routing\RouteMatch`

### `Point\Routing\Router`

- `__construct(Point\Routing\PipelineRunner $pipelineRunner)`
- `getBasePath(): ?string`
- `getRouteCollector(): FastRoute\RouteCollector`
- `group(string $path, callable $handler): Point\Routing\RouteGroup`
- `handle(Psr\Http\Message\ServerRequestInterface $request): Psr\Http\Message\ResponseInterface`
- `map(array $methods, string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `setBasePath(string $basePath): void`
- `add(Psr\Http\Server\MiddlewareInterface|callable|string $middleware): self`
- `addMiddleware(Psr\Http\Server\MiddlewareInterface $middleware): self`
- `getMiddleware(): array`
- `any(string $pattern, callable|string $handler): Point\Interfaces\RouteInterface`
- `delete(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `get(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `options(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `patch(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `post(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `put(string $path, callable|string $handler): Point\Interfaces\RouteInterface`
- `query(string $path, callable|string $handler): Point\Interfaces\RouteInterface`

### `Point\Routing\UrlGenerator`

- `__construct(Point\Interfaces\RouterInterface $router)`
- `fullUrlFor(Psr\Http\Message\UriInterface $uri, string $routeName, array $data = array (), array $queryParams = array ()): string`
- `relativeUrlFor(string $routeName, array $data = array (), array $queryParams = array ()): string`
- `urlFor(string $routeName, array $data = array (), array $queryParams = array ()): string`

### `Point\Strategy\RequestHandler`

- `__invoke(callable $callable, Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, array $routeArguments): Psr\Http\Message\ResponseInterface`

### `Point\Strategy\RequestResponse`

- `__invoke(callable $callable, Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, array $routeArguments): Psr\Http\Message\ResponseInterface`

### `Point\Strategy\RequestResponseArgs`

- `__invoke(callable $callable, Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, array $routeArguments): Psr\Http\Message\ResponseInterface`

### `Point\Strategy\RequestResponseNamedArgs`

- `__invoke(callable $callable, Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, array $routeArguments): Psr\Http\Message\ResponseInterface`

**Inventory count** 69 types, 232 public methods and 6 public constants.
