<?php

declare(strict_types=1);

namespace PhpCmd\EventIntegrationTest;

use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use PhpCmd\CmdBus\CmdBusInterface;
use PhpCmd\CmdBus\ConfigProvider as BusProvider;
use PhpCmd\Event\ConfigProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @phpstan-import-type ServiceManagerConfiguration from BusProvider
 */
final class CmdBusIntegrationTest extends TestCase
{
    private ContainerInterface&ServiceManager $container;
    private CmdBusInterface $cmdBus;

    protected function setUp(): void
    {
        parent::setUp();

        $busProvider    = new BusProvider();
        $configProvider = new ConfigProvider();

        $config = ArrayUtils::merge(
            $busProvider(),
            $configProvider()
        );

        /** @phpstan-var ServiceManagerConfiguration $dependencies */
        $dependencies = $config['dependencies'];

        $invokables = [
            TestAsset\TestCommand::class        => TestAsset\TestCommand::class,
            TestAsset\TestCommandHandler::class => TestAsset\TestCommandHandler::class,
            TestAsset\BusEventListener::class   => TestAsset\BusEventListener::class,
        ];

        $dependencies['invokables'] = ArrayUtils::merge(
            // @phpstan-ignore-next-line offsetAccess.notFound
            $dependencies['invokables'],
            $invokables
        );
        // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
        $config[BusProvider::class][BusProvider::COMMAND_MAP_KEY] = [
            TestAsset\TestCommand::class => TestAsset\TestCommandHandler::class,
        ];

        $config['listeners'] = [
            [
                'listener' => TestAsset\BusEventListener::class,
                'priority' => 100,
            ],
        ];

        $dependencies['services']['config'] = $config;
        // @phpstan-ignore-next-line
        $this->container = new ServiceManager($dependencies);
    }

    public function testCommandBus(): void
    {
        $this->cmdBus = $this->container->get(CmdBusInterface::class);
        $this->assertInstanceOf(CmdBusInterface::class, $this->cmdBus);
        $result = $this->cmdBus->handle(new TestAsset\TestCommand());
        $this->assertEquals(TestAsset\TestCommandHandler::HANDLER_RESULT, $result);
    }
}
