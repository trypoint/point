<?php

declare(strict_types=1);

namespace Point\Tests\Unit\Emitter;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Point\Emitter\ResponseEmitter;

final class ResponseEmitterTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
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
}
