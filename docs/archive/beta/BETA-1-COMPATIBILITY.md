# Beta.1 compatibility

Alpha.7 and the candidate share Composer requirements, the lock file and public signatures. The only production code change is in ResponseEmitter. The published Packagist distribution and the local tagged export contain the same 80 files with matching contents after CRLF normalization.

## PSR and actual HTTP

The existing PHPUnit matrix covers PSR-7 messages, PSR-11 resolution, PSR-15 middleware and explicit PSR-17 factory configuration, including external PHP-DI and the independent Router HTTP 202 regression. The loopback development-server matrix ran HttpSoft, Nyholm, Laminas and Guzzle on PHP 8.4.27 and 8.5.12. Each combination passed 14 checks covering GET, POST, PUT, PATCH, DELETE, HEAD, 404, 405, 500, HTML and JSON negotiation, JSON, form decoding and a multipart POST with filename, upload status and stream bytes. It also covered repeated fields, empty 204 responses, Content-Length with opt-in middleware, base-path routing, URL generation and encoded slash rejection.

Default bootstrap did not emit Content-Length on the tested normal GET and HEAD responses. Adding ContentLengthMiddleware produced the expected 13-byte length for both. HEAD returned an empty body. 204 returned no body or Content-Length.

## Security and scope

The synthetic private exception message stayed out of the HTTP 500 responses. The routed encoded slash was rejected and a percent-encoded space decoded once. The tested uploaded files came through the SAPI and provider globals creator. Upload size and policy, file naming, storage, content validation, trusted host validation and proxy header trust are application and server responsibilities. Point has no built-in trusted proxy configuration. PHP and the providers determine multipart normalization. Coverage here is for multipart POST.

Composer's locked dependency advisory check found no advisories on either PHP runtime. These checks establish the observed behavior for the tested surfaces. No confirmed vulnerability emerged from the reviewed boundaries. The 204/205 framing defect was confirmed as a correctness issue and corrected. Alpha.7 emitted an explicit positive Content-Length while suppressing the body. The candidate removes the field for 204 and emits zero for 205 when the field was supplied. The focused regression tests also preserve informational response stripping and ordinary body emission. See [RFC 9110 on Content-Length](https://www.rfc-editor.org/rfc/rfc9110.html#section-8.6) and [205 Reset Content](https://www.rfc-editor.org/rfc/rfc9110.html#section-15.3.6).

## Isolated server results

| Server on Windows | Result with PHP 8.5.12 and HttpSoft |
| --- | --- |
| Apache 2.4.69 with mod_php | 13 application checks with the default encoded-slash policy. The 14th request received Apache's HTML 404 before PHP. With AllowEncodedSlashes NoDecode, all 14 checks passed and Point returned the negotiated JSON 404. |
| Nginx 1.22.0 with php-cgi FastCGI | 14 of 14 checks passed with the fixture's explicit front-controller mapping. |
| Nginx 1.22.0 reverse proxy to the PHP development server | 14 of 14 checks passed while preserving the request URI. |

Production Linux PHP-FPM, production TLS and trusted forwarded-header integration were **NOT RUN**. The fixture exercised an HTTP loopback connection and Point's explicit base-path configuration.

## Independent consumers

Each app had its own Composer project and vendor directory. The website covered bootstrap, grouped routes, middleware, a container value, named URL generation and 404. The REST app ran the 14-check wire fixture including JSON, forms, uploads and error responses. Each app passed against Packagist Alpha.7 and the local Beta.1 package archive on PHP 8.4.27 and 8.5.12. There was no observed consumer regression.

The bundled Hello World example returned HTTP 200 on both runtimes. Its bundled greeting is “Hello, Point Framework!” The README-style packaged fixture separately returned the exact canonical “Hello, World!” greeting. The Alpha.7 JSON example returned 400 for a valid JSON POST because the explicit routing endpoint ran before the parser. The corrected example uses default routing, registers an actual parser instance and captures the HTTP factory in the JSON response helper. It returned HTTP 201 on both runtimes.
