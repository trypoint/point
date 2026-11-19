<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Routing;

use Point\Interfaces\ContainerResolverInterface;
use Point\Interfaces\RequestHandlerInvocationStrategyInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class RouteInvoker implements RequestHandlerInterface
{
    /** @var array<string, mixed> */
    private array $arguments = [];

    /** @var callable|null */
    private $handler = null;

    private RequestHandlerInvocationStrategyInterface $invocationStrategy;

    private ContainerResolverInterface $resolver;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        RequestHandlerInvocationStrategyInterface $invocationStrategy,
        ContainerResolverInterface $resolver,
    ) {
        $this->responseFactory = $responseFactory;
        $this->invocationStrategy = $invocationStrategy;
        $this->resolver = $resolver;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if ($this->handler === null) {
            throw new RuntimeException(
                'RouteInvoker has no handler assigned. Call withHandler() before execution.',
            );
        }

        return ($this->invocationStrategy)(
            $this->handler,
            $request,
            $this->responseFactory->createResponse(),
            $this->arguments,
        );
    }

    /**
     * @param callable|string $handler
     * @param array<string, mixed> $arguments
     */
    public function withHandler(callable|string $handler, array $arguments = []) : self
    {
        $clone = clone $this;
        $clone->handler = $this->resolver->resolveCallable($handler);
        $clone->arguments = $arguments;

        return $clone;
    }
}
