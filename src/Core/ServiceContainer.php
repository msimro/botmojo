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
     * Service definitions
     *
     * @var array<string, callable>
     */
    private array $definitions = [];

    /**
     * Resolved service instances
     *
     * @var array<string, object>
     */
    private array $services = [];

    /**
     * Register a service definition
     *
     * @param string   $id       The service identifier
     * @param callable $factory  The factory function that creates the service
     */
    public function register(string $id, callable $factory): void
    {
        $this->definitions[$id] = $factory;
    }

    /**
     * Set a service instance directly
     *
     * @param string $id      The service identifier
     * @param object $service The service instance
     */
    public function set(string $id, object $service): void
    {
        $this->services[$id] = $service;
    }

    /**
     * Get a service instance
     *
     * If the service doesn't exist yet, it will be created using its factory
     *
     * @param string $id The service identifier
     *
     * @throws BotMojoException If the service is not registered or cannot be created
     * @return object The service instance
     */
    public function get(string $id): object
    {
        // Return existing instance if available
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        // Create new instance if definition exists
        if (isset($this->definitions[$id])) {
            try {
                $this->services[$id] = ($this->definitions[$id])($this);
                return $this->services[$id];
            } catch (\Throwable $e) {
                throw new BotMojoException(
                    sprintf("Failed to create service '%s': %s", $id, $e->getMessage()),
                    (int) $e->getCode(),
                    $e,
                    [
                        'service' => $id,
                        'trace' => $e->getTraceAsString()
                    ]
                );
            }
        }

        throw new BotMojoException(
            sprintf("Service '%s' not found", $id),
            0,
            null,
            ['service' => $id]
        );
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
