<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Emitter;

use Point\Interfaces\EmitterInterface;

use Psr\Http\Message\ResponseInterface;

use function connection_status;
use function header;
use function headers_sent;
use function in_array;

use function min;
use function sprintf;

use function strlen;
use function strtolower;

use const CONNECTION_NORMAL;

final class ResponseEmitter implements EmitterInterface
{
    private int $responseChunkSize;

    public function __construct(int $responseChunkSize = 4096)
    {
        $this->responseChunkSize = $responseChunkSize;
    }

    /**
     * Send the response the client.
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response) : void
    {
        $status = $response->getStatusCode();
        if ($response->hasHeader('Content-Length')) {
            if ($status < 200 || $status === 204) {
                $response = $response->withoutHeader('Content-Length');
            } elseif ($status === 205) {
                $response = $response->withHeader('Content-Length', '0');
            }
        }

        $isEmpty = $this->isResponseEmpty($response);
        if (headers_sent() === false) {
            $this->emitHeaders($response);

            // Set the status _after_ the headers, because of PHP's "helpful" behavior with location headers.
            // See https://github.com/trypoint/point/issues/1730

            $this->emitStatusLine($response);
        }

        if (! $isEmpty) {
            $this->emitBody($response);
        }
    }

    /**
     * Reports an empty body or a status that must not carry content.
     *
     * @param ResponseInterface $response
     */
    public function isResponseEmpty(ResponseInterface $response) : bool
    {
        $status = $response->getStatusCode();
        if ($status < 200 || in_array($status, [204, 205, 304], true)) {
            return true;
        }
        $stream = $response->getBody();
        if ($stream->isSeekable()) {
            $position = $stream->tell();
            $stream->rewind();
            $empty = $stream->read(1) === '';
            $stream->seek($position);

            return $empty;
        }

        return $stream->eof();
    }

    /**
     * Emit Body
     *
     * @param ResponseInterface $response
     */
    private function emitBody(ResponseInterface $response) : void
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $amountToRead = (int)$response->getHeaderLine('Content-Length');
        if (! $amountToRead) {
            $amountToRead = $body->getSize();
        }

        if ($amountToRead) {
            while ($amountToRead > 0 && ! $body->eof()) {
                $length = min($this->responseChunkSize, $amountToRead);
                $data = $body->read($length);
                echo $data;

                $amountToRead -= strlen($data);

                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        } else {
            while (! $body->eof()) {
                echo $body->read($this->responseChunkSize);
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        }
    }

    /**
     * Emit Response Headers
     *
     * @param ResponseInterface $response
     */
    private function emitHeaders(ResponseInterface $response) : void
    {
        foreach ($response->getHeaders() as $name => $values) {
            $first = strtolower($name) !== 'set-cookie';
            foreach ($values as $value) {
                $header = sprintf('%s: %s', $name, $value);
                header($header, $first);
                $first = false;
            }
        }
    }

    /**
     * Emit Status Line
     *
     * @param ResponseInterface $response
     */
    private function emitStatusLine(ResponseInterface $response) : void
    {
        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase(),
        );
        header($statusLine, true, $response->getStatusCode());
    }
}
