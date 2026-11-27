<?php

declare(strict_types=1);

namespace Point\Tests\Unit\Emitter;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Point\Emitter\ResponseEmitter;
use Point\Factory\AppFactory;
use Point\Interfaces\EmitterInterface;
use Psr\Http\Message\ResponseInterface;

final class ResponseEmitterTest extends TestCase
{
    public static function informationalStatuses(): iterable
    {
        yield 'Continue' => [100];
        yield 'Early Hints' => [103];
    }

    public static function statusesWithoutContentLength(): iterable
    {
        yield from self::informationalStatuses();
        yield 'No Content' => [204];
    }

    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testEmitterExists(): void
    {
        $container = AppFactory::create()->getContainer();

        self::assertTrue($container->has(EmitterInterface::class));
        self::assertInstanceOf(EmitterInterface::class, $container->get(EmitterInterface::class));
    }

    public function testEmptyBodyDetected(): void
    {
        $response = $this->factory->createResponse(200)->withBody($this->factory->createStream(''));
        $emitter = new ResponseEmitter();
        self::assertTrue($emitter->isResponseEmpty($response));
    }

    public function testNonEmptyBodyDetected(): void
    {
        $response = $this->factory->createResponse(200)->withBody($this->factory->createStream('hello'));
        $emitter = new ResponseEmitter();
        self::assertFalse($emitter->isResponseEmpty($response));
    }

    public function testStatusCodesEmpty(): void
    {
        $emitter = new ResponseEmitter();
        foreach ([204, 205, 304] as $code) {
            $response = $this->factory->createResponse($code)->withBody($this->factory->createStream('hello'));
            self::assertTrue($emitter->isResponseEmpty($response));
        }
    }

    public function testSeekableCursorPositionPreserved(): void
    {
        $stream = $this->factory->createStream('hello');
        $stream->seek(2);
        $response = $this->factory->createResponse(200)->withBody($stream);
        $emitter = new ResponseEmitter();
        self::assertFalse($emitter->isResponseEmpty($response));
        self::assertSame(2, $stream->tell());
    }

    public function testSeekableEmptyCursorPosition(): void
    {
        $stream = $this->factory->createStream('');
        $response = $this->factory->createResponse(200)->withBody($stream);
        $emitter = new ResponseEmitter();
        self::assertTrue($emitter->isResponseEmpty($response));
        self::assertSame(0, $stream->tell());
    }

    #[DataProvider('informationalStatuses')]
    public function testInformationalResponseSuppressesBodyAndPreservesCursor(int $status): void
    {
        $stream = $this->factory->createStream('hello');
        $stream->seek(2);
        $response = $this->factory->createResponse($status)->withBody($stream);
        $emitter = new ResponseEmitter();

        $this->expectOutputString('');
        $emitter->emit($response);
        self::assertSame(2, $stream->tell());
        self::assertTrue($emitter->isResponseEmpty($response));
    }

    #[DataProvider('statusesWithoutContentLength')]
    public function testResponseDropsForbiddenLengthBeforeEmission(int $status): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getBody')->willReturn($this->factory->createStream(''));
        $response->method('hasHeader')->willReturnCallback(static fn(string $name) => $name === 'Content-Length');
        $response->expects(self::once())->method('withoutHeader')->with('Content-Length')
            ->willReturn($this->factory->createResponse($status));
        $this->expectOutputString('');

        (new ResponseEmitter())->emit($response);
    }

    public function testResetContentResponseEmitsZeroLengthAndNoBody(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(205);
        $response->method('hasHeader')->willReturnCallback(static fn(string $name) => $name === 'Content-Length');
        $response->expects(self::once())->method('withHeader')->with('Content-Length', '0')
            ->willReturn($this->factory->createResponse(205)->withBody($this->factory->createStream('hello')));

        $this->expectOutputString('');
        (new ResponseEmitter())->emit($response);
    }

    public function testNormalResponseStillEmitsBody(): void
    {
        $response = $this->factory->createResponse(200)
            ->withHeader('Content-Length', '5')
            ->withBody($this->factory->createStream('hello'));
        $this->expectOutputString('hello');

        (new ResponseEmitter())->emit($response);
    }
}
