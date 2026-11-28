<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Middleware\ExceptionLoggingMiddleware;
use Point\Middleware\HtmlExceptionMiddleware;
use Point\Middleware\JsonExceptionMiddleware;
use Point\Middleware\PlainTextExceptionMiddleware;
use Point\Middleware\XmlBodyParserMiddleware;
use Point\Middleware\XmlExceptionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * @internal
 * @small
 */
final class ExceptionMiddlewareTest extends TestCase
{
    public function testExceptionLoggingMiddlewareLogsErrors() : void
    {
        $app = AppFactory::create();
        $logger = new class () implements LoggerInterface {
            public array $logs = [];

            public function log($level, $message, array $context = []) : void
            {
                $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }

            public function emergency($message, array $context = []) : void
            {
                $this->log('emergency', $message, $context);
            }

            public function alert($message, array $context = []) : void
            {
                $this->log('alert', $message, $context);
            }

            public function critical($message, array $context = []) : void
            {
                $this->log('critical', $message, $context);
            }

            public function error($message, array $context = []) : void
            {
                $this->log('error', $message, $context);
            }

            public function warning($message, array $context = []) : void
            {
                $this->log('warning', $message, $context);
            }

            public function notice($message, array $context = []) : void
            {
                $this->log('notice', $message, $context);
            }

            public function info($message, array $context = []) : void
            {
                $this->log('info', $message, $context);
            }

            public function debug($message, array $context = []) : void
            {
                $this->log('debug', $message, $context);
            }
        };
        $middleware = $app->getContainer()->get(ExceptionLoggingMiddleware::class)
            ->withLogger($logger)
            ->withLogErrorDetails(true);
        $request = $this->createRequest('GET', '/');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                throw new RuntimeException('Test error');
            }
        };

        try {
            $middleware->process($request, $handler);
        } catch (Throwable) {
            // Expected
        }

        self::assertCount(1, $logger->logs);
        self::assertEquals('error', $logger->logs[0]['level']);
        self::assertStringContainsString('Test error', $logger->logs[0]['message']);
    }

    public function testExceptionMiddlewareUsesExceptionCodeAsStatus() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(JsonExceptionMiddleware::class)->withErrorDetails(true);
        $request = $this->createRequest('GET', '/', ['Accept' => 'application/json']);

        $exception = new \Point\Exception\HttpNotFoundException($request);
        $handler = new class ($exception) implements RequestHandlerInterface {
            private Throwable $exception;

            public function __construct(Throwable $exception)
            {
                $this->exception = $exception;
            }

            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                throw $this->exception;
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertEquals(404, $response->getStatusCode());
    }

    public function testHtmlExceptionMiddlewareRendersHtml() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(HtmlExceptionMiddleware::class)->withErrorDetails(true);
        $request = $this->createRequest('GET', '/', ['Accept' => 'text/html']);

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                throw new RuntimeException('Test error');
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertEquals(500, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Test error', (string)$response->getBody());
    }

    public function testJsonExceptionMiddlewareRendersJson() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(JsonExceptionMiddleware::class)->withErrorDetails(true);
        $request = $this->createRequest('GET', '/', ['Accept' => 'application/json']);

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                throw new RuntimeException('Test error');
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertEquals(500, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $body = json_decode((string)$response->getBody(), true);
        self::assertArrayHasKey('message', $body);
        self::assertArrayHasKey('exception', $body);
    }

    public function testMethodNotAllowedExceptionIncludesAllowHeader() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(JsonExceptionMiddleware::class)->withErrorDetails(true);
        $request = $this->createRequest('POST', '/', ['Accept' => 'application/json']);

        $exception = new \Point\Exception\HttpMethodNotAllowedException($request);
        $exception->setAllowedMethods(['GET', 'POST']);
        $handler = new class ($exception) implements RequestHandlerInterface {
            private Throwable $exception;

            public function __construct(Throwable $exception)
            {
                $this->exception = $exception;
            }

            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                throw $this->exception;
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertEquals(405, $response->getStatusCode());
        self::assertStringContainsString('GET, POST', $response->getHeaderLine('Allow'));
    }

    public function testPlainTextExceptionMiddlewareRendersText() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(PlainTextExceptionMiddleware::class)->withErrorDetails(true);
        $request = $this->createRequest('GET', '/', ['Accept' => 'text/plain']);

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                throw new RuntimeException('Test error');
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertEquals(500, $response->getStatusCode());
        self::assertStringContainsString('text/plain', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Test error', (string)$response->getBody());
    }

    public function testXmlExceptionMiddlewareRendersXml() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(XmlExceptionMiddleware::class)->withErrorDetails(true);
        $request = $this->createRequest('GET', '/', ['Accept' => 'application/xml']);

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                throw new RuntimeException('Test error');
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertEquals(500, $response->getStatusCode());
        self::assertStringContainsString('application/xml', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Test error', (string)$response->getBody());
    }

    public function testXmlExceptionMiddlewareEscapesErrorTitles() : void
    {
        $middleware = AppFactory::create()->getContainer()->get(XmlExceptionMiddleware::class)->withErrorDetails(true);
        $request = $this->createRequest('GET', '/', ['Accept' => 'application/xml']);
        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                throw new RuntimeException('A & B <title> Ω café');
            }
        };

        $response = $middleware->process($request, $handler);
        $xml = simplexml_load_string((string)$response->getBody());

        self::assertSame(500, $response->getStatusCode());
        self::assertNotFalse($xml);
        self::assertSame('A & B <title> Ω café', (string)$xml->exception->message);
    }

    public function testXmlBodyParserRestoresLibxmlErrorModeAfterSuccessAndFailure() : void
    {
        $parser = new XmlBodyParserMiddleware();
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                return (new \Nyholm\Psr7\Factory\Psr17Factory())->createResponse();
            }
        };

        $originalMode = libxml_use_internal_errors();

        try {
            foreach ([false, true] as $initialMode) {
                libxml_use_internal_errors($initialMode);
                $validRequest = $factory->createServerRequest('POST', '/')
                    ->withHeader('Content-Type', 'application/xml')
                    ->withBody($factory->createStream('<root/>'));

                $parser->process($validRequest, $handler);
                self::assertSame($initialMode, libxml_use_internal_errors());

                $invalidRequest = $factory->createServerRequest('POST', '/')
                    ->withHeader('Content-Type', 'application/xml')
                    ->withBody($factory->createStream('<root>'));

                try {
                    $parser->process($invalidRequest, $handler);
                    self::fail('Malformed XML should throw an HTTP 400 exception.');
                } catch (\Point\Exception\HttpBadRequestException) {
                    self::assertSame($initialMode, libxml_use_internal_errors());
                }

                $parser->process($validRequest, $handler);
                self::assertSame($initialMode, libxml_use_internal_errors());
            }
        } finally {
            libxml_use_internal_errors($originalMode);
        }
    }

    private function createRequest(string $method, string $uri, array $headers = []) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $request = $factory->createServerRequest($method, $uri);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }
}
