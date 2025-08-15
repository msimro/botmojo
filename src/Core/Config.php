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

use BotMojo\Exceptions\ConfigurationException;

/**
 * Config
 *
 * Handles configuration management for the application.
 */
class Config
{
    /**
     * Configuration values
     *
     * @var array<string, mixed>
     */
    private array $values = [];

    /**
     * Configuration cache
     *
     * @var array<string, mixed>
     */
    private array $cache = [];

    /**
     * Required configuration keys
     *
     * @var array<string>
     */
    private array $required = [
        'app.name',
        'app.env',
        'log.path',
        'log.level',
        'database.host',
        'database.name',
        'gemini.api_key'
    ];

    /**
     * Load configuration
     *
     * @throws ConfigurationException If configuration loading fails
     */
    public function load(): void
    {
        try {
            // Load base configuration
            $baseConfig = $this->loadFile(__DIR__ . '/../../config/default.php');
            if (!is_array($baseConfig)) {
                throw new ConfigurationException(
                    'Base configuration must return an array',
                    0,
                    null,
                    ['file' => 'default.php']
                );
            }
            $this->values = $baseConfig;

            // Load environment-specific configuration
            $env = $_ENV['APP_ENV'] ?? 'production';
            $envConfig = __DIR__ . "/../../config/{$env}.php";
            
            if (file_exists($envConfig)) {
                $envValues = $this->loadFile($envConfig);
                if (!is_array($envValues)) {
                    throw new ConfigurationException(
                        sprintf('Environment configuration must return an array: %s', $env),
                        0,
                        null,
                        ['file' => "{$env}.php"]
                    );
                }
                $this->values = array_replace_recursive($this->values, $envValues);
            }

            // Load tool configurations
            $this->loadToolConfigs();

            // Validate required values
            $this->validate();

        } catch (\Throwable $e) {
            if ($e instanceof ConfigurationException) {
                throw $e;
            }
            
            throw new ConfigurationException(
                sprintf('Failed to load configuration: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e,
                ['trace' => $e->getTraceAsString()]
            );
        }
    }

    /**
     * Get a configuration value with caching
     *
     * @param string $key     The configuration key (dot notation supported)
     * @param mixed  $default The default value if key not found
     *
     * @return mixed The configuration value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $value = $this->getValue($this->values, $key, $default);
        $this->cache[$key] = $value;

        return $value;
    }

    /**
     * Set a configuration value
     *
     * @param string $key   The configuration key (dot notation supported)
     * @param mixed  $value The value to set
     */
    public function set(string $key, mixed $value): void
    {
        $this->setValue($this->values, $key, $value);
        $this->cache = []; // Clear cache when setting new values
    }

    /**
     * Export the current configuration state
     *
     * @return array<string, mixed> The complete configuration array
     */
    public function export(): array
    {
        return $this->values;
    }

    /**
     * Validate required configuration values
     *
     * @throws ConfigurationException If any required values are missing
     */
    public function validate(): void
    {
        $missing = [];
        
        foreach ($this->required as $key) {
            if ($this->get($key) === null) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new ConfigurationException(
                'Missing required configuration values',
                0,
                null,
                ['missing' => $missing]
            );
        }
    }

    /**
     * Clear the configuration cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Add a required configuration key
     *
     * @param string $key The configuration key that should be required
     */
    public function addRequired(string $key): void
    {
        if (!in_array($key, $this->required)) {
            $this->required[] = $key;
        }
    }

    /**
     * Load configuration from a file
     *
     * @param string $file The file path
     * @throws ConfigurationException If file loading fails
     * @return mixed The loaded configuration
     */
    private function loadFile(string $file): mixed
    {
        if (!file_exists($file)) {
            throw new ConfigurationException(
                sprintf('Configuration file not found: %s', $file),
                0,
                null,
                ['file' => $file]
            );
        }

        return require $file;
    }

    /**
     * Load tool configurations
     */
    private function loadToolConfigs(): void
    {
        $toolsDir = __DIR__ . '/../../tools';
        if (!is_dir($toolsDir)) {
            return;
        }

        foreach (new \DirectoryIterator($toolsDir) as $tool) {
            if ($tool->isDot() || !$tool->isDir()) {
                continue;
            }

            $configFile = $tool->getPathname() . '/Config/defaults.php';
            if (file_exists($configFile)) {
                $toolName = $tool->getBasename();
                $toolConfig = $this->loadFile($configFile);
                
                if (!is_array($toolConfig)) {
                    throw new ConfigurationException(
                        sprintf('Tool configuration must return an array: %s', $toolName),
                        0,
                        null,
                        ['tool' => $toolName, 'file' => $configFile]
                    );
                }

                $this->values['tools'][$toolName] = array_replace_recursive(
                    $this->values['tools'][$toolName] ?? [],
                    $toolConfig
                );
            }
        }
    }

    /**
     * Get a nested configuration value using dot notation
     *
     * @param array  $array   The array to search in
     * @param string $key     The key to search for
     * @param mixed  $default The default value if key not found
     *
     * @return mixed The found value or default
     */
    private function getValue(array $array, string $key, mixed $default = null): mixed
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set a nested configuration value using dot notation
     *
     * @param array  &$array The array to set in
     * @param string $key    The key to set
     * @param mixed  $value  The value to set
     */
    private function setValue(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $last = array_pop($keys);

        foreach ($keys as $segment) {
            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                $array[$segment] = [];
            }
            $array = &$array[$segment];
        }

        $array[$last] = $value;
    }
}
