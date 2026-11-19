# Request & Response

## Overview

Point uses **PSR-7** for HTTP messages:
- `ServerRequestInterface` — Incoming request
- `ResponseInterface` — Outgoing response
- PSR-17 factories for creating messages

## Request

### Creating Requests

```php
use Nyholm\Psr7\Factory\Psr17Factory;

$factory = new Psr17Factory();

// From globals (in App::run())
$request = $app->getContainer()
    ->get(\Point\Interfaces\ServerRequestCreatorInterface::class)
    ->createServerRequestFromGlobals();

// Manual creation (testing)
$request = $factory->createServerRequest('GET', '/users?id=1');
$request = $factory->createServerRequest('POST', '/users', ['Content-Type' => 'application/json'], '{"name":"John"}');
```

### Accessing Request Data

```php
// Method & URI
$request->getMethod();           // 'GET', 'POST', etc.
$request->getUri();              // Psr\Http\Message\UriInterface
$request->getUri()->getPath();   // '/users'
$request->getUri()->getQuery();  // 'id=1'

// Headers
$request->getHeaders();          // ['Content-Type' => ['application/json']]
$request->getHeaderLine('Content-Type'); // 'application/json'
$request->hasHeader('Authorization');

// Body
$request->getBody();             // Psr\Http\Message\StreamInterface
$request->getParsedBody();       // Array or object (after body parser)
$request->getBody()->getContents(); // Raw string

// Uploaded files
$request->getUploadedFiles();    // ['file' => UploadedFileInterface]

// Attributes (route params, middleware data)
$request->getAttributes();       // All attributes
$request->getAttribute('id');    // Single attribute
$request->withAttribute('key', 'value'); // New request with attribute

// Cookies
$request->getCookieParams();     // ['session' => 'abc123']

// Server params
$request->getServerParams();     // $_SERVER data
```

### Route Parameters

After routing, parameters are available in attributes:

```php
$app->get('/user/{id}', function ($request, $response, array $args) {
    // $args['id'] from route pattern
    // Also available via:
    $id = $request->getAttribute('id');
    
    // Or via RouteMatch
    use Point\Routing\RouteMatch;
    $match = $request->getAttribute(RouteMatch::class);
    $args = $match->getArguments(); // ['id' => '123']
});
```

## Response

### Creating Responses

```php
use Nyholm\Psr7\Factory\Psr17Factory;

$factory = new Psr17Factory();
$response = $factory->createResponse(200);

// Or from container (configured PSR-17 factory)
$response = $container->get(\Psr\Http\Message\ResponseFactoryInterface::class)
    ->createResponse(200);
```

### Modifying Responses

```php
// Status
$response->getStatusCode();        // 200
$response = $response->withStatus(201);
$response = $response->withStatus(404, 'Not Found');

// Headers
$response->getHeaders();
$response->hasHeader('Content-Type');
$response->getHeaderLine('Content-Type');
$response = $response->withHeader('Content-Type', 'application/json');
$response = $response->withAddedHeader('X-Custom', 'value');
$response = $response->withoutHeader('X-Custom');

// Body
$response->getBody();              // StreamInterface
$response = $response->withBody($stream);

// Write to body
$response->getBody()->write('Hello');
$response->getBody()->write(json_encode($data));

// JSON helper
$response = $response->withHeader('Content-Type', 'application/json');
$response->getBody()->write(json_encode(['status' => 'ok']));
```

### Response Helpers

```php
// In route handlers, the response is injected
$app->get('/api/users', function ($request, $response) {
    $data = ['users' => [...]];
    
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Cache-Control', 'no-cache')
        ->withStatus(200)
        ->getBody()->write(json_encode($data));
    
    // Or:
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});
```

## PSR-17 Factories

Point requires these PSR-17 interfaces:

```php
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
```

Configure in container:

```php
$psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();

$container->set(ResponseFactoryInterface::class, $psr17);
$container->set(ServerRequestFactoryInterface::class, $psr17);
$container->set(StreamFactoryInterface::class, $psr17);
$container->set(UploadedFileFactoryInterface::class, $psr17);
$container->set(UriFactoryInterface::class, $psr17);
```

## Body Parsers

Enable automatic body parsing:

```php
$app->add(\Point\Middleware\JsonBodyParserMiddleware::class);
$app->add(\Point\Middleware\XmlBodyParserMiddleware::class);
$app->add(\Point\Middleware\FormUrlEncodedBodyParserMiddleware::class);
```

Then in handlers:

```php
$app->post('/users', function ($request, $response) {
    $data = $request->getParsedBody(); // Array from JSON
    $name = $data['name'] ?? '';
    // ...
});
```

## File Uploads

```php
$app->post('/upload', function ($request, $response) {
    $files = $request->getUploadedFiles();
    
    foreach ($files as $file) {
        if ($file->getError() === UPLOAD_ERR_OK) {
            $file->moveTo('/uploads/' . $file->getClientFilename());
        }
    }
    
    return $response->withStatus(201);
});
```

## Content Negotiation

Error middleware supports content negotiation via `Accept` header:

```php
// Request with: Accept: application/json
// Returns JSON error response

// Request with: Accept: text/html
// Returns HTML error page

// Request with: Accept: application/xml
// Returns XML error response

// No Accept header → defaults to HTML
```

## Testing Requests & Responses

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use Point\Factory\AppFactory;

$factory = new Psr17Factory();
$app = AppFactory::create();

// Create test request
$request = $factory->createServerRequest('GET', '/api/users')
    ->withHeader('Accept', 'application/json')
    ->withHeader('Authorization', 'Bearer token');

// Handle request
$response = $app->handle($request);

// Assert
assert($response->getStatusCode() === 200);
assert($response->getHeaderLine('Content-Type') === 'application/json');

$body = json_decode($response->getBody()->getContents(), true);
assert(isset($body['users']));
```

## Streaming Responses

```php
use Psr\Http\Message\StreamInterface;

$app->get('/large-file', function ($request, $response) {
    $stream = fopen('/path/to/large-file.txt', 'r');
    
    // Create PSR-7 stream
    $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
    $stream = $psr17->createStream($stream);
    
    return $response
        ->withHeader('Content-Type', 'text/plain')
        ->withBody($stream);
});
```

---

## Related Documentation

- [Application](application.md)
- [Routing](routing.md)
- [Middleware](middleware.md)
- [Error Handling](error-handling.md)