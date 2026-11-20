<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point;

use Point\Interfaces\EmitterInterface;
use Point\Interfaces\RouteInterface;
use Point\Interfaces\RouterInterface;
use Point\Interfaces\ServerRequestCreatorInterface;
use Point\Middleware\EndpointMiddleware;
use Point\Middleware\ErrorExceptionMiddleware;
use Point\Middleware\ExceptionLoggingMiddleware;
use Point\Middleware\HtmlExceptionMiddleware;
use Point\Middleware\JsonExceptionMiddleware;
use Point\Middleware\RoutingMiddleware;
use Point\Routing\PipelineRunner;
use Point\Routing\Route;
use Point\Routing\RouteCollectionTrait;
use Point\Routing\RouteGroup;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * App
 *
 * The main application class for Point framework, responsible for routing, middleware handling, and
 * running the application. It provides methods for defining routes, adding middleware, and managing
 * the application's lifecycle, including handling HTTP requests and emitting responses.
 *
 * @api
 */
class App implements RequestHandlerInterface
{
    use RouteCollectionTrait;

    /**
     * Current Point Framework version.
     *
     * @var string
     */
    public const VERSION = '0.1.0-alpha';

    /**
     * The dependency injection container instance.
     */
    private ContainerInterface $container;

    /**
     * The emitter instance for sending the HTTP response to the client.
     */
    private EmitterInterface $emitter;

    /**
     * The request handler responsible for processing the request through middleware and routing.
     */
    private RequestHandlerInterface $requestHandler;

    /**
     * The router instance for handling route definitions and matching.
     */
    private RouterInterface $router;

    /**
     * The server request creator instance.
     */
    private ServerRequestCreatorInterface $serverRequestCreator;

    /**
     * Whether automatic default middleware is enabled.
     *
     * @var bool
     */
    private bool $defaultsEnabled = false;

    /**
     * Whether error middleware was explicitly registered by the user.
     *
     * @var bool
     */
    private bool $errorMiddlewareRegistered = false;

    /**
     * Whether routing middleware was explicitly registered by the user.
     *
     * @var bool
     */
    private bool $routingMiddlewareRegistered = false;

    /**
     * The constructor.
     *
     * Initializes the Point application with the provided container, request creator,
     * request handler, router, and emitter.
     *
     * @param ContainerInterface $container The dependency injection container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->serverRequestCreator = $container->get(ServerRequestCreatorInterface::class);
        $this->requestHandler = $container->get(RequestHandlerInterface::class);
        $this->router = $container->get(RouterInterface::class);
        $this->emitter = $container->get(EmitterInterface::class);
    }

    /**
     * Add a new middleware to the stack.
     */
    public function add(MiddlewareInterface|callable|string $middleware) : self
    {
        $this->router->add($middleware);

        return $this;
    }

    /**
     * Add a set of default error handling middleware.
     *
     * @param bool $displayErrorDetails
     * @param bool $logErrors
     * @param bool $logErrorDetails
     * @param LoggerInterface|null $logger
     *
     * @return self
     */
    public function addErrorMiddleware(
        bool $displayErrorDetails = false,
        bool $logErrors = true,
        bool $logErrorDetails = true,
        ?LoggerInterface $logger = null,
    ) : self {
        $this->errorMiddlewareRegistered = true;

        $htmlMiddleware = $this->container->get(HtmlExceptionMiddleware::class)->withErrorDetails($displayErrorDetails);
        $jsonMiddleware = $this->container->get(JsonExceptionMiddleware::class)->withErrorDetails($displayErrorDetails);

        $app = $this
            ->add(ErrorExceptionMiddleware::class)
            ->add($htmlMiddleware)
            ->add($jsonMiddleware);

        if ($logErrors) {
            $loggingMiddleware = $this->container
                ->get(ExceptionLoggingMiddleware::class)
                ->withLogErrorDetails($logErrorDetails);

            if ($logger) {
                $loggingMiddleware = $loggingMiddleware->withLogger($logger);
            }

            // Add logging middleware as the innermost error middleware (closest to handler)
            // so it logs before the rendering middleware
            $app->add($loggingMiddleware);
        }

        return $app;
    }

    /**
     * Add a new middleware to the application's middleware stack.
     */
    public function addMiddleware(MiddlewareInterface $middleware) : self
    {
        $this->router->addMiddleware($middleware);

        return $this;
    }

