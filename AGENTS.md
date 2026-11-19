# Point Framework — Agent Instructions

This repository is **trypoint/point**, a small independent PHP microframework. Only modify the existing project. Target Phase 1 is core stabilization, not new features.

## Read first
`composer.json`, `Point/App.php`, `Point/Factory/AppFactory.php`, `Point/Container/DiContainerFactory.php`, `Point/Middleware/ExceptionMiddlewareTrait.php`, `Point/Routing/PipelineRunner.php`.

## Guardrails
- Preserve `Point\` PSR-4, PHP 8.4/8.5, PSR interfaces and existing public API whenever possible.
- Preserve upstream notices and all original license attribution.
- No pushes, tag/release publishing, remote deploys, new framework skeleton, or unrelated features.
- Check git status before writes; don't overwrite pre-existing user edits.
- Ensure concrete PSR-7/PSR-17 implementation is used in runtime smoke tests.
- Validate Composer's `--no-dev` runtime independently.
- Every report must distinguish observed, inferred and untested facts.

## Sequence
Point lead → autoload → container → HTTP → routing → security review → QA validation.
Parallelize **read-only** investigations; serialize edits to shared files.
