<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Integration;

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Point\App;
use Point\Container\Definition\HttpDefinitions;
use Point\Container\Definition\PointDefinitions;
use Point\Factory\AppFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 * @small
 */
final class ContainerIntegrationTest extends TestCase
{
    public function testAppFactoryCreatesContainer() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();

        self::assertInstanceOf(ContainerInterface::class, $container);
        self::assertTrue($container->has(App::class));
    }

    public function testContainerResolvesAllRequiredServices() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();

        $requiredServices = [
            \Point\Interfaces\RouterInterface::class,
            \Point\Interfaces\DispatcherInterface::class,
            \Point\Interfaces\EmitterInterface::class,
            \Point\Interfaces\UrlGeneratorInterface::class,
            \Point\Interfaces\ContainerResolverInterface::class,
            \Point\Interfaces\RequestHandlerInvocationStrategyInterface::class,
            \Psr\Log\LoggerInterface::class,
            \Psr\Http\Message\ResponseFactoryInterface::class,
            \Psr\Http\Message\ServerRequestFactoryInterface::class,
            \Point\Middleware\HtmlExceptionMiddleware::class,
            \Point\Middleware\JsonExceptionMiddleware::class,
            \Point\Middleware\ExceptionLoggingMiddleware::class,
            \Point\Middleware\RoutingMiddleware::class,
            \Point\Middleware\EndpointMiddleware::class,
        ];

        foreach ($requiredServices as $service) {
            self::assertTrue($container->has($service), "Missing service: $service");
        }
    }

    public function testCreateFromExternalContainer() : void
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions((new PointDefinitions())->getDefinitions());
        $builder->addDefinitions((new HttpDefinitions())->getDefinitions());
        $externalContainer = $builder->build();

        $app = AppFactory::createFromContainer($externalContainer);

        self::assertInstanceOf(App::class, $app);
        self::assertSame($externalContainer, $app->getContainer());
    }

    public function testExternalContainerWithoutDefinitionsFails() : void
    {
        $builder = new ContainerBuilder();
        $externalContainer = $builder->build();

        $this->expectException(\DI\Definition\Exception\InvalidDefinition::class);
        AppFactory::createFromContainer($externalContainer);
    }

    public function testSimpleContainerWorksWithoutPhpDi() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();

        self::assertInstanceOf(\Point\Container\SimpleContainer::class, $container);
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
