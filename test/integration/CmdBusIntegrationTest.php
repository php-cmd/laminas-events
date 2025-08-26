<?php

declare(strict_types=1);

namespace PhpCmd\EventIntegrationTest;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use PhpCmd\CmdBus\CmdBus;
use PhpCmd\CmdBus\CmdBusInterface;
use PhpCmd\CmdBus\ConfigProvider as BusProvider;
use PhpCmd\Event\ConfigProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class CmdBusIntegrationTest extends TestCase
{
    private ContainerInterface&ServiceManager $container;
    private CmdBusInterface $cmdBus;

    protected function setUp(): void
    {
        parent::setUp();
        // Additional setup for CmdBus integration tests
        $config = ArrayUtils::merge(
            (new BusProvider())(),
            (new ConfigProvider())()
        );
        $dependencies                                             = $config['dependencies'] ?? [];

        $invokables = [
            TestAsset\TestCommand::class        => TestAsset\TestCommand::class,
            TestAsset\TestCommandHandler::class => TestAsset\TestCommandHandler::class,
            TestAsset\BusEventListener::class   => TestAsset\BusEventListener::class,
        ];
        $dependencies['invokables'] = ArrayUtils::merge(
            $dependencies['invokables'] ?? [],
            $invokables
        );

        $config[BusProvider::class][BusProvider::COMMAND_MAP_KEY] = [
            TestAsset\TestCommand::class => TestAsset\TestCommandHandler::class,
        ];

        $config['listeners'] = [
            [
                'listener' => TestAsset\BusEventListener::class,
                'priority' => 100,
            ]
        ];

        $dependencies['services']['config']                       = $config;
        $this->container                                          = new ServiceManager($dependencies);
    }

    public function testCommandBus(): void
    {
        $this->cmdBus = $this->container->get(CmdBusInterface::class);
        $this->assertInstanceOf(CmdBusInterface::class, $this->cmdBus);
        $result      = $this->cmdBus->handle(new TestAsset\TestCommand('test-command'));
        $this->assertEquals(TestAsset\TestCommandHandler::HANDLER_RESULT, $result);
    }
}
