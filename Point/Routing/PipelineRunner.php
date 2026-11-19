<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Routing;

use Point\Interfaces\ContainerResolverInterface;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

use function is_callable;

use function sprintf;

/**
 * A pipeline runner.
 */
final class PipelineRunner implements RequestHandlerInterface
{
    /**
     * @var array<int, mixed>
     */
    private array $pipeline = [];

    private int $pipelineIndex = 0;

    private ContainerResolverInterface $resolver;

    public function __construct(ContainerResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if ($this->pipelineIndex >= count($this->pipeline)) {
            throw new RuntimeException('The middleware pipeline is empty or exhausted.');
        }

        $entry = $this->pipeline[$this->pipelineIndex];
        $this->pipelineIndex++;

        $entry = $this->resolver->resolve($entry);

        if ($entry instanceof MiddlewareInterface) {
            return $entry->process($request, $this);
        }

        if ($entry instanceof RequestHandlerInterface) {
            return $entry->handle($request);
        }

        if (is_callable($entry)) {
            return $entry($request, $this);
        }

        throw new RuntimeException(
            sprintf(
                'Invalid pipeline entry of type "%s". Expected one of: callable, %s, or %s.',
                is_object($entry) ? $entry::class : gettype($entry),
                MiddlewareInterface::class,
                RequestHandlerInterface::class,
            ),
        );
    }

    /**
     * @param array<MiddlewareInterface|RequestHandlerInterface|callable|string> $pipeline
     */
    public function withPipeline(array $pipeline) : self
    {
        $clone = clone $this;
        $clone->pipeline = array_values($pipeline);
        $clone->pipelineIndex = 0;

        return $clone;
    }
}
