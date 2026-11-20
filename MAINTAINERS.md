# Maintainers

## Current Maintainer

**Madalin Marius Stan**
- Email: madalinmariusstan@gmail.com
- Website: https://madalinmariusstan.dev
- GitHub: @trypoint

## Guidelines

### We don't merge our own PRs

Our code is better if more than one set of eyes looks at it. Therefore, we do not merge our own pull requests unless there is an exceptional circumstance. This helps to spot errors in the patch and also enables us to share information about the project around the maintainer team.

### PRs tagged `WIP` are not ready to be merged

Sometimes it's helpful to collaborate on a patch before it's ready to be merged. We use the text `WIP` (for _Work in Progress_) in the title to mark these PRs.

If a PR has `WIP` in its title, then it is not to be merged. The person who raised the PR will remove the `WIP` text when they are ready for a full review and merge.

### Assign a merged PR to a milestone

By ensuring that all merged PRs are assigned to a milestone, we can easily find which PRs were in which release.

### Code Style

- Follow PSR-12 coding standard
- Run `composer cs:fix` before committing
- Run `composer test:all` to verify all checks pass

### Testing

- All new features must include tests
- Maintain 100% test pass rate
- Test with multiple PSR-7 implementations when applicable

### Documentation

- Update relevant documentation for API changes
- Keep examples working and up to date
- Document breaking changes in CHANGELOG.md

## Security

Report vulnerabilities to [**security.point@madalinmariusstan.dev**](mailto:security.point@madalinmariusstan.dev).

See [SECURITY.md](SECURITY.md) for supported versions.
