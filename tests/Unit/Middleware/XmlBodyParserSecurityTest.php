<?php

declare(strict_types=1);

namespace Point\Tests\Unit\Middleware;

use Closure;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Point\Exception\HttpBadRequestException;
use Point\Middleware\XmlBodyParserMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SimpleXMLElement;

final class XmlBodyParserSecurityTest extends TestCase
{
    private const PARSER_OUTPUT_LIMIT = 4096;

    public function testExternalFileEntityDoesNotExposeFileContents() : void
    {
        $file = tempnam(sys_get_temp_dir(), 'point-xml-');
        self::assertNotFalse($file);
        $sentinel = 'point-xml-file-entity-sentinel';
        file_put_contents($file, $sentinel);

        try {
            $path = str_replace('\\', '/', realpath($file) ?: $file);
            $uri = 'file:///' . ltrim($path, '/');
            $xml = sprintf(
                '<!DOCTYPE root [<!ENTITY external SYSTEM "%s">]><root>&external;</root>',
                htmlspecialchars($uri, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            );

            self::assertStringNotContainsString($sentinel, $this->parseXmlBody($xml) ?? '');
        } finally {
            unlink($file);
        }
    }

    public function testExternalNetworkEntityIsLimitedToAnUnavailableLoopbackPort() : void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        self::assertNotFalse($socket, sprintf('Could not allocate a loopback port: %s (%d).', $errorMessage, $errorCode));
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        self::assertNotFalse($address);

        $port = substr((string)$address, strrpos((string)$address, ':') + 1);
        $xml = sprintf(
            '<!DOCTYPE root [<!ENTITY external SYSTEM "http://127.0.0.1:%s/entity">]><root>&external;</root>',
            $port,
        );

        self::assertStringNotContainsString('loopback-entity-sentinel', $this->parseXmlBody($xml) ?? '');
    }

    public function testRecursiveEntityIsRejectedAsBadRequest() : void
    {
        $xml = '<!DOCTYPE root [<!ENTITY recursive "&recursive;">]><root>&recursive;</root>';

        self::assertNull($this->parseXmlBody($xml));
    }

    public function testControlledEntityAmplificationRemainsBounded() : void
    {
        $declarations = ['<!ENTITY e0 "1234567890">'];
        for ($level = 1; $level <= 4; $level++) {
            $declarations[] = sprintf('<!ENTITY e%d "%s">', $level, str_repeat('&e' . ($level - 1) . ';', 4));
        }

        $xml = '<!DOCTYPE root [' . implode('', $declarations) . ']><root>&e4;</root>';
        $parsedValue = $this->parseXmlBody($xml) ?? '';

        self::assertLessThanOrEqual(self::PARSER_OUTPUT_LIMIT, strlen($parsedValue));
    }

    private function parseXmlBody(string $xml) : ?string
    {
        $factory = new Psr17Factory();
        $parsedValue = null;
        $handler = new class ($factory, static function (ServerRequestInterface $request) use (&$parsedValue) : void {
            $body = $request->getParsedBody();
            $parsedValue = $body instanceof SimpleXMLElement ? (string)$body : null;
        }) implements RequestHandlerInterface {
            public function __construct(
                private Psr17Factory $factory,
                private Closure $capture,
            ) {
            }

            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                ($this->capture)($request);

                return $this->factory->createResponse();
            }
        };

        $request = $factory->createServerRequest('POST', 'http://localhost/')
            ->withHeader('Content-Type', 'application/xml')
            ->withBody($factory->createStream($xml));

        try {
            (new XmlBodyParserMiddleware())->process($request, $handler);
        } catch (HttpBadRequestException) {
            return null;
        }

        return $parsedValue;
    }
}
