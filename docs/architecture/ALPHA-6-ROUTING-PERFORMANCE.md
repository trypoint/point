# Alpha.6 routing performance

The named-route optimization is deferred. At large route counts, collector data generation and traversal dominate URL generation. A cache keyed by the dispatcher revision cannot preserve the current behavior of renamed routes and custom collectors.

## Baseline and method

The published `v0.1.0-alpha.5` tag, GitHub prerelease, and Packagist source and distribution references identify commit `3c80b5d74f2e5b25182b37711bf337a27e385fe6`. The local HEAD was `c313b2c`, with staged, unstaged, and untracked work. The effective `Point/` and `tests/` trees and both Composer manifests matched the tag. The benchmark and validation used isolated copies of the published baseline. [GitHub prerelease](https://github.com/trypoint/point/releases/tag/v0.1.0-alpha.5) · [Packagist metadata](https://packagist.org/packages/trypoint/point.json)

Benchmarks ran on Windows with PHP 8.5.12 CLI, CLI OPcache disabled, and JIT off. Each table size and fixture used five fresh processes. The static fixture registered only literal paths. The mixed fixture registered half literal paths and half dynamic paths with one optional segment. FastRoute expands each optional pattern internally. Dynamic URL generation used a UTF-8 value with a space and literal percent sign, and supplied or omitted the optional argument. Each sample is the median of five process timings. Warm operations were timed as batches and divided by iteration count. The first cold sample is the first lookup of the last route after registration, including first-use costs. Late registration measures adding one static route and successfully dispatching it on the warmed dispatcher. Application handling measures an in-process GET of the first static route, including the default pipeline. Dynamic dispatch targets the last dynamic route in the mixed fixture.

## Named URL generation

All timings are in µs per operation. Repeated lookup uses a second batch targeting the same last route. Missing lookup includes raising and catching the existing exception.

| Routes | Fixture | Cold last | First | Middle | Last | Repeated last | Missing | Optional omitted |
| ---: | :--- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 10 | Static | 66.30 | 4.25 | 5.65 | 6.20 | 6.68 | 6.49 | — |
| 10 | Mixed | 131.30 | 8.81 | 17.56 | 30.42 | 29.29 | 27.26 | 29.31 |
| 100 | Static | 92.00 | 4.57 | 20.00 | 31.91 | 31.62 | 31.85 | — |
| 100 | Mixed | 316.30 | 43.20 | 58.91 | 226.91 | 220.36 | 208.81 | 214.98 |
| 1,000 | Static | 303.60 | 13.25 | 121.59 | 253.76 | 280.36 | 257.94 | — |
| 1,000 | Mixed | 2,185.30 | 415.64 | 555.43 | 2,202.17 | 2,334.47 | 2,286.30 | 2,371.47 |
| 10,000 | Static | 2,404.80 | 169.08 | 1,252.56 | 2,337.37 | 2,265.96 | 2,228.29 | — |
| 10,000 | Mixed | 25,134.40 | 5,099.07 | 6,190.15 | 23,744.50 | 24,070.60 | 24,203.19 | 23,547.83 |

## Cost breakdown

Independent timings in µs per operation. Traversal uses a pre-fetched collector array and the production iterator and predicate. The name column measures one getter plus strict string comparison. Formatting invokes the generator's segment logic with a known pattern, then joins the segments. Parsing and encoding are separately measured components of formatting. For a mixed fixture the optional-omitted format uses fewer substitutions but may try an additional expression. These medians include small harness costs and are unsuitable for exact subtraction across columns.

| Routes | Fixture | Collector data | Scan first | Scan last | Scan miss | Name | Parse | Encode one | Format | Format omitted |
| ---: | :--- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 10 | Static | 0.16 | 1.94 | 3.97 | 4.50 | 0.09 | 0.79 | 0.15 | 1.20 | — |
| 10 | Mixed | 4.65 | 2.05 | 17.95 | 20.41 | 0.12 | 3.24 | 0.15 | 4.12 | 4.56 |
| 100 | Static | 0.16 | 3.11 | 30.82 | 26.70 | 0.11 | 0.87 | 0.15 | 1.22 | — |
| 100 | Mixed | 35.32 | 2.15 | 174.56 | 174.54 | 0.09 | 3.44 | 0.15 | 4.64 | 4.36 |
| 1,000 | Static | 0.22 | 9.69 | 206.94 | 218.15 | 0.10 | 0.79 | 0.16 | 1.21 | — |
| 1,000 | Mixed | 348.55 | 6.13 | 1,764.76 | 1,808.85 | 0.10 | 3.39 | 0.14 | 4.62 | 4.27 |
| 10,000 | Static | 0.32 | 114.25 | 2,259.53 | 2,183.18 | 0.10 | 0.77 | 0.15 | 1.15 | — |
| 10,000 | Mixed | 5,064.27 | 68.42 | 18,761.55 | 19,196.25 | 0.10 | 3.32 | 0.17 | 4.90 | 4.62 |

`UrlGenerator::getNamedRoute()` requests FastRoute's compiled data each time, recursively walks the leaves, and returns the first `RouteInterface` whose current name matches. Static-only data retrieval is effectively flat in this measurement. Mixed data retrieval regenerates grouped dynamic data even for a first-route hit. Late hits and misses also traverse nearly the full compiled table, which contains repeated references for optional expansions. Iterator setup can grow with array width even for early hits. URL parsing, optional selection, substitution, and encoding cost only a few microseconds for the measured patterns.

## Dispatch and memory

Timings are in µs. Peak is incremental PHP heap high-water usage in KiB, starting after autoload and covering route setup, lookup, dispatch, and late registration. It includes the harness holding one collector data snapshot for the isolated scans. It excludes operating-system RSS.

| Routes | Fixture | Warm static | Warm dynamic | Full app | Add and rebuild | Peak KiB |
| ---: | :--- | ---: | ---: | ---: | ---: | ---: |
| 10 | Static | 0.64 | — | 17.41 | 20.60 | 900.17 |
| 10 | Mixed | 0.65 | 6.27 | 16.70 | 229.40 | 917.34 |
| 100 | Static | 0.82 | — | 20.54 | 25.90 | 927.23 |
| 100 | Mixed | 0.66 | 37.31 | 16.07 | 1,557.90 | 1,057.37 |
| 1,000 | Static | 0.67 | — | 17.65 | 45.10 | 1,164.11 |
| 1,000 | Mixed | 0.68 | 307.21 | 16.55 | 14,578.30 | 2,798.22 |
| 10,000 | Static | 0.64 | — | 15.17 | 331.70 | 4,444.75 |
| 10,000 | Mixed | 0.72 | 4,204.28 | 14.42 | 155,925.10 | 20,703.16 |

The last-dynamic result is specific to the generated FastRoute table and target. It excludes URL generation. No optimized build was selected, so there are no measured after timings, percentage changes, speedups, or additional index memory figures.

## Invalidation and upstream comparison

- **Registration revision only.** The revision changes when a route definition is added, including through the direct revisioned collector. `Route::setName()` mutates the registered object independently. If an earlier route is renamed to an already cached name, even checking that the cached object's name still matches leaves the wrong winner cached. Negative entries can also become stale. Collector replacement requires a separate identity check.
- **Point-specific name revision.** An index for exclusively built-in routes could combine a name mutation counter, collector identity, and the registration revision. Correct construction would need to follow the compiled collector's traversal order, including static entries preceding dynamic entries, rather than assume global registration order. This introduces a second invalidation path, additional retained storage, and potentially cross-router invalidation if the counter is global. A custom `RouteInterface` handler can change its reported name without touching either counter. A custom collector can provide different data without the built-in revision. Both cases would need to retain a live lookup path.
- **Retained collector snapshot.** A revision-keyed snapshot for the built-in collector could avoid repeated data generation and still read current object names. It leaves the dominant late-hit and miss traversal intact, and adds retained state and a custom-collector fallback. Parsed-pattern caching targets the much smaller formatting cost.
- **Slim 4.15.3.** The pinned collector scans a flat route list on cache misses and caches successful names. It rechecks the cached route's name, which cannot detect a different earlier route renamed to the same name. That check is insufficient for Point's existing first-match semantics. [Slim 4.15.3 RouteCollector](https://github.com/slimphp/Slim/blob/3675bf6baac66b07032575b7bef4200b60b7974b/Slim/Routing/RouteCollector.php)
- **Slim 5 development.** The pinned collector scans its flat route list for a current name. A flat list avoids the compiled FastRoute tree work visible in Point, but substituting a Point-only list would omit entries registered directly through the collector and could alter duplicate order. This is a development snapshot. [Slim 5 RouteCollector snapshot](https://github.com/slimphp/Slim/blob/b0d9f8131f13a1b1ac5c786e5ebc63d41eecef02/Slim/Routing/RouteCollector.php)

## Validation and compatibility

At benchmarking time, the production code, PHPUnit files, Composer requirements, and lock file matched the published Alpha.5 release. Public signatures, FIFO middleware, and dispatcher revision handling retain their existing implementations. There are nine locked runtime packages.

| Check | PHP 8.4.27 | PHP 8.5.12 |
| :--- | :--- | :--- |
| `composer validate --strict` | Passed | Passed |
| `composer dump-autoload -o --strict-psr` | Passed | Passed |
| `composer test` | 238 tests, 558 assertions | 238 tests, 558 assertions |
| `composer stan` | Level 8, zero errors | Level 8, zero errors |
| `composer cs:check` | Zero violations in 98 files | Zero violations in 98 files |

Composer emitted a root-version notice in the detached checkout. PHP-CS-Fixer emitted its advisory about running under PHP 8.5 against an 8.4 minimum. All five commands returned zero on both runtimes.

The independent PHP 8.5.12 routing run passed 28 tests and 105 assertions. Discovered integration coverage included static and dynamic routing, duplicate names after rename, encoding and encoded slash handling, direct collector registration, nested and retained groups, custom collectors, and late registration and dispatcher invalidation. Supplemental checks exercised optional segment omission, missing required parameters, base paths in relative and absolute URLs, and the exception for unknown names. At that point discovery omitted 17 test methods from five secondary classes in `tests/Unit/Routing/RouteTest.php`. The baseline counts reflect the suite before the discovery correction below.

Provider smoke checks on PHP 8.4.27 and 8.5.12 selected each of HttpSoft, Nyholm, Laminas, and Guzzle in turn and passed requests created both through PSR-17 and from globals through the app. The exact-tag archives installed all nine production packages with `--no-dev` and passed strict optimized PSR-4 autoloading on both runtimes. The README quick start served HTTP 200 with the exact body `Hello, World!` on both. The archive includes required routing sources, Composer manifests, licenses, and the migration guide, and excludes tests and repository tooling.

## Recommendation

Retain live lookup semantics. Before reconsidering an index, establish the frequency of named URL generation in representative applications and use the regression coverage below to verify any proposed change.

## PHPUnit discovery follow-up

The benchmark baseline above predates the test-only correction. PHPUnit loaded the class matching `RouteTest.php` and omitted the five secondary classes in that file. Moving those classes into their own PSR-4 files restored 17 test methods. The first run after the split exposed stale group construction, a closure passed to a PSR-15 middleware registration method, and calls to the removed URL generator method. The tests now use the current APIs.

The normal suite grew from **238 tests and 558 assertions** to **260 tests and 603 assertions**. The increase includes the 17 restored cases and five new URL generation cases for duplicate precedence, mutable custom handlers, late registration after a warm lookup, optional segments, and base paths.
