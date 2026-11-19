# Run Stage 1

From the **existing** repository root, open OpenCode or Kilo Code. Select `point-lead`, then paste:

> Audit and repair Point Framework 0.1 Alpha Phase 1 in the existing repository. First inspect git status, composer.json and all actual core entrypoints. Delegate read-only audits to the specialized point-* agents where supported. Fix verified PSR-4 class/file mismatches, missing/hidden PHP-DI dependency, HTTP 404/405/500 correctness, and broken internal Slim references without removing licensing/provenance text. Preserve existing public API. Serialize overlapping edits. Then run Composer autoload validation, smoke tests for AppFactory and PSR-7 request handling, and Composer --no-dev production test in a clean temporary directory. Document every test as PASS/FAIL/NOT RUN. Do not publish or push.

Suggested follow-up: `Ask @point-security for a read-only audit of the integrated changes, then @point-qa to validate.`

If an agent cannot be invoked by @ mention, select it from your tool's agent picker or have the lead perform its checks.
