<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Integration;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Point\Exception\HttpNotFoundException;
use Point\Factory\AppFactory;
use Point\Middleware\ContentLengthMiddleware;
use Point\Middleware\HtmlExceptionMiddleware;
use Point\Middleware\JsonExceptionMiddleware;
use Point\Middleware\OutputBufferingMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * @internal
 */
final class HttpCorrectnessTest extends TestCase
{
    public static function middlewareStacks() : iterable
    {
        yield 'default' => [true];
        yield 'explicit' => [false];
    }

    #[DataProvider('middlewareStacks')]
    public function testHeadEmitsNoBodyForRoutesAndErrors(bool $defaults) : void
    {
        $app = AppFactory::create()->withDefaults($defaults);
        if (! $defaults) {
            $app->addErrorMiddleware(false, false);
            $app->addRoutingMiddleware();
        }

        $app->get('/ok', static function ($request, $response) {
            $response->getBody()->write('GET body');

            return $response->withHeader('Content-Length', '8');
        });
        $app->get('/error', static function () {
            throw new RuntimeException('Test error');
        });

        $factory = new Psr17Factory();
        foreach (['/ok' => 200, '/missing' => 404, '/error' => 500] as $path => $status) {
            $request = $factory->createServerRequest('HEAD', $path);
            $response = $app->handle($request);
            self::assertSame($status, $response->getStatusCode());
            self::assertSame('', (string)$response->getBody());
            if ($path === '/ok') {
                self::assertSame('8', $response->getHeaderLine('Content-Length'));
            }
            ob_start();
            try {
                $app->run($request);
                self::assertSame('', ob_get_contents(), $path);
            } finally {
                ob_end_clean();
            }
        }
    }

    #[DataProvider('middlewareStacks')]
    public function testAllowIncludesHeadExactlyOnceWhenGetIsAllowed(bool $defaults) : void
    {
        $app = AppFactory::create()->withDefaults($defaults);
        if (! $defaults) {
            $app->addErrorMiddleware(false, false);
            $app->addRoutingMiddleware();
        }

        $handler = static fn($request, $response) => $response;
        $app->get('/implicit', $handler);
        $app->map(['GET', 'HEAD'], '/explicit', $handler);
        $app->post('/post-only', $handler);

        $factory = new Psr17Factory();
        foreach (['/implicit' => ['GET', 'HEAD'], '/explicit' => ['GET', 'HEAD'], '/post-only' => ['POST']] as $path => $methods) {
            $response = $app->handle($factory->createServerRequest('PUT', $path));
            self::assertSame(405, $response->getStatusCode());
            self::assertSame($methods, explode(', ', $response->getHeaderLine('Allow')));
        }
    }

    public static function acceptHeaders() : iterable
    {
        yield 'higher quality HTML' => ['application/json;q=0.2, text/html;q=0.8', 'text/html'];
        yield 'higher quality JSON' => ['text/html;q=0.2, application/json;q=0.8', 'application/json'];
        yield 'excluded JSON' => ['application/json;q=0, text/html', 'text/html'];
        yield 'exact exclusion overrides wildcard' => ['application/json;q=0, */*;q=0.5', 'text/html'];
        yield 'HTML exclusion overrides wildcard' => ['text/html;q=0, */*;q=0.5', 'application/json'];
        yield 'all excluded' => ['application/json;q=0, text/html;q=0', ''];
        yield 'wildcard excluded' => ['*/*;q=0', ''];
        yield 'unsupported with supported alternative' => ['image/png, text/html;q=0.5', 'text/html'];
        yield 'unsupported fallback' => ['image/png', 'text/html'];
    }

    #[DataProvider('middlewareStacks')]
    public function testJsonFactoryFailureIsRenderedByOuterHtmlMiddleware(bool $defaults) : void
    {
        $factory = new Psr17Factory();
        $attempts = 0;
        $app = AppFactory::create([
            JsonExceptionMiddleware::class => static function () use (&$attempts, $factory) : JsonExceptionMiddleware {
                if (++$attempts === 1) {
                    throw new RuntimeException('JSON factory failed');
                }

                return new JsonExceptionMiddleware($factory);
            },
        ])->withDefaults($defaults);
        if (! $defaults) {
            $app->add(HtmlExceptionMiddleware::class);
            $app->add(JsonExceptionMiddleware::class);
            $app->addRoutingMiddleware();
        }

        $calls = 0;
        $app->get('/', static function ($request, $response) use (&$calls) {
            $calls++;

            return $response;
        });
        $request = $factory->createServerRequest('GET', '/')->withHeader('Accept', 'text/html');

        $failed = $app->handle($request);
        self::assertSame(500, $failed->getStatusCode());
        self::assertStringStartsWith('text/html', $failed->getHeaderLine('Content-Type'));
        self::assertStringNotContainsString('JSON factory failed', (string)$failed->getBody());
        self::assertSame(0, $calls);
        self::assertSame(200, $app->handle($request)->getStatusCode());
        self::assertSame(200, $app->handle($request)->getStatusCode());
        self::assertSame(2, $calls);
        self::assertSame(2, $attempts);
    }

