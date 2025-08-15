<?php

declare(strict_types=1);

namespace BotMojo\Core;

use BotMojo\Config\Config;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

class ContainerFactory
{
    public static function create(Config $config): ServiceContainer
    {
        $container = new ServiceContainer();

        // Register logger
        $container->register('logger', function () use ($config) {
            $logger = new Logger('botmojo');
            $logger->pushHandler(new StreamHandler(
                $config->get('logging.file', __DIR__ . '/../../logs/app.log'),
                $config->get('logging.level', Logger::INFO)
            ));
            return $logger;
        });

        // Register config
        $container->set('config', $config);

        // Register tools
        self::registerTools($container, $config);

        // Register agents
        self::registerAgents($container, $config);

        // Register core services
        self::registerCoreServices($container);

        return $container;
    }

    private static function registerTools(ServiceContainer $container, Config $config): void
    {
        $toolConfigs = $config->get('tools', []);

        foreach ($toolConfigs as $id => $toolConfig) {
            if (!isset($toolConfig['class']) || !$toolConfig['enabled'] ?? true) {
                continue;
            }

            $container->register('tool.' . $id, function (ServiceContainer $container) use ($toolConfig) {
                $class = $toolConfig['class'];
                return new $class(
                    $toolConfig['config'] ?? [],
                    $container->get('logger')
                );
            });
        }
    }

    private static function registerAgents(ServiceContainer $container, Config $config): void
    {
        $agentConfigs = $config->get('agents', []);

        foreach ($agentConfigs as $id => $agentConfig) {
            if (!isset($agentConfig['class']) || !$agentConfig['enabled'] ?? true) {
                continue;
            }

            $container->register('agent.' . $id, function (ServiceContainer $container) use ($id, $agentConfig) {
                $class = $agentConfig['class'];
                
                // Get required tools for this agent
                $tools = [];
                foreach ($agentConfig['tools'] ?? [] as $toolId) {
                    $tools[$toolId] = $container->get('tool.' . $toolId);
                }

                return new $class(
                    $tools,
                    $agentConfig['config'] ?? [],
                    $container->get('logger')
                );
            });
        }
    }

    private static function registerCoreServices(ServiceContainer $container): void
    {
        // Register orchestrator
        $container->register(Orchestrator::class, function (ServiceContainer $container) {
            return new Orchestrator(
                $container,
                $container->get('config'),
                $container->get('logger')
            );
        });

        // Add more core services as needed
    }
}
