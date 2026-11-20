# Point Framework — Codex project instructions

Point is a lightweight PSR-compatible PHP microframework. Keep its core as small and predictable as Slim 5 while preserving the five-minute Hello World experience.

## Non-negotiable constraints

- Minimum PHP version: **8.4.27**. Never use syntax or APIs that require a newer minimum. Avoid deprecated PHP APIs on tested supported versions.
- Preserve existing public APIs and PSR-7, PSR-11, PSR-15, and PSR-17 interoperability unless an explicitly approved breaking change is required.
- Do not add runtime dependencies, architectural layers, CLI, ORM, Events, Hooks, Service Providers, or other framework features unless explicitly requested.
- Make the smallest justified change. Distinguish verified defects from preferences and hypotheses.
- Preserve the default `AppFactory::create()` bootstrap, and `createFromContainer()` behavior with external PSR-11 containers.
- Keep the canonical example using `$response->getBody()->write('Hello, World!');`.
- Never modify `vendor/` or unrelated user changes.
- Do not commit, push, tag, create releases, or publish packages without the user's explicit request.

## How to work

1. Read relevant files before proposing changes. Reproduce defects where feasible.
2. For audits, do not edit files. Cite precise file paths and symbols; flag unverified claims.
3. For approved fixes, write focused regression tests and keep diffs small.
4. Run relevant checks using the project's available Composer scripts; do not assume scripts exist. Typical checks: `composer validate --strict`, `composer test`, `composer stan`, `composer cs:check`.
5. Report checks actually executed, exact failures, PHP runtime version and coverage gaps. A CI job using `8.4` is not proof the exact `8.4.27` patch was tested.
6. Summaries should be concise and actionable. Do not claim speedups without measured post-change timings.

## Agent delegation

Use at most one implementation agent on a set of files at a time. Parallel work is best reserved for independent read-only reviews. A parent agent should collect findings before approving changes.