    public static function wildcardAcceptHeaders() : iterable
    {
        yield 'unsupported positive media type' => ['image/png;q=0.7', true];
        yield 'unsupported media type excluded' => ['image/png;q=0', false];
        yield 'explicit exclusion wins' => ['application/json;q=0, image/png, */*;q=0.9', false];
        yield 'exact match overrides type exclusion' => ['application/*;q=0, application/json;q=0.3', true];
        yield 'type exclusion overrides global wildcard' => ['application/*;q=0, */*;q=0.9', false];
        yield 'wildcard excluded' => ['*/*;q=0', false];
    }

    #[DataProvider('wildcardAcceptHeaders')]
    public function testExplicitWildcardRendererHonorsAccept(string $accept, bool $renders) : void
    {
        $factory = new Psr17Factory();
        $middleware = (new JsonExceptionMiddleware($factory))->withMimeType('*/*');
        $request = $factory->createServerRequest('GET', '/')->withHeader('Accept', $accept);
        $exception = new RuntimeException('Test error');
        $handler = new class ($exception) implements RequestHandlerInterface {
            public function __construct(private RuntimeException $exception)
            {
            }

            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                throw $this->exception;
            }
        };

        if (! $renders) {
            $this->expectExceptionObject($exception);
        }

        $response = $middleware->process($request, $handler);
        self::assertSame(500, $response->getStatusCode());
        self::assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
    }

    #[DataProvider('acceptHeaders')]
    public function testErrorRepresentationHonorsAccept(string $accept, string $mediaType) : void
    {
        $request = (new Psr17Factory())->createServerRequest('GET', '/missing')->withHeader('Accept', $accept);
        $response = AppFactory::create()->handle($request);

        self::assertSame(404, $response->getStatusCode());
        if ($mediaType === '') {
            self::assertFalse($response->hasHeader('Content-Type'));
            self::assertSame('', (string)$response->getBody());
        } else {
            self::assertStringStartsWith($mediaType, $response->getHeaderLine('Content-Type'));
        }
    }

    public function testHtmlDescriptionEscapesTextAndPreservesEntities() : void
    {
        $app = AppFactory::create();
        $app->get('/', static function (ServerRequestInterface $request) {
            throw (new HttpNotFoundException($request))->setDescription('Tom & Jerry <friends> &amp; family');
        });
        $request = (new Psr17Factory())->createServerRequest('GET', '/')->withHeader('Accept', 'text/html');
        $response = $app->handle($request);

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('Tom &amp; Jerry &lt;friends&gt; &amp; family', (string)$response->getBody());
    }

    public function testContentLengthOmits204AndPreservesOtherResponses() : void
    {
        $app = AppFactory::create();
        $app->add(new ContentLengthMiddleware());
        $app->get('/empty', static fn($request, $response) => $response->withStatus(204));
        $app->get('/empty-with-length', static fn($request, $response) => $response->withStatus(204)->withHeader('Content-Length', '0'));
        $app->get('/body', static function ($request, $response) {
            $response->getBody()->write('abc');

            return $response;
        });

        $factory = new Psr17Factory();
        foreach (['/empty', '/empty-with-length'] as $path) {
            $response = $app->handle($factory->createServerRequest('GET', $path));
            self::assertSame(204, $response->getStatusCode());
            self::assertFalse($response->hasHeader('Content-Length'));
        }
        self::assertSame('3', $app->handle($factory->createServerRequest('GET', '/body'))->getHeaderLine('Content-Length'));
    }

    public function testContentLengthOmitsInformationalResponses() : void
    {
        $app = AppFactory::create();
        $app->add(new ContentLengthMiddleware());
        $app->get('/{status}[/{length}]', static function ($request, $response, array $args) {
            $response->getBody()->write('abc');
            $response = $response->withStatus((int)$args['status']);

            return isset($args['length']) ? $response->withHeader('Content-Length', $args['length']) : $response;
        });

        $factory = new Psr17Factory();
        foreach ([100, 103] as $status) {
            foreach (['', '/3'] as $suffix) {
                $response = $app->handle($factory->createServerRequest('GET', '/' . $status . $suffix));
                self::assertSame($status, $response->getStatusCode());
                self::assertFalse($response->hasHeader('Content-Length'));
            }
        }
    }

    public function testOutputBufferingPreservesZero() : void
    {
        $factory = new Psr17Factory();
        $app = AppFactory::create();
        $app->add(new OutputBufferingMiddleware($factory));
        $app->get('/', static function ($request, ResponseInterface $response) : ResponseInterface {
            echo '0';

            return $response;
        });

        self::assertSame('0', (string)$app->handle($factory->createServerRequest('GET', '/'))->getBody());
    }
}
