<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Middleware;

use InvalidArgumentException;

use Psr\Http\Message\ResponseInterface;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Throwable;

use function in_array;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;

final class OutputBufferingMiddleware implements MiddlewareInterface
{
    public const APPEND = 'append';

    public const PREPEND = 'prepend';

    private StreamFactoryInterface $streamFactory;

    private string $style;

    /**
     * @param StreamFactoryInterface $streamFactory The stream factory
     * @param string $style Either "append" or "prepend"
     */
    public function __construct(StreamFactoryInterface $streamFactory, string $style = 'append')
    {
        $this->streamFactory = $streamFactory;
        $this->style = $style;

        if (! in_array($style, [self::APPEND, self::PREPEND], true)) {
            throw new InvalidArgumentException(sprintf('Invalid style `%s`. Must be `append` or `prepend`', $style));
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $level = ob_get_level();
        ob_start();

        try {
            $response = $handler->handle($request);
            $output = '';
            while (ob_get_level() > $level) {
                $output = (string)ob_get_clean() . $output;
            }
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        if ($output !== '') {
            if ($this->style === self::PREPEND) {
                $body = $this->streamFactory->createStream();
                $body->write($output . $response->getBody());
                $response = $response->withBody($body);
            } elseif ($this->style === self::APPEND && $response->getBody()->isWritable()) {
                $response->getBody()->write($output);
            }
        }

        return $response;
    }
}
