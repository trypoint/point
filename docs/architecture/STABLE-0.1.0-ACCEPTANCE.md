# Proposed stable v0.1.0 acceptance criteria

- Approve a published API inventory that reconciles supported callers, third-party extension contracts, internal types and each provisional member. Preserve signatures and behavior pending any separately approved change.
- Resolve confirmed P0/P1 findings and recheck locked dependencies. Exercise both PHP branches with strict autoloading, PHPStan level 8, PHPUnit with deprecation failures and the style check.
- Repeat the independent Router 202, response framing, safe errors, negotiation and multipart POST checks with the selected stable artifact.
- Verify Linux PHP-FPM and Nginx with PHP-FPM on a supported PHP patch. Verify the intended production reverse-proxy topology, including host validation, trusted scheme propagation and forwarding-header handling.
- Reproduce the tagged distribution from its Git tree. Confirm Packagist metadata and clean no-dev installation in an independent consumer. Record the final dependency and artifact identities.
- Verify final release notes, migration guidance and environmental limits before approving publication.
