# Error Handling

## Overview

Point provides a comprehensive error handling system with:
- Multiple response formats (HTML, JSON, XML, Plain Text)
- Configurable detail levels (development vs production)
- PSR-3 logging integration
- PHP error to exception conversion

## Error Middleware Stack

```php
$app->addErrorMiddleware(
    displayErrorDetails: true,   // Show stack traces in response
    logErrors: true,             // Log to PSR-3 logger
    logErrorDetails: true,       // Include stack traces in logs
    logger: $customLogger        // Optional custom PSR-3 logger
);
```

This adds (in order, outermost first):
1. `ErrorExceptionMiddleware` — Converts PHP errors to `ErrorException`
2. `HtmlExceptionMiddleware` — Renders HTML error pages
3. `JsonExceptionMiddleware` — Renders JSON error responses
4. `ExceptionLoggingMiddleware` — Logs exceptions (if enabled)

## Content Negotiation

The default error stack uses the `Accept` header to choose between JSON and HTML:

| Accept Header | Response Format |
|---------------|-----------------|
| `application/json` | JSON |
| `text/html` | HTML |
| (none) | JSON |
| Unsupported media type | HTML fallback |

Quality values and wildcards affect the choice. A media range with `q=0`
excludes that format unless a more specific range accepts it. If there is no
acceptable match and HTML is excluded, Point preserves the error status and
returns an empty body. XML and plain-text renderers are available as opt-in
middleware. XML rendering requires the DOM extension.

```php
// Request: GET /error Accept: application/json
// Response: 500 {"message":"Application Error","exception":[...]}

// Request: GET /error Accept: text/html
// Response: 500 <html>...</html>
```

## Configuration

### Development (Detailed Errors)

```php
$app->addErrorMiddleware(true, true, true);
// Display: full stack trace
// Log: full stack trace
```

### Production (Minimal Errors)

```php
$app->addErrorMiddleware(false, true, false, $logger);
// Display: generic message only
// Log: message without exception context
```

### Custom Logger

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('point');
$logger->pushHandler(new StreamHandler('/var/log/point.log'));

$app->addErrorMiddleware(false, true, true, $logger);
```

## Error Response Formats

### JSON

```json
{
  "message": "Application Error",
  "exception": [
    {
      "type": "RuntimeException",
      "code": 0,
      "message": "Database connection failed",
      "file": "/app/src/Database.php",
      "line": 42
    }
  ]
}
```

### HTML

Clean, readable error page with:
- Error type and message
- Stack trace (if enabled)
- File and line number
- Previous exceptions

### XML

```xml
<?xml version="1.0" encoding="UTF-8"?>
<error>
  <message>Application Error</message>
  <exception>
    <type>RuntimeException</type>
    <code>0</code>
    <message>Database connection failed</message>
    <file>/app/src/Database.php</file>
    <line>42</line>
  </exception>
</error>
```

### Plain Text

```
Application Error
Type: RuntimeException
Code: 0
Message: Database connection failed
File: /app/src/Database.php
Line: 42
Stack trace:
#0 /app/src/Database.php(42): ...
```

## HTTP Exception Classes

Point provides specific exception classes for HTTP status codes:

```php
use Point\Exception\HttpNotFoundException;
use Point\Exception\HttpMethodNotAllowedException;
use Point\Exception\HttpBadRequestException;
use Point\Exception\HttpUnauthorizedException;
use Point\Exception\HttpForbiddenException;
use Point\Exception\HttpInternalServerErrorException;
use Point\Exception\HttpNotImplementedException;
use Point\Exception\HttpGoneException;
use Point\Exception\HttpTooManyRequestsException;

// In route handlers
$app->get('/user/{id}', function ($request, $response, array $args) {
    $user = $this->userRepository->find($args['id']);
    
    if (! $user) {
        throw new HttpNotFoundException($request);
    }
    
    $response->getBody()->write(json_encode($user, JSON_THROW_ON_ERROR));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/users', function ($request, $response) {
    $data = $request->getParsedBody();
    
    if (! isset($data['email'])) {
        throw new HttpBadRequestException($request, 'Email is required');
    }
    // ...
});
```

### Method Not Allowed

Automatically thrown by routing with `Allow` header:

```php
$app->get('/users', $handler);

// POST /users → 405 Method Not Allowed
// Response header: Allow: GET, HEAD
```

## Custom Exception Handling

Add middleware that catches the exceptions you want to render. Register it
before explicit routing, or let the default stack add routing after it.

```php
$responses = $app->getContainer()->get(\Psr\Http\Message\ResponseFactoryInterface::class);

$app->add(static function ($request, $handler) use ($responses) {
    try {
        return $handler->handle($request);
    } catch (\Point\Exception\HttpNotFoundException) {
        $response = $responses->createResponse(404);
        $response->getBody()->write('{"error":"Not found"}');
        return $response->withHeader('Content-Type', 'application/json');
    }
});
```

## PHP Error Handling

`ErrorExceptionMiddleware` converts PHP errors to exceptions:

```php
// These become ErrorException and are handled by error middleware
trigger_error('Custom warning', E_USER_WARNING);
trigger_error('Custom notice', E_USER_NOTICE);

// Silenced errors (@) are ignored
@trigger_error('Ignored', E_USER_WARNING);
```

### Error Levels Mapped

| PHP Level | Exception |
|-----------|-----------|
| `E_ERROR` | `ErrorException` |
| `E_WARNING` | `ErrorException` |
| `E_PARSE` | `ErrorException` |
| `E_NOTICE` | `ErrorException` |
| `E_CORE_ERROR` | `ErrorException` |
| `E_CORE_WARNING` | `ErrorException` |
| `E_COMPILE_ERROR` | `ErrorException` |
| `E_COMPILE_WARNING` | `ErrorException` |
| `E_USER_ERROR` | `ErrorException` |
| `E_USER_WARNING` | `ErrorException` |
| `E_USER_NOTICE` | `ErrorException` |
| `E_STRICT` | `ErrorException` |
| `E_RECOVERABLE_ERROR` | `ErrorException` |
| `E_DEPRECATED` | `ErrorException` |
| `E_USER_DEPRECATED` | `ErrorException` |

## Testing Error Handling

```php
use Point\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->addRoutingMiddleware();

$app->get('/error', function () {
    throw new \RuntimeException('Test error');
});

$factory = new Psr17Factory();
$request = $factory->createServerRequest('GET', '/error')->withHeader('Accept', 'application/json');
$response = $app->handle($request);

assert($response->getStatusCode() === 500);
assert(str_contains($response->getHeaderLine('Content-Type'), 'application/json'));

$body = json_decode($response->getBody()->getContents(), true);
assert(isset($body['message']));
assert(isset($body['exception']));
```

## Best Practices

1. **Use HTTP exceptions** for expected error conditions (404, 400, 403, etc.)
2. **Let unexpected exceptions bubble** to error middleware
3. **Log in production** with a proper PSR-3 logger (Monolog, etc.)
4. **Disable displayErrorDetails** in production
5. **Test error responses** in your test suite

---

## Related Documentation

- [Application](application.md)
- [Middleware](middleware.md)
- [Container](container.md)
