<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Container\Definition;

use Point\App;
use Point\Container\ContainerResolver;
use Point\Emitter\ResponseEmitter;
use Point\Interfaces\ContainerResolverInterface;
use Point\Interfaces\DefinitionsInterface;
use Point\Interfaces\DispatcherInterface;
use Point\Interfaces\EmitterInterface;
use Point\Interfaces\RequestHandlerInvocationStrategyInterface;
use Point\Interfaces\RouterInterface;
use Point\Interfaces\UrlGeneratorInterface;
use Point\Middleware\BasePathMiddleware;
use Point\Middleware\ContentLengthMiddleware;
use Point\Middleware\EndpointMiddleware;
use Point\Middleware\ErrorExceptionMiddleware;
use Point\Middleware\ExceptionLoggingMiddleware;
use Point\Middleware\HeadMethodMiddleware;
use Point\Middleware\HtmlExceptionMiddleware;
use Point\Middleware\JsonExceptionMiddleware;
use Point\Middleware\MethodOverrideMiddleware;
use Point\Middleware\OutputBufferingMiddleware;
use Point\Middleware\PlainTextExceptionMiddleware;
use Point\Middleware\ResponseFactoryMiddleware;
use Point\Middleware\RoutingMiddleware;
use Point\Middleware\XmlExceptionMiddleware;
use Point\Routing\FastRouteDispatcher;
use Point\Routing\PipelineRunner;
use Point\Routing\RouteInvoker;
use Point\Routing\Router;
use Point\Routing\UrlGenerator;
use Point\Strategy\RequestResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Provides service definitions for the Point core components.
 *
 * The returned definitions include services for routing, dispatching,
 * resolving handlers, emitting responses, generating URLs.
 *
 * These defaults allow a Point application to be instantiated with all
 * essential framework services ready for use.
 */
final class PointDefinitions implements DefinitionsInterface
{
    public function getDefinitions() : array
    {
        return [
            App::class => static function (ContainerInterface $container) {
                return new App($container);
            },

            ContainerResolver::class => static function (ContainerInterface $container) {
                return new ContainerResolver($container);
            },

            ContainerResolverInterface::class => static function (ContainerInterface $container) {
                return $container->get(ContainerResolver::class);
            },

            EmitterInterface::class => static function () {
                return new ResponseEmitter();
            },

            LoggerInterface::class => static function () {
                return new NullLogger();
            },

            RequestHandlerInvocationStrategyInterface::class => static function (ContainerInterface $container) {
                return $container->get(RequestResponse::class);
            },

            RequestResponse::class => static function () {
                return new RequestResponse();
            },

            RequestHandlerInterface::class => static function (ContainerInterface $container) {
                return $container->get(Router::class);
            },

            Router::class => static function (ContainerInterface $container) {
                return new Router($container->get(PipelineRunner::class));
            },

            RouterInterface::class => static function (ContainerInterface $container) {
                return $container->get(Router::class);
            },

            DispatcherInterface::class => static function (ContainerInterface $container) {
                return $container->get(FastRouteDispatcher::class);
            },

            FastRouteDispatcher::class => static function (ContainerInterface $container) {
                return new FastRouteDispatcher($container->get(RouterInterface::class));
            },

            UrlGenerator::class => static function (ContainerInterface $container) {
                return new UrlGenerator($container->get(RouterInterface::class));
            },

            UrlGeneratorInterface::class => static function (ContainerInterface $container) {
                return $container->get(UrlGenerator::class);
            },

            PipelineRunner::class => static function (ContainerInterface $container) {
                return new PipelineRunner($container->get(ContainerResolverInterface::class));
            },

            \FastRoute\RouteCollector::class => static function () {
                return new \FastRoute\RouteCollector(new \FastRoute\RouteParser\Std(), new \FastRoute\DataGenerator\GroupCountBased());
            },

            RouteInvoker::class => static function (ContainerInterface $container) {
                return new RouteInvoker(
                    $container->get(ResponseFactoryInterface::class),
                    $container->get(RequestHandlerInvocationStrategyInterface::class),
                    $container->get(ContainerResolverInterface::class)
                );
            },

            // Middleware definitions
            ErrorExceptionMiddleware::class => static function () {
                return new ErrorExceptionMiddleware();
            },

            HtmlExceptionMiddleware::class => static function (ContainerInterface $container) {
                return new HtmlExceptionMiddleware($container->get(ResponseFactoryInterface::class));
            },

            JsonExceptionMiddleware::class => static function (ContainerInterface $container) {
                return new JsonExceptionMiddleware($container->get(ResponseFactoryInterface::class));
            },

            ExceptionLoggingMiddleware::class => static function (ContainerInterface $container) {
                return new ExceptionLoggingMiddleware($container->get(LoggerInterface::class));
            },

            RoutingMiddleware::class => static function (ContainerInterface $container) {
                return new RoutingMiddleware(
                    $container->get(DispatcherInterface::class),
                    $container->get(RouterInterface::class)
                );
            },

            EndpointMiddleware::class => static function (ContainerInterface $container) {
                return new EndpointMiddleware(
                    $container->get(RouteInvoker::class),
                    $container->get(PipelineRunner::class)
                );
            },

            BasePathMiddleware::class => static function (ContainerInterface $container) {
                return new BasePathMiddleware($container->get(RouterInterface::class));
            },

            ContentLengthMiddleware::class => static function () {
                return new ContentLengthMiddleware();
            },

            HeadMethodMiddleware::class => static function (ContainerInterface $container) {
                return new HeadMethodMiddleware($container->get(StreamFactoryInterface::class));
            },

            MethodOverrideMiddleware::class => static function () {
                return new MethodOverrideMiddleware();
            },

            OutputBufferingMiddleware::class => static function (ContainerInterface $container) {
                return new OutputBufferingMiddleware($container->get(StreamFactoryInterface::class));
            },

            PlainTextExceptionMiddleware::class => static function (ContainerInterface $container) {
                return new PlainTextExceptionMiddleware($container->get(ResponseFactoryInterface::class));
            },

            XmlExceptionMiddleware::class => static function (ContainerInterface $container) {
                return new XmlExceptionMiddleware($container->get(ResponseFactoryInterface::class));
            },

            ResponseFactoryMiddleware::class => static function (ContainerInterface $container) {
                return new ResponseFactoryMiddleware($container->get(ResponseFactoryInterface::class));
            },
        ];
    }
}