    /**
     * Add routing middleware.
     *
     * @return self
     */
    public function addRoutingMiddleware() : self
    {
        $this->routingMiddlewareRegistered = true;

        return $this
            ->add(RoutingMiddleware::class)
            ->add(EndpointMiddleware::class);
    }

    /**
     * Get the base path used for routing.
     */
    public function getBasePath() : string
    {
        return $this->router->getBasePath() ?? '';
    }

    /**
     * Get the dependency injection container.
     */
    public function getContainer() : ContainerInterface
    {
        return $this->container;
    }

    /**
     * Enable or disable automatic default middleware registration.
     *
     * When enabled, AppFactory::create() automatically registers error handling
     * and routing middleware so routes work without manual middleware setup.
     *
     * @param bool $enable Whether to enable automatic defaults
     *
     * @return self
     */
    public function withDefaults(bool $enable = true) : self
    {
        $this->defaultsEnabled = $enable;

        return $this;
    }

    /**
     * Define a route group with a common URI prefix and a set of routes or middleware.
     *
     * @param string $path The URI pattern prefix for the group
     * @param callable $handler The group handler which defines routes or middleware
     *
     * @return RouteGroup The newly created route group instance
     */
    public function group(string $path, callable $handler) : RouteGroup
    {
        return $this->router->group($path, $handler);
    }

    /**
     * Handle an incoming HTTP request.
     *
     * This method processes the request through the application's middleware stack and router,
     * returning the resulting HTTP response.
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if ($this->defaultsEnabled) {
            $pipeline = $this->buildEffectiveMiddleware();
            $pipelineRunner = $this->container->get(PipelineRunner::class);

            return $pipelineRunner->withPipeline($pipeline)->handle($request);
        }

        return $this->requestHandler->handle($request);
    }

    /**
     * Build the effective middleware pipeline in the correct order:
     * Error Handling → User Middleware → Routing → Endpoint.
     *
     * @return array<MiddlewareInterface|callable|string>
     */
    private function buildEffectiveMiddleware() : array
    {
        $existing = $this->router->getMiddleware();
        $effective = [];

        if (!$this->errorMiddlewareRegistered) {
            $effective[] = ErrorExceptionMiddleware::class;
            $effective[] = HtmlExceptionMiddleware::class;
            $effective[] = JsonExceptionMiddleware::class;
            $effective[] = ExceptionLoggingMiddleware::class;
        }

        foreach ($existing as $middleware) {
            if ($this->isRoutingOrEndpoint($middleware)) {
                if ($this->routingMiddlewareRegistered) {
                    $effective[] = $middleware;
                }
            } else {
                $effective[] = $middleware;
            }
        }

        if (!$this->routingMiddlewareRegistered) {
            $effective[] = RoutingMiddleware::class;
            $effective[] = EndpointMiddleware::class;
        }

        return $effective;
    }

    private function isRoutingOrEndpoint(mixed $middleware) : bool
    {
        return $middleware === RoutingMiddleware::class
            || $middleware === EndpointMiddleware::class
            || $middleware instanceof RoutingMiddleware
            || $middleware instanceof EndpointMiddleware;
    }

    /**
     * Define a new route with the specified HTTP methods and URI pattern.
     *
     * @param array<string> $methods The HTTP methods the route should respond to
     * @param string $path The URI pattern for the route
     * @param callable|string $handler The route handler callable or controller method
     *
     * @return RouteInterface The newly created route instance
     */
    public function map(array $methods, string $path, callable|string $handler) : RouteInterface
    {
        return $this->router->map($methods, $path, $handler);
    }

    /**
     * Run the Point application.
     *
     * This method traverses the application's middleware stack, processes the incoming HTTP request,
     * and emits the resultant HTTP response to the client.
     * @param ?ServerRequestInterface $request
     */
    public function run(?ServerRequestInterface $request = null) : void
    {
        if (! $request) {
            $request = $this->serverRequestCreator->createServerRequestFromGlobals();
        }

        $this->emitter->emit($this->handle($request));
    }

    /**
     * Set the base path used for routing.
     *
     * @param string $basePath The url base path
     */
    public function setBasePath(string $basePath) : self
    {
        $this->router->setBasePath($basePath);

        return $this;
    }
}
