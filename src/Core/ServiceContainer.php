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

use Exception;

/**
 * Service Container
 *
 * A simple dependency injection container that manages service instances
 * and their dependencies through factory callbacks.
 */
class ServiceContainer
{
    /**
     * Resolved service instances
     *
     * @var array<string, mixed>
     */
    private array $services = [];

    /**
     * Service factory callbacks
     *
     * @var array<string, callable>
     */
    private array $factories = [];

    /**
     * Register a service factory
     *
     * @param string   $name    The service identifier
     * @param callable $factory The factory callback that creates the service
     *
     * @return void
     */
    public function set(string $name, callable $factory): void
    {
        $this->factories[$name] = $factory;
    }

    /**
     * Get a service instance
     *
     * If the service doesn't exist yet, it will be created using its factory
     *
     * @param string $name The service identifier
     *
     * @throws Exception If the service is not registered
     * @return mixed The service instance
     */
    public function get(string $name)
    {
        if (!isset($this->services[$name])) {
            if (!isset($this->factories[$name])) {
                throw new Exception("Service '{$name}' not found.");
            }
            $this->services[$name] = $this->factories[$name]($this);
        }
        return $this->services[$name];
    }
    
    /**
     * Check if a service is registered
     *
     * @param string $name The service identifier
     *
     * @return bool True if the service exists
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]) || isset($this->factories[$name]);
    }
}
