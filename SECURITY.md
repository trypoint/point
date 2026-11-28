# Security Policy

### Supported Versions

| Version | Supported          |
|---------|--------------------|
| 0.1.x   | :white_check_mark: |

### Application and deployment boundaries

- Enforce request-body size limits at the trusted ingress, including for streamed requests, before enabling JSON, form or XML parsing. The optional parsers do not impose their own size limit. Choose limits appropriate to each endpoint.
- Validate Host at the edge. Filter client Forwarded and X-Forwarded-* fields and set trusted values at the proxy. Restrict direct access to the backend. PSR-7 request creators may use forwarding fields to populate the URI. Use a configured or validated origin for sensitive absolute links.
- Read route parameters from the route match or handler arguments. Keep authenticated identity in a distinct application-owned attribute or context. RoutingArgumentsMiddleware can overwrite an existing request attribute with the same name.
- Treat ContainerResolver identifiers as trusted application configuration. Map any client-facing selection to an explicit allowlist of configured handlers.
- Validate JSON shape in the endpoint contract. The JSON parser attaches arrays and decoded objects as parsed body data. Valid top-level scalars leave the existing parsed body unchanged.

### Reporting a Vulnerability

To report a vulnerability, please send an email to [**security.point@madalinmariusstan.dev**](mailto:security.point@madalinmariusstan.dev).

We will acknowledge receipt within 48 hours and provide a timeline for a fix.
