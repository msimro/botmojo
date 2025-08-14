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

/**
 * Abstract Tool
 *
 * Base implementation for all tools in the BotMojo system.
 * Provides common functionality and enforces the ToolInterface contract.
 */
abstract class AbstractTool implements ToolInterface
{
    /**
     * Configuration parameters
     *
     * @var array<string, mixed>
     */
    protected array $config = [];
    
    /**
     * Initialize the tool with configuration
     *
     * Set up the tool with any required configuration parameters.
     *
     * @param array<string, mixed> $config Configuration parameters for the tool
     *
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->validateConfig();
    }
    
    /**
     * Validate the configuration
     *
     * Ensure that all required configuration parameters are present.
     *
     * @throws \Exception If configuration is invalid
     * @return void
     */
    protected function validateConfig(): void
    {
        // To be implemented by subclasses
    }
    
    /**
     * Get a configuration value
     *
     * @param string $key     The configuration key
     * @param mixed  $default The default value if the key doesn't exist
     *
     * @return mixed The configuration value or default
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
