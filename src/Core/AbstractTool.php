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
     * Constructor
     *
     * @param array<string, mixed> $config Configuration parameters for the tool
     */
    public function __construct(array $config = [])
    {
        $this->initialize($config);
    }
    
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
        // Log initialization in debug mode
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $className = get_class($this);
            $configKeys = array_keys($config);
            error_log("🔧 Initializing {$className} with config keys: " . implode(', ', $configKeys));
        }
        
        $this->config = array_merge($this->config, $config);
        
        try {
            $this->validateConfig();
        } catch (\Exception $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("❌ Configuration validation failed for " . get_class($this) . ": " . $e->getMessage());
                error_log("📋 Current config: " . json_encode($this->config));
            }
            throw $e;
        }
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
