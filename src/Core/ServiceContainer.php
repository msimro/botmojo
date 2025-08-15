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
     * @throws BotMojoException If the service is not registered or cannot be created
     * @return mixed The service instance
     */
    public function get(string $name): mixed
    {
        if (!isset($this->services[$name])) {
            if (!isset($this->factories[$name])) {
                throw new BotMojoException(
                    sprintf("Service '%s' not found", $name),
                    0,
                    null,
                    ['service' => $name]
                );
            }
            
            try {
                $this->services[$name] = $this->factories[$name]($this);
            } catch (\Throwable $e) {
                throw new BotMojoException(
                    sprintf("Failed to create service '%s': %s", $name, $e->getMessage()),
                    (int) $e->getCode(),
                    $e,
                    [
                        'service' => $name,
                        'trace' => $e->getTraceAsString()
                    ]
                );
            }
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
