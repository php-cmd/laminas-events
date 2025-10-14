<?php

declare(strict_types=1);

namespace PhpCmd\EventTest;

use Laminas\EventManager;
use PhpCmd\CmdBus\ConfigProvider as BusProvider;
use PhpCmd\Event\ConfigProvider;
use PhpCmd\Event\Container\EventManagerAwareDelegator;
use PhpCmd\Event\Container\EventManagerFactory;
use PhpCmd\Event\Container\ListenerConfigurationDelegator;
use PhpCmd\Event\Middleware\PostHandleMiddleware;
use PhpCmd\Event\Middleware\PreHandleMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new ConfigProvider();
    }

    public function testInvokeReturnsConfiguration(): void
    {
        $config = ($this->provider)();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey(BusProvider::class, $config);
    }

    public function testGetDependenciesReturnsServiceConfiguration(): void
    {
        $dependencies = $this->provider->getDependencies();

        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey('aliases', $dependencies);
        $this->assertArrayHasKey('delegators', $dependencies);
        $this->assertArrayHasKey('factories', $dependencies);
        $this->assertArrayHasKey('invokables', $dependencies);
    }

    public function testAliasesConfiguration(): void
    {
        $dependencies = $this->provider->getDependencies();
        $aliases      = $dependencies['aliases'];

        $this->assertIsArray($aliases);
        $this->assertEquals(
            EventManager\EventManager::class,
            $aliases[EventManager\EventManagerInterface::class]
        );
        $this->assertEquals(
            EventManager\EventManager::class,
            $aliases['EventManager']
        );
        $this->assertEquals(
            EventManager\SharedEventManager::class,
            $aliases[EventManager\SharedEventManagerInterface::class]
        );
        $this->assertEquals(
            EventManager\SharedEventManager::class,
            $aliases['SharedEventManager']
        );
    }

    public function testDelegatorsConfiguration(): void
    {
        $dependencies = $this->provider->getDependencies();
        $delegators   = $dependencies['delegators'];

        $this->assertIsArray($delegators);
        $this->assertArrayHasKey(EventManager\EventManager::class, $delegators);
        $this->assertContains(
            ListenerConfigurationDelegator::class,
            $delegators[EventManager\EventManager::class]
        );

        $this->assertArrayHasKey(PostHandleMiddleware::class, $delegators);
        $this->assertContains(
            EventManagerAwareDelegator::class,
            $delegators[PostHandleMiddleware::class]
        );

        $this->assertArrayHasKey(PreHandleMiddleware::class, $delegators);
        $this->assertContains(
            EventManagerAwareDelegator::class,
            $delegators[PreHandleMiddleware::class]
        );
    }

    public function testFactoriesConfiguration(): void
    {
        $dependencies = $this->provider->getDependencies();
        $factories    = $dependencies['factories'];

        $this->assertIsArray($factories);
        $this->assertArrayHasKey(EventManager\EventManager::class, $factories);
        $this->assertEquals(
            EventManagerFactory::class,
            $factories[EventManager\EventManager::class]
        );
    }

    public function testInvokablesConfiguration(): void
    {
        $dependencies = $this->provider->getDependencies();
        $invokables   = $dependencies['invokables'];

        $this->assertIsArray($invokables);
        $this->assertArrayHasKey(EventManager\SharedEventManager::class, $invokables);
        $this->assertEquals(
            EventManager\SharedEventManager::class,
            $invokables[EventManager\SharedEventManager::class]
        );

        $this->assertArrayHasKey(PostHandleMiddleware::class, $invokables);
        $this->assertEquals(
            PostHandleMiddleware::class,
            $invokables[PostHandleMiddleware::class]
        );

        $this->assertArrayHasKey(PreHandleMiddleware::class, $invokables);
        $this->assertEquals(
            PreHandleMiddleware::class,
            $invokables[PreHandleMiddleware::class]
        );
    }

    public function testGetMiddlewareReturnsMiddlewarePipelineConfiguration(): void
    {
        $middleware = $this->provider->getMiddleware();

        $this->assertIsArray($middleware);
        $this->assertArrayHasKey('pre_handle', $middleware);
        $this->assertArrayHasKey('post_handle', $middleware);
    }

    public function testPreHandleMiddlewareConfiguration(): void
    {
        $middleware = $this->provider->getMiddleware();
        $preHandle  = $middleware['pre_handle'];

        $this->assertIsArray($preHandle);
        $this->assertArrayHasKey('middleware', $preHandle);
        $this->assertArrayHasKey('priority', $preHandle);
        $this->assertEquals(PreHandleMiddleware::class, $preHandle['middleware']);
        $this->assertEquals(100, $preHandle['priority']);
    }

    public function testPostHandleMiddlewareConfiguration(): void
    {
        $middleware = $this->provider->getMiddleware();
        $postHandle = $middleware['post_handle'];

        $this->assertIsArray($postHandle);
        $this->assertArrayHasKey('middleware', $postHandle);
        $this->assertArrayHasKey('priority', $postHandle);
        $this->assertEquals(PostHandleMiddleware::class, $postHandle['middleware']);
        $this->assertEquals(-100, $postHandle['priority']);
    }

    public function testGetCommandMapReturnsEmptyArray(): void
    {
        $commandMap = $this->provider->getCommandMap();

        $this->assertIsArray($commandMap);
        $this->assertEmpty($commandMap);
    }

    public function testInvokeContainsBusProviderConfiguration(): void
    {
        $config = ($this->provider)();

        $this->assertArrayHasKey(BusProvider::class, $config);
        $busConfig = $config[BusProvider::class];

        $this->assertIsArray($busConfig);
        $this->assertArrayHasKey(BusProvider::COMMAND_MAP_KEY, $busConfig);
        $this->assertArrayHasKey(BusProvider::MIDDLEWARE_PIPELINE_KEY, $busConfig);
    }
}
