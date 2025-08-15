<?php

/**
 * BotMojo - Personal AI Assistant
 *
 * @category   Core
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

namespace BotMojo\Core;

use BotMojo\Exceptions\BotMojoException;
use BotMojo\Exceptions\ConfigurationException;
use BotMojo\Services\LoggerService;
use BotMojo\Tools\DatabaseTool;
use BotMojo\Tools\GeminiTool;

/**
 * Bootstrap
 *
 * Handles application initialization and setup.
 */
class Bootstrap
{
    /**
     * Service container instance
     */
    private static ?ServiceContainer $container = null;

    /**
     * Initialize the application
     *
     * @throws BotMojoException If initialization fails
     * @return ServiceContainer
     */
    public static function init(): ServiceContainer
    {
        if (self::$container !== null) {
            return self::$container;
        }

        try {
            // Create new container
            self::$container = new ServiceContainer();

            // Initialize configuration
            self::initializeConfig();

            // Register core services
            self::registerCoreServices();

            // Initialize tools
            self::initializeTools();

            // Initialize agents
            self::initializeAgents();

            return self::$container;
        } catch (\Throwable $e) {
            throw new BotMojoException(
                sprintf('Failed to initialize application: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e,
                [
                    'component' => 'bootstrap',
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }

    /**
     * Initialize configuration
     *
     * @throws ConfigurationException If configuration initialization fails
     */
    private static function initializeConfig(): void
    {
        try {
            self::$container->set('config', function () {
                return new Config();
            });

            /** @var Config $config */
            $config = self::$container->get('config');
            $config->load();
        } catch (\Throwable $e) {
            throw new ConfigurationException(
                sprintf('Failed to initialize configuration: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e,
                [
                    'component' => 'config',
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }

    /**
     * Register core services
     *
     * @throws BotMojoException If service registration fails
     */
    private static function registerCoreServices(): void
    {
        try {
            /** @var Config $config */
            $config = self::$container->get('config');

            // Register logger
            self::$container->set('logger', function () use ($config) {
                return new LoggerService(
                    $config->get('log.path'),
                    $config->get('log.level')
                );
            });

            // Register database
            self::$container->set('database', function () use ($config) {
                return new DatabaseTool(
                    $config->get('database')
                );
            });

            // Register Gemini tool
            self::$container->set('gemini', function () use ($config) {
                return new GeminiTool(
                    $config->get('gemini.api_key')
                );
            });
        } catch (\Throwable $e) {
            throw new BotMojoException(
                sprintf('Failed to register core services: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e,
                [
                    'component' => 'core_services',
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }

    /**
     * Initialize tools
     *
     * @throws BotMojoException If tool initialization fails
     */
    private static function initializeTools(): void
    {
        try {
            /** @var Config $config */
            $config = self::$container->get('config');
            $toolsConfig = $config->get('tools', []);
            
            foreach ($toolsConfig as $toolName => $toolConfig) {
                if (!empty($toolConfig['enabled'])) {
                    $className = $toolConfig['class'];
                    self::$container->set("tool.$toolName", function () use ($className, $toolConfig) {
                        return new $className($toolConfig);
                    });
                }
            }
        } catch (\Throwable $e) {
            throw new BotMojoException(
                sprintf('Failed to initialize tools: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e,
                [
                    'component' => 'tools',
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }

    /**
     * Initialize agents
     *
     * @throws BotMojoException If agent initialization fails
     */
    private static function initializeAgents(): void
    {
        try {
            /** @var Config $config */
            $config = self::$container->get('config');
            $agentsConfig = $config->get('agents', []);
            
            foreach ($agentsConfig as $agentName => $agentConfig) {
                if (!empty($agentConfig['enabled'])) {
                    $className = $agentConfig['class'];
                    self::$container->set("agent.$agentName", function () use ($className, $agentConfig) {
                        return new $className($agentConfig);
                    });
                }
            }
        } catch (\Throwable $e) {
            throw new BotMojoException(
                sprintf('Failed to initialize agents: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e,
                [
                    'component' => 'agents',
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }
}
